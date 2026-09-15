# InnPulse Boutique Hotel

> **⚠ EDUCATIONAL / DEMONSTRATION ENVIRONMENT**
> This application uses **synthetic data only**. It is intended for controlled cybersecurity demonstrations, educational labs, and learning purposes. **Do not deploy with real customer data, real payment credentials, or real production secrets.**

---

## Overview

InnPulse is a complete hotel reservation and management web application built with:

| Layer    | Technology              |
|----------|-------------------------|
| Frontend | HTML5, Tailwind CSS, Vanilla JavaScript |
| Backend  | Supabase (PostgreSQL + Auth + Storage) |
| Auth     | Supabase Email/Password (intentionally basic — demo purposes) |

### Intentionally Basic Authentication

The login mechanism is **deliberately simple** for use as a cybersecurity demonstration environment. The following features are **intentionally absent**:

- Multi-factor authentication (MFA / 2FA)
- CAPTCHA or reCAPTCHA
- Biometric or passwordless authentication
- Custom brute-force protection or application-level rate limiting
- Device verification or trusted device flows
- Social login

This is by design. The application demonstrates what **basic** authentication looks like so students can identify and discuss its weaknesses in a controlled lab setting.

---

## Project Structure

```
InnPulse/
├── index.html           — Public guest portal & landing page
├── login.html           — Unified staff/admin login
├── front-desk.html      — Front desk dashboard (staff/admin)
├── admin.html           — User management (admin only)
├── audit-logs.html      — Security audit logs (admin only)
├── system-config.html   — System configuration (admin only)
├── schema.html          — Database schema viewer (admin only)
├── assets/
│   ├── css/
│   │   ├── input.css    — Tailwind source
│   │   └── output.css   — Compiled CSS (generated)
│   └── images/
├── js/
│   ├── supabase.js      — Supabase client initialization
│   ├── auth.js          — Auth helpers
│   └── utils.js         — Shared utilities
├── supabase/
│   ├── schema.sql       — Database schema + RLS policies
│   └── seed.sql         — Demo seed data
├── .env.example         — Environment variable template
├── tailwind.config.js
├── package.json
└── README.md
```

---

## Installation & Setup

### 1. Install Dependencies

```bash
npm install
```

### 2. Build Tailwind CSS

```bash
npm run build:css
```

To watch for changes during development:

```bash
npm run watch:css
```

### 3. Create a Supabase Project

1. Go to [https://supabase.com](https://supabase.com) and create a new project.
2. Note your **Project URL** and **Anon (public) key** from **Settings → API**.

> Never use or expose the **service_role key** in frontend code.

### 4. Execute Schema

In the Supabase **SQL Editor**, paste and run the contents of `supabase/schema.sql`.

This creates:
- `profiles` — Staff/admin user records
- `rooms` — Hotel room inventory
- `reservations` — Guest reservations
- `deposits` — Deposit tracking
- `reviews` — Guest reviews
- `audit_logs` — Security event log
- `system_config` — Key-value configuration

### 5. Execute Seed Data

In the Supabase SQL Editor, paste and run `supabase/seed.sql`.

This populates:
- 13 hotel rooms (various types and statuses)
- 8 sample reservations
- Matching deposit records
- 5 approved guest reviews
- 10 demo audit log entries
- System configuration defaults

### 6. Create Supabase Storage Bucket

1. Go to **Storage** in your Supabase dashboard.
2. Create a new bucket named exactly: `deposit-receipts`
3. Set it to **Public** (so receipt thumbnails are viewable in the front desk).

### 7. Configure Environment Variables

Copy `.env.example` to `.env` and fill in your values:

```bash
SUPABASE_URL=https://your-project-ref.supabase.co
SUPABASE_ANON_KEY=your-anon-key-here
```

Then update **both values** in each HTML file's inline script block. Search for:

```javascript
const SUPABASE_URL  = 'https://your-project-ref.supabase.co';
const SUPABASE_ANON = 'your-anon-key-here';
```

Replace these in: `index.html`, `login.html`, `front-desk.html`, `admin.html`, `audit-logs.html`, `system-config.html`, `schema.html`.

> **Tip:** For a real project, use a bundler (Vite/Webpack) to inject environment variables. For this demo, inline replacement is fine since the anon key is public-safe.

### 8. Create Demo Auth Users

In your Supabase dashboard, go to **Authentication → Users → Add User**:

| Email               | Password         | Note                    |
|---------------------|------------------|-------------------------|
| `admin@innpulse`    | `admin@innpulse` | Will become admin role  |
| `staff@innpulse`    | `staff@innpulse` | Will become staff role  |

> Passwords are set by you in the Supabase Auth dashboard — they are not hardcoded anywhere in the frontend HTML.

### 9. Assign Profile Roles

After creating the auth users, the trigger automatically creates profile records. Set the roles:

```sql
UPDATE public.profiles
  SET role = 'admin', username = 'admin.root'
  WHERE email = 'admin@innpulse';

UPDATE public.profiles
  SET role = 'staff', username = 'jane.staff'
  WHERE email = 'staff@innpulse';
```

### 10. Start Local Development Server

Since this is a plain HTML project (no build server required), serve it with any static file server:

```bash
# Using Node.js http-server
npx http-server . -p 3000 -o

# Or using Python
python -m http.server 3000

# Or using VS Code Live Server extension
# Right-click index.html → Open with Live Server
```

Then open `http://localhost:3000` in your browser.

---

## Application Routes

| URL                | Access      | Description                       |
|--------------------|-------------|-----------------------------------|
| `/index.html`      | Public      | Guest portal, room search, reviews |
| `/login.html`      | Public      | Unified staff/admin login          |
| `/front-desk.html` | Staff/Admin | Reservation management dashboard   |
| `/admin.html`      | Admin only  | User management                    |
| `/audit-logs.html` | Admin only  | Security audit event stream        |
| `/system-config.html` | Admin only | System configuration            |
| `/schema.html`     | Admin only  | Database schema viewer             |

---

## Demo Accounts

| Email               | Password         | Role  | Redirects To           |
|---------------------|------------------|-------|------------------------|
| `admin@innpulse`    | `admin@innpulse` | Admin | `/admin.html`          |
| `staff@innpulse`    | `staff@innpulse` | Staff | `/front-desk.html`     |

Login is accessed via the **Login** button on the public landing page — it opens as a modal overlay, not a separate page. Guests **do not need an account** to browse rooms or make reservations.

---

## Security Notes (For Demonstration)

This application intentionally demonstrates a **basic, unsecured login** flow:

- ❌ No MFA
- ❌ No CAPTCHA
- ❌ No rate limiting
- ❌ No brute-force lockout (only manual admin lock)
- ✅ Role-based redirect after login
- ✅ Manual account lock/unlock in admin UI
- ✅ Audit log records login events
- ✅ RLS policies restrict database access by role
- ✅ Anon key used (service_role key never in frontend)

---

## Design System

| Token          | Value     |
|----------------|-----------|
| Primary Charcoal | `#12161A` |
| Dark Secondary | `#1C2228` |
| Off-White      | `#F8F9FA` |
| Luxury Gold    | `#D4AF37` |
| Muted Gold     | `#B9982E` |
| Muted Text     | `#8B9198` |

Fonts: **Playfair Display** (headings) · **Inter** (body) · **JetBrains Mono** (technical labels)

---

## License

This project is provided for **educational and demonstration purposes only**.

> ⚠ Do not use this application with real customer data, real payment information, or in a production environment without proper security hardening.
