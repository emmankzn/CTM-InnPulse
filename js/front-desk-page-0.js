import { escapeHTML, safeImage } from './security.js';

        import api from './api.js';

        let currentUser = null;
        let currentProfile = null;
        let allReservations = [];
        let allRooms = [];

        // ── Auth gate ─────────────────────────────────────────────────────────────────
        async function init() {
            const sessionProfile = await api.session();
            const stored = sessionProfile ? JSON.stringify(sessionProfile) : null;
            if (!stored) { window.location.href = 'index.php?login=1'; return; }
            const sessionUser = JSON.parse(stored);
            if (sessionUser.role !== 'staff' && sessionUser.role !== 'admin') { window.location.href = 'index.php?login=1'; return; }
            currentUser = { id: sessionUser.id, email: sessionUser.email };
            currentProfile = sessionUser;
            document.getElementById('user-display-name').textContent = sessionUser.username || sessionUser.email;
            await Promise.all([loadRooms(), loadReservations()]);
            setTodayLabel();
        }

        function setTodayLabel() {
            const today = new Date().toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
            document.getElementById('kpi-today-label').textContent = today;
        }

        // ── Load rooms ────────────────────────────────────────────────────────────────
        async function loadRooms() {
            const { data, error } = await api.from('rooms').select('*').order('room_number');
            if (error || !data) { allRooms = []; showToast('Unable to load database records.', 'error'); }
            else { allRooms = data; }
            renderRoomGrid();
            populateRoomSelect();
        }

        function renderRoomGrid() {
            const grid = document.getElementById('room-status-grid');
            if (!allRooms.length) { grid.innerHTML = '<p style="color:var(--muted);padding:20px">No rooms found.</p>'; return; }
            grid.innerHTML = allRooms.map(r => {
                const cls = { available: 'rs-available', occupied: 'rs-occupied', reserved: 'rs-reserved', maintenance: 'rs-maintenance' }[r.status] || 'rs-available';
                const badge = { available: 'rs-avail-badge', occupied: 'rs-occ-badge', reserved: 'rs-res-badge', maintenance: 'rs-maint-badge' }[r.status] || 'rs-avail-badge';
                const label = { available: 'Available', occupied: 'Occupied', reserved: 'Reserved', maintenance: 'Maintenance' }[r.status] || r.status;
                const guestName = r.current_guest || (r.status === 'available' ? '' : '—');
                return `<div class="room-status-card ${cls}">
      <p class="rs-num">#${escapeHTML(r.room_number)}</p>
      <p class="rs-name">${escapeHTML(r.room_name)}</p>
      ${guestName ? `<p class="rs-guest">${escapeHTML(guestName)}</p>` : `<p class="rs-guest">&nbsp;</p>`}
      <span class="rs-badge ${badge}">${escapeHTML(label)}</span>
    </div>`;
            }).join('');
        }

        function populateRoomSelect() {
            const sel = document.getElementById('rf-room');
            sel.innerHTML = '<option value="">Select room…</option>' +
                allRooms.map(r => `<option value="${escapeHTML(r.id)}">${escapeHTML(r.room_number)} — ${escapeHTML(r.room_name)}</option>`).join('');
        }

        // ── Load reservations ─────────────────────────────────────────────────────────
        async function loadReservations() {
            const { data, error } = await api
                .from('reservations')
                .select(`*, rooms(room_number, room_name), deposits(status, receipt_url, amount)`)
                .order('created_at', { ascending: false });
            if (error || !data) { allReservations = []; showToast('Unable to load database records.', 'error'); }
            else { allReservations = data; }
            renderReservations(allReservations);
            updateKPIs();
        }

        function updateKPIs() {
            const today = new Date().toISOString().split('T')[0];
            const total = allReservations.length;
            const checkins = allReservations.filter(r => r.check_in === today).length;
            const allDeposits = allReservations.flatMap(r => r.deposits || []);
            const paid = allDeposits.filter(d => d.status === 'paid').length;
            const pending = allDeposits.filter(d => d.status === 'pending').length;
            document.getElementById('kpi-total').textContent = total;
            document.getElementById('kpi-checkins').textContent = checkins;
            document.getElementById('kpi-paid').textContent = paid;
            document.getElementById('kpi-pending').textContent = pending;
        }

        function renderReservations(reservations) {
            const tbody = document.getElementById('reservations-tbody');
            if (!reservations.length) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:48px;color:var(--muted)">No reservations found.</td></tr>';
                return;
            }
            tbody.innerHTML = reservations.map(r => {
                const dep = (r.deposits && r.deposits[0]) || {};
                const depBadge = dep.status
                    ? `<span class="badge badge-${escapeHTML(dep.status)}" style="font-size:10px">${escapeHTML(dep.status.charAt(0).toUpperCase() + dep.status.slice(1))}</span>`
                    : '<span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:10px">—</span>';
                const receipt = dep.receipt_url
                    ? `<img class="receipt-thumb" src="${escapeHTML(safeImage(dep.receipt_url))}" alt="Receipt" data-call="openReceiptModal" data-args="${escapeHTML(JSON.stringify([safeImage(dep.receipt_url)]))}" title="View receipt" />`
                    : '<span class="no-receipt">No file</span>';
                const roomLabel = r.rooms ? `${r.rooms.room_number}` : '—';
                const statusBadge = r.status
                    ? `<span class="badge badge-${escapeHTML(r.status === 'confirmed' ? 'confirmed' : r.status === 'cancelled' ? 'cancelled' : 'confirmed')}" style="font-size:10px">${escapeHTML(r.status || '')}</span>` : '—';

                return `<tr>
      <td class="mono">${escapeHTML(r.guest_id || '—')}</td>
      <td style="font-weight:600">${escapeHTML(r.guest_name || '—')}</td>
      <td class="mono" style="color:var(--muted-gold)">${escapeHTML(roomLabel)}</td>
      <td>${fmtDate(r.check_in)}</td>
      <td>${fmtDate(r.check_out)}</td>
      <td>${depBadge}</td>
      <td>${receipt}</td>
      <td>${statusBadge}</td>
      <td style="white-space:nowrap">
        <button class="btn-icon" data-call="openEditModal" data-args="${escapeHTML(JSON.stringify([r.id]))}">✏ Edit</button>
        <button class="btn-icon btn-del" data-call="deleteReservation" data-args="${escapeHTML(JSON.stringify([r.id]))}" style="margin-left:5px">✕ Delete</button>
      </td>
    </tr>`;
            }).join('');
        }

        // ── Search ────────────────────────────────────────────────────────────────────
        window.searchTable = function () {
            const q = document.getElementById('table-search').value.toLowerCase();
            if (!q) { renderReservations(allReservations); return; }
            const filtered = allReservations.filter(r =>
                (r.guest_name || '').toLowerCase().includes(q) ||
                (r.guest_id || '').toLowerCase().includes(q) ||
                (r.booking_ref || '').toLowerCase().includes(q) ||
                (r.rooms?.room_number || '').toLowerCase().includes(q)
            );
            renderReservations(filtered);
        };

        // ── New reservation modal ─────────────────────────────────────────────────────
        window.openNewReservationModal = function () {
            document.getElementById('res-modal-title').textContent = 'New Reservation';
            document.getElementById('res-id').value = '';
            document.getElementById('res-form').reset();
            const today = new Date(); const tomorrow = new Date(today); tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('rf-checkin').value = today.toISOString().split('T')[0];
            document.getElementById('rf-checkout').value = tomorrow.toISOString().split('T')[0];
            document.getElementById('res-submit-btn').textContent = 'Save Reservation';
            document.getElementById('res-modal').classList.add('open');
            document.body.style.overflow = 'hidden';
        };

        window.openEditModal = function (id) {
            const res = allReservations.find(r => String(r.id) === String(id));
            if (!res) return;
            document.getElementById('res-modal-title').textContent = 'Edit Reservation';
            document.getElementById('res-id').value = res.id;
            document.getElementById('rf-name').value = res.guest_name || '';
            document.getElementById('rf-email').value = res.guest_email || '';
            document.getElementById('rf-phone').value = res.guest_phone || '';
            document.getElementById('rf-room').value = res.room_id || '';
            document.getElementById('rf-checkin').value = res.check_in || '';
            document.getElementById('rf-checkout').value = res.check_out || '';
            document.getElementById('rf-guests').value = res.guest_count || 1;
            document.getElementById('rf-requests').value = res.special_requests || '';
            const dep = res.deposits && res.deposits[0];
            if (dep) document.getElementById('rf-deposit').value = dep.status || 'pending';
            document.getElementById('res-submit-btn').textContent = 'Update Reservation';
            document.getElementById('res-modal').classList.add('open');
            document.body.style.overflow = 'hidden';
        };

        window.closeResModal = function () {
            document.getElementById('res-modal').classList.remove('open');
            document.body.style.overflow = '';
        };

        document.getElementById('res-modal').addEventListener('click', e => { if (e.target === e.currentTarget) closeResModal(); });

        // ── Save reservation ──────────────────────────────────────────────────────────
        window.saveReservation = async function (e) {
            e.preventDefault();
            const btn = document.getElementById('res-submit-btn');
            btn.disabled = true; btn.innerHTML = '<span class="spin"></span>';

            const id = document.getElementById('res-id').value;
            const name = document.getElementById('rf-name').value.trim();
            const email = document.getElementById('rf-email').value.trim();
            const phone = document.getElementById('rf-phone').value.trim();
            const roomId = document.getElementById('rf-room').value;
            const checkin = document.getElementById('rf-checkin').value;
            const checkout = document.getElementById('rf-checkout').value;
            const guests = document.getElementById('rf-guests').value;
            const requests = document.getElementById('rf-requests').value.trim();
            const depStatus = document.getElementById('rf-deposit').value;

            const payload = {
                guest_name: name, guest_email: email, guest_phone: phone, room_id: roomId,
                check_in: checkin, check_out: checkout, guest_count: parseInt(guests), special_requests: requests, status: 'confirmed'
            };

            if (id) {
                // Update
                const { error } = await api.from('reservations').update({ ...payload, updated_at: new Date().toISOString() }).eq('id', id);
                if (error) { showToast('Failed to update reservation.', 'error'); }
                else {
                    await api.from('deposits').update({ status: depStatus }).eq('reservation_id', id);
                    showToast('Reservation updated.', 'success');
                    closeResModal();
                    await loadReservations();
                    await logAudit(`Reservation updated: ${name}`, 'INFO');
                }
            } else {
                // Create
                const bookingRef = generateRef();
                const guestId = generateGuestId();
                const { data: res, error } = await api.from('reservations')
                    .insert({ ...payload, guest_id: guestId, booking_ref: bookingRef })
                    .select().single();
                if (error) { showToast('Failed to create reservation.', 'error'); }
                else {
                    await api.from('deposits').insert({ reservation_id: res.id, amount: 0, status: depStatus });
                    // Update room status to reserved
                    await api.from('rooms').update({ status: 'reserved' }).eq('id', roomId);
                    showToast(`Reservation created: ${bookingRef}`, 'success');
                    closeResModal();
                    await loadReservations();
                    await loadRooms();
                    await logAudit(`Reservation created: ${bookingRef} for ${name}`, 'INFO');
                }
            }
            btn.disabled = false; btn.textContent = id ? 'Update Reservation' : 'Save Reservation';
        };

        // ── Delete reservation ────────────────────────────────────────────────────────
        window.deleteReservation = async function (id) {
            const confirmed = await showConfirm('Delete this reservation? This action cannot be undone.');
            if (!confirmed) return;
            await api.from('deposits').delete().eq('reservation_id', id);
            const { error } = await api.from('reservations').delete().eq('id', id);
            if (error) { showToast('Failed to delete reservation.', 'error'); return; }
            showToast('Reservation deleted.', 'info');
            await loadReservations();
            await loadRooms();
            await logAudit('Reservation deleted', 'WARN');
        };

        // ── Receipt modal ─────────────────────────────────────────────────────────────
        window.openReceiptModal = function (url) {
            document.getElementById('receipt-modal-img').src = safeImage(url);
            document.getElementById('receipt-modal').classList.add('open');
            document.body.style.overflow = 'hidden';
        };
        window.closeReceiptModal = function () {
            document.getElementById('receipt-modal').classList.remove('open');
            document.body.style.overflow = '';
        };
        document.getElementById('receipt-modal').addEventListener('click', e => { if (e.target === e.currentTarget) closeReceiptModal(); });

        // ── Sign out ──────────────────────────────────────────────────────────────────
        window.signOut = async function () {
            await api.logout();
            window.location.href = 'index.php?login=1';
        };

        // ── Helpers ───────────────────────────────────────────────────────────────────
        function generateRef() { return `INP-${new Date().getFullYear()}-${String(Math.floor(Math.random() * 9000) + 1000)}`; }
        function generateGuestId() { return `G-${String(Math.floor(Math.random() * 9000) + 1000)}`; }
        function fmtDate(d) { if (!d) return '—'; const dt = new Date(d + 'T00:00:00'); return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); }

        async function logAudit(event, severity = 'INFO') {
            await api.from('audit_logs').insert({
                user_id: currentUser?.id, username: currentProfile?.username || currentUser?.email,
                ip_address: '127.0.0.1', event, severity,
                session_reference: `sess_demo_${Math.random().toString(36).slice(2, 8)}`,
                metadata: {}
            }).catch(() => { });
        }

        function showToast(msg, type = 'info') {
            const el = document.createElement('div');
            const icons = { success: '✓', error: '✕', info: 'ℹ', warning: '⚠' };
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
      <h3 style="font-family:'Playfair Display',serif;font-size:18px;margin-bottom:8px">Confirm Delete</h3>
      <p style="font-size:14px;color:var(--muted);margin-bottom:22px">${escapeHTML(msg)}</p>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button id="c-no" style="padding:9px 18px;border:1px solid var(--border);border-radius:8px;background:#fff;cursor:pointer;font-size:13px;font-weight:600">Cancel</button>
        <button id="c-yes" style="padding:9px 18px;background:#dc2626;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600">Delete</button>
      </div></div>`;
                document.body.appendChild(ov);
                ov.querySelector('#c-no').onclick = () => { ov.remove(); resolve(false) };
                ov.querySelector('#c-yes').onclick = () => { ov.remove(); resolve(true) };
                ov.onclick = e => { if (e.target === ov) { ov.remove(); resolve(false) } };
            });
        }

        // ── Fallback data ─────────────────────────────────────────────────────────────
        function getFallbackRooms() {
            return [
                { id: 'r1', room_number: '101', room_name: 'Grand Deluxe Suite', status: 'occupied', current_guest: 'M. Ashworth' },
                { id: 'r2', room_number: '102', room_name: 'Classic Room', status: 'available' },
                { id: 'r3', room_number: '103', room_name: 'Classic Room', status: 'occupied', current_guest: 'T. Bellworth' },
                { id: 'r4', room_number: '115', room_name: 'Classic Room', status: 'available' },
                { id: 'r5', room_number: '201', room_name: 'Garden Loft', status: 'reserved', current_guest: 'P. Mehta' },
                { id: 'r6', room_number: '202', room_name: 'Heritage Loft', status: 'reserved' },
                { id: 'r7', room_number: '210', room_name: 'Botanical Suite', status: 'available' },
                { id: 'r8', room_number: '211', room_name: 'Deluxe Suite', status: 'available' },
                { id: 'r9', room_number: '218', room_name: 'Signature Suite', status: 'occupied', current_guest: 'N. Okonkwo' },
                { id: 'r10', room_number: '303', room_name: 'Atelier Penthouse', status: 'available' },
                { id: 'r11', room_number: '401', room_name: 'Studio', status: 'available' },
                { id: 'r12', room_number: '405', room_name: 'Library Studio', status: 'maintenance' },
            ];
        }

        function getFallbackReservations() {
            return [
                { id: 'res1', guest_id: 'G-2291', guest_name: 'Margaret Ashworth', rooms: { room_number: '101' }, check_in: '2026-09-01', check_out: '2026-09-05', status: 'confirmed', deposits: [{ status: 'paid', receipt_url: null }] },
                { id: 'res2', guest_id: 'G-3312', guest_name: 'James Lafferty', rooms: { room_number: '202' }, check_in: '2026-09-03', check_out: '2026-09-07', status: 'confirmed', deposits: [{ status: 'pending', receipt_url: null }] },
                { id: 'res3', guest_id: 'G-4401', guest_name: 'Priya Mehta', rooms: { room_number: '201' }, check_in: '2026-09-04', check_out: '2026-09-09', status: 'confirmed', deposits: [{ status: 'paid', receipt_url: null }] },
                { id: 'res4', guest_id: 'G-5520', guest_name: 'Thomas Bellworth', rooms: { room_number: '103' }, check_in: '2026-09-02', check_out: '2026-09-06', status: 'confirmed', deposits: [{ status: 'paid', receipt_url: null }] },
                { id: 'res5', guest_id: 'G-6631', guest_name: 'Nadia Okonkwo', rooms: { room_number: '218' }, check_in: '2026-09-01', check_out: '2026-09-10', status: 'confirmed', deposits: [{ status: 'pending', receipt_url: null }] },
                { id: 'res6', guest_id: 'G-7742', guest_name: 'Rafael Vega', rooms: { room_number: '210' }, check_in: '2026-09-06', check_out: '2026-09-08', status: 'confirmed', deposits: [{ status: 'paid', receipt_url: null }] },
            ];
        }

        init();
    