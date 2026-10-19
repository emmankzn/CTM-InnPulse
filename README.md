# InnPulse — PHP + SQLite

InnPulse uses PHP 8.1+ with PDO SQLite, server sessions, Tailwind CSS and vanilla JavaScript. Supabase is no longer used.

## Run locally

```powershell
php -S 127.0.0.1:8000 router.php
```

Open http://127.0.0.1:8000. The database initializes at storage/innpulse.sqlite. On this computer PHP is available through XAMPP. Static file servers do not execute PHP.

## Security update

The active code now enforces per-account email verification for admin and staff, rate limits, a three-minute lockout after five failed attempts, CAPTCHA after failed login when its keys are configured, per-operation account-change approval, CSRF checks, role/field permissions, strong-password checks, session revocation and XSS-safe rendering. The original admin login email becomes qemespena@tip.edu.ph. Old sessions are rejected; verifying the email code signs the user in directly without a mandatory password-change screen.

**Configure Gmail SMTP and Turnstile before deploying or attempting staff or admin sign-in.** Copy app/config.example.php to app/config.local.php and follow [SECURITY-DEPLOYMENT.md](SECURITY-DEPLOYMENT.md). Missing verification-provider configuration never grants an unverified admin session.

The public update is built with:

```powershell
python tools/package_hosting.py
```

It creates deployment/security-update/innpulse-security-update.zip and a separate private temporary-credentials file. Only the ZIP contents and the configured app/config.local.php belong on the host. The existing SQLite database is migrated in place. The release rotates the two original demo users' passwords once. Existing unknown accounts require owner review. Do not upload the private credentials, manifest, .git, tests or node_modules.

The former intentional lab login is disabled. Record security tests on localhost or an explicitly authorized environment. No penetration testing is performed against the public hosting service by the test suite.

## Verify

```powershell
python tests/smoke.py
npm run build:css
```

The tests use disposable localhost databases and cover authentication defenses, role/field authorization, code expiry/replay/session binding, session revocation, bookings, and legacy stored-XSS rendering. External provider credentials and live inbox delivery are separate deployment checks.

## Project layout

- index.php / login.php: guest portal and sign-in.
- front-desk.php: staff/admin reservation management.
- admin.php: owner-approved account management.
- audit-logs.php: security events and JSON export.
- system-config.php / schema.php: admin settings and SQLite query examples.
- api.php, app/bootstrap.php, app/security.php, app/auth-actions.php: backend and defenses.
- js/: external page scripts, safe DOM helpers and same-origin API transport.
- database/: schema and synthetic seed data.
- storage/: SQLite file, private to the server.

Keep router.php when running PHP's development server. Apache hosting must honor the supplied .htaccess rules. Public packages require working HTTPS. Provider-level SSH controls, WAF, IDS/SIEM and threat-intelligence services are not configured by application code; see the deployment guide for the remaining work.

The project contains synthetic data and is an educational application. The security changes reduce identified risks and have not been independently audited. Existing contact-form behavior remains demonstrative and does not deliver mail; configured security mail is only for authentication and account approvals.
