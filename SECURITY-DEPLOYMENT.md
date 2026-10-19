# InnPulse defense update

This update is implemented and tested locally. It is not active at innpulse.free.nf until the updated files and private provider configuration are uploaded. It reduces the demonstrated risks; it does not establish that the whole application or hosting account is immune to attack.

## What changed

| Threat | Application defense | Observable evidence |
|---|---|---|
| Password guessing | 40 login requests per IP per three minutes; account and IP failure counters; progressive 1/2/4/8 second delays; five failed passwords trigger a three-minute lockout | HTTP 429, Retry-After header, audit events |
| Automated retries | Turnstile required after the first failed password, using persistent IP/account counters and session state | Missing, invalid, expired or replayed tokens are rejected server-side |
| Stolen admin password | Every account receives a code at its own registered email before an authenticated session is created; the owner account uses qemespena@tip.edu.ph | Password-only requests cannot access protected pages or APIs |
| Backdoor account creation using a stolen session | Each account mutation requires current admin password plus a separate email code bound to the exact operation, filters and values | A code for creating staff cannot authorize creating an admin; used codes cannot be replayed |
| Privilege escalation | Explicit per-role/per-table/per-operation policy and writable-field allowlists | Staff cannot access profiles, settings or audit-log reads; raw password_hash and session_version writes are rejected |
| Stored XSS | Text/attribute encoding at render time, safe image URL schemes/hosts, JSON data attributes instead of interpolated JavaScript, and plain-text validation | Existing malicious records display as text; new HTML in guest fields is rejected |
| Script injection | External scripts and delegated handlers; CSP rejects inline scripts and event attributes; nosniff, frame denial and base-uri denial | Browser Content-Security-Policy includes script-src-attr 'none' |
| Session abuse | New session cookie name, strict/HttpOnly/SameSite cookies, secure cookies on HTTPS, session regeneration, 15-minute idle and eight-hour absolute limits, version-based revocation | Old sessions are rejected; account/password/role changes invalidate target sessions |
| Weak/reused credentials | 14–72 characters, uppercase/lowercase/number/symbol, local predictable-password checks, and HIBP range-based breach checks | Weak/common/breached passwords rejected; new or reset account passwords are checked; login has no forced password replacement |
| Missing security evidence | Server-generated audit events; audit deletion denied through the app; 15-second dashboard refresh and JSON export of the latest 1,000 events | Record the audit dashboard and export evidence |

The code challenge expires after five minutes, allows five guesses, and is bound to one PHP session. The database stores a password hash of the code, not the plaintext code. No authenticated admin session exists while verification is pending. The security mailbox is server configuration, not an editable browser field or a system_config value.

Email codes are an email verification second step, not a TOTP authenticator app. Protect the receiving mailbox with its own MFA. Every login, including staff logins, verifies the mailbox saved on that account. Account-management approval codes go to the acting administrator's registered email. The admin_email configuration sets the original owner's address, not a global recipient for other users. Codes are never redirected using a client-supplied recipient parameter.

## 1. Configure Gmail SMTP through PHPMailer

The sender account is hari.zxc33@gmail.com. The original owner receives codes at qemespena@tip.edu.ph. Every new staff/admin account receives codes at its own saved email. The sender and recipient do not need to be the same address. This is an email-code verification flow, not a TOTP authenticator app or the Gmail REST API.

1. Revoke the Gmail app password that was shared in chat, then create a replacement under Google's App Passwords page. Keep 2-Step Verification enabled on the sender Google account.
2. Open app/config.local.php for the localhost website. For the hosted website, edit the separate deployment/security-update/config.local.php, keeping its HTTPS origin and production CAPTCHA hostname.
3. Set mail_transport to smtp, smtp_username to hari.zxc33@gmail.com, smtp_password to the replacement app password, and smtp_port to 587. Spaces in Google's displayed app password are automatically removed. The app password is separate from your InnPulse login password.
4. Gmail uses smtp.gmail.com with STARTTLS on port 587. Port 465 with implicit TLS is also supported by setting smtp_port to 465. TLS certificate verification stays enabled; do not disable it to fix connection errors.
5. Test delivery from the project terminal:

