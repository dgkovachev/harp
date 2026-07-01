<?php
namespace App;

use Exception;
use PDO;

class RedisService extends REDIS_CON
{
    private $client;
    private $sessionTtl = 86400;
    private $maxLoginAttempts = 5;
    private $loginBlockMinutes = 15;
    private static $pdo = null;

    public static function setPdo(PDO $pdo)
    {
        self::$pdo = $pdo;
    }

    public function __construct()
    {
        parent::__construct();
        $this->client = $this->redis;
    }

    public function isAvailable()
    {
        return $this->client !== null;
    }

    public function createSession($user)
    {
        if (!$this->client)
            return bin2hex(random_bytes(32));
        $token = bin2hex(random_bytes(32));
        $session = [
            'user_id' => $user['user_id'],
            'user_email' => $user['user_email'],
            'display_name' => $user['display_name'],
            'role' => $user['role'],
            'school_id' => $user['school_id'] ?? null,
            'created_at' => time(),
        ];
        $this->client->setex("session:{$token}", $this->sessionTtl, json_encode($session));
        return $token;
    }

    public function getSession($token)
    {
        if (!$this->client)
            return null;
        $data = $this->client->get("session:{$token}");
        return $data ? json_decode($data, true) : null;
    }

    public function destroySession($token)
    {
        if (!$this->client)
            return;
        $this->client->del("session:{$token}");
    }

    public function checkLoginRateLimit($email)
    {
        if (!$this->client)
            return true;
        $key = "login_attempts:" . strtolower($email);
        $attempts = $this->client->get($key);
        return !($attempts && $attempts >= $this->maxLoginAttempts);
    }

    public function getLoginBlockTTL($email)
    {
        if (!$this->client)
            return 0;
        return $this->client->ttl("login_attempts:" . strtolower($email));
    }

    public function incrementLoginAttempts($email)
    {
        if (!$this->client)
            return;
        $key = "login_attempts:" . strtolower($email);
        $this->client->incr($key);
        $this->client->expire($key, $this->loginBlockMinutes * 60);
    }

    public function clearLoginAttempts($email)
    {
        if (!$this->client)
            return;
        $this->client->del("login_attempts:" . strtolower($email));
    }

    public function pushNotification($type, $data)
    {
        if (!$this->client) {
            return;
        }
        $this->client->xadd('queue:notifications', [
            'type'       => $type,
            'data'       => json_encode($data),
        ], '*');

        if (self::$pdo) {
            $stmt = self::$pdo->prepare(
                "INSERT INTO notification_logs (type, recipient_email, recipient_name, payload, created_at) VALUES (?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $type,
                $data['email'] ?? null,
                $data['name'] ?? null,
                json_encode($data),
            ]);
        }
    }
}
