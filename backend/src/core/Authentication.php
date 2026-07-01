<?php

namespace App;

use PDO;
use App\TokenService;
use App\RedisService; 
class Authentication extends PDO_CON
{
    private $tokenService;
    private $redis;
    public function __construct()
    {
        parent::__construct();
        $this->redis = new RedisService();
        $this->tokenService = new TokenService($this->redis);
    }

    public function login($params)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$this->redis->checkLoginRateLimit($data['user_email'] ?? '')) {
            $ttl = $this->redis->getLoginBlockTTL($data['user_email']);
            $this->HandleError("Too many attempts. Try again in {$ttl} seconds", 429);
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_email = ?");
        $stmt->execute([$data['user_email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $this->HandleError('User not found', 404);
        }
        if ($user && password_verify($data['password'] ?? '', $user['password_hash'])) {
            
            if (!$user['is_verified']) {
                    $this->HandleError('Please verify your email before signing in', 403);
             }

            $this->redis->clearLoginAttempts($data['user_email']);
            unset($user['password_hash'], $user['join_code'], $user['promoted_by'], $user['promoted_at']);
            $token = $this->redis->createSession($user);
            echo json_encode(['success' => true, 'token' => $token, 'data' => $user]);
        } else {
            $this->redis->incrementLoginAttempts($data['user_email'] ?? '');
            $this->HandleError('Invalid email or password', 401);
        }
    }

    public function register($params)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$this->validateEmail($data['user_email'] ?? '')) {
            $this->HandleError('Invalid email format', 400);
        }
        $passErr = $this->validatePassword($data['password'] ?? '');
        if ($passErr) {
            $this->HandleError($passErr, 400);
        }

        if ($this->isDomainBlocked($data['user_email'])) {
            if (empty($data['join_code']) || !$this->isJoinCodeValid($data['join_code'])) {
                $this->HandleError('This email requires a valid school join code', 400);
            }
            $schoolId = $this->getSchoolFromJoinCode($data['join_code']);
        } else {

            $domain = substr(strrchr($data['user_email'], "@"), 1);
            $schoolId = $this->getSchoolFromDomain($domain);
            if (!$schoolId) {
                if (!empty($data['join_code']) && $this->isJoinCodeValid($data['join_code'])) {
                    $schoolId = $this->getSchoolFromJoinCode($data['join_code']);
                } else {
                    $this->HandleError('This email domain is not associated with any school', 400);
                }
            }
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (user_email, display_name, password_hash, grade, role, school_id, is_verified, join_code, promoted_by, created_at) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $data['user_email'],
                $data['display_name'],
                $this->hashPassword($data['password']),
                $data['grade'] ?? 0,
                $data['role'] ?? 'student',
                $schoolId ?? null,
                $data['is_verified'] ?? 0,
                $data['join_code'] ?? '',
                $data['promoted_by'] ?? null
            ]);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $msg = strpos($e->getMessage(), 'user_email') !== false
                    ? 'This email is already registered'
                    : 'This display name is already taken';
                $this->HandleError($msg, 409);
            }
            throw $e;
        }

        $userId = $this->pdo->lastInsertId();
        $user = [
            'user_id' => $userId,
            'user_email' => $data['user_email'],
            'display_name' => $data['display_name'],
            'role' => $data['role'] ?? 'student',
            'school_id' => $schoolId,
        ];
        $token = $this->redis->createSession($user);
        $this->redis->pushNotification('verify_email', [
            'user_id' => $userId,
            'email' => $data['user_email'],
            'name' => $data['display_name'],
            'token' => $token
        ]);
        echo json_encode(['success' => true, 'token' => $token, 'user_id' => $userId]);
    }

    public function getMe($params)
    {
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();

        $stmt = $this->pdo->prepare("SELECT user_id, user_email, display_name, grade, role, school_id, is_verified, created_at FROM users WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            $this->HandleError('User not found', 404);
        }
    }

    public function getUser($params)
    {
        $this->requireAuth();

        $id = $params['id'] ?? 0;
        $stmt = $this->pdo->prepare("SELECT user_id, user_email, display_name, grade, role, school_id, is_verified, created_at FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            $this->HandleError('User not found', 404);
        }
    }

    public function updateUser($params)
    {
        $this->requireAuth();

        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['user_email']) && !$this->validateEmail($data['user_email'])) {
            $this->HandleError('Invalid email format', 400);
        }
        if (isset($data['password'])) {
            $passErr = $this->validatePassword($data['password']);
            if ($passErr) {
                $this->HandleError($passErr, 400);
            }
            $data['password_hash'] = $this->hashPassword($data['password']);
        }

        $stmt = $this->pdo->prepare("UPDATE users SET user_email = COALESCE(?, user_email), display_name = COALESCE(?, display_name), password_hash = COALESCE(?, password_hash), grade = COALESCE(?, grade), role = COALESCE(?, role), school_id = COALESCE(?, school_id), is_verified = COALESCE(?, is_verified), promoted_by = COALESCE(?, promoted_by) WHERE user_id = ?");
        $stmt->execute([
            $data['user_email'] ?? null,
            $data['display_name'] ?? null,
            $data['password_hash'] ?? null,
            $data['grade'] ?? null,
            $data['role'] ?? null,
            $data['school_id'] ?? null,
            isset($data['is_verified']) ? (int)$data['is_verified'] : null,
            $data['promoted_by'] ?? null,
            $params['id']
        ]);
        echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
    }

    public function deleteUser($params)
    {
        $this->requireAuth();
        $userId = (int)$params['id'];

        $this->pdo->beginTransaction();
        $this->pdo->exec("DELETE FROM announcement_attachments WHERE announcement_id IN (SELECT announcement_id FROM announcements WHERE created_by = $userId)");
        $this->pdo->exec("DELETE FROM announcements WHERE created_by = $userId");
        $this->pdo->exec("DELETE FROM registrations WHERE user_id = $userId");
        $this->pdo->exec("DELETE FROM event_members WHERE user_id = $userId");
        $this->pdo->exec("DELETE FROM club_members WHERE user_id = $userId");
        $this->pdo->exec("DELETE FROM event_members WHERE event_id IN (SELECT event_id FROM events WHERE created_by = $userId)");
        $this->pdo->exec("DELETE FROM events WHERE created_by = $userId");
        $this->pdo->exec("UPDATE clubs SET approved_by = NULL WHERE approved_by = $userId");
        $this->pdo->exec("DELETE FROM clubs WHERE created_by = $userId");
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $this->pdo->commit();

        $this->redis->destroySession($this->tokenService->extractToken());
        echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
    }

    public function setRole($params)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $devSecret = $_ENV['DEV_SECRET'] ?? getenv('DEV_SECRET') ?? 'password';
        if (!$devSecret || ($data['secret'] ?? '') !== $devSecret) {
            $this->HandleError('Forbidden', 403);
        }

        $stmt = $this->pdo->prepare("UPDATE users SET role = ? WHERE user_email = ?");
        $stmt->execute([$data['role'], $data['email']]);
        echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
    }

    public function logout($params)
    {
        $token = $this->tokenService->extractToken();
        if ($token) {
            $this->redis->destroySession($token);
        }
        echo json_encode(['success' => true]);
    }

    public function requireAuth()
    {
        $token = $this->tokenService->extractToken();
        if (!$this->tokenService->authenticate($token)) {
            $this->HandleError('Missing or invalid token', 401);
        }
    }

    public function requireAuthToken($token)
    {
        if (!$this->tokenService->authenticate($token)) {
            $this->HandleError('Missing or invalid token', 401);
        }
    }

    public function requireRole($role)
    {
        $this->requireAuth();
        $user = $this->tokenService->getCurrentUser();
        if ($user['role'] !== $role) {
            $this->HandleError('Forbidden', 403);
        }
    }

    private function hashPassword($password)
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID);
        }
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function checkDomain($params)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $domain = $data['domain'] ?? '';
        if (!$domain) {
            $this->HandleError('Domain parameter is required', 400);
        }

        $stmt = $this->pdo->prepare("SELECT 1 FROM blocked_domains WHERE domain = ?");
        $stmt->execute([$domain]);
        if ($stmt->fetchColumn()) {
            echo json_encode(['blocked' => true, 'reason' => 'blocked']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT school_id FROM schools WHERE school_domain = ?");
        $stmt->execute([$domain]);
        $schoolId = $stmt->fetchColumn();
        if ($schoolId) {
            echo json_encode(['blocked' => false, 'reason' => 'ok', 'school_id' => $schoolId]);
            return;
        }

        echo json_encode(['blocked' => true, 'reason' => 'not_found']);
    }

    public function resendVerification($params)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';

        if (!$email) {
            $this->HandleError('Email is required', 400);
        }

        $stmt = $this->pdo->prepare("SELECT user_id, user_email, display_name, role, school_id, is_verified FROM users WHERE user_email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $this->HandleError('User not found', 404);
        }

        if ($user['is_verified']) {
            $this->HandleError('Email is already verified', 400);
        }

        $newToken = $this->redis->createSession($user);
        $this->redis->pushNotification('verify_email', [
            'email' => $email,
            'name' => $user['display_name'],
            'token' => $newToken,
        ]);

        echo json_encode(['success' => true, 'message' => 'Verification email sent']);
    }

    public function verifyUser($params)
    {
        $frontendUrl = rtrim(getenv('FRONTEND_URL') ?: 'http://localhost:5000', '/');

        $token = $_GET['token'] ?? '';
        $email = $_GET['email'] ?? '';

        if (!$token || !$email) {
            $data = json_decode(file_get_contents('php://input'), true);
            $token = $data['token'] ?? '';
            $email = $data['email'] ?? '';
        }

        if (!$token || !$email) {
            $this->HandleError('Missing token or email', 400);
        }

        $user = $this->tokenService->authenticate($token);
        if (!$user) {
            $stmt = $this->pdo->prepare("SELECT user_id, user_email, display_name, role, school_id FROM users WHERE user_email = ?");
            $stmt->execute([$email]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($userData) {
                $newToken = $this->redis->createSession($userData);
                $this->redis->pushNotification('verify_email', [
                    'email' => $email,
                    'name' => $userData['display_name'],
                    'token' => $newToken,
                ]);
            }

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                header("Location: $frontendUrl/login?verify=expired");
                exit;
            }
            $this->HandleError('Verification link expired. A new email has been sent.', 410);
        }

        $stmt = $this->pdo->prepare('UPDATE users SET is_verified = 1 WHERE user_email = ?');
        $stmt->execute([$email]);

        $this->redis->pushNotification('verified', [
            'email' => $email,
            'name' => $user['display_name'] ?? 'User',
        ]);

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            header("Location: $frontendUrl/login?verified=1&token=$token");
            exit;
        }

        echo json_encode(['success' => true, 'token' => $token, 'message' => 'Email verified']);
    }

    private function isJoinCodeValid($joinCode)
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM schools WHERE join_code = ?");
        $stmt->execute([$joinCode]);
        return (bool) $stmt->fetchColumn();
    }

    private function getSchoolFromJoinCode($joinCode)
    {
        $stmt = $this->pdo->prepare("SELECT school_id FROM schools WHERE join_code = ?");
        $stmt->execute([$joinCode]);
        return $stmt->fetchColumn();
    }

    private function isDomainBlocked($email)
    {
        $domain = substr(strrchr($email, "@"), 1);
        $stmt = $this->pdo->prepare("SELECT 1 FROM blocked_domains WHERE domain = ?");
        $stmt->execute([$domain]);
        return (bool) $stmt->fetchColumn();
    }

    private function getSchoolFromDomain($domain)
    {
        $stmt = $this->pdo->prepare("SELECT school_id FROM schools WHERE school_domain = ?");
        $stmt->execute([$domain]);
        return $stmt->fetchColumn();
    }

    private function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validatePassword($password)
    {
        if (strlen($password) < 8) return 'Password must be at least 8 characters';
        if (!preg_match('/[A-Za-z]/', $password)) return 'Password must contain at least one letter';
        if (!preg_match('/[0-9]/', $password)) return 'Password must contain at least one number';
        return null;
    }


    private function HandleError($message = '', $code = 500)
    {
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}
