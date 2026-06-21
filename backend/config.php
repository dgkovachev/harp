<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

class Connect
{
    protected $pdo;
    private $host;
    private $user;
    private $pass;
    private $name;

    public function __construct()
    {
        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
        $this->user = $_ENV['DB_USER'] ?? getenv('DB_USER');
        $this->pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
        $this->name = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
        $this->connect();
    }

    private function connect()
    {
        $this->pdo = new PDO(
            "mysql:host={$this->host};dbname={$this->name};charset=utf8mb4",
            $this->user,
            $this->pass
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
}
