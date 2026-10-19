<?php
// Router for the PHP development server. Apache uses the included .htaccess files.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (str_contains($path, '..') || preg_match('~^/(?:app|database|storage|tests|tools|PHPMailer|deployment|node_modules|supabase|\.)~i', $path)
    || preg_match('~\.(?:sqlite|sql|env|log|py)$~i', $path)) {
    http_response_code(404); exit('Not found');
}
if (preg_match('~^/([a-z-]+)\.html$~', $path, $m) && is_file(__DIR__ . '/' . $m[1] . '.php')) {
    header('Location: /' . $m[1] . '.php', true, 301); exit;
}
return false;
