<?php
namespace Worker;
require __DIR__ . '/../vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;



class Mailer
{
    private $mailer;
    public function __construct()
    {
        $this->LoadMailer();
    }




    private function LoadMailer()
    {
        $this->mailer = new PHPMailer(true);

        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['SMTP_HOST'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['SMTP_USER'];
            $this->mailer->Password = $_ENV['SMTP_PASS'];
            if(!$this->mailer->Host || !$this->mailer->Username || !$this->mailer->Password) {
                throw new Exception('Cant get env data');
            }
            $port = getenv('SMTP_PORT') ?: 587;
            $this->mailer->Port = $port;
            if ($port == 465) {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
        } catch (Exception $e) {
            error_log("SMTP Error: " . $this->mailer->ErrorInfo);
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }


    public function sendMail($to, $subject, $message, $textMessage = "")
    {
        try {
            $this->mailer->SMTPDebug = 0;
            $this->mailer->setFrom('harp@smartech.bg', 'HARP Notifications');
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $message;
            $this->mailer->AltBody = $textMessage;

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("SMTP Error: " . $this->mailer->ErrorInfo);
            return false;
        }

    }

}