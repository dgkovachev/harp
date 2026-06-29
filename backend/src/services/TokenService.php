<?php
namespace App;

class TokenService
{
    private $redis;
    private $currentUser;

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    public function extractToken()
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if ($header && preg_match('/Bearer\s(\S+)/', $header, $m)) {
            return $m[1];
        }
        if (function_exists('getallheaders')) {
            $allHeaders = getallheaders();
            $authHeader = $allHeaders['Authorization'] ?? $allHeaders['authorization'] ?? '';
            if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $m)) {
                return $m[1];
            }
        }
        return null;
    }

    public function authenticate($token)
    {
        if (!$token) {
            return null;
        }
        $user = $this->redis->getSession($token);
        if ($user) {
            $this->currentUser = $user;
        }
        return $user;
    }

    public function getCurrentUser()
    {
        return $this->currentUser;
    }
}
