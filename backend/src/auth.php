<?php

require_once __DIR__ . '/../config.php';

class Authintication extends Connect
{
    private $method;
    private $token;
    private $currentUser;
    private $sessionTtl = 86400;         // 24 hours
    private $maxLoginAttempts = 5;       // lock after 5 failures
    private $loginBlockMinutes = 15;     // lock duration

    public function __construct($method)
    {
        parent::__construct();
        $this->method = $method;
        $this->token = $this->extractToken();
    }

    // Route request to the correct handler based on HTTP method
    public function Authintiocate()
    {
        try {
            switch ($this->method) {
                case 'POST':
                    $this->HandlePOST();
                    break;
                case 'GET':
                    $this->requireAuth();
                    $this->HandleGET();
                    break;
                case 'PUT':
                    $this->requireAuth();
                    $this->HandlePUT();
                    break;
                case 'DELETE':
                    $this->requireAuth();
                    $this->HandleDELETE();
                    break;
                default:
                    $this->HandleError('Method not allowed', 405);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Read Bearer token from Authorization header
    private function extractToken()
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s(\S+)/', $header, $m)) {
            return $m[1];
        }
        return null;
    }

    // Verify token exists and is valid in Redis
    private function requireAuth()
    {
        if (!$this->token) {
            $this->HandleError('Missing authorization token', 401);
            exit;
        }

        $session = $this->redis ? $this->redis->get("session:{$this->token}") : null;
        if (!$session) {
            $this->HandleError('Invalid or expired token', 401);
            exit;
        }

        $this->currentUser = json_decode($session, true);
    }

    // Generate a session token and store it in Redis with a TTL
    private function createSession($user)
    {
        $token = bin2hex(random_bytes(32));
        $session = [
            'user_id' => $user['user_id'],
            'user_email' => $user['user_email'],
            'display_name' => $user['display_name'],
            'role' => $user['role'],
            'created_at' => time(),
        ];

        if ($this->redis) {
            $this->redis->setex("session:{$token}", $this->sessionTtl, json_encode($session));
        }

        return $token;
    }

    // Push a job onto the notification queue for async processing
    private function pushNotification($type, $data)
    {
        if (!$this->redis) return;
        $job = json_encode(['type' => $type, 'data' => $data, 'created_at' => time()]);
        $this->redis->rpush('queue:notifications', $job);
    }

    // Hash password using Argon2ID (fallback to bcrypt cost 12)
    private function hashPassword($password)
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID);
        }
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    // Validate email format
    private function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    // Enforce password strength requirements
    private function validatePassword($password)
    {
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters';
        }
        if (!preg_match('/[A-Za-z]/', $password)) {
            return 'Password must contain at least one letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must contain at least one number';
        }
        return null;
    }

    // Check if this email has exceeded the login attempt limit
    private function checkLoginRateLimit($email)
    {
        if (!$this->redis) return true;
        $key = "login_attempts:" . strtolower($email);
        $attempts = $this->redis->get($key);
        if ($attempts && $attempts >= $this->maxLoginAttempts) {
            $ttl = $this->redis->ttl($key);
            $this->HandleError("Too many login attempts. Try again in {$ttl} seconds", 429);
            return false;
        }
        return true;
    }

    // Record a failed login attempt in Redis
    private function incrementLoginAttempts($email)
    {
        if (!$this->redis) return;
        $key = "login_attempts:" . strtolower($email);
        $this->redis->incr($key);
        $this->redis->expire($key, $this->loginBlockMinutes * 60);
    }

    // Clear failed attempts after a successful login
    private function clearLoginAttempts($email)
    {
        if (!$this->redis) return;
        $this->redis->del("login_attempts:" . strtolower($email));
    }

    // Handle POST — login (no display_name) or registration (has display_name)
    private function HandlePOST()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // --- LOGIN ---
        if (empty($data['display_name'])) {
            if (!$this->checkLoginRateLimit($data['user_email'] ?? '')) return;

            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_email = ?");
            $stmt->execute([$data['user_email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($data['password'] ?? '', $user['password_hash'])) {
                $this->clearLoginAttempts($data['user_email']);
                unset($user['password_hash'], $user['join_code'], $user['promoted_by'], $user['promoted_at']);
                $token = $this->createSession($user);
                echo json_encode(['success' => true, 'token' => $token, 'data' => $user]);
            } else {
                $this->incrementLoginAttempts($data['user_email'] ?? '');
                $this->HandleError('Invalid email or password', 401);
            }
            return;
        }

        // --- REGISTRATION ---
        if (!$this->validateEmail($data['user_email'] ?? '')) {
            $this->HandleError('Invalid email format', 400);
            return;
        }
        $passErr = $this->validatePassword($data['password'] ?? '');
        if ($passErr) {
            $this->HandleError($passErr, 400);
            return;
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
        $user = ['user_id' => $userId, 'user_email' => $data['user_email'], 'display_name' => $data['display_name'], 'role' => $data['role'] ?? 'student'];
        $token = $this->createSession($user);

        // Queue welcome email for async delivery
        $this->pushNotification('welcome_email', [
            'user_id' => $userId,
            'email' => $data['user_email'],
            'name' => $data['display_name'],
        ]);

        echo json_encode(['success' => true, 'token' => $token, 'user_id' => $userId]);
    }

    // GET — retrieve user by ID
    private function HandleGET()
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $stmt = $this->pdo->prepare("SELECT user_id, user_email, display_name, grade, role, school_domain, is_verified, created_at FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            $this->HandleError('User not found', 404);
        }
    }

    // PUT — update user fields
    private function HandlePUT()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $this->pdo->prepare("UPDATE users SET user_email = ?, display_name = ?, grade = ?, role = ?, school_domain = ?, is_verified = ?, promoted_by = ? WHERE user_id = ?");
        $stmt->execute([
            $data['user_email'] ?? null,
            $data['display_name'] ?? null,
            $data['grade'] ?? 0,
            $data['role'] ?? null,
            $data['school_domain'] ?? '',
            $data['is_verified'] ?? null,
            $data['promoted_by'] ?? null,
            $data['user_id']
        ]);
        echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
    }

    // DELETE — remove user by ID
    private function HandleDELETE()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$data['user_id']]);
        echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
    }

    // Send a JSON error response with the given status code
    private function HandleError($message = '', $code = 500)
    {
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $message]);
    }
}