```powershell
php tools/test_mail.php qemespena@tip.edu.ph
```

The diagnostic sends one clearly labeled test message, not a usable login code. Server acceptance is not proof of inbox delivery: check the recipient inbox and spam folder. The diagnostic does not echo credentials or code values. Do not upload tools/ to public hosting.

The configured sender and replacement password belong only in the ignored private PHP configuration. Do not paste the replacement into chat, commit it, or put it in JavaScript. The public ZIP excludes private configuration and the original PHPMailer examples folder. It includes the pinned PHPMailer 7.1.1 SMTP runtime and upstream license under the protected app/vendor/phpmailer directory. The supplied PHPMailer 6.7.1 folder is preserved and blocked from HTTP access; it is not used by the application.

Outgoing authenticated SMTP must be allowed by your network/hosting provider. A working localhost setup does not prove InfinityFree permits the same connection. If the host blocks Gmail SMTP, the existing Brevo HTTPS transport remains available by selecting mail_transport=brevo and configuring brevo_api_key plus a verified mail_sender_email. No automatic fallback bypasses verification.

Missing settings, invalid credentials, TLS errors or delivery failure never grant an authenticated session. Fix the sender settings rather than weakening login checks. CAPTCHA is a separate service and still requires its own configuration.

References: [Google app passwords](https://support.google.com/mail/answer/185833), [Google SMTP configuration](https://support.google.com/a/answer/176600), [PHPMailer releases](https://github.com/PHPMailer/PHPMailer/releases), [optional Brevo HTTPS API](https://developers.brevo.com/docs/send-a-transactional-email).

## 2. Configure CAPTCHA

1. Create a Cloudflare account and open Turnstile.
2. Create a Managed widget for innpulse.free.nf. Turnstile can be used without moving the site's DNS to Cloudflare.
3. Put its site key in turnstile_site_key and its secret key in turnstile_secret_key in the private PHP configuration.
4. Set turnstile_hostname to innpulse.free.nf.

When Turnstile keys are configured, the next eligible attempt after a failed password must include a valid token. The server checks success, hostname and action=login; the token cannot be reused. With both CAPTCHA keys absent, CAPTCHA is not enabled; mandatory per-account email verification and the five-failure/three-minute lockout still apply. A partially configured widget or invalid token is rejected. The UI displays the remaining attempts and a countdown; after three minutes the failed-attempt count resets. Restarting PHP does not clear counters because they persist in SQLite. Ordinary staff creation is admin-only; there is no public registration endpoint.

Reference: [Turnstile setup](https://developers.cloudflare.com/turnstile/get-started/) and [mandatory server validation](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/).

## 3. Upload the update

1. Confirm that your site's HTTPS certificate is working before uploading; this release redirects HTTP to HTTPS.
2. Back up the hosted files and storage/innpulse.sqlite using the hosting account. Keep the backup private. If real compromise is suspected, review the hosting files and all accounts as well as updating this code.
3. Extract deployment/security-update/innpulse-security-update.zip locally.
4. Upload its contents directly into the domain's htdocs, replacing the application files. Include all updated JavaScript files and .htaccess files.
5. Upload the configured private file to htdocs/app/config.local.php. Never upload the private credentials text, release manifest, tests, tools, .git or node_modules.
6. Preserve storage/innpulse.sqlite. The migration adds security tables/columns to the existing database. It revokes old sessions, changes the original admin.root login email to qemespena@tip.edu.ph, and rotates the original admin.root and jane.staff passwords once for this release. Existing reservations, reviews and other accounts are retained.
7. Use the new temporary passwords in deployment/security-update/HOSTED-LOGIN-PRIVATE.txt. Both admin and staff must verify a code at their own registered email. Successful verification signs them in directly, without a mandatory password-change step. Change the demo staff email to a real inbox using the owner account before using it. Reuploading the same release does not repeatedly reset those passwords.
8. Verify admin email delivery, direct sign-in after verification, staff permissions, room booking and escaped historical data. Confirm that /app/config.local.php and /storage/innpulse.sqlite return access denied rather than file contents.
9. In User Management, review every account. Unknown/backdoor accounts are not automatically deleted: investigate and explicitly remove or lock them with the owner-approved change flow.

If an account password is changed through account management or a local owner-requested reset, the credential file no longer reflects that account password. The SMTP credentials and API keys stay in the private PHP file; never paste keys or passwords into chat or screenshots.

The package intentionally excludes an existing database, provider keys and test harness. Public builds disable the localhost mock mail transport even under PHP's development server. No intentional SQL injection or unrestricted lab login remains in the active code.

## 4. Record the defense demonstration

SQL injection protection uses PDO prepared statements for login, bookings, filters and writes. User values are bound as data; table/column identifiers come from allowlists, operators are fixed, sort direction must be a boolean and row limits must be integers. Nested or malformed filters are rejected. Database errors returned to the browser do not include SQL statements or driver diagnostics. Apostrophes and SQL-looking text in legitimate text fields remain data; blocking SQL keywords is not the defense.

The local regression suite tests login bypass payloads, injected table/column/operator/sort/limit inputs, equality and IN filter payloads, write-filter scope and SQL-looking review text read back from storage. It verifies that the schema and room records remain unchanged. These checks use only a disposable database, not real accounts or the hosted site.

Use a localhost copy or a teacher-provided environment that permits testing. Do not run brute-force or penetration tests against InfinityFree without the provider's explicit authorization.

Record these sequences using synthetic accounts:

1. Show a failed password, the CAPTCHA on the next attempt, progressive HTTP 429 responses and the lockout event. The automated tests exercise five failures by advancing only their disposable test state; the deployed application does not offer an endpoint to clear limits.
2. Enter the correct admin password. Show that the dashboard/API is still inaccessible until the email code is verified. Show an invalid code and then a successful code without revealing the real password/API key in the recording.
3. As staff, request an admin page or profiles API and record HTTP 403. As admin, attempt an account change without its separate authorization and show denial, then complete the owner-approved flow.
4. Show a reservation containing previously stored HTML rendered harmlessly as text, and a new HTML submission rejected by validation. Use DevTools to inspect the CSP header and confirm that inline script/event injection does not execute.
5. Open Audit Logs, observe the events and export the JSON evidence. Do not expose real session cookies or OTPs in a shared recording.
6. On localhost, submit a SQL-looking value to a room ID filter and show that it returns no matching rooms. Show that an injected column/operator is rejected, and that failed login payloads do not create a session. Run the regression suite to capture database integrity checks.

For repeatable local regression evidence, run:

```powershell
python tests/smoke.py
```

The suite starts its own temporary localhost server/database. A test-only mail outbox and CAPTCHA token are usable only with an explicit test flag on PHP's development server on loopback, and are disabled in public packages. They are not public bypass endpoints. Live Gmail SMTP/Turnstile/HIBP connectivity and real inbox delivery need separate checks after provider configuration.

## Hosting controls and remaining limits

The application implements its own throttling; it is not an edge WAF. InfinityFree manages SSH/root access and server accounts. Changing an admin URL is not an authorization defense, so the existing URLs remain server-protected. An edge WAF, private management network, external threat-intelligence feed, host IDS or SIEM collector requires a compatible provider, domain and account configuration; none is silently installed by this update. JSON audit export is evidence/collector input, not a full SIEM deployment. Audit data is append-only through the app, but a person with filesystem/database access could still change it.

A stolen, fully verified session may still exercise its authorized read access until revocation or expiration. Sensitive account mutations require a second approval even then. Changing code cannot remove backdoors outside the application or repair a compromised hosting account. Use the provider's account controls to change exposed hosting/FTP passwords and enable provider MFA if available.

The breach-password check sends only the first five SHA-1 hash characters to the [Pwned Passwords range API](https://haveibeenpwned.com/API/v3#PwnedPasswords); it never sends the plaintext password. If that service is unreachable, new password changes are refused by default. Local predictable-password checks still apply. The SHA-1 prefix is only for querying the breach database; stored account passwords continue to use PHP password_hash.

Design references: [OWASP authentication](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html), [OWASP XSS prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html).
