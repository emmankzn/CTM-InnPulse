<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

function respond($data = null, ?string $error = null, int $status = 200): never {
    http_response_code($status);
    echo json_encode(['data' => $data, 'error' => $error ? ['message' => $error] : null]);
    exit;
}
function fail(string $message, int $status = 400): never { respond(null, $message, $status); }
function normalize(array $row): array {
    unset($row['password_hash']);
    foreach (['active', 'locked', 'approved', 'is_featured'] as $key) if (isset($row[$key])) $row[$key] = (bool)$row[$key];
    foreach (['amenities', 'metadata', 'config_value'] as $key) if (isset($row[$key])) $row[$key] = json_decode($row[$key], true);
    return $row;
}
function valid_booking(array $v): void {
    foreach (['guest_name', 'guest_email', 'room_id', 'check_in', 'check_out'] as $key) if (empty($v[$key])) fail('Missing reservation field: ' . $key);
    if (!filter_var($v['guest_email'], FILTER_VALIDATE_EMAIL)) fail('Enter a valid guest email.');
    foreach (['check_in', 'check_out'] as $key) {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $v[$key]);
        if (!$d || $d->format('Y-m-d') !== $v[$key]) fail('Invalid reservation date.');
    }
    if ($v['check_out'] <= $v['check_in'] || (int)($v['guest_count'] ?? 1) < 1) fail('Check-out must follow check-in and guest count must be positive.');
    $s = db()->prepare('SELECT capacity FROM rooms WHERE id = ?'); $s->execute([$v['room_id']]);
    $capacity = $s->fetchColumn();
    if (!$capacity || (int)($v['guest_count'] ?? 1) > $capacity) fail('Guest count exceeds room capacity.');
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required.', 405);
    // A custom request header and JSON prevent cross-origin form submissions.
    if (($_SERVER['HTTP_X_INNPULSE_REQUEST'] ?? '') !== '1' || !str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) fail('Invalid request.', 403);
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 3000000) fail('Request is too large.', 413);
    $input = json_decode(file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($input)) fail('Invalid JSON.');
    $pdo = db();
    $action = $input['action'] ?? '';
    if ($action === 'session') respond(current_profile());
    if ($action === 'logout') {
        audit('Logout'); $_SESSION = []; session_destroy(); respond(true);
    }
    if ($action === 'login') {
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $password = (string)($input['password'] ?? '');
        if (!$email || !$password || strlen($email) > 255 || strlen($password) > 1024) fail('Enter an email and password.');
        if (!lab_mode()) {
            $s = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND occurred_at >= datetime('now', '-5 minutes')");
            $s->execute([$_SERVER['REMOTE_ADDR']]);
            if ($s->fetchColumn() >= 10) fail('Too many attempts. Try again in five minutes.', 429);
        }
        $s = $pdo->prepare('INSERT INTO login_attempts (ip_address) VALUES (?)'); $s->execute([$_SERVER['REMOTE_ADDR']]);
        if (lab_mode()) {
            // INTENTIONAL CLASSROOM VULNERABILITY: enabled only by an environment flag on loopback.
            // SQL concatenation allows the email expression to bypass the password condition.
            $pdo->sqliteCreateFunction('verify_password', fn($plain, $hash) => password_verify($plain, $hash) ? 1 : 0, 2);
            try { $user = $pdo->query("SELECT * FROM profiles WHERE email = '$email' AND verify_password('$password', password_hash) = 1")->fetch(); }
            catch (PDOException $e) { audit('Failed login: invalid lab SQL', 'WARN', $email); fail('Invalid email or password.', 401); }
        } else {
            $s = $pdo->prepare('SELECT * FROM profiles WHERE email = ?'); $s->execute([$email]); $user = $s->fetch();
            if ($user && !password_verify($password, $user['password_hash'])) $user = false;
        }
        if (!$user || !$user['active'] || $user['locked']) { audit('Failed login attempt', 'WARN', $email); fail('Invalid email or password, or account unavailable.', 401); }
        session_regenerate_id(true); $_SESSION['user_id'] = $user['id'];
        $s = $pdo->prepare("UPDATE profiles SET last_login = strftime('%Y-%m-%dT%H:%M:%fZ','now') WHERE id = ?"); $s->execute([$user['id']]);
        audit('Login success' . (lab_mode() ? ' (lab mode)' : ''));
        respond(current_profile());
    }
    if ($action === 'book') {
        $v = $input['reservation'] ?? []; valid_booking($v);
        // Serialize availability check and all writes in a single transaction.
        $pdo->exec('BEGIN IMMEDIATE');
        try {
            $s = $pdo->prepare('SELECT * FROM rooms WHERE id = ?'); $s->execute([$v['room_id']]); $room = $s->fetch();
            if ($room['status'] !== 'available') throw new RuntimeException('This room is no longer available.');
            $id = bin2hex(random_bytes(16)); $ref = 'INP-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $s = $pdo->prepare('INSERT INTO reservations (id, guest_id, guest_name, guest_email, guest_phone, room_id, check_in, check_out, guest_count, special_requests, booking_ref) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $s->execute([$id, 'G-' . strtoupper(bin2hex(random_bytes(3))), $v['guest_name'], $v['guest_email'], $v['guest_phone'] ?? null, $v['room_id'], $v['check_in'], $v['check_out'], $v['guest_count'] ?? 1, $v['special_requests'] ?? null, $ref]);
            $receipt = $input['receipt'] ?? null;
            if ($receipt !== null && (!is_string($receipt) || strlen($receipt) > 2800000 || !preg_match('~^data:image/(png|jpeg|webp);base64,[A-Za-z0-9+/=]+$~', $receipt))) throw new RuntimeException('Receipt must be a PNG, JPEG or WebP under 2 MB.');
            $s = $pdo->prepare('INSERT INTO deposits (id, reservation_id, amount, receipt_url) VALUES (?, ?, ?, ?)');
            $s->execute([bin2hex(random_bytes(16)), $id, $room['deposit_amount'], $receipt]);
            $s = $pdo->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ?"); $s->execute([$room['id']]);
            audit('Reservation created: ' . $ref);
            $pdo->exec('COMMIT'); respond(['id' => $id, 'booking_ref' => $ref]);
        } catch (Throwable $e) { $pdo->exec('ROLLBACK'); throw $e; }
    }
    $user = current_profile();
    if ($action !== 'query') fail('Unknown action.');
    $table = $input['table'] ?? '';
    $op = $input['operation'] ?? 'select';
    $tables = ['profiles', 'rooms', 'reservations', 'deposits', 'reviews', 'audit_logs', 'system_config'];
    if (!in_array($table, $tables, true) || !in_array($op, ['select', 'insert', 'update', 'delete', 'upsert'], true)) fail('Unsupported query.');
    $public = !$user;
    if ($public && !(($table === 'rooms' || $table === 'reviews') && $op === 'select') && !($table === 'reviews' && $op === 'insert')) fail('Sign in required.', 401);
    if ($user && $user['role'] !== 'admin' && in_array($table, ['profiles', 'system_config'], true)) fail('Admin access required.', 403);
    if ($table === 'audit_logs' && (($op === 'select' || $op === 'delete') && ($user['role'] ?? '') !== 'admin')) fail('Admin access required.', 403);
    $columns = array_column($pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll(), 'name');
    $checkColumn = function ($c) use ($columns) { if (!in_array($c, $columns, true) || $c === 'password_hash') fail('Unsupported column.'); return '"' . $c . '"'; };
    $where = []; $params = [];
    foreach ($input['filters'] ?? [] as [$column, $operator, $value]) {
        $col = $checkColumn($column);
        if ($operator === 'IN' && is_array($value) && count($value) > 0 && count($value) <= 100) {
            $where[] = $col . ' IN (' . implode(',', array_fill(0, count($value), '?')) . ')'; array_push($params, ...$value);
        } elseif (in_array($operator, ['=', '!='], true) && !is_array($value)) {
            $where[] = $col . ' ' . $operator . ' ?'; $params[] = is_bool($value) ? (int)$value : $value;
        } else fail('Unsupported filter.');
    }
    if ($public && $table === 'reviews' && $op === 'select') $where[] = 'approved = 1';
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    if ($op === 'select') {
        $sql = 'SELECT * FROM ' . $table . $whereSql;
        if (isset($input['order'])) $sql .= ' ORDER BY ' . $checkColumn($input['order'][0]) . ($input['order'][1] ? ' ASC' : ' DESC');
        $sql .= ' LIMIT ' . max(1, min(1000, (int)($input['limit'] ?? 1000)));
        $s = $pdo->prepare($sql); $s->execute($params); $rows = array_map('normalize', $s->fetchAll());
        if ($table === 'reservations' && str_contains($input['columns'] ?? '', 'rooms(')) {
            foreach ($rows as &$row) {
                $s = $pdo->prepare('SELECT room_number, room_name FROM rooms WHERE id = ?'); $s->execute([$row['room_id']]); $row['rooms'] = $s->fetch();
                $s = $pdo->prepare('SELECT status, receipt_url, amount FROM deposits WHERE reservation_id = ?'); $s->execute([$row['id']]); $row['deposits'] = $s->fetchAll();
            } unset($row);
        }
        respond(!empty($input['single']) ? ($rows[0] ?? null) : $rows);
    }
    if (in_array($op, ['update', 'delete'], true) && !$where) fail('A filter is required.');
    if ($table === 'profiles' && in_array($op, ['update', 'delete'], true)) {
        $s = $pdo->prepare('SELECT id FROM profiles' . $whereSql); $s->execute($params);
        if (in_array($user['id'], $s->fetchAll(PDO::FETCH_COLUMN), true)) fail('Use another admin to change or remove your own account.');
    }
    if ($op === 'delete') {
        $s = $pdo->prepare('DELETE FROM ' . $table . $whereSql); $s->execute($params); respond(true);
    }
    $v = $input['values'] ?? [];
    if (!is_array($v) || !$v || array_is_list($v)) fail('An object is required.');
    if ($table === 'reservations') valid_booking($v);
    if ($table === 'profiles') {
        if (isset($v['email']) && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) fail('Enter a valid email.');
        if (isset($v['email'])) $v['email'] = strtolower(trim($v['email']));
        if ($op === 'insert' && empty($v['password'])) fail('Password is required.');
        if (isset($v['password'])) {
            if (strlen($v['password']) < 6) fail('Password must be at least 6 characters.');
            $v['password_hash'] = password_hash($v['password'], PASSWORD_DEFAULT); unset($v['password']);
        }
    }
    if ($public && $table === 'reviews') $v = ['guest_name' => $v['guest_name'] ?? '', 'rating' => $v['rating'] ?? 0, 'review_text' => $v['review_text'] ?? '', 'approved' => false];
    if ($table === 'audit_logs') {
        $v['user_id'] = $user['id']; $v['username'] = $user['username']; $v['ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
    foreach ($v as $key => &$value) {
        if ($key === 'password_hash' && $table === 'profiles') continue;
        $checkColumn($key);
        if (in_array($key, ['amenities', 'metadata', 'config_value'], true)) $value = json_encode($value);
        elseif (is_bool($value)) $value = (int)$value;
        elseif (is_array($value)) fail('Invalid field value.');
    } unset($value);
    if ($op === 'update') {
        unset($v['id']);
        $set = implode(', ', array_map(fn($key) => '"' . $key . '" = ?', array_keys($v)));
        $s = $pdo->prepare('UPDATE ' . $table . ' SET ' . $set . $whereSql); $s->execute([...array_values($v), ...$params]); respond(true);
    }
    if ($op === 'upsert' && $table !== 'system_config') fail('Upsert only supports configuration.');
    $v['id'] = bin2hex(random_bytes(16));
    $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', array_map(fn($key) => '"' . $key . '"', array_keys($v))) . ') VALUES (' . implode(',', array_fill(0, count($v), '?')) . ')';
    if ($op === 'upsert') $sql .= ' ON CONFLICT(config_key) DO UPDATE SET config_value = excluded.config_value, updated_at = excluded.updated_at';
    $s = $pdo->prepare($sql); $s->execute(array_values($v));
    $s = $pdo->prepare('SELECT * FROM ' . $table . ' WHERE id = ?'); $s->execute([$v['id']]); $row = $s->fetch();
    respond($row ? normalize($row) : true);
} catch (JsonException $e) { fail('Invalid JSON.'); }
catch (RuntimeException $e) { fail($e instanceof PDOException ? 'Database operation failed. Check required fields and duplicate values.' : $e->getMessage()); }
catch (Throwable $e) { error_log((string)$e); fail('Server error. Check PHP SQLite support and storage permissions.', 500); }
