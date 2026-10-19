import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
let now = 1000000;
let tick;
const nodes = new Map();
const button = { disabled: false, textContent: '', before(node) { nodes.set(node.id, node); } };
nodes.set('lm-submit-btn', button);
const context = { Date: { now: () => now }, console,
    document: { getElementById: id => nodes.get(id), createElement: () => ({ style: {}, setAttribute() {} }) },
    setInterval(fn) { tick = fn; return 1; }, clearInterval() {} };
vm.createContext(context);
const source = fs.readFileSync(new URL('../js/login-security.js', import.meta.url), 'utf8')
    .replace(/^import[^\n]+$/gm, '').replace(/^export /gm, '');
vm.runInContext(source, context);
context.loginFeedback({ retry_after: 180, locked: true, attempts_remaining: 0 });
assert(button.disabled && button.textContent.includes('3:00'));
assert(nodes.get('login-attempt-warning').textContent.includes('Five failed attempts'));
now += 179000; tick(); assert(button.disabled && button.textContent.includes('0:01'));
now += 1000; tick(); assert(!button.disabled && !context.loginCoolingDown());
context.loginFeedback({retry_after:1, attempts_remaining:4});
assert(nodes.get('login-attempt-warning').textContent.includes('4 attempt(s) remaining'));
now += 1000; tick(); assert(!button.disabled);
console.log('Login countdown passed: warning, remaining attempts, 3:00 lockout and automatic re-enable at expiry.');
