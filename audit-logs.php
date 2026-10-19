<?php require __DIR__ . '/app/bootstrap.php'; require_page(['admin']); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>InnPulse — Audit Logs</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet" />
    <style>
        :root {
            --charcoal: #12161A;
            --dark-sec: #1C2228;
            --off-white: #F8F9FA;
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

        /* SIDEBAR (shared) */
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
            flex-shrink: 0
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
            width: 100%
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

        /* MAIN */
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

        /* KPI CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px
        }

        .stat-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05)
        }

        .stat-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.13em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            font-family: 'Playfair Display', serif;
            line-height: 1
        }

        .stat-sub {
            font-size: 12px;
            color: var(--muted);
            margin-top: 5px
        }

        /* FILTER BAR */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            flex-wrap: wrap
        }

        .filter-chip {
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid var(--border);
            background: #fff;
            cursor: pointer;
            transition: all 150ms;
            color: var(--muted)
        }

        .filter-chip.active,
        .filter-chip:hover {
            background: var(--charcoal);
            color: #fff;
            border-color: var(--charcoal)
        }

        .filter-chip.info-chip.active {
            background: #1d4ed8;
            border-color: #1d4ed8;
            color: #fff
        }

        .filter-chip.warn-chip.active {
            background: #d97706;
            border-color: #d97706;
            color: #fff
        }

        .filter-chip.crit-chip.active {
            background: #dc2626;
            border-color: #dc2626;
            color: #fff
        }

        .search-input {
            padding: 7px 12px;
            border: 1px solid var(--border);
            border-radius: 7px;
            font-size: 13px;
            outline: none;
            font-family: 'Inter', sans-serif;
            transition: border-color 180ms;
            flex: 1;
            min-width: 0;
            max-width: 280px
        }

        .search-input:focus {
            border-color: var(--gold)
        }

        /* EVENT STREAM */
        .event-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden
        }

        .event-row {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            transition: background 150ms;
            gap: 14px
        }

        .event-row:last-child {
            border-bottom: none
        }

        .event-row:hover {
            background: #FAFAF8
        }

        .event-sev-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0
        }

        .sev-info {
            background: #3b82f6
        }

        .sev-warn {
            background: #f59e0b
        }

        .sev-crit {
            background: #ef4444
        }

        .event-icon {
            font-size: 18px;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0
        }

        .icon-info {
            background: #eff6ff
        }

        .icon-warn {
            background: #fffbeb
        }

        .icon-crit {
            background: #fef2f2
        }

        .event-content {
            flex: 1;
            min-width: 0
        }

        .event-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--charcoal);
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .event-meta {
            font-size: 11px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap
        }

        .event-meta span {
            display: flex;
            align-items: center;
            gap: 4px
        }

        .event-time {
            font-size: 11px;
            color: var(--muted);
            font-family: 'JetBrains Mono', monospace;
            white-space: nowrap;
            flex-shrink: 0
        }

        .event-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 7px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 700;
            flex-shrink: 0
        }

        .badge-info {
            background: #dbeafe;
            color: #1d4ed8
        }

        .badge-warn {
            background: #fef3c7;
            color: #92400e
        }

        .badge-crit {
            background: #fee2e2;
            color: #dc2626
        }

        /* EXPAND */
        .event-expand {
            padding: 14px 20px 16px 62px;
            background: #FAFAF8;
            border-bottom: 1px solid #f3f4f6;
            font-size: 12px;
            color: var(--muted);
            display: none;
            font-family: 'JetBrains Mono', monospace;
            line-height: 1.7
        }

        .event-expand.open {
            display: block
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

        .toast-info {
            background: #1a1e2e;
            color: #6f9fcf;
            border: 1px solid #2d3d5a
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

            .stats-grid {
                grid-template-columns: 1fr 1fr
            }
        }

        @media(max-width:560px) {
            .stats-grid {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>

    <button class="sidebar-toggle" id="sidebar-toggle" data-evt-click="h6">☰</button>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">🏨</div>
            <p class="sidebar-logo-name">InnPulse</p>
            <p class="sidebar-logo-role">Administrator</p>
        </div>
        <nav class="sidebar-nav">
            <p class="nav-section-label">Controls</p>
            <a class="nav-item" href="admin.php"><span class="nav-icon">👥</span> User Management</a>
            <a class="nav-item active" href="audit-logs.php"><span class="nav-icon">🔍</span> Security Audit</a>
            <a class="nav-item" href="system-config.php"><span class="nav-icon">⚙</span> System Config</a>
            <a class="nav-item" href="front-desk.php"><span class="nav-icon">🖥</span> Front Desk</a>
            <p class="nav-section-label">Database</p>
            <a class="nav-item" href="schema.php"><span class="nav-icon">🗄</span> Schema Viewer</a>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar">👤</div>
                <div>
                    <p class="sidebar-username" id="sidebar-username">Loading…</p>
                    <p class="sidebar-userrole">Super Admin</p>
                </div>
            </div>
            <button class="btn-signout-side" data-evt-click="h7">Sign Out</button>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="main">
        <div class="top-bar">
            <div class="top-bar-title">
                <p>Security Operations</p>
                <h1>Audit Logs</h1>
            </div>
            <button id="export-audit" style="padding:8px 16px;border:1px solid #ccc;border-radius:8px">Export security events</button>
            <button data-evt-click="h8"
                style="padding:8px 16px;background:var(--off-white);border:1px solid var(--border);border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;display:flex;align-items:center;gap:6px">
                🔄 Refresh
            </button>
        </div>

        <div class="content">
            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <p class="stat-label">📊 Total Events</p>
                    <p class="stat-value" id="stat-total">—</p>
                    <p class="stat-sub">Latest 200 events</p>
                </div>
                <div class="stat-card">
                    <p class="stat-label" style="color:#d97706">⚠ Warnings</p>
                    <p class="stat-value" id="stat-warn" style="color:#d97706">—</p>
                    <p class="stat-sub">Requires attention</p>
                </div>
                <div class="stat-card">
                    <p class="stat-label" style="color:#dc2626">🚨 Critical</p>
                    <p class="stat-value" id="stat-crit" style="color:#dc2626">—</p>
                    <p class="stat-sub">Immediate review</p>
                </div>
            </div>

            <!-- FILTER BAR -->
            <div class="filter-bar">
                <span class="filter-chip active" data-filter="ALL" data-evt-click="h9">All</span>
                <span class="filter-chip info-chip" data-filter="INFO" data-evt-click="h10">INFO</span>
                <span class="filter-chip warn-chip" data-filter="WARN" data-evt-click="h11">WARN</span>
                <span class="filter-chip crit-chip" data-filter="CRITICAL"
                    data-evt-click="h12">CRITICAL</span>
                <input type="text" class="search-input" placeholder="Search events…" id="log-search"
                    data-evt-input="h13" />
            </div>

            <!-- EVENT STREAM -->
            <div class="event-card" id="event-stream">
                <div class="event-row">
                    <div class="event-sev-dot sev-info"></div>
                    <div class="event-icon icon-info">ℹ</div>
                    <div class="event-content">
                        <p class="event-title">Loading audit events…</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="module" src="js/audit-logs-page-0.js?v=security5"></script>
<script type="module" src="js/events.js?v=security5"></script>
</body>

</html>
