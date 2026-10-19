/** Context encoders and dialogs: untrusted values never become executable code. */
export function escapeHTML(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
}
export function safeImage(value) {
    const text = String(value ?? '');
    if (/^data:image\/(png|jpeg|webp);base64,[A-Za-z0-9+/=]+$/.test(text)) return text;
    try {
        const url = new URL(text, location.href);
        if (url.protocol === 'https:' && url.hostname === 'images.unsplash.com') return url.href;
        if (url.origin === location.origin && /^\/assets\//.test(url.pathname)) return url.href;
    } catch (_) { /* invalid URL */ }
    return 'assets/placeholder.svg';
}
export function secureDialog({ title, description = '', fields, submitLabel = 'Continue', onSubmit }) {
    return new Promise(resolve => {
        const overlay = document.createElement('dialog');
        overlay.style.cssText = 'width:min(440px,92vw);border:1px solid #ccc;border-radius:14px;padding:28px;color:#12161A;background:#fff;z-index:10000;font-family:Inter,sans-serif';
        const heading = document.createElement('h2'); heading.textContent = title;
        heading.style.cssText = 'font-size:22px;margin-bottom:12px;font-weight:600';
        const info = document.createElement('p'); info.textContent = description; info.style.cssText = 'font-size:14px;margin-bottom:16px';
        const form = document.createElement('form');
        const inputs = {};
        for (const field of fields) {
            const label = document.createElement('label'); label.textContent = field.label; label.style.display = 'block';
            const input = document.createElement('input'); input.type = field.type || 'text'; input.required = true;
            input.autocomplete = field.autocomplete || 'off'; input.maxLength = field.maxLength || 128;
            if (field.numeric) input.inputMode = 'numeric';
            input.style.cssText = 'display:block;width:100%;padding:10px;margin:6px 0 16px;border:1px solid #aaa;border-radius:6px';
            label.append(input); form.append(label); inputs[field.name] = input;
        }
        const error = document.createElement('p'); error.setAttribute('role', 'alert'); error.style.cssText = 'color:#991b1b;margin:10px 0';
        const submit = document.createElement('button'); submit.type = 'submit'; submit.textContent = submitLabel;
        submit.style.cssText = 'padding:10px 18px;background:#12161A;color:white;border-radius:6px';
        const cancel = document.createElement('button'); cancel.type = 'button'; cancel.textContent = 'Cancel'; cancel.style.marginLeft = '12px';
        const close = result => { overlay.close(); overlay.remove(); resolve(result); };
        cancel.onclick = () => close(null);
        overlay.addEventListener('cancel', event => { event.preventDefault(); close(null); });
        form.append(error, submit, cancel); overlay.append(heading, info, form); document.body.append(overlay); overlay.showModal();
        Object.values(inputs)[0]?.focus();
        form.onsubmit = async event => {
            event.preventDefault(); submit.disabled = true; error.textContent = '';
            try {
                const values = Object.fromEntries(Object.entries(inputs).map(([name, input]) => [name, input.value]));
                const result = await onSubmit(values);
                if (result?.error) error.textContent = result.error.message;
                else close(result);
            } catch (_) { error.textContent = 'Unable to complete the request. Please try again.'; }
            submit.disabled = false;
        };
    });
}
