<?php
declare(strict_types=1);
class SecurityServiceException extends RuntimeException {}

function security_config(): array {
    static $config;
    if ($config !== null) return $config;
    $path = __DIR__ . '/config.local.php';
    $config = ['admin_email' => 'qemespena@tip.edu.ph', 'public_origin' => '', 'mail_transport' => 'smtp',
        'smtp_username' => '', 'smtp_password' => '', 'smtp_port' => 587, 'brevo_api_key' => '', 'mail_sender_email' => '',
        'mail_sender_name' => 'InnPulse Security', 'turnstile_site_key' => '', 'turnstile_secret_key' => '',
        'turnstile_hostname' => '', 'check_breached_passwords' => true];
    if (is_file($path)) $config = array_replace($config, require $path);
    return $config;
}
function security_test_mode(): bool {
    return !defined('INNPULSE_PUBLIC_BUILD') && PHP_SAPI === 'cli-server'
        && getenv('INNPULSE_TEST_MODE') === '1' && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
}
function security_migrate(PDO $pdo): void {
    $cols = array_column($pdo->query('PRAGMA table_info(profiles)')->fetchAll(), 'name');
    foreach (['session_version' => 'INTEGER NOT NULL DEFAULT 1', 'must_change_password' => 'INTEGER NOT NULL DEFAULT 0'] as $name => $type) {
        if (!in_array($name, $cols, true)) $pdo->exec("ALTER TABLE profiles ADD COLUMN $name $type");
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS security_meta (key TEXT PRIMARY KEY, value TEXT NOT NULL);
        CREATE TABLE IF NOT EXISTS auth_limits (scope TEXT NOT NULL, key TEXT NOT NULL, failures INTEGER NOT NULL DEFAULT 0, started INTEGER NOT NULL DEFAULT 0, blocked_until INTEGER NOT NULL DEFAULT 0, next_allowed INTEGER NOT NULL DEFAULT 0, PRIMARY KEY(scope,key));
        CREATE TABLE IF NOT EXISTS security_requests (ip TEXT NOT NULL, category TEXT NOT NULL, at INTEGER NOT NULL);
        CREATE INDEX IF NOT EXISTS security_requests_lookup ON security_requests(ip,category,at);
        CREATE TABLE IF NOT EXISTS auth_challenges (id TEXT PRIMARY KEY, user_id TEXT NOT NULL REFERENCES profiles(id) ON DELETE CASCADE, session_hash TEXT NOT NULL, purpose TEXT NOT NULL, digest TEXT NOT NULL, code_hash TEXT NOT NULL, expires INTEGER NOT NULL, attempts INTEGER NOT NULL DEFAULT 0, consumed INTEGER NOT NULL DEFAULT 0);");
    $pdo->exec('CREATE INDEX IF NOT EXISTS auth_challenges_expiry ON auth_challenges(expires)');
    $s = $pdo->prepare('DELETE FROM auth_challenges WHERE expires < ?'); $s->execute([time() - 86400]);
    $key = 'defenses-v1';
    $s = $pdo->prepare('SELECT value FROM security_meta WHERE key=?'); $s->execute([$key]);
    if (!$s->fetchColumn()) {
        $s = $pdo->prepare("UPDATE profiles SET email=?, session_version=session_version+1, must_change_password=0 WHERE username='admin.root' AND role='admin'");
        $s->execute([security_config()['admin_email']]);
        $s = $pdo->prepare('INSERT INTO security_meta(key,value) VALUES (?,?)'); $s->execute([$key, '1']);
    }
    $s = $pdo->prepare('SELECT value FROM security_meta WHERE key=?'); $s->execute(['login-three-minute-policy']);
    if (!$s->fetchColumn()) {
        // Do not retain the former five-minute request bucket or twenty-minute blocks after this policy update.
        $pdo->exec("DELETE FROM security_requests WHERE category='login'");
        $s = $pdo->prepare('UPDATE auth_limits SET blocked_until=MIN(blocked_until,?) WHERE blocked_until>?'); $s->execute([time() + 180, time()]);
        $s = $pdo->prepare('INSERT INTO security_meta(key,value) VALUES (?,?)'); $s->execute(['login-three-minute-policy', '1']);
    }
    // Public release rotates the two original demo identities exactly once, including existing databases.
    if (defined('INNPULSE_RELEASE_ID') && defined('INNPULSE_SEED_HASHES')) {
        $key = 'release-' . INNPULSE_RELEASE_ID;
        $s = $pdo->prepare('SELECT value FROM security_meta WHERE key=?'); $s->execute([$key]);
        if (!$s->fetchColumn()) {
            foreach (['admin.root' => 'admin', 'jane.staff' => 'staff'] as $name => $role) {
                $s = $pdo->prepare('UPDATE profiles SET password_hash=?, session_version=session_version+1, must_change_password=0 WHERE username=?');
                $s->execute([INNPULSE_SEED_HASHES[$role], $name]);
            }
            $s = $pdo->prepare('INSERT INTO security_meta(key,value) VALUES (?,?)'); $s->execute([$key, '1']);
        }
    }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function request_security(string $action): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $expected = security_config()['public_origin'];
    if (!$expected) $expected = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '');
    if ($origin && !hash_equals(rtrim($expected, '/'), rtrim($origin, '/'))) fail('Request origin denied.', 403);
    if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') fail('Cross-site request denied.', 403);
    if ($action !== 'csrf' && !hash_equals(csrf_token(), (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) fail('Session verification failed. Refresh the page.', 403);
}
function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? 'unknown'; }
function email_key(string $email): string { return hash('sha256', strtolower(trim($email))); }
function limited(string $category, int $max, int $window): void {
    $pdo = db(); $now = time();
    $pdo->exec('BEGIN IMMEDIATE');
    $s = $pdo->prepare('DELETE FROM security_requests WHERE at < ?'); $s->execute([$now - 86400]);
    $s = $pdo->prepare('SELECT COUNT(*) AS attempts, MIN(at) AS first_at FROM security_requests WHERE ip=? AND category=? AND at>?');
    $s->execute([client_ip(), $category, $now - $window]);
    $bucket = $s->fetch();
    if ((int)$bucket['attempts'] >= $max) {
        $wait = max(1, (int)$bucket['first_at'] + $window - $now + 1);
        $pdo->exec('COMMIT'); header('Retry-After: ' . $wait);
        audit('Request throttle: ' . $category, 'WARN'); fail('Too many requests. Please wait ' . $wait . ' seconds before trying again.', 429, ['retry_after' => $wait]);
    }
    $s = $pdo->prepare('INSERT INTO security_requests(ip,category,at) VALUES(?,?,?)'); $s->execute([client_ip(), $category, $now]);
    $pdo->exec('COMMIT');
}
function login_limits(string $email): array {
    $s = db()->prepare("SELECT * FROM auth_limits WHERE (scope='account' AND key=?) OR (scope='ip' AND key=?)");
    $s->execute([email_key($email), client_ip()]);
    $rows = $s->fetchAll(); $failures = 0; $wait = 0; $locked = false;
    foreach ($rows as $row) {
        $expiredLock = $row['blocked_until'] > 0 && $row['blocked_until'] <= time();
        if (!$expiredLock && $row['started'] > time() - 900) $failures = max($failures, (int)$row['failures']);
        $locked = $locked || $row['blocked_until'] > time();
        $wait = max($wait, (int)$row['blocked_until'] - time(), (int)$row['next_allowed'] - time());
    }
    return ['failures' => $failures, 'retry_after' => max(0, $wait), 'locked' => $locked, 'attempts_remaining' => max(0, 5 - $failures)];
}
function record_failure(string $email): array {
    $pdo = db(); $now = time(); $pdo->exec('BEGIN IMMEDIATE');
    foreach (['account' => email_key($email), 'ip' => client_ip()] as $scope => $key) {
        $s = $pdo->prepare('SELECT * FROM auth_limits WHERE scope=? AND key=?'); $s->execute([$scope, $key]); $row = $s->fetch();
        $expiredLock = $row && $row['blocked_until'] > 0 && $row['blocked_until'] <= $now;
        $count = ($row && !$expiredLock && $row['started'] > $now - 900) ? (int)$row['failures'] + 1 : 1;
        $start = $count === 1 ? $now : $row['started'];
        $block = $count >= 5 ? $now + 180 : 0;
        $delay = min(60, 2 ** min(6, $count - 1));
        $s = $pdo->prepare('INSERT INTO auth_limits(scope,key,failures,started,blocked_until,next_allowed) VALUES(?,?,?,?,?,?) ON CONFLICT(scope,key) DO UPDATE SET failures=excluded.failures,started=excluded.started,blocked_until=excluded.blocked_until,next_allowed=excluded.next_allowed');
        $s->execute([$scope, $key, $count, $start, $block, $now + $delay]);
    }
    $pdo->exec('COMMIT');
    $limits = login_limits($email);
    audit($limits['locked'] ? 'Automatic 3-minute login lockout after 5 failed attempts' : 'Failed login attempt', 'WARN', mb_substr($email, 0, 255));
    return $limits;
}
function captcha_enabled(): bool {
    if (security_test_mode()) return true;
    $cfg = security_config();
    // No widget is deployed until keys are supplied. Partial configuration still fails closed.
    return $cfg['turnstile_site_key'] !== '' || $cfg['turnstile_secret_key'] !== '';
}
function verify_captcha(string $token): void {
    if (security_test_mode() && hash_equals('test-captcha-pass', $token)) return;
    $cfg = security_config();
    if (!$cfg['turnstile_secret_key'] || !$cfg['turnstile_site_key']) fail('Bot verification is not configured. Contact the site owner.', 503);
    if (!$token || strlen($token) > 2048) fail('Complete the bot verification challenge.', 403);
    $r = https_json('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
        'secret' => $cfg['turnstile_secret_key'], 'response' => $token, 'remoteip' => client_ip()], []);
    if (empty($r['success']) || ($r['action'] ?? '') !== 'login' || !$cfg['turnstile_hostname'] || ($r['hostname'] ?? '') !== $cfg['turnstile_hostname']) {
        audit('Bot verification rejected', 'WARN'); fail('Bot verification failed. Please try a new challenge.', 403);
    }
}
function https_json(string $url, array $payload, array $headers): array {
    if (!function_exists('curl_init')) throw new SecurityServiceException('HTTPS delivery is unavailable. Contact the site owner.');
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'Accept: application/json'], $headers),
        CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15, CURLOPT_USERAGENT => 'InnPulse-Security/2.0', CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS]);
    $raw = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
    if ($raw === false || $status < 200 || $status >= 300) throw new SecurityServiceException('Verification provider unavailable. Please try again later.');
    return json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
}
function send_security_code(string $code, string $purpose, string $id, string $recipient): void {
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) throw new SecurityServiceException('This account needs a valid email address. Contact an administrator.');
    if (security_test_mode()) {
        $dir = getenv('INNPULSE_TEST_MAIL_DIR');
        if (!$dir || !is_dir($dir)) throw new RuntimeException('Test mail directory missing.');
        file_put_contents($dir . '/' . $id . '.json', json_encode(['code' => $code, 'purpose' => $purpose, 'recipient' => $recipient])); return;
    }
    require_once __DIR__ . '/mailer.php';
    deliver_security_message($recipient, 'InnPulse ' . ($purpose === 'login' ? 'login' : 'account change') . ' verification',
        "Your InnPulse verification code is: $code\n\nIt expires in 5 minutes and can be used once. Purpose: $purpose.\nIP: " . client_ip() . "\nDo not share this code. If you did not request it, do not approve the action.");
}
function create_challenge(array $user, string $purpose, string $digest = ''): string {
    limited('email-codes', 20, 180);
    $s = db()->prepare('SELECT COUNT(*) AS attempts,MIN(expires) AS first_expiry FROM auth_challenges WHERE user_id=? AND expires>?');
    $s->execute([$user['id'], time() + 120]);
    $bucket = $s->fetch();
    if ($bucket['attempts'] >= 5) {
        $wait = max(1, (int)$bucket['first_expiry'] - time() - 120 + 1);
        header('Retry-After: ' . $wait);
        fail('Too many verification emails. Please wait ' . $wait . ' seconds.', 429, ['retry_after' => $wait]);
    }
    $id = bin2hex(random_bytes(24)); $code = (string)random_int(100000, 999999);
    // Delivery must succeed before a code is usable; no plaintext codes in the database or API.
    // Use only the password-verified database profile, never a client-supplied recipient.
    send_security_code($code, $purpose, $id, $user['email']);
    $s = db()->prepare('UPDATE auth_challenges SET consumed=1 WHERE user_id=? AND session_hash=? AND purpose=?');
    $s->execute([$user['id'], hash('sha256', session_id()), $purpose]);
    $s = db()->prepare('INSERT INTO auth_challenges(id,user_id,session_hash,purpose,digest,code_hash,expires) VALUES(?,?,?,?,?,?,?)');
    $s->execute([$id, $user['id'], hash('sha256', session_id()), $purpose, $digest, password_hash($code, PASSWORD_DEFAULT), time() + 300]);
    audit('Verification code sent: ' . $purpose, 'INFO'); return $id;
}
function consume_challenge(string $id, string $code, string $purpose, string $digest = ''): array {
    limited('verify-code', 30, 180);
    $pdo = db(); $pdo->exec('BEGIN IMMEDIATE');
    $s = $pdo->prepare('SELECT * FROM auth_challenges WHERE id=?'); $s->execute([$id]); $challenge = $s->fetch();
    $valid = $challenge && !$challenge['consumed'] && $challenge['expires'] >= time() && $challenge['attempts'] < 5
        && hash_equals($challenge['session_hash'], hash('sha256', session_id())) && $challenge['purpose'] === $purpose && hash_equals($challenge['digest'], $digest);
    if ($valid) {
        $s = $pdo->prepare('UPDATE auth_challenges SET attempts=attempts+1 WHERE id=?'); $s->execute([$id]);
        $valid = preg_match('/^\d{6}$/D', $code) && password_verify($code, $challenge['code_hash']);
        if ($valid) { $s = $pdo->prepare('UPDATE auth_challenges SET consumed=1 WHERE id=?'); $s->execute([$id]); }
    }
    $pdo->exec('COMMIT');
    if (!$valid) { audit('Verification code rejected or expired', 'WARN'); fail('Invalid or expired code. Start again if you have tried five times.', 401); }
    $s = $pdo->prepare('SELECT * FROM profiles WHERE id=? AND active=1 AND locked=0'); $s->execute([$challenge['user_id']]); $user = $s->fetch();
    if (!$user) fail('Account unavailable.', 401);
    if ($purpose === 'login' && !hash_equals((string)$user['session_version'], $digest)) fail('Credentials changed. Sign in again.', 401);
    return $user;
}
function password_policy(string $password, string $email = '', string $username = ''): void {
    if (strlen($password) < 14 || strlen($password) > 72 || !preg_match('/[A-Z]/', $password)
        || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        fail('Use 14–72 characters with uppercase, lowercase, a number, and a symbol. A long passphrase is recommended.');
    }
    $weak = preg_replace('/[^a-z0-9]/', '', strtolower($password));
    foreach (['password', 'qwerty', '123456', 'letmein', 'innpulse', 'admin', 'welcome', 'changeme'] as $word) if (str_contains($weak, $word)) fail('Choose a password without common words, predictable sequences, or the hotel name.');
    $identity = strtolower(explode('@', $email)[0]);
    if (strlen($identity) >= 4 && str_contains(strtolower($password), $identity)) fail('Do not include your email name in your password.');
    if (security_config()['check_breached_passwords'] && !security_test_mode()) {
        $hash = strtoupper(sha1($password));
        $ch = curl_init('https://api.pwnedpasswords.com/range/' . substr($hash, 0, 5));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_USERAGENT => 'InnPulse-Security/2.0', CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_HTTPHEADER => ['Add-Padding: true'], CURLOPT_FOLLOWLOCATION => false]);
        $body = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
        if (!$body || $status !== 200) fail('Password breach checking is unavailable. Try again later.', 503);
        foreach (explode("\n", $body) as $line) {
            [$suffix, $count] = array_pad(explode(':', trim($line), 2), 2, '0');
            if (hash_equals(substr($hash, 5), $suffix) && (int)$count > 0) fail('This password appears in known breaches. Choose another.');
        }
    }
}
function establish_session(array $user, bool $mfa): void {
    if (!$mfa) fail('Email verification required.', 401);
    session_regenerate_id(true);
    $_SESSION = ['csrf' => bin2hex(random_bytes(32)), 'user_id' => $user['id'], 'session_version' => (int)$user['session_version'],
        'security_revision' => 3, 'issued' => time(), 'last_seen' => time(), 'mfa' => $mfa];
    $s = db()->prepare("UPDATE profiles SET last_login=strftime('%Y-%m-%dT%H:%M:%fZ','now') WHERE id=?"); $s->execute([$user['id']]);
    $s = db()->prepare("DELETE FROM auth_limits WHERE (scope='account' AND key=?) OR (scope='ip' AND key=?)"); $s->execute([email_key($user['email']), client_ip()]);
    audit('Login success' . ($mfa ? ' with email verification' : ''));
}
function finish_password_login(array $user, bool $mfa): never {
    establish_session($user, $mfa); respond(current_profile());
}
function mutation_digest(array $input): string {
    // Authorization is bound to exactly one operation, filter and values object.
    return hash('sha256', json_encode([$input['table'] ?? '', $input['operation'] ?? '', $input['filters'] ?? [], $input['values'] ?? []]));
}
