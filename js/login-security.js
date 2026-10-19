import { request } from './api.js';
import { secureDialog } from './security.js';
let widgetId = null;
let token = '';
let loader;
let cooldownUntil = 0;
let cooldownTimer = null;
export function loginCoolingDown() { return Date.now() < cooldownUntil; }
export function loginFeedback(details = {}) {
    let notice = document.getElementById('login-attempt-warning');
    if (!notice) {
        notice = document.createElement('p'); notice.id = 'login-attempt-warning'; notice.setAttribute('role', 'status'); notice.setAttribute('aria-live', 'polite');
        notice.style.cssText = 'margin:10px 0;color:#991b1b;font-size:13px';
        document.getElementById('lm-submit-btn').before(notice);
    }
    if (cooldownTimer) clearInterval(cooldownTimer);
    const seconds = Math.max(0, Number(details.retry_after || 0));
    const remaining = details.attempts_remaining;
    const hint = remaining !== undefined && remaining < 5 && remaining > 0 ? `${remaining} attempt(s) remaining before a three-minute lockout.` : '';
    cooldownUntil = seconds > 0 ? Date.now() + seconds * 1000 : 0;
    const button = document.getElementById('lm-submit-btn');
    const update = () => {
        const wait = Math.max(0, Math.ceil((cooldownUntil - Date.now()) / 1000));
        if (wait > 0) {
            const clock = `${Math.floor(wait / 60)}:${String(wait % 60).padStart(2, '0')}`;
            notice.textContent = details.locked ? `Five failed attempts. Login is locked. Try again in ${clock}.` : `Please wait ${clock} before trying again. ${hint}`;
            button.disabled = true; button.textContent = `Try again in ${clock}`;
        } else {
            notice.textContent = seconds > 0 ? 'You can try signing in again now.' : hint;
            button.disabled = false; button.textContent = 'Sign In →';
            if (cooldownTimer) { clearInterval(cooldownTimer); cooldownTimer = null; }
        }
    };
    update();
    if (seconds > 0) cooldownTimer = setInterval(update, 1000);
}
function loadTurnstile() {
    if (window.turnstile) return Promise.resolve();
    if (!loader) loader = new Promise((resolve, reject) => {
        const script = document.createElement('script'); script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.onload = resolve; script.onerror = reject; document.head.append(script);
    });
    return loader;
}
export async function refreshLoginChallenge(showFeedback = true) {
    const email = document.getElementById('lm-email').value.trim();
    const result = await request({ action: 'auth_options', email });
    const options = result.data;
    if (!options) return '';
    if (showFeedback) loginFeedback(options);
    let container = document.getElementById('login-captcha');
    if (!container) { container = document.createElement('div'); container.id = 'login-captcha'; document.getElementById('lm-submit-btn').before(container); }
    if (!options.captcha_required) { container.hidden = true; return ''; }
    container.hidden = false;
    if (!options.site_key) { container.textContent = 'Bot verification needs to be configured by the site owner.'; return ''; }
    try {
        await loadTurnstile();
        if (widgetId === null) widgetId = window.turnstile.render(container, { sitekey: options.site_key, action: 'login',
            callback: value => { token = value; }, 'expired-callback': () => { token = ''; } });
    } catch (_) { container.textContent = 'Unable to load bot verification. Check your connection.'; }
    return token;
}
export function resetLoginSecurity() {
    token = '';
    if (widgetId !== null && window.turnstile) window.turnstile.reset(widgetId);
}
export async function finishLogin(result) {
    if (result.data?.mfa_required) {
        const challengeId = result.data.challenge_id;
        result = await secureDialog({ title: 'Verify your email',
            description: 'Enter the six-digit code sent to the email address registered to your account. It expires in five minutes.',
            fields: [{ name: 'code', label: 'Verification code', numeric: true, maxLength: 6, autocomplete: 'one-time-code' }],
            onSubmit: fields => request({ action: 'verify_login', challenge_id: challengeId, code: fields.code }) });
        if (!result) return { error: { message: 'Verification cancelled. Sign in again to request a new code.' } };
    }

    return result;
}
