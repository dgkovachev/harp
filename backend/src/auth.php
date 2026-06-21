<?php

require_once __DIR__ . '/../config.php';

class Authintication extends Connect
{
    private $method;

    public function __construct($method)
    {
        parent::__construct();
        $this->method = $method;
    }

    public function Authintiocate()
    {
        try {
            switch ($this->method) {
                case 'POST':
                    $this->HandlePOST();
                    break;
                case 'GET':
                    $this->HandleGET();
                    break;
                case 'PUT':
                    $this->HandlePUT();
                    break;
                case 'DELETE':
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

    private function HandlePOST()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['display_name'])) {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_email = ?");
            $stmt->execute([$data['user_email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($data['password'] ?? '', $user['password_hash'])) {
                unset($user['password_hash']);
                echo json_encode(['success' => true, 'data' => $user]);
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
            }
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO users (user_email, display_name, password_hash, grade, role, school_domain, is_verified, join_code, promoted_by, created_at) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $data['user_email'],
                $data['display_name'],
                password_hash($data['password'] ?? '', PASSWORD_BCRYPT),
                $data['grade'] ?? 0,
                $data['role'] ?? 'student',
                $data['school_domain'] ?? '',
                $data['is_verified'] ?? 0,
                $data['join_code'] ?? '',
                $data['promoted_by'] ?? null
            ]);

            echo json_encode(['success' => true, 'user_id' => $this->pdo->lastInsertId()]);
        }
    }

    private function HandleGET()
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $stmt = $this->pdo->prepare("SELECT user_id, user_email, display_name, grade, role, school_domain, is_verified, created_at FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
        }
    }

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

    private function HandleDELETE()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$data['user_id']]);
        echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
    }

    private function HandleError($message = '', $code = 500)
    {
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $message]);
    }
}
