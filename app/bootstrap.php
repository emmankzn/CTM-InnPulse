<?php
declare(strict_types=1);

date_default_timezone_set('UTC');
require_once __DIR__ . '/security.php';
if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('INNPULSE_SESSION');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => str_starts_with(security_config()['public_origin'], 'https://') || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')]);
    session_start();
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: DENY');
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
    // Scripts and event handlers are external files; injected scripts and HTML handlers are blocked.
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://challenges.cloudflare.com; script-src-attr 'none'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: https://images.unsplash.com; font-src 'self' https://fonts.gstatic.com; connect-src 'self' https://challenges.cloudflare.com; frame-src https://challenges.cloudflare.com; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') header('Strict-Transport-Security: max-age=31536000');
}

function db(): PDO {
    static $db;
    if ($db) return $db;
    $path = getenv('INNPULSE_DB_PATH') ?: __DIR__ . '/../storage/innpulse.sqlite';
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
    $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $db->exec('PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
    $db->beginTransaction();
    try {
        $exists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='profiles'")->fetchColumn();
        if (!$exists) {
            $db->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
            $db->exec(file_get_contents(__DIR__ . '/../database/seed.sql'));
            $stmt = $db->prepare('INSERT INTO profiles (id, username, email, role, password_hash) VALUES (?, ?, ?, ?, ?)');
            foreach ([['admin.root', 'admin@innpulse.com', 'admin'], ['jane.staff', 'staff@innpulse.com', 'staff']] as [$name, $email, $role]) {
                $stmt->execute([bin2hex(random_bytes(16)), $name, $email, $role, password_hash($role . '@innpulse', PASSWORD_DEFAULT)]);
            }
        }
        security_migrate($db);
        $db->commit();
    } catch (Throwable $e) { $db->rollBack(); throw $e; }
    return $db;
}

function current_profile(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    if (($_SESSION['security_revision'] ?? 0) !== 3 || time() - ($_SESSION['last_seen'] ?? 0) > 900 || time() - ($_SESSION['issued'] ?? 0) > 28800) {
        unset($_SESSION['user_id']); return null;
    }
    $stmt = db()->prepare('SELECT * FROM profiles WHERE id = ? AND active = 1 AND locked = 0');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if (!$row) return null;
    if ((int)$row['session_version'] !== ($_SESSION['session_version'] ?? 0) || empty($_SESSION['mfa'])) {
        unset($_SESSION['user_id']); return null;
    }
    $_SESSION['last_seen'] = time();
    unset($row['password_hash'], $row['session_version'], $row['must_change_password']);
    $row['active'] = (bool)$row['active'];
    $row['locked'] = (bool)$row['locked'];
    return $row;
}

function require_page(array $roles): void {
    $user = current_profile();
    if (!$user) { header('Location: index.php?login=1'); exit; }
    if (!in_array($user['role'], $roles, true)) { http_response_code(403); exit('Access denied.'); }
}

function lab_mode(): bool {
    return false; // The active application is hardened, including localhost.
}

function audit(string $event, string $severity = 'INFO', ?string $username = null): void {
    $user = current_profile();
    $stmt = db()->prepare('INSERT INTO audit_logs (id, user_id, username, ip_address, event, severity, session_reference) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([bin2hex(random_bytes(16)), $user['id'] ?? null, $username ?? ($user['username'] ?? 'guest'), $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $event, $severity, 'sess_' . substr(hash('sha256', session_id()), 0, 16)]);
}
