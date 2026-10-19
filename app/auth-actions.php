<?php
// Included after JSON, CSRF and database initialization in api.php.
if ($action === 'csrf') respond(['csrf' => csrf_token()]);
if ($action === 'auth_options') {
    $limits = login_limits(strtolower(trim((string)($input['email'] ?? ''))));
    respond(['captcha_required' => captcha_enabled() && $limits['failures'] > 0,
        'site_key' => security_config()['turnstile_site_key'], 'retry_after' => $limits['retry_after'],
        'attempts_remaining' => $limits['attempts_remaining'], 'locked' => $limits['locked']]);
}
if ($action === 'login') {
    unset($_SESSION['user_id'], $_SESSION['password_pending']);
    limited('login', 40, 180);
    if (!is_string($input['email'] ?? null) || !is_string($input['password'] ?? null)) fail('Enter a valid email and password.');
    $email = strtolower(trim((string)($input['email'] ?? ''))); $password = (string)($input['password'] ?? '');
    if (!$email || !$password || strlen($email) > 255 || strlen($password) > 128) fail('Enter a valid email and password.');
    $limits = login_limits($email);
    if ($limits['retry_after'] > 0) {
        header('Retry-After: ' . $limits['retry_after']); audit('Blocked login during delay or lockout', 'WARN');
        fail(($limits['locked'] ? 'Five failed attempts. Login is locked for three minutes.' : 'Please wait before the next login attempt.') . ' Try again in ' . $limits['retry_after'] . ' seconds.', 429, $limits);
    }
    if (captcha_enabled() && $limits['failures'] > 0) verify_captcha((string)($input['captcha_token'] ?? ''));
    $s = $pdo->prepare('SELECT * FROM profiles WHERE email=?'); $s->execute([$email]); $user = $s->fetch();
    $hash = $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
    $passwordOK = password_verify($password, $hash);
    if (!$user || !$passwordOK || !$user['active'] || $user['locked']) {
        $limits = record_failure($email);
        header('Retry-After: ' . $limits['retry_after']);
        fail($limits['locked'] ? 'Five failed attempts. Please wait three minutes before signing in again.' : 'Invalid credentials or account unavailable. ' . $limits['attempts_remaining'] . ' attempt(s) remaining before a three-minute lockout.', $limits['locked'] ? 429 : 401, $limits);
    }
    session_regenerate_id(true);
    $id = create_challenge($user, 'login', (string)$user['session_version']);
    respond(['mfa_required' => true, 'challenge_id' => $id, 'message' => 'Enter the code sent to the email address registered to your account.']);
}
if ($action === 'verify_login') {
    $id = (string)($input['challenge_id'] ?? '');
    $s = $pdo->prepare('SELECT p.session_version FROM profiles p JOIN auth_challenges c ON p.id=c.user_id WHERE c.id=?'); $s->execute([$id]); $version = $s->fetchColumn();
    $user = consume_challenge($id, (string)($input['code'] ?? ''), 'login', (string)$version);
    finish_password_login($user, true);
}
if ($action === 'change_initial_password') fail('No password change is required. Refresh the page and sign in again.', 410);
if ($action === 'authorize_account_change') {
    limited('account-authorization', 5, 600);
    $actor = current_profile();
    if (!$actor || $actor['role'] !== 'admin' || empty($_SESSION['mfa'])) fail('Admin verification required.', 403);
    $s = $pdo->prepare('SELECT * FROM profiles WHERE id=?'); $s->execute([$actor['id']]); $user = $s->fetch();
    if (!password_verify((string)($input['password'] ?? ''), $user['password_hash'])) {
        audit('Account change password rejected', 'WARN'); fail('Reauthentication failed.', 401);
    }
    $mutation = $input['mutation'] ?? [];
    if (($mutation['table'] ?? '') !== 'profiles' || !in_array($mutation['operation'] ?? '', ['insert', 'update', 'delete'], true)) fail('Unsupported account change.');
    $digest = mutation_digest($mutation);
    $id = create_challenge($user, 'account-change', $digest . ':' . $user['session_version']);
    respond(['challenge_id' => $id]);
}
