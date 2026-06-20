<?php
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host   = '10.0.0.4';
$user   = 'harp';
$pass   = 'Zjf0!zqhQunFsfKK7U5r';
$dbname = 'harp';



try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        // Login: only email + password, no display_name
        if (empty($data['display_name'])) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_email = ?");
            $stmt->execute([$data['user_email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($data['password'] ?? '', $user['password_hash'])) {
                unset($user['password_hash']);
                echo json_encode(['success' => true, 'data' => $user]);
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
            }
        }

        // Registration: has display_name
        else {
            $stmt = $pdo->prepare("INSERT INTO users (user_email, display_name, password_hash, grade, role, school_domain, is_verified, join_code, promoted_by, created_at) 
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

            echo json_encode(['success' => true, 'user_id' => $pdo->lastInsertId()]);
        }
    }

    else if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $stmt = $pdo->prepare("SELECT user_id, user_email, display_name, grade, role, school_domain, is_verified, created_at FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
        }
    }

    else if ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("UPDATE users SET user_email = ?, display_name = ?, grade = ?, role = ?, school_domain = ?, is_verified = ?, promoted_by = ? WHERE user_id = ?");
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

    else if ($method === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$data['user_id']]);
        echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>