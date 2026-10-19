import { secureDialog } from './security.js';
let csrf = '';
async function send(payload) {
    const response = await fetch('api.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-InnPulse-Request': '1', 'X-CSRF-Token': csrf },
        body: JSON.stringify(payload),
    });
    const result = await response.json();
    if (result.error && !result.error.retry_after) result.error.retry_after = Number(response.headers.get('Retry-After') || 0);
    if (result.csrf) csrf = result.csrf;
    return result;
}
/** Same-origin transport with CSRF and per-operation account-change approval. */
export async function request(payload) {
    try {
        if (!csrf) await send({ action: 'csrf' });
        if (payload.action === 'query' && payload.table === 'profiles' && payload.operation !== 'select' && !payload.change_id) {
            const approval = await secureDialog({ title: 'Approve account change',
                description: 'Confirm your current admin password. We will send a code to your registered email for this exact change.',
                fields: [{ name: 'password', label: 'Your admin password', type: 'password', autocomplete: 'current-password' }],
                onSubmit: values => send({ action: 'authorize_account_change', password: values.password, mutation: payload }) });
            if (!approval) return { data: null, error: { message: 'Account change cancelled.' } };
            const result = await secureDialog({ title: 'Verify account change', description: 'Enter the six-digit email code. It expires in five minutes.',
                fields: [{ name: 'code', label: 'Email code', numeric: true, maxLength: 6, autocomplete: 'one-time-code' }],
                onSubmit: values => send({ ...payload, change_id: approval.data.challenge_id, change_code: values.code }) });
            return result || { data: null, error: { message: 'Account change cancelled.' } };
        }
        return await send(payload);
    } catch (error) { return { data: null, error: { message: 'Cannot reach the PHP server.' } }; }
}
class Query {
    constructor(table) { this.payload = { action: 'query', table, operation: 'select', filters: [] }; }
    select(columns = '*') { this.payload.columns = columns; return this; }
    insert(values) { this.payload.operation = 'insert'; this.payload.values = values; return this; }
    update(values) { this.payload.operation = 'update'; this.payload.values = values; return this; }
    upsert(values) { this.payload.operation = 'upsert'; this.payload.values = values; return this; }
    delete() { this.payload.operation = 'delete'; return this; }
    eq(column, value) { this.payload.filters.push([column, '=', value]); return this; }
    neq(column, value) { this.payload.filters.push([column, '!=', value]); return this; }
    in(column, value) { this.payload.filters.push([column, 'IN', value]); return this; }
    order(column, options = {}) { this.payload.order = [column, options.ascending !== false]; return this; }
    limit(value) { this.payload.limit = value; return this; }
    single() { this.payload.single = true; return this; }
    then(resolve, reject) { return request(this.payload).then(resolve, reject); }
    catch(reject) { return this.then(value => value, reject); }
}
const api = {
    from: table => new Query(table),
    session: async () => (await request({ action: 'session' })).data,
    login: (email, password, captcha_token = '') => request({ action: 'login', email, password, captcha_token }),
    logout: () => request({ action: 'logout' }),
};
export default api;
