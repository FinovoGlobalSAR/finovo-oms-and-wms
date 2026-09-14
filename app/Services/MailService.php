<?php
require_once __DIR__ . '/../../libraries/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../libraries/phpmailer/SMTP.php';
require_once __DIR__ . '/../../libraries/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    public function sendOtp(string $toEmail, string $toName, string $otp): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = env('SMTP_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = env('SMTP_USERNAME', '');
            $mail->Password = env('SMTP_PASSWORD', '');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int) env('SMTP_PORT', 587);

            $mail->setFrom(env('SMTP_FROM_EMAIL', 'no-reply@finovo.com'), env('SMTP_FROM_NAME', 'Finovo'));
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = 'Your Finovo Password Reset Code';
            $mail->Body = "
                <p>Hi " . htmlspecialchars($toName) . ",</p>
                <p>Your password reset code is:</p>
                <h2 style='letter-spacing:4px;'>{$otp}</h2>
                <p>This code expires in 10 minutes. If you did not request this, please ignore this email.</p>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}