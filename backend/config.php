<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables from .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

class Connect
{
    protected $pdo;
    protected $redis;
    private $host;
    private $user;
    private $pass;
    private $name;

    public function __construct()
    {
        // Read DB credentials from environment
        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
        $this->user = $_ENV['DB_USER'] ?? getenv('DB_USER');
        $this->pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
        $this->name = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
        $this->connect();
        $this->connectRedis();
    }

    // Connect to MySQL
    private function connect()
    {
        $this->pdo = new PDO(
            "mysql:host={$this->host};dbname={$this->name};charset=utf8mb4",
            $this->user,
            $this->pass
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // Connect to Redis for sessions, rate limiting, and queues
    private function connectRedis()
    {
        $host = $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?? '127.0.0.1';
        $port = $_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?? 6379;

        try {
            $this->redis = new Predis\Client([
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
            ]);
        } catch (Exception $e) {
            // Redis unavailable — fall back gracefully
            $this->redis = null;
        }
    }
}
