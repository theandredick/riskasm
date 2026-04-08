<?php

declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

class Mailer
{
    public static function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody = ''
    ): bool {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST']        ?? '';
            $mail->Port       = (int) ($_ENV['MAIL_PORT'] ?? 587);
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME']    ?? '';
            $mail->Password   = $_ENV['MAIL_PASSWORD']    ?? '';
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION']  ?? PHPMailer::ENCRYPTION_STARTTLS;

            $mail->setFrom(
                $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com',
                $_ENV['MAIL_FROM_NAME']    ?? 'Smart Risk Assessment'
            );
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $plainBody ?: strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (MailerException) {
            error_log('Mailer error: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
