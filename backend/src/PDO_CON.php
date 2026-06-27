<?php
namespace App;

use PDO;
use PDOException;
use Exception;

class PDO_CON
{
    protected $pdo;

    public function __construct()
    {
        if(!$this->connect()){
            throw new Exception('SQL failed to connect');
        }
    }

    private function connect(): bool
    {
        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
        $user = $_ENV['DB_USER'] ?? getenv('DB_USER');
        $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
        $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
        try {
            $this->pdo = new PDO(
                "mysql:host={$host};dbname={$name};charset=utf8mb4",
                $user,
                $pass
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return true;
        } catch (PDOException $e) {
            error_log('DB connection error: ' . $e->getMessage());
            $this->pdo = null;
            return false;
        }
    }
}
