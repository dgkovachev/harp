<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST');
$port = $_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT');

try {
$redis = new Predis\Client(['scheme' => 'tcp', 'host' => $host, 'port' => $port]);
echo "Worker started, waiting for jobs...\n";

$streamName = 'queue:notifications';

echo "Worker started, waiting for jobs...\n";

while (true) {
    // Use the lesson's exact signature:
    // xread(count, block, [$streamName, startingId])
    // '>' means "only new messages"
    $messages = $redis->xread(1, 0, [$streamName, '0-0']);

    // $messages is null if no messages (but block=0 so it waits forever)
    if ($messages) {
        // Structure from the lesson:
        // $messages[$streamName][0] = [messageId, flatFieldArray]
        // flatFieldArray = ['event', 'login', 'user', 'Alice', ...]
        foreach ($messages[$streamName] as $entry) {
            $id = $entry[0];
            $flatFields = $entry[1];

            // Convert flat array to associative array
            // Example: ['event', 'login', 'user', 'Alice'] → ['event' => 'login', 'user' => 'Alice']
            $fields = [];
            for ($i = 0; $i < count($flatFields); $i += 2) {
                $key = $flatFields[$i];
                $value = $flatFields[$i + 1] ?? null;
                $fields[$key] = $value;
            }

            // Now we have an associative array: ['type' => 'welcome_email', 'data' => '{"email":"..."}']
            $type = $fields['type'] ?? 'unknown';
            $data = json_decode($fields['data'] ?? '{}', true); // decode the nested JSON

            $time = date('Y-m-d H:i:s');
            echo "[$time] Processing: $type\n";

            switch ($type) {
                case 'welcome_email':
                    echo "  Sending welcome email to {$data['email']} ({$data['name']})\n";
                    break;
                case 'password_reset':
                    echo "  Sending password reset to {$data['email']}\n";
                    break;
                default:
                    echo "  Unknown job type: $type\n";
                    // Debug: show what we actually got
                    print_r($fields);
            }

            $redis->xdel($streamName, $id);
        }
    }
}
} catch (Exception $e) {
    echo "Worker error: " . $e->getMessage() . "\n";
    exit(1);
}
