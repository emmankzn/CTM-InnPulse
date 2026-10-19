import { escapeHTML, safeImage } from './security.js';

        import api from './api.js';

        // ── Auth gate ─────────────────────────────────────────────────────────────────
        async function init() {
            const sessionProfile = await api.session();
            const stored = sessionProfile ? JSON.stringify(sessionProfile) : null;
            if (!stored) { window.location.href = 'index.php?login=1'; return; }
            const sessionUser = JSON.parse(stored);
            if (sessionUser.role !== 'admin') { window.location.href = 'index.php?login=1'; return; }
            loadQuery('bookings');
            logAudit('Schema viewer accessed', sessionUser.email, sessionUser.username || sessionUser.email);
        }

        // ── Preset queries ────────────────────────────────────────────────────────────
        const queries = {
            bookings: {
                sql: `-- Active Bookings with Deposit Status
SELECT
  r.booking_ref,
  r.guest_name,
  r.guest_email,
  rooms.room_number,
  rooms.room_name,
  r.check_in,
  r.check_out,
  d.status  AS deposit_status,
  d.amount  AS deposit_amount
FROM   reservations r
JOIN   rooms
  ON   rooms.id = r.room_id
LEFT   JOIN deposits d
  ON   d.reservation_id = r.id
WHERE  r.status = 'confirmed'
ORDER  BY r.check_in ASC;`,
                rows: 6,
                time: '0.003s',
            },
            failed: {
                sql: `-- Failed Login Attempts (last 24h)
SELECT
  username,
  ip_address,
  event,
  severity,
  session_reference,
  occurred_at
FROM   audit_logs
WHERE  severity = 'WARN'
  AND  event LIKE '%failed login%'
  AND  occurred_at >= strftime('%Y-%m-%dT%H:%M:%fZ','now','-24 hours')
ORDER  BY occurred_at DESC;`,
                rows: 3,
                time: '0.005s',
            },
            schema: {
                sql: `-- SQLite Table + Column Overview
SELECT m.name AS table_name, p.name AS column_name,
       p.type AS data_type, p.dflt_value AS column_default
FROM sqlite_master m
JOIN pragma_table_info(m.name) p
WHERE m.type = 'table'
ORDER BY m.name, p.cid;`,
                rows: 52,
                time: '0.011s',
            },
            locked: {
                sql: `-- Currently Locked Accounts
SELECT
  id,
  username,
  email,
  role,
  locked,
  last_login
FROM   profiles
WHERE  locked = TRUE
ORDER  BY username;`,
                rows: 1,
                time: '0.002s',
            },
            revenue: {
                sql: `-- Revenue by Room Type (current month)
SELECT
  rooms.room_type,
  COUNT(r.id)           AS total_reservations,
  SUM(d.amount)         AS total_deposits,
  AVG(rooms.nightly_rate) AS avg_nightly_rate,
  SUM(
    rooms.nightly_rate *
    (julianday(r.check_out) - julianday(r.check_in))
  ) AS estimated_revenue
FROM   reservations r
JOIN   rooms
  ON   rooms.id = r.room_id
LEFT   JOIN deposits d
  ON   d.reservation_id = r.id
WHERE  r.check_in >= date('now','start of month')
GROUP  BY rooms.room_type
ORDER  BY estimated_revenue DESC;`,
                rows: 5,
                time: '0.008s',
            }
        };

        window.loadQuery = function (key) {
            document.querySelectorAll('.sql-chip').forEach(c => c.classList.remove('active'));
            const clicked = [...document.querySelectorAll('.sql-chip')].find(c =>
                c.textContent.toLowerCase().replace(/\s+/g, '').includes(key.toLowerCase().replace(/\s+/g, ''))
            );
            if (clicked) clicked.classList.add('active');

            const q = queries[key];
            if (!q) return;
            document.getElementById('sql-display').innerHTML = highlightSQL(q.sql);
            document.getElementById('metric-rows').textContent = 'Preview';
            document.getElementById('metric-time').textContent = 'Not executed';
        };

        function highlightSQL(sql) {
            const keywords = ['SELECT', 'FROM', 'WHERE', 'JOIN', 'LEFT', 'INNER', 'ON', 'ORDER BY', 'ORDER', 'GROUP BY', 'GROUP', 'AND', 'OR', 'AS', 'IN', 'WITH', 'HAVING', 'DISTINCT', 'COUNT', 'SUM', 'AVG', 'MIN', 'MAX', 'NULL', 'TRUE', 'FALSE', 'LIKE', 'INTERVAL', 'NOW', 'CURRENT_DATE', 'CURRENT_TIMESTAMP'];
            let result = escapeHTML(sql);
            keywords.forEach(kw => {
                result = result.replace(new RegExp(`\\b${kw}\\b`, 'g'),
                    `<span class="sql-keyword">${kw}</span>`);
            });
            result = result.replace(/--[^\n]*/g, m => `<span class="sql-comment">${m}</span>`);
            result = result.replace(/\b(reservations|rooms|deposits|profiles|audit_logs|reviews|system_config)\b/g,
                '<span class="sql-table">$1</span>');
            result = result.replace(/\b([a-z_]+)\s+AS\s+/gi, (_, a) => `<span class="sql-alias">${escapeHTML(a)}</span> AS `);
            return result;
        }


        async function logAudit(event, userId, username) {
            await api.from('audit_logs').insert({
                user_id: userId, username,
                ip_address: '127.0.0.1', event, severity: 'INFO',
                session_reference: `sess_demo_${Math.random().toString(36).slice(2, 8)}`,
                metadata: {}
            }).catch(() => { });
        }

        init();
    