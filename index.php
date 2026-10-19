<?php require __DIR__ . '/app/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>InnPulse Boutique Hotel</title>
    <link rel="stylesheet" href="assets/css/output.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet" />
    <style>
        :root {
            --charcoal: #12161A;
            --dark-secondary: #1C2228;
            --off-white: #F8F9FA;
            --warm-cream: #FAFAF8;
            --luxury-gold: #D4AF37;
            --muted-gold: #B9982E;
            --border-grey: #E5E7EB;
            --muted-text: #8B9198;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--charcoal);
            background: var(--off-white);
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Playfair Display', Georgia, serif;
        }

        /* ── HEADER ── */
        .site-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: var(--charcoal);
            padding: 0 32px;
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .logo-group {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            background: var(--luxury-gold);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1;
        }

        .logo-text span:first-child {
            font-family: 'Playfair Display', serif;
            color: #fff;
            font-size: 17px;
            font-weight: 600;
            letter-spacing: 0.01em;
        }

        .logo-text span:last-child {
            font-family: 'Inter', sans-serif;
            color: var(--muted-text);
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .nav-links a {
            color: rgba(255, 255, 255, 0.72);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 14px;
            border-radius: 7px;
            transition: color 180ms, background 180ms;
        }

        .nav-links a:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.06);
        }

        .btn-login {
            background: var(--luxury-gold);
            color: var(--charcoal);
            border: none;
            padding: 9px 22px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: background 180ms, box-shadow 180ms;
            text-decoration: none;
            display: inline-block;
        }

        .btn-login:hover {
            background: var(--muted-gold);
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.35);
        }

        /* ── LOGIN MODAL ── */
        .login-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 500;
            background: rgba(10, 13, 16, 0.72);
            backdrop-filter: blur(6px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .login-modal-overlay.open {
            display: flex;
        }

        .login-modal {
            background: #fff;
            border-radius: 18px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 32px 80px rgba(0, 0, 0, 0.4);
            animation: loginModalIn 0.22s cubic-bezier(0.34, 1.3, 0.64, 1);
            overflow: hidden;
        }

        @keyframes loginModalIn {
            from {
                transform: scale(0.88) translateY(30px);
                opacity: 0;
            }

            to {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
        }

        .lm-header {
            background: var(--charcoal);
            padding: 22px 26px 18px;
            position: relative;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }


        .lm-eyebrow {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--luxury-gold);
            margin-bottom: 4px;
        }

        .lm-title {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 600;
            color: #fff;
            line-height: 1.1;
        }

        .lm-close {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.7);
            font-size: 16px;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 150ms;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .lm-close:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .lm-body {
            padding: 26px;
        }

        .lm-form-group {
            margin-bottom: 16px;
        }

        .lm-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted-text);
            margin-bottom: 6px;
        }

        .lm-input-wrap {
            position: relative;
        }

        .lm-input {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--border-grey);
            border-radius: 9px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: var(--charcoal);
            background: #fafafa;
            outline: none;
            transition: border-color 180ms, box-shadow 180ms, background 180ms;
        }

        .lm-input:focus {
            border-color: var(--luxury-gold);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.13);
        }

        .lm-input.lm-error {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .lm-input::placeholder {
            color: #bbb;
        }

        .lm-pw-toggle {
            position: absolute;
            right: 11px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--muted-text);
            font-size: 15px;
            padding: 4px;
            transition: color 150ms;
            line-height: 1;
        }

        .lm-pw-toggle:hover {
            color: var(--charcoal);
        }

        .lm-error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 10px 13px;
            font-size: 13px;
            color: #dc2626;
            margin-bottom: 16px;
            display: none;
            align-items: center;
            gap: 8px;
        }

        .lm-submit {
            width: 100%;
            padding: 13px;
            background: var(--charcoal);
            color: #fff;
            border: none;
            border-radius: 9px;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: background 180ms, box-shadow 180ms;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .lm-submit:hover {
            background: #1C2228;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.22);
        }

        .lm-submit:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .lm-hint {
            margin-top: 16px;
            padding: 13px 14px;
            background: var(--off-white);
            border-radius: 9px;
            border: 1px solid var(--border-grey);
        }

        .lm-hint-title {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--muted-text);
            margin-bottom: 8px;
        }

        .lm-hint-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid var(--border-grey);
            cursor: pointer;
        }

        .lm-hint-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .lm-hint-cred {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--charcoal);
        }

        .lm-hint-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 100px;
        }

        .lm-hint-badge.admin {
            background: rgba(212, 175, 55, 0.15);
            color: var(--muted-gold);
        }

        .lm-hint-badge.staff {
            background: #dbeafe;
            color: #1d4ed8;
        }

        @keyframes lm-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .lm-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: lm-spin 0.6s linear infinite;
        }

        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 6px;
        }

        .hamburger span {
            display: block;
            width: 22px;
            height: 2px;
            background: #fff;
            border-radius: 2px;
            transition: all 200ms;
        }

        /* ── HERO ── */
        .hero-section {
            min-height: 100vh;
            background: url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=1800&q=80') center/cover no-repeat;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 32px 80px;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(160deg, rgba(18, 22, 26, 0.82) 0%, rgba(18, 22, 26, 0.65) 50%, rgba(18, 22, 26, 0.8) 100%);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 780px;
        }

        .hero-eyebrow {
            display: inline-block;
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--luxury-gold);
            margin-bottom: 24px;
            border: 1px solid rgba(212, 175, 55, 0.35);
            padding: 6px 18px;
            border-radius: 100px;
        }

        .hero-heading {
            font-size: clamp(44px, 6vw, 76px);
            font-weight: 600;
            color: #fff;
            line-height: 1.08;
            letter-spacing: -0.02em;
            margin-bottom: 24px;
        }

        .hero-heading .gold {
            color: var(--luxury-gold);
            font-style: italic;
        }

        .hero-subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.7;
            max-width: 520px;
            margin: 0 auto 48px;
        }

        /* ── SEARCH BAR ── */
        .search-bar {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: stretch;
            overflow: hidden;
            max-width: 920px;
            margin: 0 auto;
        }

        .search-field {
            flex: 1;
            padding: 18px 20px;
            border-right: 1px solid var(--border-grey);
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
        }

        .search-field:last-of-type {
            border-right: none;
        }

        .search-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted-text);
        }

        .search-input {
            border: none;
            outline: none;
            font-size: 14px;
            font-weight: 500;
            color: var(--charcoal);
            background: transparent;
            width: 100%;
        }

        .search-input::placeholder {
            color: #aaa;
        }

        .search-select {
            border: none;
            outline: none;
            font-size: 14px;
            font-weight: 500;
            color: var(--charcoal);
            background: transparent;
            width: 100%;
            cursor: pointer;
        }

        .search-btn {
            background: var(--luxury-gold);
            color: var(--charcoal);
            border: none;
            padding: 18px 28px;
            font-size: 14px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: background 180ms;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-btn:hover {
            background: var(--muted-gold);
        }

        /* ── SECTIONS ── */
        .section {
            padding: 88px 32px;
        }

        .section-eyebrow {
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--luxury-gold);
            margin-bottom: 12px;
        }

        .section-heading {
            font-size: clamp(28px, 3.5vw, 44px);
            font-weight: 600;
            color: var(--charcoal);
            line-height: 1.15;
            margin-bottom: 8px;
        }

        .section-sub {
            font-size: 15px;
            color: var(--muted-text);
            line-height: 1.65;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* ── ROOMS ── */
        #rooms {
            background: var(--warm-cream);
        }

        .filter-chips {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .chip {
            padding: 7px 16px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid var(--border-grey);
            background: #fff;
            cursor: pointer;
            transition: all 180ms;
            color: var(--muted-text);
        }

        .chip.active,
        .chip:hover {
            background: var(--charcoal);
            color: #fff;
            border-color: var(--charcoal);
        }

        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 32px;
        }

        .room-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--border-grey);
            overflow: hidden;
            transition: box-shadow 200ms, transform 200ms;
            display: flex;
            flex-direction: column;
        }

        .room-card:hover {
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .room-card.unavailable {
            opacity: 0.55;
            pointer-events: none;
        }

        .room-img-wrap {
            position: relative;
            aspect-ratio: 4/3;
            overflow: hidden;
        }

        .room-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 300ms;
        }

        .room-card:hover .room-img-wrap img {
            transform: scale(1.04);
        }

        .room-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 10px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
        }

        .badge-popular {
            background: var(--luxury-gold);
            color: var(--charcoal);
        }

        .badge-new {
            background: #10b981;
            color: #fff;
        }

        .badge-limited {
            background: #f59e0b;
            color: #fff;
        }

        .badge-unavailable {
            background: #ef4444;
            color: #fff;
        }

        .capacity-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(18, 22, 26, 0.75);
            color: #fff;
            padding: 4px 10px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 500;
            backdrop-filter: blur(4px);
        }

        .room-body {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .room-type-num {
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted-text);
            margin-bottom: 6px;
        }

        .room-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--charcoal);
            margin-bottom: 4px;
        }

        .room-price {
            font-size: 20px;
            font-weight: 700;
            color: var(--charcoal);
            margin-bottom: 16px;
        }

        .room-price span {
            font-size: 13px;
            font-weight: 400;
            color: var(--muted-text);
        }

        .amenity-pills {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .pill {
            padding: 4px 10px;
            background: var(--off-white);
            border: 1px solid var(--border-grey);
            border-radius: 100px;
            font-size: 11px;
            font-weight: 500;
            color: var(--muted-text);
        }

        .room-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid var(--border-grey);
            padding-top: 16px;
            margin-top: auto;
        }

        .deposit-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--muted-text);
            margin-bottom: 2px;
        }

        .deposit-amount {
            font-size: 14px;
            font-weight: 700;
            color: var(--charcoal);
        }

        .btn-reserve {
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
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-reserve:hover {
            background: var(--luxury-gold);
            color: var(--charcoal);
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
        }

        /* ── DINING ── */
        #dining {
            background: #fff;
        }

        .dining-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 40px;
        }

        .dining-card {
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid var(--border-grey);
            transition: box-shadow 200ms;
            background: #fff;
        }

        .dining-card:hover {
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.1);
        }

        .dining-img {
            aspect-ratio: 3/2;
            overflow: hidden;
        }

        .dining-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 300ms;
        }

        .dining-card:hover .dining-img img {
            transform: scale(1.05);
        }

        .dining-body {
            padding: 20px;
        }

        .dining-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .dining-desc {
            font-size: 14px;
            color: var(--muted-text);
            line-height: 1.6;
        }

        .dining-hours {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--luxury-gold);
            font-weight: 600;
            margin-top: 12px;
            background: rgba(212, 175, 55, 0.08);
            padding: 4px 10px;
            border-radius: 100px;
        }

        /* ── AMENITIES ── */
        #amenities {
            background: var(--warm-cream);
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 40px;
        }

        .amenity-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--border-grey);
            padding: 28px 24px;
            transition: all 200ms;
        }

        .amenity-card:hover {
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.09);
            transform: translateY(-2px);
        }

        .amenity-icon {
            font-size: 32px;
            margin-bottom: 14px;
        }

        .amenity-name {
            font-size: 17px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .amenity-desc {
            font-size: 14px;
            color: var(--muted-text);
            line-height: 1.6;
        }

        /* ── EVENTS ── */
        #events {
            background: #fff;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 40px;
        }

        .event-card {
            border-radius: 14px;
            overflow: hidden;
            position: relative;
            aspect-ratio: 4/3;
            cursor: pointer;
        }

        .event-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 300ms;
        }

        .event-card:hover img {
            transform: scale(1.06);
        }

        .event-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(18, 22, 26, 0.85) 0%, transparent 55%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 24px;
        }

        .event-name {
            font-size: 20px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 4px;
        }

        .event-desc {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.72);
        }

        /* ── REVIEWS ── */
        #reviews {
            background: var(--charcoal);
            padding: 88px 32px;
        }

        .review-card {
            background: var(--dark-secondary);
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 28px;
        }

        .review-stars {
            color: var(--luxury-gold);
            font-size: 16px;
            margin-bottom: 14px;
        }

        .review-text {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.82);
            line-height: 1.7;
            font-style: italic;
            margin-bottom: 18px;
        }

        .review-author {
            font-size: 13px;
            font-weight: 600;
            color: #fff;
        }

        .review-date {
            font-size: 12px;
            color: var(--muted-text);
            margin-top: 2px;
        }

        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 32px;
        }

        .submit-review-card {
            background: var(--dark-secondary);
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 32px;
            margin-top: 48px;
        }

        /* ── CONTACT ── */
        #contact {
            background: var(--warm-cream);
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            margin-top: 40px;
        }

        .contact-info-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 20px;
        }

        .contact-icon {
            font-size: 18px;
            margin-top: 2px;
            color: var(--luxury-gold);
        }

        /* ── FOOTER ── */
        footer {
            background: var(--charcoal);
            color: rgba(255, 255, 255, 0.5);
            padding: 32px;
            text-align: center;
            font-size: 13px;
        }

        /* ── MODAL ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-overlay.open {
            display: flex;
        }

        .modal-box {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 580px;
            max-height: 92vh;
            overflow-y: auto;
            animation: modalIn 0.22s ease;
        }

        @keyframes modalIn {
            from {
                transform: scale(0.94) translateY(20px);
                opacity: 0;
            }

            to {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 22px 26px;
            border-bottom: 1px solid var(--border-grey);
        }

        .modal-close {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--off-white);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: background 150ms;
            color: var(--charcoal);
        }

        .modal-close:hover {
            background: var(--border-grey);
        }

        .modal-body {
            padding: 26px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--muted-text);
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border-grey);
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: var(--charcoal);
            background: #fff;
            outline: none;
            transition: border-color 180ms, box-shadow 180ms;
        }

        .form-input:focus {
            border-color: var(--luxury-gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.12);
        }

        .form-input::placeholder {
            color: #aaa;
        }

        .upload-area {
            border: 2px dashed var(--border-grey);
            border-radius: 10px;
            padding: 28px;
            text-align: center;
            cursor: pointer;
            transition: border-color 180ms;
            position: relative;
        }

        .upload-area:hover {
            border-color: var(--luxury-gold);
        }

        .upload-area input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-preview {
            max-height: 120px;
            border-radius: 8px;
            margin-top: 10px;
            object-fit: cover;
        }

        .booking-success {
            text-align: center;
            padding: 32px 24px;
        }

        .booking-ref {
            font-family: 'JetBrains Mono', monospace;
            font-size: 22px;
            font-weight: 700;
            color: var(--luxury-gold);
            letter-spacing: 0.06em;
            background: rgba(212, 175, 55, 0.08);
            border: 1px solid rgba(212, 175, 55, 0.25);
            display: inline-block;
            padding: 10px 24px;
            border-radius: 10px;
            margin: 12px 0;
        }

        /* ── TOAST ── */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            min-width: 280px;
            padding: 14px 20px;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
            animation: slideInRight 0.25s ease;
        }

        .toast-success {
            background: #1a2e1a;
            color: #6fcf6f;
            border: 1px solid #2d5a2d;
        }

        .toast-error {
            background: #2e1a1a;
            color: #cf6f6f;
            border: 1px solid #5a2d2d;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(120%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* ── SKELETON ── */
        .skeleton {
            background: linear-gradient(90deg, #e8e8e8 25%, #f0f0f0 50%, #e8e8e8 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 6px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0
            }

            100% {
                background-position: 200% 0
            }
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 1024px) {

            .rooms-grid,
            .dining-grid,
            .amenities-grid,
            .events-grid {
                grid-template-columns: 1fr 1fr;
            }

            .reviews-grid {
                grid-template-columns: 1fr;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .site-header {
                padding: 0 20px;
            }

            .nav-links {
                display: none;
            }

            .hamburger {
                display: flex;
            }

            .hero-section {
                padding: 100px 20px 60px;
            }

            .search-bar {
                flex-direction: column;
            }

            .search-field {
                border-right: none;
                border-bottom: 1px solid var(--border-grey);
            }

            .search-btn {
                justify-content: center;
            }

            .rooms-grid,
            .dining-grid,
            .amenities-grid,
            .events-grid {
                grid-template-columns: 1fr;
            }

            .section {
                padding: 60px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .mobile-menu {
                display: block;
                position: fixed;
                top: 68px;
                left: 0;
                right: 0;
                background: var(--charcoal);
                padding: 16px 20px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                z-index: 99;
            }

            .mobile-menu a {
                display: block;
                color: rgba(255, 255, 255, 0.8);
                text-decoration: none;
                font-size: 15px;
                font-weight: 500;
                padding: 12px 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            }
        }
    </style>
</head>

<body>
    <?php if (lab_mode()): ?>
        <div style="background:#991b1b;color:white;padding:12px;text-align:center">Classroom lab mode: SQL injection and unlimited login attempts enabled on localhost.</div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════════
     STICKY HEADER
══════════════════════════════════════════════════════════════ -->
    <header class="site-header" id="site-header">
        <a href="index.php" class="logo-group">
            <div class="logo-icon">🏨</div>
            <div class="logo-text">
                <span>InnPulse</span>
                <span>Boutique</span>
            </div>
        </a>

        <nav class="nav-links" id="nav-links">
            <a href="#rooms">Rooms</a>
            <a href="#dining">Dining</a>
            <a href="#amenities">Amenities</a>
            <a href="#events">Events</a>
            <a href="#contact">Contact</a>
        </nav>

        <div style="display:flex;align-items:center;gap:12px">
            <button class="btn-login" onclick="openLoginModal()">Login</button>
            <div class="hamburger" id="hamburger" onclick="toggleMobileMenu()">
                <span></span><span></span><span></span>
            </div>
        </div>
    </header>

    <!-- Mobile menu -->
    <div class="mobile-menu" id="mobile-menu" style="display:none">
        <a href="#rooms" onclick="closeMobileMenu()">Rooms</a>
        <a href="#dining" onclick="closeMobileMenu()">Dining</a>
        <a href="#amenities" onclick="closeMobileMenu()">Amenities</a>
        <a href="#events" onclick="closeMobileMenu()">Events</a>
        <a href="#contact" onclick="closeMobileMenu()">Contact</a>
        <a href="#" onclick="closeMobileMenu();openLoginModal();"
            style="color:var(--luxury-gold);border-bottom:none">Login</a>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
     HERO SECTION
══════════════════════════════════════════════════════════════ -->
    <section class="hero-section" id="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <span class="hero-eyebrow">✦ Boutique Hospitality Redefined</span>
            <h1 class="hero-heading">
                Reserve Your<br>
                <span class="gold">Perfect Stay</span>
            </h1>
            <p class="hero-subtitle">
                Each room curated for discerning travellers who appreciate<br>
                the art of genuine luxury.
            </p>

            <!-- Search Bar -->
            <div class="search-bar">
                <div class="search-field">
                    <span class="search-label">Property / Branch</span>
                    <select class="search-select" id="search-property">
                        <option value="">InnPulse Boutique Hotel</option>
                        <option value="main">Main Tower</option>
                        <option value="garden">Garden Wing</option>
                        <option value="penthouse">Penthouse Level</option>
                    </select>
                </div>
                <div class="search-field">
                    <span class="search-label">Check-In</span>
                    <input type="date" class="search-input" id="search-checkin" />
                </div>
                <div class="search-field">
                    <span class="search-label">Check-Out</span>
                    <input type="date" class="search-input" id="search-checkout" />
                </div>
                <div class="search-field">
                    <span class="search-label">Room Type</span>
                    <select class="search-select" id="search-type">
                        <option value="">Any Type</option>
                        <option value="Suite">Suite</option>
                        <option value="Penthouse">Penthouse</option>
                        <option value="Loft">Loft</option>
                        <option value="Classic">Classic</option>
                        <option value="Studio">Studio</option>
                    </select>
                </div>
                <button class="search-btn" onclick="filterRooms()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    Search Rooms
                </button>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════
     AVAILABLE ROOMS
══════════════════════════════════════════════════════════════ -->
    <section id="rooms" class="section">
        <div class="container">
            <div
                style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:32px">
                <div>
                    <p class="section-eyebrow">✦ Our Collection</p>
                    <h2 class="section-heading">Available Rooms</h2>
                    <p class="section-sub" id="rooms-count">Loading rooms...</p>
                </div>
                <div class="filter-chips" id="filter-chips">
                    <span class="chip active" data-filter="All">All</span>
                    <span class="chip" data-filter="Suite">Suite</span>
                    <span class="chip" data-filter="Penthouse">Penthouse</span>
                    <span class="chip" data-filter="Loft">Loft</span>
                    <span class="chip" data-filter="Classic">Classic</span>
                    <span class="chip" data-filter="Studio">Studio</span>
                </div>
            </div>

            <!-- Skeleton loading -->
            <div class="rooms-grid" id="rooms-skeleton" style="display:none">
                <div class="room-card" style="height:420px">
                    <div style="background:#e8e8e8;height:200px"></div>
                    <div style="padding:20px">
                        <div class="skeleton" style="height:12px;width:60%;margin-bottom:10px"></div>
                        <div class="skeleton" style="height:20px;width:80%;margin-bottom:16px"></div>
                        <div class="skeleton" style="height:32px;width:40%"></div>
                    </div>
                </div>
            </div>

            <div class="rooms-grid" id="rooms-grid"></div>

            <div id="rooms-empty" style="display:none;text-align:center;padding:64px 0">
                <p style="font-size:40px;margin-bottom:12px">🔍</p>
                <h3 style="font-family:'Playfair Display',serif;font-size:22px;margin-bottom:8px">No rooms found</h3>
                <p style="color:var(--muted-text);font-size:14px">Try adjusting your search or filter criteria.</p>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════
     DINING
══════════════════════════════════════════════════════════════ -->
    <section id="dining" class="section">
        <div class="container">
            <div style="text-align:center;margin-bottom:16px">
                <p class="section-eyebrow">✦ Culinary Experiences</p>
                <h2 class="section-heading">Dining & Cuisine</h2>
                <p class="section-sub">Indulge in thoughtfully crafted menus from our award-winning culinary team.</p>
            </div>
            <div class="dining-grid">
                <div class="dining-card">
                    <div class="dining-img">
                        <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&q=80"
                            alt="Fine Dining" loading="lazy" />
                    </div>
                    <div class="dining-body">
                        <h3 class="dining-name">The Atelier — Fine Dining</h3>
                        <p class="dining-desc">A refined tasting menu experience featuring locally sourced seasonal
                            ingredients, curated wine pairings, and impeccable service in an intimate setting.</p>
                        <span class="dining-hours">🕯 Dinner: 6:30 PM — 11:00 PM</span>
                    </div>
                </div>
                <div class="dining-card">
                    <div class="dining-img">
                        <img src="https://images.unsplash.com/photo-1554118811-1e0d58224f24?w=600&q=80" alt="Lobby Café"
                            loading="lazy" />
                    </div>
                    <div class="dining-body">
                        <h3 class="dining-name">Lobby Café & Lounge</h3>
                        <p class="dining-desc">Artisan coffees, fresh pastries, and light fare in an elegant setting.
                            Perfect for morning meetings or a relaxed afternoon break.</p>
                        <span class="dining-hours">☕ Daily: 7:00 AM — 8:00 PM</span>
                    </div>
                </div>
                <div class="dining-card">
                    <div class="dining-img">
                        <img src="https://images.unsplash.com/photo-1551632436-cbf8dd35adfa?w=600&q=80"
                            alt="Rooftop Dining" loading="lazy" />
                    </div>
                    <div class="dining-body">
                        <h3 class="dining-name">Rooftop — Sky Terrace</h3>
                        <p class="dining-desc">Open-air dining above the city skyline. Tapas, handcrafted cocktails, and
                            a curated wine list with breathtaking panoramic views.</p>
                        <span class="dining-hours">🌙 Evenings: 5:00 PM — 1:00 AM</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════
     AMENITIES
══════════════════════════════════════════════════════════════ -->
    <section id="amenities" class="section">
        <div class="container">
            <div style="margin-bottom:16px">
                <p class="section-eyebrow">✦ Hotel Facilities</p>
                <h2 class="section-heading">Amenities & Services</h2>
                <p class="section-sub">Everything you need for a perfect stay, curated to the highest standard.</p>
            </div>
            <div class="amenities-grid">
                <div class="amenity-card">
                    <div class="amenity-icon">🏊</div>
                    <h3 class="amenity-name">Swimming Pool</h3>
                    <p class="amenity-desc">Heated infinity pool with city views. Open daily 6:00 AM – 10:00 PM.
                        Poolside service available.</p>
                </div>
                <div class="amenity-card">
                    <div class="amenity-icon">🏋️</div>
                    <h3 class="amenity-name">Fitness Center</h3>
                    <p class="amenity-desc">State-of-the-art equipment, personal trainers on request. Open 24 hours for
                        hotel guests.</p>
                </div>
                <div class="amenity-card">
                    <div class="amenity-icon">🧖</div>
                    <h3 class="amenity-name">Spa & Wellness</h3>
                    <p class="amenity-desc">Full-service spa with signature treatments, massages, sauna, and steam room.
                        Book in advance.</p>
                </div>
                <div class="amenity-card">
                    <div class="amenity-icon">⚡</div>
                    <h3 class="amenity-name">High-Speed Wi-Fi</h3>
                    <p class="amenity-desc">Complimentary ultra-fast fiber connectivity throughout all rooms and public
                        areas. 1 Gbps.</p>
                </div>
                <div class="amenity-card">
                    <div class="amenity-icon">🎩</div>
                    <h3 class="amenity-name">Concierge Service</h3>
                    <p class="amenity-desc">24/7 personal concierge for reservations, travel arrangements, and bespoke
                        guest experiences.</p>
                </div>
                <div class="amenity-card">
                    <div class="amenity-icon">🛋️</div>
                    <h3 class="amenity-name">Private Lounge</h3>
                    <p class="amenity-desc">Exclusive residents' lounge with curated reading selections, complimentary
                        refreshments, and a quiet atmosphere.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════
     EVENTS
══════════════════════════════════════════════════════════════ -->
    <section id="events" class="section">
        <div class="container">
            <div style="margin-bottom:16px">
                <p class="section-eyebrow">✦ Private Events</p>
                <h2 class="section-heading">Venues & Events</h2>
                <p class="section-sub">Sophisticated spaces for your most important moments.</p>
            </div>
            <div class="events-grid">
                <div class="event-card">
                    <img src="https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=700&q=80"
                        alt="Wedding Reception" loading="lazy" />
                    <div class="event-overlay">
                        <h3 class="event-name">Wedding Reception</h3>
                        <p class="event-desc">Intimate ceremonies &amp; grand celebrations</p>
                    </div>
                </div>
                <div class="event-card">
                    <img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=700&q=80"
                        alt="Corporate Events" loading="lazy" />
                    <div class="event-overlay">
                        <h3 class="event-name">Corporate Events</h3>
                        <p class="event-desc">Boardrooms, conferences &amp; team retreats</p>
                    </div>
                </div>
                <div class="event-card">
                    <img src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=700&q=80"
                        alt="Private Celebrations" loading="lazy" />
                    <div class="event-overlay">
                        <h3 class="event-name">Private Celebrations</h3>
                        <p class="event-desc">Birthdays, anniversaries &amp; galas</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════
     GUEST REVIEWS
══════════════════════════════════════════════════════════════ -->
    <section id="reviews" style="background:var(--charcoal);padding:88px 32px">
        <div class="container">
            <div style="text-align:center;margin-bottom:16px">
                <p
                    style="font-family:'Inter',sans-serif;font-size:11px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:var(--luxury-gold);margin-bottom:12px">
                    ✦ Guest Voices</p>
                <h2
                    style="font-family:'Playfair Display',serif;font-size:clamp(28px,3vw,42px);color:#fff;margin-bottom:8px">
                    Recent Reviews</h2>
                <p style="color:rgba(255,255,255,0.5);font-size:15px">Authentic experiences from our guests.</p>
            </div>

            <div class="reviews-grid" id="reviews-grid">
                <!-- Populated by JS -->
                <div class="review-card">
                    <div class="review-stars">★★★★★</div>
                    <p class="review-text">"Exceptional service and impeccable attention to detail. The suite exceeded
                        every expectation — truly a world-class experience."</p>
                    <p class="review-author">Charlotte B.</p>
                    <p class="review-date">Aug 2026</p>
                </div>
                <div class="review-card">
                    <div class="review-stars">★★★★★</div>
                    <p class="review-text">"From the moment we arrived, the team made us feel like royalty. The rooftop
                        terrace at sunset is absolutely unforgettable."</p>
                    <p class="review-author">James L.</p>
                    <p class="review-date">Aug 2026</p>
                </div>
                <div class="review-card">
                    <div class="review-stars">★★★★☆</div>
                    <p class="review-text">"Beautiful property, immaculate rooms, and the concierge team went above and
                        beyond. Will absolutely return for our anniversary."</p>
                    <p class="review-author">Priya M.</p>
                    <p class="review-date">Jul 2026</p>
                </div>
            </div>

            <!-- Submit Review Form -->
            <div class="submit-review-card">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start">
                    <div>
                        <h3 style="font-family:'Playfair Display',serif;font-size:22px;color:#fff;margin-bottom:8px">
                            Share Your Experience</h3>
                        <p style="color:rgba(255,255,255,0.5);font-size:14px;line-height:1.6">We value your honest
                            feedback. Help other guests discover the InnPulse experience.</p>
                    </div>
                    <form id="review-form" onsubmit="submitReview(event)">
                        <div class="form-group">
                            <label class="form-label" style="color:rgba(255,255,255,0.5)">Your Name</label>
                            <input type="text" class="form-input" id="review-name" placeholder="Full name" required
                                style="background:rgba(255,255,255,0.06);border-color:rgba(255,255,255,0.1);color:#fff" />
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="color:rgba(255,255,255,0.5)">Rating</label>
                            <div id="star-rating" style="display:flex;gap:6px;margin-bottom:4px">
                                <span class="star-btn" data-val="1" onclick="setRating(1)">★</span>
                                <span class="star-btn" data-val="2" onclick="setRating(2)">★</span>
                                <span class="star-btn" data-val="3" onclick="setRating(3)">★</span>
                                <span class="star-btn" data-val="4" onclick="setRating(4)">★</span>
                                <span class="star-btn" data-val="5" onclick="setRating(5)">★</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="color:rgba(255,255,255,0.5)">Describe your stay</label>
                            <textarea class="form-input" id="review-text" rows="3"
                                placeholder="Tell us about your experience..." required
                                style="background:rgba(255,255,255,0.06);border-color:rgba(255,255,255,0.1);color:#fff;resize:vertical"></textarea>
                        </div>
                        <button type="submit" id="review-btn" class="btn-gold" style="width:100%">Submit Review</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════
     CONTACT
══════════════════════════════════════════════════════════════ -->
    <section id="contact" class="section">
        <div class="container">
            <div style="margin-bottom:16px">
                <p class="section-eyebrow">✦ Get In Touch</p>
                <h2 class="section-heading">Contact Us</h2>
            </div>
            <div class="contact-grid">
                <div>
                    <div class="contact-info-item">
                        <span class="contact-icon">📍</span>
                        <div>
                            <p style="font-weight:600;margin-bottom:2px">InnPulse Boutique Hotel</p>
                            <p style="color:var(--muted-text);font-size:14px;line-height:1.6">12 Harbourview
                                Crescent<br>City Centre, Metropolitan District 10001</p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <span class="contact-icon">📞</span>
                        <div>
                            <p style="font-weight:600;margin-bottom:2px">Reservations</p>
                            <p style="color:var(--muted-text);font-size:14px">+1 (555) 800-INN1</p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <span class="contact-icon">✉️</span>
                        <div>
                            <p style="font-weight:600;margin-bottom:2px">Email</p>
                            <p style="color:var(--muted-text);font-size:14px">reservations@innpulse.demo</p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <span class="contact-icon">🕐</span>
                        <div>
                            <p style="font-weight:600;margin-bottom:2px">Check-In / Check-Out</p>
                            <p style="color:var(--muted-text);font-size:14px">Check-in: 3:00 PM · Check-out: 11:00 AM
                            </p>
                        </div>
                    </div>
                </div>
                <div>
                    <form onsubmit="submitContact(event)">
                        <div class="form-group">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-input" placeholder="Your full name" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-input" placeholder="your@email.com" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Message</label>
                            <textarea class="form-input" rows="4" placeholder="How can we assist you?" required
                                style="resize:vertical"></textarea>
                        </div>
                        <button type="submit" class="btn-gold" style="width:100%">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════════════════ -->
    <footer>
        <div style="display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:12px">
            <div
                style="width:28px;height:28px;background:var(--luxury-gold);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:14px">
                🏨</div>
            <span style="font-family:'Playfair Display',serif;color:#fff;font-size:16px">InnPulse Boutique</span>
        </div>
        <p>© 2026 InnPulse Boutique Hotel. All rights reserved.</p>
        <p style="margin-top:6px;font-size:11px;color:rgba(255,255,255,0.3)">⚠ Educational / Demonstration Environment —
            Synthetic Data Only</p>
    </footer>

    <!-- ══════════════════════════════════════════════════════════════
     RESERVATION MODAL
══════════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="reserve-modal">
        <div class="modal-box" id="reserve-modal-box">
            <div class="modal-header">
                <div>
                    <h3 style="font-family:'Playfair Display',serif;font-size:20px;font-weight:600">Reserve Your Room
                    </h3>
                    <p style="font-size:13px;color:var(--muted-text);margin-top:2px" id="modal-room-label">Loading room
                        details...</p>
                </div>
                <button class="modal-close" onclick="closeReserveModal()">✕</button>
            </div>

            <!-- Room preview -->
            <div style="padding:0 26px 0">
                <div
                    style="display:grid;grid-template-columns:140px 1fr;gap:16px;padding:16px 0;border-bottom:1px solid var(--border-grey)">
                    <img id="modal-room-img" src="" alt=""
                        style="width:140px;height:95px;object-fit:cover;border-radius:10px;background:#eee" />
                    <div>
                        <p style="font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted-text);margin-bottom:4px"
                            id="modal-room-type"></p>
                        <p style="font-size:17px;font-weight:600;margin-bottom:4px" id="modal-room-name"></p>
                        <p style="font-size:18px;font-weight:700;color:var(--charcoal)" id="modal-room-rate"></p>
                        <p style="font-size:12px;color:var(--muted-text)">Deposit: <span id="modal-room-deposit"
                                style="font-weight:600;color:var(--charcoal)"></span></p>
                    </div>
                </div>
            </div>

            <!-- Booking form -->
            <div class="modal-body" id="reserve-form-wrap">
                <form id="reserve-form" onsubmit="submitReservation(event)">
                    <input type="hidden" id="res-room-id" />
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-input" id="res-name" placeholder="Margaret Ashworth"
                                required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-input" id="res-email" placeholder="guest@email.com"
                                required />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-input" id="res-phone" placeholder="+1 555 000 0000" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Number of Guests</label>
                            <input type="number" class="form-input" id="res-guests" min="1" max="6" value="1" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Check-In Date *</label>
                            <input type="date" class="form-input" id="res-checkin" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Check-Out Date *</label>
                            <input type="date" class="form-input" id="res-checkout" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Special Requests</label>
                        <textarea class="form-input" id="res-requests" rows="2"
                            placeholder="Late check-in, dietary requirements, etc." style="resize:vertical"></textarea>
                    </div>
                    <button type="submit" class="btn-gold" id="reserve-submit-btn"
                        style="width:100%;padding:14px;font-size:15px;border-radius:10px">
                        Confirm Reservation →
                    </button>
                </form>
            </div>

            <!-- Success state -->
            <div class="booking-success" id="booking-success" style="display:none">
                <!-- Congratulations popup banner -->
                <div
                    style="background:linear-gradient(135deg,#12161A 0%,#1C2228 100%);border-radius:12px;padding:20px 24px;margin-bottom:24px;border:1px solid rgba(212,175,55,0.3)">
                    <div style="font-size:36px;margin-bottom:8px">🎉</div>
                    <p
                        style="font-size:11px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:var(--luxury-gold);margin-bottom:6px">
                        Congratulations!</p>
                    <h3
                        style="font-family:'Playfair Display',serif;font-size:22px;font-weight:600;color:#fff;margin-bottom:4px">
                        Successful Reservation</h3>
                    <p style="font-size:13px;color:rgba(255,255,255,0.6)">for InnPulse Boutique Hotel</p>
                </div>
                <p style="color:var(--muted-text);font-size:14px;margin-bottom:12px">Your booking reference number is:
                </p>
                <div class="booking-ref" id="booking-ref-display"></div>
                <p style="color:var(--muted-text);font-size:13px;margin-top:14px;line-height:1.6">
                    Please save this reference number. Our team will reach out to confirm your stay.
                </p>
                <button onclick="closeReserveModal()" class="btn-gold"
                    style="margin-top:22px;padding:12px 36px;font-size:15px">Close</button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════════ -->
    <script type="module">
        import api, {
            request
        } from './js/api.js';

        // ── State ────────────────────────────────────────────────────────────────────
        let allRooms = [];
        let activeFilter = 'All';
        let selectedRoom = null;
        let selectedRating = 0;

        // ── Initialize ───────────────────────────────────────────────────────────────
        async function init() {
            setDefaultDates();
            await loadRooms();
            await loadReviews();
        }

        function setDefaultDates() {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            const fmt = d => d.toISOString().split('T')[0];
            document.getElementById('search-checkin').value = fmt(today);
            document.getElementById('search-checkout').value = fmt(tomorrow);
            document.getElementById('res-checkin').value = fmt(today);
            document.getElementById('res-checkout').value = fmt(tomorrow);
        }

        // ── Load Rooms ───────────────────────────────────────────────────────────────
        async function loadRooms() {
            document.getElementById('rooms-skeleton').style.display = 'grid';
            const {
                data,
                error
            } = await api.from('rooms').select('*').order('room_number');
            document.getElementById('rooms-skeleton').style.display = 'none';
            if (error || !data) {
                showToast('Unable to load rooms from SQLite.', 'error');
                return;
            }
            allRooms = data;
            renderRooms(allRooms);
        }

        function renderFallbackRooms() {
            allRooms = [];
            showToast('Unable to load database records.', 'error');
            renderRooms(allRooms);
        }

        function renderRooms(rooms) {
            const grid = document.getElementById('rooms-grid');
            const empty = document.getElementById('rooms-empty');
            const count = document.getElementById('rooms-count');
            const available = rooms.filter(r => r.status === 'available').length;

            if (!rooms.length) {
                grid.innerHTML = '';
                grid.style.display = 'none';
                empty.style.display = 'block';
                count.textContent = 'No rooms match your search.';
                return;
            }
            empty.style.display = 'none';
            grid.style.display = 'grid';
            count.textContent = `${rooms.length} room${rooms.length !== 1 ? 's' : ''} found · ${available} available`;

            grid.innerHTML = rooms.map(room => roomCardHTML(room)).join('');
        }

        function roomCardHTML(room) {
            const isUnavail = room.status !== 'available';
            const badges = {
                available: '',
                reserved: '',
                occupied: '',
                maintenance: ''
            };
            const badgeMap = {
                available: 'badge-popular',
                reserved: 'badge-limited',
                occupied: 'badge-unavailable',
                maintenance: 'badge-unavailable'
            };
            const badgeLabel = {
                available: 'Most Popular',
                reserved: 'Reserved',
                occupied: 'Unavailable',
                maintenance: 'Maintenance'
            };
            const showBadge = room.status !== 'available' || room.is_featured;

            const amenities = Array.isArray(room.amenities) ? room.amenities : (room.amenities || []);
            const amenityPills = amenities.slice(0, 3).map(a => `<span class="pill">${a}</span>`).join('');

            const imgSrc = room.image_url || getDefaultRoomImage(room.room_type);
            const price = room.nightly_rate ? `$${Number(room.nightly_rate).toLocaleString()}` : '$—';
            const deposit = room.deposit_amount ? `$${Number(room.deposit_amount).toLocaleString()}` : '$—';

            return `
  <div class="room-card${isUnavail ? ' unavailable' : ''}">
    <div class="room-img-wrap">
      <img src="${imgSrc}" alt="${room.room_name}" loading="lazy"
        onerror="this.src='https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&q=80'" />
      ${showBadge ? `<span class="room-badge ${badgeMap[room.status]}">${badgeLabel[room.status]}</span>` : ''}
      <span class="capacity-badge">👤 ${room.capacity || 2} guests</span>
    </div>
    <div class="room-body">
      <p class="room-type-num">${room.room_type?.toUpperCase() || 'ROOM'} · #${room.room_number}</p>
      <h3 class="room-name">${room.room_name}</h3>
      <p class="room-price">${price}<span>/night</span></p>
      <div class="amenity-pills">${amenityPills || '<span class="pill">Premium Room</span>'}</div>
      <div class="room-footer">
        <div>
          <p class="deposit-label">Deposit</p>
          <p class="deposit-amount">${deposit}</p>
        </div>
        <button class="btn-reserve" onclick="openReserveModal('${room.id}')"
          ${isUnavail ? 'disabled' : ''}>
          ${isUnavail ? 'Unavailable' : 'Reserve →'}
        </button>
      </div>
    </div>
  </div>`;
        }

        // ── Filter logic ──────────────────────────────────────────────────────────────
        window.filterRooms = function() {
            const type = document.getElementById('search-type').value;
            const checkin = document.getElementById('search-checkin').value;
            const checkout = document.getElementById('search-checkout').value;
            activeFilter = type || 'All';
            updateChips(activeFilter);
            let filtered = allRooms;
            if (type) filtered = filtered.filter(r => r.room_type === type);
            renderRooms(filtered);
        };

        document.getElementById('filter-chips').addEventListener('click', (e) => {
            const chip = e.target.closest('.chip');
            if (!chip) return;
            activeFilter = chip.dataset.filter;
            updateChips(activeFilter);
            const filtered = activeFilter === 'All' ? allRooms : allRooms.filter(r => r.room_type === activeFilter);
            renderRooms(filtered);
        });

        function updateChips(active) {
            document.querySelectorAll('.chip').forEach(c => {
                c.classList.toggle('active', c.dataset.filter === active);
            });
        }

        // ── Reserve Modal ─────────────────────────────────────────────────────────────
        window.openReserveModal = function(roomId) {
            selectedRoom = allRooms.find(r => r.id == roomId || String(r.id) === String(roomId));
            if (!selectedRoom) return;

            document.getElementById('modal-room-label').textContent = `Room #${selectedRoom.room_number}`;
            document.getElementById('modal-room-img').src = selectedRoom.image_url || getDefaultRoomImage(selectedRoom.room_type);
            document.getElementById('modal-room-type').textContent = `${selectedRoom.room_type?.toUpperCase()} · #${selectedRoom.room_number}`;
            document.getElementById('modal-room-name').textContent = selectedRoom.room_name;
            document.getElementById('modal-room-rate').textContent = `$${Number(selectedRoom.nightly_rate).toLocaleString()}/night`;
            document.getElementById('modal-room-deposit').textContent = `$${Number(selectedRoom.deposit_amount).toLocaleString()}`;
            document.getElementById('res-room-id').value = selectedRoom.id;

            document.getElementById('reserve-form-wrap').style.display = 'block';
            document.getElementById('booking-success').style.display = 'none';
            document.getElementById('reserve-modal').classList.add('open');
            document.body.style.overflow = 'hidden';
        };

        window.closeReserveModal = function() {
            document.getElementById('reserve-modal').classList.remove('open');
            document.body.style.overflow = '';
            document.getElementById('reserve-form').reset();
        };

        document.getElementById('reserve-modal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) closeReserveModal();
        });

        window.previewReceipt = function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                const img = document.getElementById('receipt-preview');
                img.src = ev.target.result;
                img.style.display = 'block';
            };
            reader.readAsDataURL(file);
        };

        // ── Submit Reservation ────────────────────────────────────────────────────────
        window.submitReservation = async function(e) {
            e.preventDefault();
            const btn = document.getElementById('reserve-submit-btn');

            const name = document.getElementById('res-name').value.trim();
            const email = document.getElementById('res-email').value.trim();
            const phone = document.getElementById('res-phone').value.trim();
            const guests = document.getElementById('res-guests').value;
            const checkin = document.getElementById('res-checkin').value;
            const checkout = document.getElementById('res-checkout').value;
            const requests = document.getElementById('res-requests').value.trim();
            const roomId = document.getElementById('res-room-id').value;

            if (!name || !email || !checkin || !checkout || !roomId) {
                showToast('Please fill in all required fields.', 'error');
                return;
            }

            const bookingRef = `INP-${new Date().getFullYear()}-${Date.now().toString().slice(-6)}`;
            const guestId = `G-${String(Math.floor(Math.random() * 90000) + 10000)}`;

            if (checkout <= checkin) {
                showToast('Check-out must follow check-in.', 'error');
                return;
            }
            btn.disabled = true;
            const receiptImage = document.getElementById('receipt-preview');
            const {
                data,
                error
            } = await request({
                action: 'book',
                reservation: {
                    guest_name: name,
                    guest_email: email,
                    guest_phone: phone,
                    room_id: roomId,
                    check_in: checkin,
                    check_out: checkout,
                    guest_count: parseInt(guests) || 1,
                    special_requests: requests,
                },
                receipt: receiptImage?.src.startsWith('data:') ? receiptImage.src : null
            });
            btn.disabled = false;
            if (error) {
                showToast(error.message, 'error');
                return;
            }
            document.getElementById('booking-ref-display').textContent = data.booking_ref;
            document.getElementById('reserve-form-wrap').style.display = 'none';
            document.getElementById('booking-success').style.display = 'block';
            await loadRooms();
        };

        // ── Load Reviews ──────────────────────────────────────────────────────────────
        async function loadReviews() {
            const {
                data
            } = await api.from('reviews').select('*')
                .eq('approved', true).order('created_at', {
                    ascending: false
                }).limit(3);
            if (!data || !data.length) return;
            const grid = document.getElementById('reviews-grid');
            grid.innerHTML = data.map(r => `
    <div class="review-card">
      <div class="review-stars">${'★'.repeat(r.rating)}${'☆'.repeat(5 - r.rating)}</div>
      <p class="review-text">"${r.review_text}"</p>
      <p class="review-author">${r.guest_name}</p>
      <p class="review-date">${new Date(r.created_at).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })}</p>
    </div>`).join('');
        }

        // ── Submit Review ─────────────────────────────────────────────────────────────
        window.submitReview = async function(e) {
            e.preventDefault();
            if (!selectedRating) {
                showToast('Please select a star rating.', 'warning');
                return;
            }
            const name = document.getElementById('review-name').value.trim();
            const text = document.getElementById('review-text').value.trim();
            const btn = document.getElementById('review-btn');
            btn.disabled = true;
            btn.textContent = 'Submitting…';

            const {
                error
            } = await api.from('reviews').insert({
                guest_name: name,
                rating: selectedRating,
                review_text: text,
                approved: false,
            });

            if (error) {
                showToast('Could not submit review.', 'error');
            } else {
                showToast('Thank you for your review! It will appear once approved.', 'success');
                e.target.reset();
                setRating(0);
            }
            btn.disabled = false;
            btn.textContent = 'Submit Review';
        };

        // ── Star rating ───────────────────────────────────────────────────────────────
        window.setRating = function(val) {
            selectedRating = val;
            document.querySelectorAll('.star-btn').forEach((s, i) => {
                s.style.color = i < val ? '#D4AF37' : '#8B9198';
            });
        };

        // ── Contact form ──────────────────────────────────────────────────────────────
        window.submitContact = function(e) {
            e.preventDefault();
            showToast('Message sent! We\'ll be in touch shortly.', 'success');
            e.target.reset();
        };

        // ── Mobile menu ───────────────────────────────────────────────────────────────
        window.toggleMobileMenu = function() {
            const menu = document.getElementById('mobile-menu');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        };
        window.closeMobileMenu = function() {
            document.getElementById('mobile-menu').style.display = 'none';
        };

        // ── Helpers ───────────────────────────────────────────────────────────────────
        function generateBookingRef() {
            return `INP-${new Date().getFullYear()}-${String(Math.floor(Math.random() * 9000) + 1000)}`;
        }

        function generateGuestId() {
            return `G-${String(Math.floor(Math.random() * 9000) + 1000)}`;
        }

        function getDefaultRoomImage(type) {
            const imgs = {
                Suite: 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&q=80',
                Penthouse: 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=600&q=80',
                Loft: 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=600&q=80',
                Classic: 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=600&q=80',
                Studio: 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=600&q=80',
            };
            return imgs[type] || imgs.Suite;
        }

        function getFallbackRooms() {
            return [{
                    id: 'r1',
                    room_number: '101',
                    room_name: 'Grand Deluxe Suite',
                    room_type: 'Suite',
                    capacity: 2,
                    nightly_rate: 420,
                    deposit_amount: 150,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&q=80',
                    amenities: ['King Bed', 'Ocean View', 'Butler Service'],
                    is_featured: true
                },
                {
                    id: 'r2',
                    room_number: '102',
                    room_name: 'Classic Room',
                    room_type: 'Classic',
                    capacity: 2,
                    nightly_rate: 180,
                    deposit_amount: 60,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=600&q=80',
                    amenities: ['Queen Bed', 'City View', 'Free Wi-Fi']
                },
                {
                    id: 'r3',
                    room_number: '103',
                    room_name: 'Classic Room',
                    room_type: 'Classic',
                    capacity: 2,
                    nightly_rate: 180,
                    deposit_amount: 60,
                    status: 'occupied',
                    image_url: 'https://images.unsplash.com/photo-1566195992011-5f6b21e539aa?w=600&q=80',
                    amenities: ['Twin Beds', 'Garden View']
                },
                {
                    id: 'r4',
                    room_number: '115',
                    room_name: 'Classic Room',
                    room_type: 'Classic',
                    capacity: 2,
                    nightly_rate: 195,
                    deposit_amount: 65,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=600&q=80',
                    amenities: ['King Bed', 'Pool View']
                },
                {
                    id: 'r5',
                    room_number: '201',
                    room_name: 'Garden Loft',
                    room_type: 'Loft',
                    capacity: 3,
                    nightly_rate: 320,
                    deposit_amount: 110,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=600&q=80',
                    amenities: ['King Bed', 'Garden Access', 'Terrace']
                },
                {
                    id: 'r6',
                    room_number: '202',
                    room_name: 'Heritage Loft Room',
                    room_type: 'Loft',
                    capacity: 2,
                    nightly_rate: 280,
                    deposit_amount: 95,
                    status: 'reserved',
                    image_url: 'https://images.unsplash.com/photo-1560185893-a55cbc8c57e8?w=600&q=80',
                    amenities: ['Queen Bed', 'City View', 'Workspace']
                },
                {
                    id: 'r7',
                    room_number: '210',
                    room_name: 'Botanical Suite',
                    room_type: 'Suite',
                    capacity: 2,
                    nightly_rate: 380,
                    deposit_amount: 130,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=600&q=80',
                    amenities: ['King Bed', 'Botanical Garden View', 'Spa Bath']
                },
                {
                    id: 'r8',
                    room_number: '211',
                    room_name: 'Deluxe Suite',
                    room_type: 'Suite',
                    capacity: 3,
                    nightly_rate: 450,
                    deposit_amount: 160,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&q=80',
                    amenities: ['King Bed', 'Sea View', 'Private Lounge']
                },
                {
                    id: 'r9',
                    room_number: '218',
                    room_name: 'Signature Suite',
                    room_type: 'Suite',
                    capacity: 4,
                    nightly_rate: 620,
                    deposit_amount: 200,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1616594039964-ae9021a400a0?w=600&q=80',
                    amenities: ['Master Bed', 'Ocean Panorama', 'Butler Service'],
                    is_featured: true
                },
                {
                    id: 'r10',
                    room_number: '303',
                    room_name: 'Atelier Penthouse',
                    room_type: 'Penthouse',
                    capacity: 4,
                    nightly_rate: 980,
                    deposit_amount: 300,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=600&q=80',
                    amenities: ['2 Bedrooms', 'Panoramic View', 'Private Pool'],
                    is_featured: true
                },
                {
                    id: 'r11',
                    room_number: '401',
                    room_name: 'Studio',
                    room_type: 'Studio',
                    capacity: 1,
                    nightly_rate: 140,
                    deposit_amount: 50,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=600&q=80',
                    amenities: ['Double Bed', 'Compact Kitchen']
                },
                {
                    id: 'r12',
                    room_number: '405',
                    room_name: 'Library Studio',
                    room_type: 'Studio',
                    capacity: 2,
                    nightly_rate: 165,
                    deposit_amount: 55,
                    status: 'maintenance',
                    image_url: 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=600&q=80',
                    amenities: ['Queen Bed', 'Reading Nook']
                },
            ];
        }

        function showToast(msg, type = 'info') {
            const el = document.createElement('div');
            const icons = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };
            el.className = `toast toast-${type}`;
            el.innerHTML = `<span style="font-size:16px">${icons[type] || 'ℹ'}</span><span>${msg}</span>`;
            document.body.appendChild(el);
            setTimeout(() => {
                el.style.opacity = '0';
                el.style.transform = 'translateX(60px)';
                el.style.transition = 'all 0.3s';
                setTimeout(() => el.remove(), 300);
            }, 3500);
        }

        // ══════════════════════════════════════════════════════════════
        // LOGIN MODAL
        // ══════════════════════════════════════════════════════════════

        window.openLoginModal = async function() {
            const sessionProfile = await api.session();
            const stored = sessionProfile ? JSON.stringify(sessionProfile) : null;
            if (stored) {
                try {
                    const u = JSON.parse(stored);
                    window.location.href = u.role === 'admin' ? 'admin.php' : 'front-desk.php';
                    return;
                } catch (_) {
                    await api.logout();
                }
            }
            document.getElementById('login-modal-overlay').classList.add('open');
            document.body.style.overflow = 'hidden';
            setTimeout(() => document.getElementById('lm-email').focus(), 120);
        };

        window.closeLoginModal = function() {
            document.getElementById('login-modal-overlay').classList.remove('open');
            document.body.style.overflow = '';
            document.getElementById('lm-form').reset();
            document.getElementById('lm-error').style.display = 'none';
            document.querySelectorAll('.lm-input').forEach(i => i.classList.remove('lm-error'));
            const btn = document.getElementById('lm-submit-btn');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Sign In →';
            }
        };

        window.handleLoginOverlayClick = function(e) {
            if (e.target === document.getElementById('login-modal-overlay')) closeLoginModal();
        };

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeLoginModal();
        });

        window.handleLoginModal = async function(e) {
            e.preventDefault();
            const email = document.getElementById('lm-email').value.trim().toLowerCase();
            const password = document.getElementById('lm-password').value;
            const btn = document.getElementById('lm-submit-btn');

            document.getElementById('lm-error').style.display = 'none';
            document.querySelectorAll('.lm-input').forEach(i => i.classList.remove('lm-error'));

            if (!email || !password) {
                showLmError('Please enter your email and password.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="lm-spinner"></span>';

            const {
                data: account,
                error
            } = await api.login(email, password);
            if (error || !account) {
                showLmError(error?.message || 'Sign in failed.');
                btn.disabled = false;
                btn.textContent = 'Sign In →';
                return;
            }
            window.location.href = account.role === 'admin' ? 'admin.php' : 'front-desk.php';
        };

        function showLmError(msg) {
            document.getElementById('lm-error-text').textContent = msg;
            document.getElementById('lm-error').style.display = 'flex';
        }

        window.fillLmDemo = function(email, password) {
            document.getElementById('lm-email').value = email;
            document.getElementById('lm-password').value = password;
            document.getElementById('lm-error').style.display = 'none';
        };

        window.toggleLmPw = function() {
            const input = document.getElementById('lm-password');
            const btn = input.nextElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈';
            } else {
                input.type = 'password';
                btn.textContent = '👁';
            }
        };

        // On page load: auto-open if ?login=1, show Portal button if already logged in
        (async () => {
            if (new URLSearchParams(window.location.search).get('login') === '1') {
                history.replaceState({}, '', window.location.pathname);
                setTimeout(() => window.openLoginModal(), 300);
            }
            const sessionProfile = await api.session();
            const stored = sessionProfile ? JSON.stringify(sessionProfile) : null;
            if (stored) {
                try {
                    const u = JSON.parse(stored);
                    document.querySelectorAll('.btn-login').forEach(btn => {
                        btn.textContent = '→ Portal';
                        btn.onclick = () => {
                            window.location.href = u.role === 'admin' ? 'admin.php' : 'front-desk.php';
                        };
                    });
                } catch (_) {
                    await api.logout();
                }
            }
        })();

        init();
    </script>

    <!-- ══════════════════════════════════════════════════════════════
     LOGIN MODAL
══════════════════════════════════════════════════════════════ -->
    <div class="login-modal-overlay" id="login-modal-overlay" onclick="handleLoginOverlayClick(event)">
        <div class="login-modal" id="login-modal">
            <!-- Header -->
            <div class="lm-header">
                <div class="lm-header-left">
                    <p class="lm-eyebrow">Administrator Access</p>
                    <h2 class="lm-title">Sign In</h2>
                </div>
                <button class="lm-close" onclick="closeLoginModal()" aria-label="Close">✕</button>
            </div>

            <!-- Body -->
            <div class="lm-body">
                <!-- Error message -->
                <div class="lm-error-box" id="lm-error">
                    <span>⚠</span><span id="lm-error-text">Invalid email or password.</span>
                </div>

                <form id="lm-form" onsubmit="handleLoginModal(event)" novalidate>
                    <div class="lm-form-group">
                        <label class="lm-label" for="lm-email">Email</label>
                        <input type="email" class="lm-input" id="lm-email" placeholder="admin@innpulse.com"
                            autocomplete="email" required />
                    </div>
                    <div class="lm-form-group">
                        <label class="lm-label" for="lm-password">Password</label>
                        <div class="lm-input-wrap">
                            <input type="password" class="lm-input" id="lm-password" placeholder="••••••••••"
                                autocomplete="current-password" required style="padding-right:42px" />
                            <button type="button" class="lm-pw-toggle" onclick="toggleLmPw()"
                                aria-label="Toggle password">👁</button>
                        </div>
                    </div>
                    <button type="submit" class="lm-submit" id="lm-submit-btn">
                        Sign In →
                    </button>
                </form>
            </div>
        </div>
    </div>

</body>

</html>