<?php
require __DIR__ . '/../app/security.php';
require __DIR__ . '/../app/mailer.php';
function check(bool $value): void { if (!$value) throw new RuntimeException('SMTP adapter regression failed.'); }
$cfg = ['smtp_username' => 'sender@example.test', 'smtp_password' => 'abcd efgh ijkl mnop', 'smtp_port' => 587, 'mail_sender_name' => 'InnPulse Security'];
$mail = smtp_mailer($cfg);
check($mail->Mailer === 'smtp' && $mail->Host === 'smtp.gmail.com' && $mail->Port === 587);
check($mail->SMTPSecure === 'tls' && $mail->SMTPAuth && $mail->SMTPDebug === 0);
check($mail->Password === 'abcdefghijklmnop');
check($mail->From === 'sender@example.test');
check($mail->SMTPOptions['ssl']['verify_peer'] && $mail->SMTPOptions['ssl']['verify_peer_name'] && !$mail->SMTPOptions['ssl']['allow_self_signed']);
$mail->addAddress('recipient@example.test'); $mail->Subject = 'Login verification'; $mail->Body = 'Example verification message';
check($mail->preSend());
check(str_contains($mail->getSentMIMEMessage(), 'recipient@example.test'));
check(!str_contains($mail->getSentMIMEMessage(), 'abcdefghijklmnop'));
$next = smtp_mailer($cfg); check(count($next->getToAddresses()) === 0);
$cfg['smtp_port'] = 465; check(smtp_mailer($cfg)->SMTPSecure === 'ssl');
$cfg['smtp_password'] = '';
try { smtp_mailer($cfg); throw new RuntimeException('Empty password accepted.'); } catch (SecurityServiceException $expected) {}
echo "PHPMailer adapter passed: TLS, authentication, per-message recipients, configuration validation and no password leakage in message content.\n";
