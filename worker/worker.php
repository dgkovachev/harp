<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?? 'redis';
$port = $_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?? 6379;

try {
    $redis = new Predis\Client(['scheme' => 'tcp', 'host' => $host, 'port' => $port]);
    echo "Worker started, waiting for jobs...\n";

    while (true) {
        $job = $redis->blpop('queue:notifications', 0);

        if ($job) {
            $payload = json_decode($job[1], true);
            $type = $payload['type'] ?? 'unknown';
            $data = $payload['data'] ?? [];

            echo "[" . date('Y-m-d H:i:s') . "] Processing: {$type}\n";

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
