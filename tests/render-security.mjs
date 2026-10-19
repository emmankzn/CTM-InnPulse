import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import { pathToFileURL, fileURLToPath } from 'node:url';
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const { escapeHTML, safeImage } = await import('data:text/javascript,' + encodeURIComponent(fs.readFileSync(path.join(root, 'js/security.js'), 'utf8')));
globalThis.location = { href: 'https://innpulse.free.nf/', origin: 'https://innpulse.free.nf' };
const payload = `'"><img src=x onerror=alert(1)><script>window.pwned=1</script>&`;
assert.equal(safeImage('javascript:alert(1)'), 'assets/placeholder.svg');
assert.equal(safeImage('data:image/svg+xml,<svg/onload=alert(1)>'), 'assets/placeholder.svg');
assert.equal(safeImage('https://evil.test/track'), 'assets/placeholder.svg');
assert(!escapeHTML(payload).includes('<'));

function page(filename, rows = []) {
    const nodes = new Map();
    const node = id => {
        if (!nodes.has(id)) nodes.set(id, { innerHTML: '', textContent: '', style: {}, value: '',
            addEventListener() {}, classList: { add() {}, remove() {}, toggle() {} } });
        return nodes.get(id);
    };
    const context = { escapeHTML, safeImage, console, URL, setTimeout: () => 0, clearTimeout() {},
        api: { session: async () => null, from: () => ({ select() { return this; }, order() { return this; }, limit() { return Promise.resolve({data: rows}); } }) },
        document: { getElementById: node, querySelectorAll: () => [], addEventListener() {}, body: { style: {} } },
        location: globalThis.location, URLSearchParams, history: { replaceState() {} } };
    context.window = context; vm.createContext(context);
    let source = fs.readFileSync(path.join(root, 'js', filename), 'utf8').replace(/^\s*import[\s\S]*?;/gm, '');
    source = source.replace(/\binit\(\);\s*$/, '');
    vm.runInContext(source, context);
    return { context, node };
}
function noExecutablePayload(markup) {
    assert(!markup.includes('<script>'), markup);
    assert(!markup.includes('<img src=x'), markup);
    const parser = spawnSync('python', ['-c', `import sys,json
from html.parser import HTMLParser
class Check(HTMLParser):
 def handle_starttag(self,tag,attrs):
  assert tag not in ('script','iframe','object'),tag
  assert not any(k.startswith('on') for k,v in attrs),attrs
Check().feed(sys.stdin.read())`], {input: markup, encoding:'utf8'});
    assert.equal(parser.status, 0, parser.stderr);
    assert(markup.includes('&lt;') || markup.includes('assets/placeholder.svg'), markup);
}
const desk = page('front-desk-page-0.js');
desk.context.renderReservations([{ id: payload, guest_id: payload, guest_name: payload, rooms: {room_number: payload},
    check_in: '2026-11-01', check_out: '2026-11-02', status: 'confirmed', deposits: [{status:'pending',receipt_url:'javascript:alert(1)'}] }]);
noExecutablePayload(desk.node('reservations-tbody').innerHTML);
const admin = page('admin-page-0.js');
admin.context.renderUsers([{ id: payload, username: payload, email: payload, role: 'staff', locked: false }]);
noExecutablePayload(admin.node('users-tbody').innerHTML);
const logs = page('audit-logs-page-0.js');
logs.context.renderLogs([{event:payload, username:payload, ip_address:payload, session_reference:payload, severity:'WARN', metadata:{data:payload}}]);
noExecutablePayload(logs.node('event-stream').innerHTML);
const index = page('index-page-0.js');
noExecutablePayload(index.context.roomCardHTML({ id: payload, room_name: payload, room_type: 'Suite', room_number: payload,
    status:'available', amenities:[payload], image_url:'javascript:alert(1)', nightly_rate:100 }));
// The actual renderer uses attributes containing encoded JSON, with no JavaScript source interpolation.
assert(admin.node('users-tbody').innerHTML.includes('data-args="[&quot;'));
index.context.api.from = () => ({ select() { return this; }, eq() { return this; }, order() { return this; },
    limit() { return Promise.resolve({data:[{guest_name:payload, review_text:payload, rating:5, created_at:'2026-10-19'}]}); } });
await index.context.loadReviews();
noExecutablePayload(index.node('reviews-grid').innerHTML);
console.log('Stored-XSS rendering checks passed for legacy reservation, account, audit and room data; unsafe URL schemes are rejected.');
