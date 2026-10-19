<?php require __DIR__ . '/app/bootstrap.php'; require_page(['staff', 'admin']); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>InnPulse — Front Desk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
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
            --muted: #8B9198;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--off-white);
            color: var(--charcoal)
        }

        h1,
        h2,
        h3 {
            font-family: 'Playfair Display', serif
        }

        /* TOP HEADER */
        .top-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: var(--charcoal);
            height: 64px;
            display: flex;
            align-items: center;
            padding: 0 28px;
            gap: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .logo-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-right: 16px;
            text-decoration: none
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: var(--gold);
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px
        }

        .logo-name {
            font-family: 'Playfair Display', serif;
            color: #fff;
            font-size: 15px;
            font-weight: 600
        }

        .logo-sep {
            width: 1px;
            height: 20px;
            background: rgba(255, 255, 255, 0.15);
            margin: 0 4px
        }

        .logo-section {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.55);
            font-weight: 500
        }

        .search-wrap {
            flex: 1;
            max-width: 360px;
            position: relative
        }

        .search-wrap input {
            width: 100%;
            padding: 8px 14px 8px 36px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            font-size: 13px;
            color: #fff;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color 180ms;
        }

        .search-wrap input::placeholder {
            color: rgba(255, 255, 255, 0.4)
        }

        .search-wrap input:focus {
            border-color: rgba(212, 175, 55, 0.5)
        }

        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            font-size: 14px
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: auto
        }

        .system-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.25);
            padding: 5px 12px;
            border-radius: 100px
        }

        .system-dot {
            width: 7px;
            height: 7px;
            background: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 6px #10b981
        }

        .system-text {
            font-size: 11px;
            font-weight: 600;
            color: #10b981;
            text-transform: uppercase;
            letter-spacing: 0.08em
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 8px
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            background: rgba(212, 175, 55, 0.2);
            border: 1px solid rgba(212, 175, 55, 0.35);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px
        }

        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: #fff
        }

        .user-role {
            font-size: 10px;
            color: var(--muted);
            font-weight: 500
        }

        .btn-signout {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.7);
            padding: 7px 14px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 180ms;
            font-family: 'Inter', sans-serif
        }

        .btn-signout:hover {
            background: rgba(255, 255, 255, 0.14);
            color: #fff
        }

        /* MAIN */
        .main {
            padding: 28px 28px 48px
        }

        .page-title {
            font-size: 26px;
            font-weight: 600;
            margin-bottom: 4px
        }

        .page-sub {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 28px
        }

        /* KPI CARDS */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px
        }

        .kpi-card {
            background: #fff;
            border-radius: 13px;
            border: 1px solid var(--border);
            padding: 22px 22px 18px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06)
        }

        .kpi-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.13em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px
        }

        .kpi-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--charcoal);
            font-family: 'Playfair Display', serif;
            line-height: 1
        }

        .kpi-sub {
            font-size: 12px;
            color: var(--muted);
            margin-top: 6px
        }

        /* SECTION CARD */
        .section-card {
            background: #fff;
            border-radius: 13px;
            border: 1px solid var(--border);
            margin-bottom: 24px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05)
        }

        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 22px;
            border-bottom: 1px solid var(--border)
        }

        .section-title {
            font-size: 18px;
            font-weight: 600
        }

        .btn-new {
            background: var(--charcoal);
            color: #fff;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            transition: all 180ms
        }

        .btn-new:hover {
            background: var(--gold);
            color: var(--charcoal);
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3)
        }

        /* TABLE */
        .table-wrap {
            overflow-x: auto
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
            padding: 12px 16px;
            background: var(--off-white);
            border-bottom: 1px solid var(--border);
            text-align: left;
            white-space: nowrap
        }

        td {
            padding: 14px 16px;
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
            font-size: 12px
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

        .badge-paid {
            background: #d1fae5;
            color: #065f46
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e
        }

        .badge-refunded {
            background: #e0e7ff;
            color: #3730a3
        }

        .badge-confirmed {
            background: #dbeafe;
            color: #1d4ed8
        }

        .badge-cancelled {
            background: #fee2e2;
            color: #dc2626
        }

        /* RECEIPT THUMB */
        .receipt-thumb {
            width: 42px;
            height: 32px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            border: 1px solid var(--border);
            transition: transform 150ms
        }

        .receipt-thumb:hover {
            transform: scale(1.1)
        }

        .no-receipt {
            font-size: 11px;
            color: var(--muted)
        }

        /* ACTION BTNS */
        .btn-icon {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--muted);
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 150ms;
            font-family: 'Inter', sans-serif;
            font-weight: 500
        }

        .btn-icon:hover {
            border-color: var(--gold);
            color: var(--gold)
        }

        .btn-del {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626
        }

        .btn-del:hover {
            background: #dc2626;
            color: #fff;
            border-color: #dc2626
        }

        /* ROOM STATUS GRID */
        .room-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(148px, 1fr));
            gap: 12px;
            padding: 20px
        }

        .room-status-card {
            border-radius: 10px;
            padding: 14px;
            cursor: default;
            transition: transform 150ms
        }

        .room-status-card:hover {
            transform: translateY(-2px)
        }

        .rs-available {
            background: #f0fdf4;
            border: 1px solid #bbf7d0
        }

        .rs-occupied {
            background: var(--charcoal);
            border: 1px solid #1C2228;
            color: #fff
        }

        .rs-reserved {
            background: #fffbeb;
            border: 1px solid #fde68a
        }

        .rs-maintenance {
            background: #fffbeb;
            border: 1px solid #fcd34d
        }

        .rs-num {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            font-weight: 700;
            opacity: 0.6;
            margin-bottom: 4px
        }

        .rs-name {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 3px;
            line-height: 1.3
        }

        .rs-guest {
            font-size: 11px;
            opacity: 0.6;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 6px
        }

        .rs-badge {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 2px 7px;
            border-radius: 100px
        }

        .rs-avail-badge {
            background: #d1fae5;
            color: #065f46
        }

        .rs-occ-badge {
            background: #12161A;
            color: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.15)
        }

        .rs-res-badge {
            background: #fef3c7;
            color: #92400e
        }

        .rs-maint-badge {
            background: #fde68a;
            color: #78350f
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
            max-width: 600px;
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
            font-size: 17px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 150ms;
            color: var(--charcoal)
        }

        .modal-close:hover {
            background: var(--border)
        }

        .modal-body {
            padding: 24px
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px
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

        .form-input::placeholder {
            color: #aaa
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

        /* Toast */
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

        /* Legend */
        .legend {
            display: flex;
            gap: 16px;
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            flex-wrap: wrap
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--muted)
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%
        }

        @media(max-width:1100px) {
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr)
            }
        }

        @media(max-width:768px) {
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr)
            }

            .top-header .search-wrap {
                display: none
            }

            .main {
                padding: 20px 16px 40px
            }
        }

        @media(max-width:540px) {
            .kpi-grid {
                grid-template-columns: 1fr
            }

            ;

            .form-row {
                grid-template-columns: 1fr
            }
        }

        /* Spinner */
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
    </style>
