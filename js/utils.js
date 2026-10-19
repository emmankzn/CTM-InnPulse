/**
 * InnPulse — Shared Utilities
 */

// ─── Toast Notifications ─────────────────────────────────────────────────────
let toastContainer = null;

function getToastContainer() {
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
        document.body.appendChild(toastContainer);
    }
    return toastContainer;
}

export function showToast(message, type = 'info', duration = 3500) {
    const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
    const container = getToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<span style="font-size:16px">${icons[type] || 'ℹ'}</span><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('toast-leaving');
        setTimeout(() => toast.remove(), 320);
    }, duration);
}

// ─── Format Utilities ─────────────────────────────────────────────────────────
export function formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount);
}

export function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr + (dateStr.length === 10 ? 'T00:00:00' : ''));
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

export function formatDatetime(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

export function formatRelativeTime(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now - d) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return formatDate(dateStr);
}

// ─── Booking Reference Generator ─────────────────────────────────────────────
export function generateBookingRef() {
    const year = new Date().getFullYear();
    const rand = String(Math.floor(Math.random() * 9000) + 1000);
    return `INP-${year}-${rand}`;
}

// ─── Guest ID Generator ───────────────────────────────────────────────────────
export function generateGuestId() {
    return `G-${String(Math.floor(Math.random() * 9000) + 1000)}`;
}

// ─── DOM Helpers ──────────────────────────────────────────────────────────────
export function el(id) { return document.getElementById(id); }
export function qs(selector) { return document.querySelector(selector); }
export function qsa(selector) { return document.querySelectorAll(selector); }

export function setLoading(btn, loading, originalText = null) {
    if (loading) {
        btn.disabled = true;
        btn.dataset.originalText = btn.textContent;
        btn.innerHTML = `<span class="spinner" style="display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite"></span>`;
        if (!document.getElementById('spin-style')) {
            const s = document.createElement('style');
            s.id = 'spin-style';
            s.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
            document.head.appendChild(s);
        }
    } else {
        btn.disabled = false;
        btn.textContent = originalText || btn.dataset.originalText || 'Submit';
    }
}

export function createSkeleton(rows = 4, cols = 5) {
    let html = '';
    for (let i = 0; i < rows; i++) {
        html += '<tr>';
        for (let j = 0; j < cols; j++) {
            html += `<td class="px-4 py-4"><div class="skeleton h-4 rounded" style="width:${60 + Math.random() * 30}%"></div></td>`;
        }
        html += '</tr>';
    }
    return html;
}

// ─── Badge Helpers ────────────────────────────────────────────────────────────
export function depositBadge(status) {
    const map = {
        paid: '<span class="badge-paid px-2 py-0.5 rounded-full text-xs font-semibold">Paid</span>',
        pending: '<span class="badge-pending px-2 py-0.5 rounded-full text-xs font-semibold">Pending</span>',
        refunded: '<span class="badge-refunded px-2 py-0.5 rounded-full text-xs font-semibold">Refunded</span>',
    };
    return map[status] || `<span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs">${status}</span>`;
}

export function roomStatusBadge(status) {
    const map = {
        available: 'badge-available',
        occupied: 'badge-occupied',
        reserved: 'badge-reserved',
        maintenance: 'badge-maintenance',
    };
    const cls = map[status] || 'bg-gray-100 text-gray-600';
    return `<span class="${cls} px-2 py-0.5 rounded-full text-xs font-semibold capitalize">${status}</span>`;
}

export function roleBadge(role) {
    const map = {
        admin: 'bg-yellow-100 text-yellow-800',
        staff: 'bg-blue-100 text-blue-800',
    };
    const cls = map[role] || 'bg-gray-100 text-gray-600';
    return `<span class="${cls} px-2 py-0.5 rounded-full text-xs font-semibold capitalize">${role}</span>`;
}

export function severityBadge(severity) {
    const map = {
        INFO: 'bg-blue-100 text-blue-800',
        WARN: 'bg-yellow-100 text-yellow-800',
        CRITICAL: 'bg-red-100 text-red-800',
    };
    const cls = map[severity] || 'bg-gray-100 text-gray-600';
    return `<span class="${cls} px-2 py-0.5 rounded-full text-xs font-bold">${severity}</span>`;
}

// ─── Confirmation Modal ───────────────────────────────────────────────────────
export function confirm(message, title = 'Confirm Action') {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.innerHTML = `
      <div class="modal-box max-w-sm p-6" style="max-width:420px">
        <h3 class="text-lg font-semibold mb-2" style="font-family:'Playfair Display',serif">${title}</h3>
        <p class="text-sm text-gray-600 mb-6">${message}</p>
        <div class="flex gap-3 justify-end">
          <button id="conf-cancel" class="btn-outline text-sm py-2 px-4">Cancel</button>
          <button id="conf-ok" class="btn-danger">Confirm</button>
        </div>
      </div>`;
        document.body.appendChild(overlay);
        overlay.querySelector('#conf-cancel').onclick = () => { overlay.remove(); resolve(false); };
        overlay.querySelector('#conf-ok').onclick = () => { overlay.remove(); resolve(true); };
        overlay.onclick = (e) => { if (e.target === overlay) { overlay.remove(); resolve(false); } };
    });
}

// ─── Audit Log Helper ─────────────────────────────────────────────────────────
export async function logAuditEvent(api, { event, severity = 'INFO', username = null, metadata = {} }) {
    try {
        const user = await api.session();
        await api.from('audit_logs').insert({
            user_id: user?.id || null,
            username: username || user?.email || 'system',
            ip_address: '127.0.0.1',
            event,
            severity,
            session_reference: `sess_demo_${Math.random().toString(36).slice(2, 10)}`,
            metadata,
        });
    } catch (_) { /* silently ignore audit failures */ }
}
