import { escapeHTML } from './security.js';
/**
 * InnPulse — Authentication & Session Helpers
 */

import api from './api.js';

// ─── Get current session user ─────────────────────────────────────────────────
export async function getCurrentUser() {
    return await api.session();
}

// ─── Get user profile (role etc.) ────────────────────────────────────────────
export async function getUserProfile(userId) {
    const session = await api.session();
    if (session?.id === userId) return session;
    const { data, error } = await api
        .from('profiles')
        .select('*')
        .eq('id', userId)
        .single();
    if (error) return null;
    return data;
}

// ─── Require authentication + role ───────────────────────────────────────────
export async function requireAuth(allowedRoles = ['staff', 'admin']) {
    const user = await getCurrentUser();
    if (!user) {
        window.location.href = 'login.php';
        return null;
    }
    const profile = await getUserProfile(user.id);
    if (!profile || !allowedRoles.includes(profile.role)) {
        if (profile && profile.role === 'staff') {
            showAccessDenied('Staff cannot access this area.');
            setTimeout(() => window.location.href = 'front-desk.php', 2000);
        } else {
            window.location.href = 'login.php';
        }
        return null;
    }
    return { user, profile };
}

// ─── Sign out ─────────────────────────────────────────────────────────────────
export async function signOut() {
    await api.logout();
    window.location.href = 'login.php';
}

// ─── Access Denied Banner ─────────────────────────────────────────────────────
function showAccessDenied(msg = 'Access Denied') {
    const banner = document.createElement('div');
    banner.style.cssText = 'position:fixed;inset:0;background:rgba(18,22,26,0.95);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:9999;color:#F8F9FA;font-family:Inter,sans-serif';
    banner.innerHTML = `
    <div style="font-size:48px;margin-bottom:16px">⛔</div>
    <h2 style="font-family:\'Playfair Display\',serif;font-size:28px;margin-bottom:8px">Access Denied</h2>
    <p style="color:#8B9198;font-size:15px">${escapeHTML(msg)}</p>
    <p style="color:#D4AF37;font-size:13px;margin-top:12px">Redirecting…</p>`;
    document.body.appendChild(banner);
}

// ─── Populate user display in headers/sidebars ────────────────────────────────
export function renderUserBadge(profile, containerSelector) {
    const el = document.querySelector(containerSelector);
    if (!el || !profile) return;
    el.textContent = profile.username || profile.email || 'User';
}

export { api };
