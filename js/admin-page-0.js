import { escapeHTML, safeImage, secureDialog } from './security.js';

        import api from './api.js';

        let currentUser = null;
        let currentProfile = null;
        let allUsers = [];

        // ── Auth gate ─────────────────────────────────────────────────────────────────
        async function init() {
            const sessionProfile = await api.session();
            const stored = sessionProfile ? JSON.stringify(sessionProfile) : null;
            if (!stored) { window.location.href = 'index.php?login=1'; return; }
            const sessionUser = JSON.parse(stored);
            if (sessionUser.role !== 'admin') { showAccessDenied(); setTimeout(() => window.location.href = 'front-desk.php', 2000); return; }
            currentUser = { id: sessionUser.id, email: sessionUser.email };
            currentProfile = sessionUser;
            document.getElementById('sidebar-username').textContent = sessionUser.username || sessionUser.email;
            await loadUsers();
            logAudit('Admin portal accessed', 'INFO');
        }

        // ── Load users ────────────────────────────────────────────────────────────────
        async function loadUsers() {
            const { data, error } = await api.from('profiles').select('*').order('created_at');
            if (error || !data) { allUsers = []; showToast('Unable to load database records.', 'error'); }
            else { allUsers = data; }
            renderUsers(allUsers);
        }

        function renderUsers(users) {
            const tbody = document.getElementById('users-tbody');
            if (!users.length) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted)">No users found.</td></tr>'; return;
            }
            tbody.innerHTML = users.map(u => {
                const roleBadge = u.role === 'admin'
                    ? '<span class="badge badge-admin">Admin</span>'
                    : '<span class="badge badge-staff">Staff</span>';
                const statusBadge = u.locked
                    ? '<span class="badge badge-locked">Locked</span>'
                    : '<span class="badge badge-active">Active</span>';
                const lastLogin = u.last_login
                    ? new Date(u.last_login).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                    : 'Never';
                const isSelf = currentUser && u.id === currentUser.id;

                return `<tr>
      <td class="mono" style="max-width:100px;overflow:hidden;text-overflow:ellipsis" title="${escapeHTML(u.id)}">${escapeHTML((u.id || '').slice(0, 12))}…</td>
      <td style="font-weight:600">${escapeHTML(u.username || '—')}</td>
      <td style="color:var(--muted);font-size:12px">${escapeHTML(u.email || '—')}</td>
      <td>${roleBadge}</td>
      <td>${statusBadge}</td>
      <td style="font-size:12px;color:var(--muted)">${escapeHTML(lastLogin)}</td>
      <td>
        <div style="display:flex;gap:5px;flex-wrap:wrap">
          <button class="btn-sm btn-edit" data-call="openEditUserModal" data-args="${escapeHTML(JSON.stringify([u.id]))}">Edit</button>
          <button class="btn-sm btn-reset" data-call="resetPassword" data-args="${escapeHTML(JSON.stringify([u.id,u.email]))}">Reset PW</button>
          ${u.locked
                        ? `<button class="btn-sm btn-unlock" data-call="toggleLock" data-args="${escapeHTML(JSON.stringify([u.id,false]))}">Unlock</button>`
                        : `<button class="btn-sm btn-lock" data-call="toggleLock" data-args="${escapeHTML(JSON.stringify([u.id,true]))}" ${isSelf ? 'disabled title="Cannot lock yourself"' : ''}>Lock</button>`}
          ${!isSelf ? `<button class="btn-sm btn-danger" data-call="removeUser" data-args="${escapeHTML(JSON.stringify([u.id,u.username || u.email]))}">Remove</button>` : ''}
        </div>
      </td>
    </tr>`;
            }).join('');
        }

        // ── Filter ────────────────────────────────────────────────────────────────────
        window.filterUsers = function () {
            const q = document.getElementById('user-search').value.toLowerCase();
            if (!q) { renderUsers(allUsers); return; }
            renderUsers(allUsers.filter(u =>
                (u.username || '').toLowerCase().includes(q) ||
                (u.email || '').toLowerCase().includes(q) ||
                (u.role || '').toLowerCase().includes(q)
            ));
        };

        // ── Invite modal ──────────────────────────────────────────────────────────────
        window.openInviteModal = function () {
            document.getElementById('user-modal-title').textContent = 'Invite User';
            document.getElementById('uf-id').value = '';
            document.getElementById('user-form').reset();
            document.getElementById('password-group').style.display = 'block';
            document.getElementById('user-submit-btn').textContent = 'Create User';
            document.getElementById('user-modal').classList.add('open');
            document.body.style.overflow = 'hidden';
        };

        window.openEditUserModal = function (id) {
            const user = allUsers.find(u => String(u.id) === String(id));
            if (!user) return;
            document.getElementById('user-modal-title').textContent = 'Edit User';
            document.getElementById('uf-id').value = user.id;
            document.getElementById('uf-username').value = user.username || '';
            document.getElementById('uf-email').value = user.email || '';
            document.getElementById('uf-role').value = user.role || 'staff';
            document.getElementById('password-group').style.display = 'none';
            document.getElementById('user-submit-btn').textContent = 'Save Changes';
            document.getElementById('user-modal').classList.add('open');
            document.body.style.overflow = 'hidden';
        };

        window.closeUserModal = function () {
            document.getElementById('user-modal').classList.remove('open');
            document.body.style.overflow = '';
        };

        document.getElementById('user-modal').addEventListener('click', e => { if (e.target === e.currentTarget) closeUserModal(); });

        // ── Save user ─────────────────────────────────────────────────────────────────
        window.saveUser = async function (e) {
            e.preventDefault();
            const btn = document.getElementById('user-submit-btn');
            btn.disabled = true; btn.innerHTML = '<span class="spin"></span>';

            const id = document.getElementById('uf-id').value;
            const username = document.getElementById('uf-username').value.trim();
            const email = document.getElementById('uf-email').value.trim();
            const role = document.getElementById('uf-role').value;
            const password = document.getElementById('uf-password')?.value;

            if (id) {
                // Update profile only
                const { error } = await api.from('profiles').update({ username, email, role }).eq('id', id);
                if (error) { showToast('Failed to update user.', 'error'); }
                else { showToast('User updated successfully.', 'success'); closeUserModal(); await loadUsers(); logAudit(`User role changed: ${username} → ${role}`, 'WARN'); }
            } else {
                const { error } = await api.from('profiles').insert({ username, email, role, password });
                if (error) showToast(error.message, 'error');
                else { showToast('User created.', 'success'); closeUserModal(); await loadUsers(); }
            }
            btn.disabled = false; btn.textContent = id ? 'Save Changes' : 'Create User';
        };

        // ── Lock / Unlock ─────────────────────────────────────────────────────────────
        window.toggleLock = async function (id, lock) {
            if (id === currentUser.id) { showToast('You cannot lock your own account.', 'warning'); return; }
            const { error } = await api.from('profiles').update({ locked: lock }).eq('id', id);
            if (error) { showToast('Failed to update lock status.', 'error'); return; }
            const action = lock ? 'locked' : 'unlocked';
            showToast(`Account ${action}.`, lock ? 'warning' : 'success');
            await loadUsers();
            logAudit(`Account manually ${action}: ${id}`, lock ? 'WARN' : 'INFO');
        };

        // ── Reset password ────────────────────────────────────────────────────────────
        window.resetPassword = async function (id, email) {
            if (!email) { showToast('No email on record.', 'warning'); return; }
            const entry = await secureDialog({ title: 'New account password', fields: [{ name: 'password', label: 'New password (14–72 characters)', type: 'password', autocomplete: 'new-password' }], onSubmit: async values => ({ data: values }) });
            if (!entry) return;
            const password = entry.data.password;
            const { error } = await api.from('profiles').update({ password }).eq('id', id);
            showToast(error ? error.message : 'Password updated.', error ? 'error' : 'success');
            if (!error) logAudit(`Password reset for: ${email}`, 'WARN');
        };

        // ── Remove user ───────────────────────────────────────────────────────────────
        window.removeUser = async function (id, label) {
            if (id === currentUser?.id) { showToast('Cannot remove your own account.', 'warning'); return; }
            const ok = await showConfirm(`Remove "${escapeHTML(label)}" from the system?`);
            if (!ok) return;
            const { error } = await api.from('profiles').delete().eq('id', id);
            if (error) { showToast('Failed to remove user.', 'error'); return; }
            showToast('User removed.', 'info');
            await loadUsers();
            logAudit(`User removed: ${escapeHTML(label)}`, 'WARN');
        };

        // ── Sign out ──────────────────────────────────────────────────────────────────
        window.signOut = async function () {
            await api.logout();
            window.location.href = 'index.php?login=1';
        };

        // ── Mobile sidebar ────────────────────────────────────────────────────────────
        window.toggleSidebar = function () {
            document.getElementById('sidebar').classList.toggle('open');
        };

        // ── Access denied ─────────────────────────────────────────────────────────────
        function showAccessDenied() {
            document.body.innerHTML = `<div style="position:fixed;inset:0;background:#12161A;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#F8F9FA;font-family:Inter,sans-serif">
    <div style="font-size:48px;margin-bottom:16px">⛔</div>
    <h2 style="font-family:'Playfair Display',serif;font-size:28px;margin-bottom:8px">Access Denied</h2>
    <p style="color:#8B9198">Admin access required. Redirecting…</p></div>`;
        }

        // ── Fallback data ─────────────────────────────────────────────────────────────
        function getFallbackUsers() {
            return [
                { id: 'a1', username: 'admin.root', email: 'admin@innpulse.demo', role: 'admin', locked: false, last_login: '2026-09-01T10:23:00Z', created_at: '2026-01-01T00:00:00Z' },
                { id: 'a2', username: 'jane.staff', email: 'staff@innpulse.demo', role: 'staff', locked: false, last_login: '2026-09-01T08:15:00Z', created_at: '2026-01-15T00:00:00Z' },
                { id: 'a3', username: 'tom.front', email: 'tom@innpulse.demo', role: 'staff', locked: true, last_login: '2026-08-28T14:05:00Z', created_at: '2026-02-01T00:00:00Z' },
            ];
        }

        // ── Helpers ───────────────────────────────────────────────────────────────────
        async function logAudit(event, severity = 'INFO') {
            await api.from('audit_logs').insert({
                user_id: currentUser?.id, username: currentProfile?.username || currentUser?.email,
                ip_address: '127.0.0.1', event, severity,
                session_reference: `sess_demo_${Math.random().toString(36).slice(2, 8)}`,
                metadata: {}
            }).catch(() => { });
        }

        function showToast(msg, type = 'info', duration = 3500) {
            const el = document.createElement('div');
            const icons = { success: '✓', error: '✕', info: 'ℹ', warning: '⚠' };
            el.className = `toast toast-${type}`;
            el.innerHTML = `<span style="font-size:15px">${icons[type] || 'ℹ'}</span><span>${escapeHTML(msg)}</span>`;
            document.body.appendChild(el);
            setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateX(60px)'; el.style.transition = 'all 0.3s'; setTimeout(() => el.remove(), 300) }, duration);
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

        init();
    