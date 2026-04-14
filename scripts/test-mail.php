<?php

/**
 * Standalone SMTP test — run from project root:
 *   php scripts/test-mail.php your@email.com
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
$dotenv->safeLoad();

$to = $argv[1] ?? null;

if ($to === null) {
    echo "Usage: php scripts/test-mail.php your@email.com\n";
    exit(1);
}

echo "SMTP settings loaded from .env:\n";
echo "  Host:       " . ($_ENV['MAIL_HOST']         ?? '(not set)') . "\n";
echo "  Port:       " . ($_ENV['MAIL_PORT']         ?? '(not set)') . "\n";
echo "  Username:   " . ($_ENV['MAIL_USERNAME']     ?? '(not set)') . "\n";
echo "  From:       " . ($_ENV['MAIL_FROM_ADDRESS'] ?? '(not set)') . "\n";
echo "  Encryption: " . ($_ENV['MAIL_ENCRYPTION']   ?? '(not set)') . "\n";
echo "\nSending test email to: $to\n";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;   // full SMTP conversation in output
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
    $mail->addAddress($to);

    $mail->isHTML(true);
    $mail->Subject = 'Smart Risk Assessment — SMTP test';
    $mail->Body    = '<p>This is a test email from <strong>Smart Risk Assessment</strong>. If you received this, SMTP is working correctly.</p>';
    $mail->AltBody = 'This is a test email from Smart Risk Assessment. SMTP is working correctly.';

    $mail->send();
    echo "\n✅  Email sent successfully to $to\n";

} catch (MailerException $e) {
    echo "\n❌  Send failed: " . $mail->ErrorInfo . "\n";
    exit(1);
}
