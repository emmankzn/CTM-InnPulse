<?php require __DIR__ . '/app/bootstrap.php'; require_page(['admin']); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>InnPulse — Admin Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet" />
    <style>
        :root {
            --charcoal: #12161A;
            --dark-sec: #1C2228;
            --off-white: #F8F9FA;
            --warm-cream: #FAFAF8;
            --gold: #D4AF37;
            --muted-gold: #B9982E;
            --border: #E5E7EB;
            --muted: #8B9198
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--off-white);
            color: var(--charcoal);
            display: flex;
            min-height: 100vh
        }

        h1,
        h2,
        h3 {
            font-family: 'Playfair Display', serif
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 240px;
            min-height: 100vh;
            background: var(--charcoal);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            flex-shrink: 0;
            transition: width 250ms;
        }

        .sidebar-logo {
            padding: 24px 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06)
        }

        .sidebar-logo-icon {
            width: 38px;
            height: 38px;
            background: var(--gold);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 10px
        }

        .sidebar-logo-name {
            font-family: 'Playfair Display', serif;
            color: #fff;
            font-size: 16px;
            font-weight: 600
        }

        .sidebar-logo-role {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--gold);
            margin-top: 2px
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 12px
        }

        .nav-section-label {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.28);
            padding: 8px 12px 4px;
            margin-top: 8px
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            cursor: pointer;
            transition: all 180ms;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #fff
        }

        .nav-item.active {
            background: rgba(212, 175, 55, 0.14);
            color: var(--gold);
            border-left: 2px solid var(--gold)
        }

        .nav-icon {
            font-size: 15px;
            width: 20px;
            text-align: center
        }

        .sidebar-footer {
            padding: 16px 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.06)
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px
        }

        .sidebar-avatar {
            width: 34px;
            height: 34px;
            background: rgba(212, 175, 55, 0.18);
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px
        }

        .sidebar-username {
            font-size: 13px;
            font-weight: 600;
            color: #fff
        }

        .sidebar-userrole {
            font-size: 10px;
            color: var(--muted)
        }

        .btn-signout-side {
            width: 100%;
            padding: 8px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 180ms;
            font-family: 'Inter', sans-serif
        }

        .btn-signout-side:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #fff
        }

        /* ── MAIN ── */
        .main {
            flex: 1;
            overflow: auto
        }

        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 28px;
            border-bottom: 1px solid var(--border);
            background: #fff;
            position: sticky;
            top: 0;
            z-index: 40
        }

        .top-bar-title p {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 2px
        }

        .top-bar-title h1 {
            font-size: 22px;
            font-weight: 600
        }

        .content {
            padding: 28px
        }

        /* TABLE */
        .card {
            background: #fff;
            border-radius: 13px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05)
        }

        .card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 22px;
            border-bottom: 1px solid var(--border)
        }

        .card-title {
            font-size: 17px;
            font-weight: 600
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 11px 16px;
            background: var(--off-white);
            border-bottom: 1px solid var(--border);
            text-align: left;
            white-space: nowrap
        }

        td {
            padding: 13px 16px;
            border-bottom: 1px solid #F3F4F6;
            font-size: 13px;
            vertical-align: middle
        }

        tr:last-child td {
            border-bottom: none
        }

        tr:hover td {
            background: #FAFAF8
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--muted)
        }

        /* BADGES */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700
        }

        .badge-admin {
            background: rgba(212, 175, 55, 0.15);
            color: var(--muted-gold)
        }

        .badge-staff {
            background: #dbeafe;
            color: #1d4ed8
        }

        .badge-active {
            background: #d1fae5;
            color: #065f46
        }

        .badge-locked {
            background: #fee2e2;
            color: #dc2626
        }

        /* BUTTONS */
        .btn-primary {
            background: var(--charcoal);
            color: #fff;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 180ms;
            display: inline-flex;
            align-items: center;
            gap: 7px
        }

        .btn-primary:hover {
            background: #1C2228;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2)
        }

        .btn-sm {
            padding: 5px 11px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            border: none;
            transition: all 150ms
        }

        .btn-edit {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid var(--border)
        }

        .btn-edit:hover {
            background: var(--charcoal);
            color: #fff;
            border-color: var(--charcoal)
        }

        .btn-lock {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a
        }

        .btn-lock:hover {
            background: #d97706;
            color: #fff;
            border-color: #d97706
        }

        .btn-unlock {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #bbf7d0
        }

        .btn-unlock:hover {
            background: #065f46;
            color: #fff
        }

        .btn-reset {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe
        }

        .btn-reset:hover {
            background: #1d4ed8;
            color: #fff
        }

        .btn-danger {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca
        }

        .btn-danger:hover {
            background: #dc2626;
            color: #fff
        }

        /* MODAL */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(3px);
            z-index: 200;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px
        }

        .modal-overlay.open {
            display: flex
        }

        .modal-box {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 520px;
            max-height: 92vh;
            overflow-y: auto;
            animation: modalIn 0.2s ease
        }

        @keyframes modalIn {
            from {
                transform: scale(0.94) translateY(20px);
                opacity: 0
            }

            to {
                transform: scale(1) translateY(0);
                opacity: 1
            }
        }

        .modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border)
        }

        .modal-title {
            font-size: 19px;
            font-weight: 600
        }

        .modal-close {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--off-white);
            border: none;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 150ms
        }

        .modal-close:hover {
            background: var(--border)
        }

        .modal-body {
            padding: 24px
        }

        .form-group {
            margin-bottom: 14px
        }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px
        }

        .form-input {
            width: 100%;
            padding: 9px 13px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            color: var(--charcoal);
            background: #fff;
            outline: none;
            transition: border-color 180ms, box-shadow 180ms
        }

        .form-input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1)
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: var(--charcoal);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 180ms;
            margin-top: 6px
        }

        .btn-submit:hover {
            background: var(--gold);
            color: var(--charcoal)
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed
        }

        /* TOAST */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            min-width: 280px;
            padding: 13px 18px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 9px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.18);
            animation: slideIn 0.25s ease
        }

        .toast-success {
            background: #1a2e1a;
            color: #6fcf6f;
            border: 1px solid #2d5a2d
        }

        .toast-error {
            background: #2e1a1a;
            color: #cf6f6f;
            border: 1px solid #5a2d2d
        }

        .toast-info {
            background: #1a1e2e;
            color: #6f9fcf;
            border: 1px solid #2d3d5a
        }

        .toast-warning {
            background: #2e2a1a;
            color: var(--gold);
            border: 1px solid #5a4d2d
        }

        @keyframes slideIn {
            from {
                transform: translateX(120%);
                opacity: 0
            }

            to {
                transform: translateX(0);
                opacity: 1
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg)
            }
        }

        .spin {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite
        }

        /* SIDEBAR MOBILE TOGGLE */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 300;
            background: var(--charcoal);
            border: none;
            border-radius: 8px;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #fff;
            font-size: 18px
        }

        @media(max-width:900px) {
            .sidebar {
                position: fixed;
                left: -240px;
                z-index: 200;
                transition: left 250ms
            }

            .sidebar.open {
                left: 0
            }

            .sidebar-toggle {
                display: flex
            }

            .main {
                margin-left: 0
            }
        }
    </style>
