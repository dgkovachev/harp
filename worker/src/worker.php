<?php
namespace Worker;

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Predis\Client as RedisClient;
use Worker\Mailer;

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

class Worker
{
    private $redis;
    private $mailer;
    private $streamName;

    public function __construct()
    {
        $this->streamName = 'queue:notifications';

        $host = $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?? '127.0.0.1';
        $port = $_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?? 6379;

        $this->redis = new RedisClient([
            'scheme' => 'tcp',
            'host' => $host,
            'port' => $port,
        ]);


        $this->mailer = new Mailer();

    }

    private function checkType($type, $data)
    {
        switch ($type) {
            case 'welcome_email':
                echo "  Sending welcome email to {$data['email']} ({$data['name']})\n";
                break;
            case 'password_reset':
                echo "  Sending password reset to {$data['email']}\n";
                break;
            case "verify_email":
                $email = $data['email'] ?? '';
                $name = htmlspecialchars($data['name'] ?? 'User');
                $token = $data['token'] ?? bin2hex(random_bytes(32));
                $verifyLink = "https://harpapi.smartech.bg/verify?token={$token}&email=" . urlencode($email);
                if ($email) {
                    require_once __DIR__ . '/../templates/verify.php';
                    $htmlBody = renderVerifyEmail($name, $verifyLink);
                    $textBody = "Hello {$name},\n\n";
                    $textBody .= "Please verify your email address by visiting this link:\n\n";
                    $textBody .= "{$verifyLink}\n\n";
                    $textBody .= "This link expires in 24 hours.";
                    
                    $success = $this->mailer->sendMail($email, "Verify Your HARP Account", $htmlBody, $textBody);
                    echo $success ? "  ✅ Verification email sent\n" : "  ❌ SMTP failed\n";
                }
                break;

            case "registration_confirmed":
                $email = $data['email'] ?? '';
                $name = htmlspecialchars($data['name'] ?? 'User');
                $eventTitle = htmlspecialchars($data['event_title'] ?? 'Event');
                $eventDate = $data['event_date'] ?? 'TBD';
                $location = htmlspecialchars($data['location'] ?? 'TBD');
                if ($email) {
                    require_once __DIR__ . '/../templates/event_confirmed.php';
                    $htmlBody = renderEventConfirmedEmail($name, $eventTitle, $eventDate, $location);
                    $textBody = "Hello {$name},\n\nYou are registered for {$eventTitle} on {$eventDate} at {$location}.";
                    $success = $this->mailer->sendMail($email, "Registration Confirmed: {$eventTitle}", $htmlBody, $textBody);
                    echo $success ? "  ✅ Confirmation email sent\n" : "  ❌ SMTP failed\n";
                }
                break;

            case "registration_waitlisted":
                $email = $data['email'] ?? '';
                $name = htmlspecialchars($data['name'] ?? 'User');
                $eventTitle = htmlspecialchars($data['event_title'] ?? 'Event');
                $position = $data['queue_position'] ?? '?';
                if ($email) {
                    require_once __DIR__ . '/../templates/event_waitlisted.php';
                    $htmlBody = renderEventWaitlistedEmail($name, $eventTitle, $position);
                    $textBody = "Hello {$name},\n\nYou are #{$position} on the waitlist for {$eventTitle}.";
                    $success = $this->mailer->sendMail($email, "Waitlisted: {$eventTitle}", $htmlBody, $textBody);
                    echo $success ? "  ✅ Waitlist email sent\n" : "  ❌ SMTP failed\n";
                }
                break;

            case "waitlist_promoted":
                $email = $data['email'] ?? '';
                $name = htmlspecialchars($data['name'] ?? 'User');
                $eventTitle = htmlspecialchars($data['event_title'] ?? 'Event');
                $eventDate = $data['event_date'] ?? 'TBD';
                $location = htmlspecialchars($data['location'] ?? 'TBD');
                if ($email) {
                    require_once __DIR__ . '/../templates/event_promoted.php';
                    $htmlBody = renderEventPromotedEmail($name, $eventTitle, $eventDate, $location);
                    $textBody = "Hello {$name},\n\nGreat news! You have been promoted from the waitlist for {$eventTitle} on {$eventDate} at {$location}.";
                    $success = $this->mailer->sendMail($email, "Spot Opened: {$eventTitle}", $htmlBody, $textBody);
                    echo $success ? "  ✅ Promotion email sent\n" : "  ❌ SMTP failed\n";
                }
                break;

            default:
                echo "  Unknown job type: $type\n";
        }
    }

    private function readMessages()
    {
        $messages = $this->redis->xread(1, 0, [$this->streamName, "0-0"]);
        if ($messages) {
            $entry = $messages[$this->streamName][0];
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
            $this->checkType($type, $data);

            $this->redis->xdel($this->streamName, $id);
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
