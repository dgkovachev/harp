<?php
namespace Worker;

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Predis\Client as RedisClient;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

class Worker
{
    private $redis;
    private $streamName;
    private $group;
    private $consumer;

    public function __construct()
    {
        $this->streamName = 'queue:notifications';
        $this->group = 'worker';
        $this->consumer = 'consumer-1';

        $host = $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?? '127.0.0.1';
        $port = $_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?? 6379;

        $this->redis = new RedisClient([
            'scheme' => 'tcp',
            'host' => $host,
            'port' => $port,
        ]);

        try {
            $this->redis->xgroup('CREATE', $this->streamName, $this->group, '0', true);
        } catch (\Exception $e) {
            // Group already exists — ignore
        }
    }

    private function checkType($type, $data, $fields)
    {
        switch ($type) {
            case 'welcome_email':
                echo "  Sending welcome email to {$data['email']} ({$data['name']})\n";
                break;
            case 'password_reset':
                echo "  Sending password reset to {$data['email']}\n";
                break;
            default:
                echo "  Unknown job type: $type\n";
                print_r($fields);
        }
    }

    private function readMessages()
    {
        $messages = $this->redis->xreadgroup($this->group, $this->consumer, 1, null, false, $this->streamName, '>');

        if ($messages) {
            foreach ($messages[$this->streamName] as $entry) {
                $id = $entry[0];
                $flatFields = $entry[1];

                $fields = [];
                for ($i = 0; $i < count($flatFields); $i += 2) {
                    $fields[$flatFields[$i]] = $flatFields[$i + 1] ?? null;
                }

                $type = $fields['type'] ?? 'unknown';
                $data = json_decode($fields['data'] ?? '{}', true);

                $time = date('Y-m-d H:i:s');
                echo "[$time] Processing: $type\n";
                $this->checkType($type, $data, $fields);

                $this->redis->xack($this->streamName, $this->group, $id);
            }
        }
    }

    public function run(): never
    {
        while (true) {
            try {
                $this->readMessages();
            } catch (\Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
                sleep(1);
            }
        }
    }
}
