<?php
namespace App;

use Exception;
use Predis\Client as PredisClient;

class REDIS_CON
{
    protected $redis;

    public function __construct(){
        if(!$this->connectRedis()){
            throw new Exception('Redis failed to connect');
        }
    }
    private function connectRedis(): bool
    {
        $host = $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?? '127.0.0.1';
        $port = $_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?? 6379;

        try {
            $this->redis = new  PredisClient([
                'scheme'   => 'tcp',
                'host'     => $host,
                'port'     => $port
            ]);
            return true;
        } catch (Exception $e) {
            error_log('Redis connection failed: ' . $e->getMessage());
            $this->redis = null;
            return false;
        }
    }
}
