<?php
declare(strict_types=1);

date_default_timezone_set('UTC');
if (PHP_SAPI !== 'cli') {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
    header('Cache-Control: no-store');
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
        $db->commit();
    } catch (Throwable $e) { $db->rollBack(); throw $e; }
    return $db;
}

function current_profile(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $stmt = db()->prepare('SELECT * FROM profiles WHERE id = ? AND active = 1 AND locked = 0');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if (!$row) return null;
    unset($row['password_hash']);
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
    return getenv('INNPULSE_LAB_MODE') === '1' && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
}

function audit(string $event, string $severity = 'INFO', ?string $username = null): void {
    $user = current_profile();
    $stmt = db()->prepare('INSERT INTO audit_logs (id, user_id, username, ip_address, event, severity, session_reference) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([bin2hex(random_bytes(16)), $user['id'] ?? null, $username ?? ($user['username'] ?? 'guest'), $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $event, $severity, 'sess_' . substr(hash('sha256', session_id()), 0, 16)]);
}
