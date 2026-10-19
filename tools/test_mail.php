<?php
// Local terminal diagnostic only. Never upload tools/ to the website.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../app/security.php';
require __DIR__ . '/../app/mailer.php';
$recipient = $argv[1] ?? 'qemespena@tip.edu.ph';
try {
    deliver_security_message($recipient, 'InnPulse email delivery test', 'InnPulse can send security messages to this mailbox. This is a delivery test, not a login code.');
    echo "SMTP server accepted the test message. Check the recipient inbox/spam folder to confirm delivery.\n";
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . "\n"); exit(1); }
