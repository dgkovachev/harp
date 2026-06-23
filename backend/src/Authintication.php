<?php
namespace App;

use PDO;
use App\TokenService;

class Authintication extends Connect
{
    private $tokenService;

    public function __construct()
    {
        parent::__construct();
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

        if ($user && password_verify($data['password'] ?? '', $user['password_hash'])) {
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

        $stmt = $this->pdo->prepare("INSERT INTO users (user_email, display_name, password_hash, grade, role, school_domain, is_verified, join_code, promoted_by, created_at) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $data['user_email'],
            $data['display_name'],
            $this->hashPassword($data['password']),
            $data['grade'] ?? 0,
            $data['role'] ?? 'student',
            $data['school_domain'] ?? '',
            $data['is_verified'] ?? 0,
            $data['join_code'] ?? '',
            $data['promoted_by'] ?? null
        ]);

        $userId = $this->pdo->lastInsertId();
        $user = [
            'user_id' => $userId,
            'user_email' => $data['user_email'],
            'display_name' => $data['display_name'],
            'role' => $data['role'] ?? 'student',
        ];
        $token = $this->redis->createSession($user);
        $this->redis->pushNotification('welcome_email', [
            'user_id' => $userId,
            'email' => $data['user_email'],
            'name' => $data['display_name'],
        ]);

        echo json_encode(['success' => true, 'token' => $token, 'user_id' => $userId]);
    }

    public function getUser($params)
    {
        $this->requireAuth();

        $id = $params['id'] ?? 0;
        $stmt = $this->pdo->prepare("SELECT user_id, user_email, display_name, grade, role, school_domain, is_verified, created_at FROM users WHERE user_id = ?");
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

        $stmt = $this->pdo->prepare("UPDATE users SET user_email = COALESCE(?, user_email), display_name = COALESCE(?, display_name), password_hash = COALESCE(?, password_hash), grade = COALESCE(?, grade), role = COALESCE(?, role), school_domain = COALESCE(?, school_domain), is_verified = COALESCE(?, is_verified), promoted_by = COALESCE(?, promoted_by) WHERE user_id = ?");
        $stmt->execute([
            $data['user_email'] ?? null,
            $data['display_name'] ?? null,
            $data['password_hash'] ?? null,
            $data['grade'] ?? null,
            $data['role'] ?? null,
            $data['school_domain'] ?? null,
            isset($data['is_verified']) ? (int)$data['is_verified'] : null,
            $data['promoted_by'] ?? null,
            $params['id']
        ]);
        echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
    }

    public function deleteUser($params)
    {
        $this->requireAuth();

        $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$params['id']]);
        $this->redis->destroySession($this->tokenService->extractToken());
        echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
    }

    public function logout($params)
    {
        $token = $this->tokenService->extractToken();
        if ($token) {
            $this->redis->destroySession($token);
        }
        echo json_encode(['success' => true]);
    }

    private function requireAuth()
    {
        $token = $this->tokenService->extractToken();
        if (!$this->tokenService->authenticate($token)) {
            $this->HandleError('Missing or invalid token', 401);
        }
    }

    private function hashPassword($password)
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID);
        }
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
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
