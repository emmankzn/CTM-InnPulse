/** Local PHP/SQLite transport. Query builder preserves the existing page rendering. */
export async function request(payload) {
    try {
        const response = await fetch('api.php', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-InnPulse-Request': '1' },
            body: JSON.stringify(payload),
        });
        return await response.json();
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
    login: (email, password) => request({ action: 'login', email, password }),
    logout: () => request({ action: 'logout' }),
};
export default api;
