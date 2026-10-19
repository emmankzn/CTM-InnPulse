# InnPulse — PHP + SQLite

InnPulse keeps its existing hotel interface, Tailwind CSS, and JavaScript, with PHP pages, a PHP JSON API, server sessions, and a local SQLite database. Supabase is no longer used.

## Run locally

Requirements: PHP 8.1+ with PDO SQLite enabled. XAMPP on this computer already provides PHP 8.2 and `pdo_sqlite`.

From the project directory:

```powershell
php -S 127.0.0.1:8000 router.php
```

Open **http://127.0.0.1:8000**. PHP automatically creates `storage/innpulse.sqlite` and seeds demo accounts, 13 rooms, eight reservations, deposits, reviews, audit events, and settings on first use. No database server, Composer, npm install, or Supabase account is required to run the app.

If PHP is unavailable in a different terminal, use `C:\xampp\php\php.exe` instead of `php`.

PHP must serve the pages: VS Code Live Server, Python static servers, and opening files directly will not execute PHP. Always include `router.php` when using PHP's development server; it blocks downloads of private application files and the database.

## Demo accounts

| Email | Password | Role |
|---|---|---|
| admin@innpulse.com | admin@innpulse | Admin |
| staff@innpulse.com | staff@innpulse | Staff |

Use the Login button on the home page. Admins can create accounts, change passwords, and lock/unlock accounts from User Management. Passwords are hashed in SQLite. PHP sessions determine access; browser local storage no longer grants permissions.

## Classroom security modes

Use synthetic data for the teacher-authorized exercises on this local copy.

**Protected mode (default):** login uses a prepared SQL statement and password verification. Ten login attempts per IP within five minutes trigger an HTTP 429 response. Failed and successful logins appear in Audit Logs.

**Lab mode:** stop the server with Ctrl+C, then run:

```powershell
$env:INNPULSE_LAB_MODE = "1"
php -S 127.0.0.1:8000 router.php
```

A red banner identifies lab mode. It intentionally concatenates login inputs into SQL and disables the login attempt limit. The flag takes effect only for requests from `127.0.0.1` or `::1`; keep the server bound to loopback as shown. Manual locks and roles still apply.

For a local SQL injection demonstration, enter `admin@innpulse.com' --` as the login email and any nonempty password. This bypasses the password condition in lab mode. The same input fails in protected mode. The login form permits this classroom input without browser email validation.

For a brute-force demonstration, the lab login accepts repeated password guesses without an automatic lockout or rate limit. Use only the seeded local accounts. Compare failed-login audit events and HTTP responses with protected mode.

To restore protected mode, stop the server, then run:

```powershell
$env:INNPULSE_LAB_MODE = "0"
php -S 127.0.0.1:8000 router.php
```

These modes compare login weaknesses; the application has not undergone a full production security audit. The lab flag does not intentionally weaken every database operation.

## Pages and backend

| Route | Access |
|---|---|
| index.php, login.php | Public guest portal and login |
| front-desk.php | Staff/admin reservation management |
| admin.php | Admin account management |
| audit-logs.php | Admin audit events |
| system-config.php | Admin settings |
| schema.php | Admin SQLite schema and SQL examples |

`api.php` handles authentication, bookings, reviews, and authorized database operations. Requests use POST JSON with the header `X-InnPulse-Request: 1`. `js/api.js` provides the browser transport. `app/bootstrap.php` initializes SQLite and sessions. `database/schema.sql` and `database/seed.sql` contain the SQLite schema and demo fixtures.

Guest reservations save the booking, pending deposit, room status, and audit event in one transaction. The interface reports success only after saving. Uploading a receipt, if added to the interface, would not count as payment verification. The existing contact form remains a demonstration and sends no email. Schema queries are examples, not a live SQL console. Hotel settings persist in SQLite; the existing interface does not enforce every configuration toggle.

The supplied data is demo seed data, **not an export of your hosted Supabase database**. Existing live Supabase records have not been copied. Fonts and hotel images still use their existing external URLs.

## Database location and reset

Optionally set `INNPULSE_DB_PATH` to an absolute file path outside the web root before starting PHP. Environment variables must be set in the shell or server configuration: PHP does not automatically load `.env`.

To reset the default demo database, stop PHP and remove only `storage/innpulse.sqlite`; restarting creates fresh demo data. Back up that file first if you want to keep your changes. Private directories include Apache `.htaccess` rules as well as development-router restrictions. With XAMPP Apache, ensure `AllowOverride` permits these rules before serving this project.

## Verification and CSS

```powershell
python tests/smoke.py
npm run build:css
```

The integration suite requires Python, PHP, and Node. It starts temporary loopback servers with disposable databases and checks both security modes, authorization, bookings, reviews, settings, account management, and PHP/JavaScript syntax. It leaves your application database unchanged.

Compiled CSS is included. npm and Tailwind are needed only to rebuild CSS; Tailwind now scans `.php` pages.
