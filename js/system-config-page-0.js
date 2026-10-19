import { escapeHTML, safeImage } from './security.js';

        import api from './api.js';

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
            await loadConfig();
        }

        async function loadConfig() {
            const keys = ['hotel_name', 'hotel_email', 'hotel_phone', 'hotel_address',
                'default_checkin', 'default_checkout', 'currency', 'deposit_pct',
                'allow_reviews', 'maintenance_mode', 'online_reservations', 'deposit_required'];
            const { data } = await api.from('system_config').select('*').in('config_key', keys);
            if (!data) return;
            const map = {};
            data.forEach(row => { map[row.config_key] = row.config_value; });

            const set = (id, key, fallback) => {
                const el = document.getElementById(id);
                if (!el) return;
                const val = map[key] !== undefined ? map[key] : fallback;
                if (el.type === 'checkbox') el.checked = !!val;
                else el.value = val || fallback;
            };
            set('cfg-hotel-name', 'hotel_name', 'InnPulse Boutique Hotel');
            set('cfg-hotel-email', 'hotel_email', 'reservations@innpulse.demo');
            set('cfg-hotel-phone', 'hotel_phone', '+1 (555) 800-INN1');
            set('cfg-hotel-address', 'hotel_address', '12 Harbourview Crescent, City Centre');
            set('cfg-checkin-time', 'default_checkin', '15:00');
            set('cfg-checkout-time', 'default_checkout', '11:00');
            set('cfg-currency', 'currency', 'USD');
            set('cfg-deposit-pct', 'deposit_pct', '15');
            set('tog-reviews', 'allow_reviews', true);
            set('tog-maintenance', 'maintenance_mode', false);
            set('tog-reservations', 'online_reservations', true);
            set('tog-deposit', 'deposit_required', false);
        }

        async function upsertConfig(key, value) {
            await api.from('system_config').upsert(
                { config_key: key, config_value: value, updated_at: new Date().toISOString() },
                { onConflict: 'config_key' }
            );
        }

        window.saveSection = async function (section) {
            const btns = document.querySelectorAll('.btn-save');
            btns.forEach(b => { b.disabled = true; });

            if (section === 'hotel') {
                await upsertConfig('hotel_name', document.getElementById('cfg-hotel-name').value);
                await upsertConfig('hotel_email', document.getElementById('cfg-hotel-email').value);
                await upsertConfig('hotel_phone', document.getElementById('cfg-hotel-phone').value);
                await upsertConfig('hotel_address', document.getElementById('cfg-hotel-address').value);
            }
            if (section === 'reservation') {
                await upsertConfig('default_checkin', document.getElementById('cfg-checkin-time').value);
                await upsertConfig('default_checkout', document.getElementById('cfg-checkout-time').value);
                await upsertConfig('currency', document.getElementById('cfg-currency').value);
                await upsertConfig('deposit_pct', document.getElementById('cfg-deposit-pct').value);
            }
            if (section === 'toggles') {
                await upsertConfig('allow_reviews', document.getElementById('tog-reviews').checked);
                await upsertConfig('maintenance_mode', document.getElementById('tog-maintenance').checked);
                await upsertConfig('online_reservations', document.getElementById('tog-reservations').checked);
                await upsertConfig('deposit_required', document.getElementById('tog-deposit').checked);
            }
            showToast('Settings saved successfully.', 'success');
            await logAudit(`System config saved: ${section}`, 'INFO');
            btns.forEach(b => { b.disabled = false; });
        };

        window.clearAuditLogs = async function () { showToast('Audit history is retained for security review.', 'info'); };

        async function logAudit(event, severity = 'INFO') {
            await api.from('audit_logs').insert({
                user_id: currentUser?.id, username: currentProfile?.username || currentUser?.email,
                ip_address: '127.0.0.1', event, severity,
                session_reference: `sess_demo_${Math.random().toString(36).slice(2, 8)}`, metadata: {}
            }).catch(() => { });
        }

        function showToast(msg, type = 'info') {
            const el = document.createElement('div');
            const icons = { success: '✓', error: '✕', info: 'ℹ' };
            el.className = `toast toast-${type}`;
            el.innerHTML = `<span style="font-size:15px">${icons[type] || 'ℹ'}</span><span>${escapeHTML(msg)}</span>`;
            document.body.appendChild(el);
            setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateX(60px)'; el.style.transition = 'all 0.3s'; setTimeout(() => el.remove(), 300) }, 3500);
        }

        function showConfirm(msg) {
            return new Promise(resolve => {
                const ov = document.createElement('div');
                ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.55);backdrop-filter:blur(3px);z-index:500;display:flex;align-items:center;justify-content:center;padding:16px';
                ov.innerHTML = `<div style="background:#fff;border-radius:14px;max-width:400px;width:100%;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,0.25)">
      <h3 style="font-family:'Playfair Display',serif;font-size:18px;margin-bottom:8px">Confirm Action</h3>
      <p style="font-size:14px;color:#8B9198;margin-bottom:22px">${escapeHTML(msg)}</p>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button id="c-no" style="padding:9px 18px;border:1px solid #E5E7EB;border-radius:8px;background:#fff;cursor:pointer;font-size:13px;font-weight:600">Cancel</button>
        <button id="c-yes" style="padding:9px 18px;background:#dc2626;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600">Confirm</button>
      </div></div>`;
                document.body.appendChild(ov);
                ov.querySelector('#c-no').onclick = () => { ov.remove(); resolve(false) };
                ov.querySelector('#c-yes').onclick = () => { ov.remove(); resolve(true) };
                ov.onclick = e => { if (e.target === ov) { ov.remove(); resolve(false) } };
            });
        }

        window.signOut = async function () { await api.logout(); window.location.href = 'index.php?login=1'; };
        window.toggleSidebar = function () { document.getElementById('sidebar').classList.toggle('open'); };

        init();
    