<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

function respond($data = null, ?string $error = null, int $status = 200, array $details = []): never {
    http_response_code($status);
    echo json_encode(['data' => $data, 'error' => $error ? array_merge(['message' => $error], $details) : null, 'csrf' => csrf_token()]);
    exit;
}
function fail(string $message, int $status = 400, array $details = []): never { respond(null, $message, $status, $details); }
function normalize(array $row): array {
    unset($row['password_hash'], $row['session_version'], $row['must_change_password']);
    foreach (['active', 'locked', 'approved', 'is_featured'] as $key) if (isset($row[$key])) $row[$key] = (bool)$row[$key];
    foreach (['amenities', 'metadata', 'config_value'] as $key) if (isset($row[$key])) $row[$key] = json_decode($row[$key], true);
    return $row;
}
function valid_booking(array $v): void {
    foreach (['guest_name', 'guest_email', 'room_id', 'check_in', 'check_out'] as $key) if (empty($v[$key])) fail('Missing reservation field: ' . $key);
    foreach (['guest_name','guest_phone','special_requests'] as $field) if (isset($v[$field]) && (!is_string($v[$field]) || strlen($v[$field]) > 2000 || preg_match('/[<>\x00-\x08\x0b\x0c\x0e-\x1f]/', $v[$field]))) fail('Reservation fields must be plain text, without HTML markup.');
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
    $raw = file_get_contents('php://input', false, null, 0, 3000001);
    if (strlen($raw) > 3000000) fail('Request is too large.', 413);
    $input = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($input)) fail('Invalid JSON.');
    $pdo = db();
    $action = $input['action'] ?? '';
    request_security($action);
    require __DIR__ . '/app/auth-actions.php';
    if ($action === 'session') respond(current_profile());
    if ($action === 'logout') {
        audit('Logout'); $_SESSION = ['csrf' => bin2hex(random_bytes(32))]; session_regenerate_id(true); respond(true);
    }
    if ($action === 'book') {
        limited('public-booking', 8, 600);
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
    $role = $user['role'] ?? 'guest';
    $policy = [
        'guest' => ['rooms' => ['select'], 'reviews' => ['select', 'insert']],
        'staff' => ['rooms' => ['select', 'update'], 'reservations' => ['select', 'insert', 'update', 'delete'],
            'deposits' => ['select', 'insert', 'update', 'delete'], 'reviews' => ['select', 'insert'], 'audit_logs' => ['insert']],
        'admin' => ['profiles' => ['select', 'insert', 'update', 'delete'], 'rooms' => ['select', 'update'],
            'reservations' => ['select', 'insert', 'update', 'delete'], 'deposits' => ['select', 'insert', 'update', 'delete'],
            'reviews' => ['select', 'insert', 'update', 'delete'], 'audit_logs' => ['select', 'insert'], 'system_config' => ['select', 'upsert']],
    ];
    if (!in_array($op, $policy[$role][$table] ?? [], true)) { audit('Authorization denied: ' . $table . '/' . $op, 'WARN'); fail('Access denied.', $public ? 401 : 403); }
    if ($op !== 'select') limited('database-writes', 120, 60);
    if ($table === 'profiles' && $op !== 'select') {
        $verified = consume_challenge((string)($input['change_id'] ?? ''), (string)($input['change_code'] ?? ''), 'account-change', mutation_digest($input) . ':' . $_SESSION['session_version']);
        if ($verified['id'] !== $user['id'] || $verified['role'] !== 'admin' || (int)$verified['session_version'] !== $_SESSION['session_version']) fail('Account change authorization denied.', 403);
    }
    if ($table === 'reviews' && $op === 'insert') limited('reviews', 8, 600);
    $columns = array_column($pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll(), 'name');
    // Values are bound separately. SQL identifiers/operators cannot be bound, so
    // accept only schema identifiers and the fixed operators below, never SQL fragments.
    $checkColumn = function ($c) use ($columns) { if (!is_string($c) || !in_array($c, $columns, true) || $c === 'password_hash') fail('Unsupported column.'); return '"' . $c . '"'; };
    $filters = $input['filters'] ?? [];
    if (!is_array($filters) || !array_is_list($filters) || count($filters) > 50) fail('Invalid filters.');
    $where = []; $params = [];
    foreach ($filters as $filter) {
        if (!is_array($filter) || !array_is_list($filter) || count($filter) !== 3) fail('Invalid filter.');
        [$column, $operator, $value] = $filter;
        $col = $checkColumn($column);
        if ($operator === 'IN' && is_array($value) && array_is_list($value) && count($value) > 0 && count($value) <= 100) {
            foreach ($value as &$item) {
                if (!is_scalar($item) && $item !== null) fail('Invalid filter value.');
                if (is_bool($item)) $item = (int)$item;
            } unset($item);
            $where[] = $col . ' IN (' . implode(',', array_fill(0, count($value), '?')) . ')'; array_push($params, ...$value);
        } elseif (in_array($operator, ['=', '!='], true) && !is_array($value)) {
            $where[] = $col . ' ' . $operator . ' ?'; $params[] = is_bool($value) ? (int)$value : $value;
        } else fail('Unsupported filter.');
    }
    if ($role !== 'admin' && $table === 'reviews' && $op === 'select') $where[] = 'approved = 1';
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    if ($op === 'select') {
        $sql = 'SELECT * FROM ' . $table . $whereSql;
        if (isset($input['order'])) {
            $order = $input['order'];
            if (!is_array($order) || !array_is_list($order) || count($order) !== 2 || !is_bool($order[1])) fail('Invalid sort order.');
            $sql .= ' ORDER BY ' . $checkColumn($order[0]) . ($order[1] ? ' ASC' : ' DESC');
        }
        if (isset($input['limit']) && !is_int($input['limit'])) fail('Limit must be an integer.');
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
        $s = $pdo->prepare('DELETE FROM ' . $table . $whereSql); $s->execute($params); audit('Deleted from ' . $table, 'WARN'); respond(true);
    }
    $v = $input['values'] ?? [];
    if (!is_array($v) || !$v || array_is_list($v)) fail('An object is required.');
    $allowedFields = [
        'profiles' => ['username', 'email', 'role', 'active', 'locked', 'password'],
        'rooms' => ['status'],
        'reservations' => ['guest_id', 'guest_name', 'guest_email', 'guest_phone', 'room_id', 'check_in', 'check_out', 'guest_count', 'special_requests', 'status', 'booking_ref', 'updated_at'],
        'deposits' => ['reservation_id', 'amount', 'status', 'receipt_url', 'paid_at'],
        'reviews' => ['guest_name', 'rating', 'review_text', 'approved'],
        'audit_logs' => ['user_id', 'username', 'ip_address', 'event', 'severity', 'session_reference', 'metadata'],
        'system_config' => ['config_key', 'config_value', 'updated_at'],
    ];
    if (array_diff(array_keys($v), $allowedFields[$table])) fail('Field is not writable.', 403);
    foreach ($v as $field => $value) {
        if (is_string($value) && strlen($value) > ($field === 'receipt_url' ? 2800000 : 5000)) fail('Field is too long.');
        if (is_string($value) && in_array($field, ['guest_name', 'guest_phone', 'special_requests', 'username', 'review_text'], true)
            && preg_match('/[<>\x00-\x08\x0b\x0c\x0e-\x1f]/', $value)) fail('Use plain text without HTML markup.');
    }
    if ($table === 'profiles' && isset($v['role']) && !in_array($v['role'], ['admin','staff'], true)) fail('Invalid role.');
    if ($table === 'deposits' && !empty($v['receipt_url']) && !preg_match('~^data:image/(png|jpeg|webp);base64,[A-Za-z0-9+/=]+$~', $v['receipt_url'])) fail('Invalid receipt.');
    if ($table === 'reviews' && (empty($v['guest_name']) || empty($v['review_text']) || !isset($v['rating']) || !is_int($v['rating']) || $v['rating'] < 1 || $v['rating'] > 5)) fail('Invalid review.');
    if ($table === 'reservations') valid_booking($v);
    if ($table === 'profiles') {
        if ($op === 'insert') $v['must_change_password'] = 0;
        if (isset($v['email']) && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) fail('Enter a valid email.');
        if (isset($v['email'])) $v['email'] = strtolower(trim($v['email']));
        if ($op === 'insert' && empty($v['password'])) fail('Password is required.');
        if (isset($v['password'])) {
            password_policy((string)$v['password'], $v['email'] ?? '', $v['username'] ?? '');
            $v['password_hash'] = password_hash($v['password'], PASSWORD_DEFAULT); unset($v['password']);
        }
    }
    if ($role !== 'admin' && $table === 'reviews') $v = ['guest_name' => $v['guest_name'] ?? '', 'rating' => $v['rating'] ?? 0, 'review_text' => $v['review_text'] ?? '', 'approved' => false];
    if ($table === 'audit_logs') {
        $v['user_id'] = $user['id']; $v['username'] = $user['username']; $v['ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
    foreach ($v as $key => &$value) {
        if (in_array($key, ['password_hash', 'must_change_password'], true) && $table === 'profiles') continue;
        $checkColumn($key);
        if (in_array($key, ['amenities', 'metadata', 'config_value'], true)) $value = json_encode($value);
        elseif (is_bool($value)) $value = (int)$value;
        elseif (is_array($value)) fail('Invalid field value.');
    } unset($value);
    if ($op === 'update') {
        unset($v['id']);
        $set = implode(', ', array_map(fn($key) => '"' . $key . '" = ?', array_keys($v)));
        $s = $pdo->prepare('UPDATE ' . $table . ' SET ' . $set . $whereSql); $s->execute([...array_values($v), ...$params]);
        if ($table === 'profiles') { $s = $pdo->prepare('UPDATE profiles SET session_version=session_version+1' . $whereSql); $s->execute($params); }
        audit('Updated ' . $table, 'INFO'); respond(true);
    }
    if ($op === 'upsert' && $table !== 'system_config') fail('Upsert only supports configuration.');
    $v['id'] = bin2hex(random_bytes(16));
    $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', array_map(fn($key) => '"' . $key . '"', array_keys($v))) . ') VALUES (' . implode(',', array_fill(0, count($v), '?')) . ')';
    if ($op === 'upsert') $sql .= ' ON CONFLICT(config_key) DO UPDATE SET config_value = excluded.config_value, updated_at = excluded.updated_at';
    $s = $pdo->prepare($sql); $s->execute(array_values($v));
    $s = $pdo->prepare('SELECT * FROM ' . $table . ' WHERE id = ?'); $s->execute([$v['id']]); $row = $s->fetch();
    if ($table !== 'audit_logs') audit('Created or saved ' . $table);
    respond($row ? normalize($row) : true);
} catch (JsonException $e) { fail('Invalid JSON.'); }
catch (SecurityServiceException $e) { audit('Verification provider unavailable', 'WARN'); fail($e->getMessage(), 503); }
catch (RuntimeException $e) { fail($e instanceof PDOException ? 'Database operation failed. Check required fields and duplicate values.' : $e->getMessage()); }
catch (Throwable $e) { error_log((string)$e); fail('Server error. Check PHP SQLite support and storage permissions.', 500); }