</head>

<body>

    <!-- SIDEBAR TOGGLE (mobile) -->
    <button class="sidebar-toggle" id="sidebar-toggle" data-evt-click="h0">☰</button>

    <!-- ══════════════════════════ SIDEBAR ═══════════════════════════ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">🏨</div>
            <p class="sidebar-logo-name">InnPulse</p>
            <p class="sidebar-logo-role">Administrator</p>
        </div>

        <nav class="sidebar-nav">
            <p class="nav-section-label">Controls</p>
            <a class="nav-item active" href="admin.php">
                <span class="nav-icon">👥</span> User Management
            </a>
            <a class="nav-item" href="audit-logs.php">
                <span class="nav-icon">🔍</span> Security Audit
            </a>
            <a class="nav-item" href="system-config.php">
                <span class="nav-icon">⚙</span> System Config
            </a>
            <a class="nav-item" href="front-desk.php">
                <span class="nav-icon">🖥</span> Front Desk
            </a>

            <p class="nav-section-label">Database</p>
            <a class="nav-item" href="schema.php">
                <span class="nav-icon">🗄</span> Schema Viewer
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar">👤</div>
                <div>
                    <p class="sidebar-username" id="sidebar-username">Loading…</p>
                    <p class="sidebar-userrole">Super Admin</p>
                </div>
            </div>
            <button class="btn-signout-side" data-evt-click="h1">Sign Out</button>
        </div>
    </aside>

    <!-- ══════════════════════════ MAIN ══════════════════════════════ -->
    <div class="main">
        <div class="top-bar">
            <div class="top-bar-title">
                <p>Role-Based Access Control</p>
                <h1>User Management</h1>
            </div>
            <button class="btn-primary" data-evt-click="h2">
                <span>+</span> Invite User
            </button>
        </div>

        <div class="content">
            <div class="card">
                <div class="card-head">
                    <h2 class="card-title">Staff &amp; Administrators</h2>
                    <div style="display:flex;align-items:center;gap:10px">
                        <input type="text" placeholder="Search users…" id="user-search" data-evt-input="h3"
                            style="padding:7px 12px;border:1px solid var(--border);border-radius:7px;font-size:13px;outline:none;width:200px" />
                    </div>
                </div>
                <div style="overflow-x:auto">
                    <table id="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-tbody">
                            <tr>
                                <td colspan="7" style="text-align:center;padding:40px;color:var(--muted)">Loading users…
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════ INVITE / EDIT USER MODAL ══════ -->
    <div class="modal-overlay" id="user-modal">
        <div class="modal-box">
            <div class="modal-head">
                <h3 class="modal-title" id="user-modal-title">Invite User</h3>
                <button class="modal-close" data-evt-click="h4">✕</button>
            </div>
            <div class="modal-body">
                <form id="user-form" data-evt-submit="h5">
                    <input type="hidden" id="uf-id" />
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-input" id="uf-username" placeholder="john.doe" required />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email for login verification *</label>
                        <input type="email" class="form-input" id="uf-email" placeholder="Their real email for login codes"
                            required />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select class="form-input" id="uf-role" required>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group" id="password-group">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-input" id="uf-password" placeholder="At least 14 characters" />
                        <p style="font-size:11px;color:var(--muted);margin-top:4px">Use a strong password. This user must verify their registered email at login.</p>
                    </div>
                    <button type="submit" class="btn-submit" id="user-submit-btn">Create User</button>
                </form>
            </div>
        </div>
    </div>

    <script type="module" src="js/admin-page-0.js?v=security5"></script>
<script type="module" src="js/events.js?v=security5"></script>
</body>

</html>