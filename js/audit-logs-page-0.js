import { escapeHTML, safeImage } from './security.js';

        import api from './api.js';

        document.getElementById('export-audit').addEventListener('click', async () => {
            const result = await api.from('audit_logs').select('*').order('occurred_at', { ascending: false }).limit(1000);
            if (result.error) { window.alert(result.error.message); return; }
            const url = URL.createObjectURL(new Blob([JSON.stringify(result.data, null, 2)], { type: 'application/json' }));
            const link = document.createElement('a'); link.href = url; link.download = 'innpulse-security-events.json'; link.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        });

        let allLogs = [];
        let activeFilter = 'ALL';
        let currentUser = null, currentProfile = null;

        async function init() {
            const sessionProfile = await api.session();
            const stored = sessionProfile ? JSON.stringify(sessionProfile) : null;
            if (!stored) { window.location.href = 'index.php?login=1'; return; }
            const sessionUser = JSON.parse(stored);
            if (sessionUser.role !== 'admin') { window.location.href = 'index.php?login=1'; return; }
            currentUser = { id: sessionUser.id, email: sessionUser.email };
            currentProfile = sessionUser;
            document.getElementById('sidebar-username').textContent = sessionUser.username || sessionUser.email;
            await loadLogs();
            setInterval(() => { if (!document.hidden) loadLogs(); }, 15000);
        }

        window.loadLogs = async function () {
            const { data, error } = await api
                .from('audit_logs').select('*')
                .order('occurred_at', { ascending: false }).limit(200);
            allLogs = data || []; if (error) console.error(error.message);
            updateStats();
            renderLogs(allLogs);
        };

        function updateStats() {
            document.getElementById('stat-total').textContent = allLogs.length;
            document.getElementById('stat-warn').textContent = allLogs.filter(l => l.severity === 'WARN').length;
            document.getElementById('stat-crit').textContent = allLogs.filter(l => l.severity === 'CRITICAL').length;
        }

        function renderLogs(logs) {
            const stream = document.getElementById('event-stream');
            if (!logs.length) {
                stream.innerHTML = '<div class="event-row"><div class="event-content"><p class="event-title" style="color:#8B9198">No events found.</p></div></div>'; return;
            }
            stream.innerHTML = logs.map((log, i) => {
                const sev = (log.severity || 'INFO').toUpperCase();
                const sevDot = { INFO: 'sev-info', WARN: 'sev-warn', CRITICAL: 'sev-crit' }[sev] || 'sev-info';
                const sevIcon = { INFO: 'icon-info', WARN: 'icon-warn', CRITICAL: 'icon-crit' }[sev] || 'icon-info';
                const icon = getEventIcon(log.event || '');
                const badge = `badge-${sev.toLowerCase()}`;
                const badge_label = sev;
                const time = log.occurred_at ? fmtDatetime(log.occurred_at) : '—';
                const ip = log.ip_address || '192.168.1.100';
                const uname = log.username || 'system';
                const session = log.session_reference || `sess_demo_${Math.random().toString(36).slice(2, 10)}`;

                return `<div>
      <div class="event-row" data-call="toggleExpand" data-args="${escapeHTML(JSON.stringify([i]))}">
        <div class="event-sev-dot ${sevDot}"></div>
        <div class="event-icon ${sevIcon}">${icon}</div>
        <div class="event-content">
          <p class="event-title">${escapeHTML(log.event || 'System event')}</p>
          <div class="event-meta">
            <span>👤 ${escapeHTML(uname)}</span>
            <span>🌐 IP: ${escapeHTML(ip)}</span>
            <span>🔑 ${escapeHTML(session)}</span>
          </div>
        </div>
        <span class="event-badge ${escapeHTML(badge)}">${escapeHTML(badge_label)}</span>
        <span class="event-time">${time}</span>
      </div>
      <div class="event-expand" id="expand-${i}">
IP Address: ${escapeHTML(ip)}
Username: ${escapeHTML(uname)}
Session Reference: ${escapeHTML(session)}
Severity: ${escapeHTML(sev)}
Occurred: ${escapeHTML(log.occurred_at ? new Date(log.occurred_at).toISOString() : '—')}
${log.metadata ? `Metadata: ${escapeHTML(JSON.stringify(log.metadata, null, 2))}` : ''}</div>
    </div>`;
            }).join('');
        }

        window.toggleExpand = function (i) {
            const el = document.getElementById(`expand-${i}`);
            if (el) el.classList.toggle('open');
        };

        window.setFilter = function (el, filter) {
            activeFilter = filter;
            document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
            el.classList.add('active');
            filterLogs();
        };

        window.filterLogs = function () {
            const q = document.getElementById('log-search').value.toLowerCase();
            let filtered = allLogs;
            if (activeFilter !== 'ALL') filtered = filtered.filter(l => (l.severity || 'INFO').toUpperCase() === activeFilter);
            if (q) filtered = filtered.filter(l =>
                (l.event || '').toLowerCase().includes(q) ||
                (l.username || '').toLowerCase().includes(q) ||
                (l.ip_address || '').toLowerCase().includes(q)
            );
            renderLogs(filtered);
        };

        function getEventIcon(event) {
            const e = event.toLowerCase();
            if (e.includes('login success')) return '✅';
            if (e.includes('failed login') || e.includes('failed')) return '🚫';
            if (e.includes('locked')) return '🔒';
            if (e.includes('unlock')) return '🔓';
            if (e.includes('role')) return '👑';
            if (e.includes('reservation')) return '📋';
            if (e.includes('schema')) return '🗄';
            if (e.includes('reset')) return '🔑';
            if (e.includes('admin')) return '🛡';
            return 'ℹ';
        }

        function fmtDatetime(d) {
            if (!d) return '—';
            return new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        }

        function getFallbackLogs() {
            const now = new Date();
            const ago = (m) => new Date(now - m * 60000).toISOString();
            return [
                { id: 'l1', username: 'admin.root', ip_address: '192.168.1.104', event: 'Admin login success', severity: 'INFO', session_reference: 'sess_demo_8aB2KxmQ', metadata: {}, occurred_at: ago(5) },
                { id: 'l2', username: 'unknown', ip_address: '203.112.56.78', event: 'Failed login attempt: 3 consecutive failures', severity: 'WARN', session_reference: 'sess_demo_Xp4NrQzL', metadata: { attempts: 3 }, occurred_at: ago(12) },
                { id: 'l3', username: 'jane.staff', ip_address: '192.168.1.22', event: 'Staff login success', severity: 'INFO', session_reference: 'sess_demo_Vy7MsPwR', metadata: {}, occurred_at: ago(30) },
                { id: 'l4', username: 'admin.root', ip_address: '192.168.1.104', event: 'Account manually locked: tom.front', severity: 'WARN', session_reference: 'sess_demo_Kd9TqBnE', metadata: { target: 'tom.front' }, occurred_at: ago(60) },
                { id: 'l5', username: 'admin.root', ip_address: '192.168.1.104', event: 'User role changed: jane.staff → admin', severity: 'WARN', session_reference: 'sess_demo_Jf2LxPuA', metadata: {}, occurred_at: ago(90) },
                { id: 'l6', username: 'guest@email.com', ip_address: '0.0.0.0', event: 'Reservation created: INP-2026-5523 for Margaret Ashworth', severity: 'INFO', session_reference: 'sess_guest_kp4xmQ', metadata: { booking_ref: 'INP-2026-5523' }, occurred_at: ago(120) },
                { id: 'l7', username: 'jane.staff', ip_address: '192.168.1.22', event: 'Reservation deleted', severity: 'WARN', session_reference: 'sess_demo_Vy7MsPwR', metadata: {}, occurred_at: ago(180) },
                { id: 'l8', username: 'jane.staff', ip_address: '192.168.1.22', event: 'Deposit marked paid for reservation INP-2026-5501', severity: 'INFO', session_reference: 'sess_demo_Pr5GhNkX', metadata: {}, occurred_at: ago(240) },
                { id: 'l9', username: 'admin.root', ip_address: '192.168.1.104', event: 'Schema viewer accessed', severity: 'INFO', session_reference: 'sess_demo_8aB2KxmQ', metadata: {}, occurred_at: ago(360) },
                { id: 'l10', username: 'unknown', ip_address: '45.89.234.11', event: 'Critical: Repeated failed login attempts from external IP', severity: 'CRITICAL', session_reference: 'sess_demo_Rb8TvHnQ', metadata: { attempts: 15 }, occurred_at: ago(720) },
            ];
        }

        window.signOut = async function () {
            await api.logout();
            window.location.href = 'index.php?login=1';
        };
        window.toggleSidebar = function () {
            document.getElementById('sidebar').classList.toggle('open');
        };

        init();
    
