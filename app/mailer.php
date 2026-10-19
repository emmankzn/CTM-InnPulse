<?php
declare(strict_types=1);

/** Build a new authenticated SMTP client for each message; never share recipient lists. */
function smtp_mailer(array $cfg): \PHPMailer\PHPMailer\PHPMailer {
    require_once __DIR__ . '/vendor/phpmailer/src/Exception.php';
    require_once __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/vendor/phpmailer/src/SMTP.php';
    $username = trim((string)($cfg['smtp_username'] ?? ''));
    $password = preg_replace('/\s+/', '', (string)($cfg['smtp_password'] ?? ''));
    if (!filter_var($username, FILTER_VALIDATE_EMAIL) || !preg_match('/^[A-Za-z0-9]{16}$/D', $password)) {
        throw new SecurityServiceException('Gmail SMTP needs its sender email and a valid app password in the private configuration.');
    }
    $port = (int)($cfg['smtp_port'] ?? 587);
    if (!in_array($port, [465, 587], true)) throw new SecurityServiceException('Use Gmail SMTP port 587 (STARTTLS) or 465 (TLS).');
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = $port;
    $mail->SMTPSecure = $port === 465 ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPAuth = true;
    $mail->Username = $username;
    $mail->Password = $password;
    $mail->SMTPDebug = 0;
    $mail->Timeout = 10;
    $mail->Timelimit = 15;
    $mail->SMTPOptions = ['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false]];
    $mail->CharSet = 'UTF-8';
    $mail->XMailer = 'InnPulse Security';
    $mail->setFrom($username, $cfg['mail_sender_name'] ?? 'InnPulse Security');
    $mail->isHTML(false);
    return $mail;
}

function deliver_security_message(string $recipient, string $subject, string $body): void {
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) throw new SecurityServiceException('Account email is invalid.');
    $cfg = security_config();
    if (($cfg['mail_transport'] ?? 'smtp') === 'smtp') {
        try {
            $mail = smtp_mailer($cfg);
            $mail->addAddress($recipient);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
        } catch (SecurityServiceException $e) { throw $e; }
        catch (Throwable $e) {
            // Do not expose SMTP conversations, credentials, codes or full mailer errors to the browser/logs.
            throw new SecurityServiceException('Gmail SMTP delivery failed. Check the private sender settings, app password and outbound SMTP access.');
        }
        return;
    }
    if (($cfg['mail_transport'] ?? '') !== 'brevo') throw new SecurityServiceException('Unsupported email transport.');
    if (!$cfg['brevo_api_key'] || !filter_var($cfg['mail_sender_email'], FILTER_VALIDATE_EMAIL)) {
        throw new SecurityServiceException('Email API delivery is not configured.');
    }
    https_json('https://api.brevo.com/v3/smtp/email', [
        'sender' => ['name' => $cfg['mail_sender_name'], 'email' => $cfg['mail_sender_email']],
        'to' => [['email' => $recipient]], 'subject' => $subject, 'textContent' => $body,
    ], ['api-key: ' . $cfg['brevo_api_key']]);
}
