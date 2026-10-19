<?php
// Copy to config.local.php. Never put API keys in JavaScript or commit this file.
return [
    'admin_email' => 'qemespena@tip.edu.ph',
    'public_origin' => 'https://innpulse.free.nf',
    'mail_transport' => 'smtp', // Gmail via PHPMailer; 'brevo' remains available as an HTTP API alternative.
    'smtp_username' => 'hari.zxc33@gmail.com',
    'smtp_password' => '', // Insert a NEW Gmail app password privately. Never commit/share it.
    'smtp_port' => 587, // STARTTLS; use 465 for implicit TLS if your host requires it.
    'brevo_api_key' => '',
    'mail_sender_email' => '', // Used only by the optional Brevo transport.
    'mail_sender_name' => 'InnPulse Security',
    'turnstile_site_key' => '',
    'turnstile_secret_key' => '',
    'turnstile_hostname' => 'innpulse.free.nf',
    'check_breached_passwords' => true,
];
