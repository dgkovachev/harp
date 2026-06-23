<?php
namespace App;

use Exception;

class RedisService
{
    private $client;
    private $sessionTtl = 86400;
    private $maxLoginAttempts = 5;
    private $loginBlockMinutes = 15;

    public function __construct($host = '127.0.0.1', $port = 6379)
    {
        try {
            $this->client = new \Predis\Client([
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
            ]);
        } catch (Exception $e) {
            $this->client = null;
        }
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
        if (!$this->client) return;
        $this->client->xadd('queue:notifications', [
            'type'       => $type,
            'data'       => json_encode($data),
            'created_at' => time()
        ], '*');
    }
}
