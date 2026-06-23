<?php

require_once __DIR__ . '/../backend/vendor/autoload.php';

use Predis\Client;
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../backend');
$dotenv->load();

$host = $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?? '127.0.0.1';
$port = $_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?? 6379;

try {
    $redis = new Client(['scheme' => 'tcp', 'host' => $host, 'port' => $port]);
    echo "Worker started, waiting for jobs...\n";

    while (true) {
        $result = $redis->blpop('queue:notifications', 0);

        if ($result) {
            $payload = json_decode($result[1], true);
            $type = $payload['type'] ?? 'unknown';
            $data = $payload['data'] ?? [];
            $time = date('Y-m-d H:i:s');

            echo "[{$time}] Processing: {$type}\n";

            switch ($type) {
                case 'welcome_email':
                    echo "  Sending welcome email to {$data['email']} ({$data['name']})\n";
                    break;
                case 'password_reset':
                    echo "  Sending password reset to {$data['email']}\n";
                    break;
                default:
                    echo "  Unknown job type: {$type}\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Worker error: " . $e->getMessage() . "\n";
    exit(1);
}