</head>

<body>

    <!-- ═══════════════════════════════════════ TOP HEADER ═══════ -->
    <header class="top-header">
        <a href="index.php" class="logo-wrap">
            <div class="logo-icon">🏨</div>
            <span class="logo-name">InnPulse</span>
        </a>
        <div class="logo-sep"></div>
        <span class="logo-section">Front Desk</span>

        <div class="search-wrap">
            <span class="search-icon">🔍</span>
            <input type="text" placeholder="Search guest, room, booking…" id="table-search" data-evt-input="h14" />
        </div>

        <div class="header-right">
            <div class="system-badge">
                <div class="system-dot"></div>
                <span class="system-text">System Online</span>
            </div>
            <div class="user-info">
                <div class="user-avatar">👤</div>
                <div>
                    <p class="user-name" id="user-display-name">Loading…</p>
                    <p class="user-role">Front Desk</p>
                </div>
            </div>
            <button class="btn-signout" data-evt-click="h15">Sign Out</button>
        </div>
    </header>

    <!-- ═══════════════════════════════════════ MAIN CONTENT ══════ -->
    <main class="main">
        <h1 class="page-title">Front Desk</h1>
        <p class="page-sub">Manage reservations, deposits, and room status.</p>

        <!-- KPI CARDS -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <p class="kpi-label">📋 Total Reservations</p>
                <p class="kpi-value" id="kpi-total">—</p>
                <p class="kpi-sub">All time</p>
            </div>
            <div class="kpi-card">
                <p class="kpi-label">📅 Today's Check-Ins</p>
                <p class="kpi-value" id="kpi-checkins">—</p>
                <p class="kpi-sub" id="kpi-today-label">Loading…</p>
            </div>
            <div class="kpi-card">
                <p class="kpi-label" style="color:#065f46">✅ Deposits Paid</p>
                <p class="kpi-value" id="kpi-paid" style="color:#059669">—</p>
                <p class="kpi-sub">Confirmed receipts</p>
            </div>
            <div class="kpi-card">
                <p class="kpi-label" style="color:#92400e">⏳ Deposits Pending</p>
                <p class="kpi-value" id="kpi-pending" style="color:#d97706">—</p>
                <p class="kpi-sub">Awaiting receipt</p>
            </div>
        </div>

        <!-- RESERVATIONS TABLE -->
        <div class="section-card">
            <div class="section-head">
                <h2 class="section-title">Reservations</h2>
                <button class="btn-new" data-evt-click="h16">
                    <span>+</span> New Reservation
                </button>
            </div>
            <div class="table-wrap">
                <table id="reservations-table">
                    <thead>
                        <tr>
                            <th>Guest ID</th>
                            <th>Name</th>
                            <th>Room</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Deposit</th>
                            <th>Receipt</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="reservations-tbody">
                        <tr>
                            <td colspan="9" style="text-align:center;padding:40px;color:var(--muted)">Loading
                                reservations…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ROOM STATUS GRID -->
        <div class="section-card">
            <div class="section-head">
                <h2 class="section-title">Room Status</h2>
            </div>
            <div class="legend">
                <span class="legend-item"><span class="legend-dot" style="background:#10b981"></span> Available</span>
                <span class="legend-item"><span class="legend-dot" style="background:#12161A"></span> Occupied</span>
                <span class="legend-item"><span class="legend-dot" style="background:#f59e0b"></span> Reserved</span>
                <span class="legend-item"><span class="legend-dot" style="background:#fbbf24"></span> Maintenance</span>
            </div>
            <div class="room-status-grid" id="room-status-grid">
                <p style="color:var(--muted);padding:20px">Loading rooms…</p>
            </div>
        </div>
    </main>

    <!-- ══════ NEW / EDIT RESERVATION MODAL ══════ -->
    <div class="modal-overlay" id="res-modal">
        <div class="modal-box">
            <div class="modal-head">
                <h3 class="modal-title" id="res-modal-title">New Reservation</h3>
                <button class="modal-close" data-evt-click="h17">✕</button>
            </div>
            <div class="modal-body">
                <form id="res-form" data-evt-submit="h18">
                    <input type="hidden" id="res-id" />
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Guest Name *</label>
                            <input type="text" class="form-input" id="rf-name" placeholder="Full name" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-input" id="rf-email" placeholder="guest@email.com"
                                required />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-input" id="rf-phone" placeholder="+1 555 000 0000" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Room *</label>
                            <select class="form-input" id="rf-room" required></select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Check-In *</label>
                            <input type="date" class="form-input" id="rf-checkin" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Check-Out *</label>
                            <input type="date" class="form-input" id="rf-checkout" required />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Guests</label>
                            <input type="number" class="form-input" id="rf-guests" min="1" max="6" value="1" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Deposit Status</label>
                            <select class="form-input" id="rf-deposit">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Special Requests</label>
                        <textarea class="form-input" id="rf-requests" rows="2" placeholder="Optional"
                            style="resize:vertical"></textarea>
                    </div>
                    <button type="submit" class="btn-submit" id="res-submit-btn">Save Reservation</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ══════ RECEIPT PREVIEW MODAL ══════ -->
    <div class="modal-overlay" id="receipt-modal">
        <div class="modal-box" style="max-width:480px">
            <div class="modal-head">
                <h3 class="modal-title">Deposit Receipt</h3>
                <button class="modal-close" data-evt-click="h19">✕</button>
            </div>
            <div class="modal-body" style="padding:16px">
                <img id="receipt-modal-img" src="" alt="Receipt"
                    style="width:100%;border-radius:10px;object-fit:contain;max-height:480px" />
            </div>
        </div>
    </div>

    <script type="module" src="js/front-desk-page-0.js?v=security5"></script>
<script type="module" src="js/events.js?v=security5"></script>
</body>

</html>