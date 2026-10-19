"""Integration checks against disposable SQLite databases in both security modes."""
from pathlib import Path
import http.cookiejar
import contextlib
import json
import os
import re
import socket
import sqlite3
import subprocess
import tempfile
import time
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parents[1]

def run_mode(lab):
    with tempfile.TemporaryDirectory(prefix='innpulse-test-') as temp:
        path = str(Path(temp) / 'test.sqlite')
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0))
            port = sock.getsockname()[1]
        env = dict(os.environ, INNPULSE_DB_PATH=path, INNPULSE_LAB_MODE=str(int(lab)))
        log = open(Path(temp) / 'server.log', 'w+')
        server = subprocess.Popen(['php', '-S', f'127.0.0.1:{port}', 'router.php'], cwd=ROOT, env=env, stdout=log, stderr=log,
                                  creationflags=subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0)
        base = f'http://127.0.0.1:{port}/'
        client = urllib.request.build_opener(urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        def call(action, **payload):
            req = urllib.request.Request(base + 'api.php', json.dumps(dict(action=action, **payload)).encode(),
                                         {'Content-Type': 'application/json', 'X-InnPulse-Request': '1'})
            try:
                with client.open(req) as response: return response.status, json.load(response)
            except urllib.error.HTTPError as error: return error.code, json.load(error)
        def query(table, operation='select', **payload):
            return call('query', table=table, operation=operation, **payload)
        try:
            for _ in range(100):
                try:
                    with client.open(base): pass
                    break
                except urllib.error.URLError: time.sleep(0.05)
            else: raise AssertionError('PHP server did not start')
            assert call('session')[1]['data'] is None
            assert 'lm-form' in client.open(base + 'admin.php').read().decode()  # redirects to login
            for blocked in ['storage/innpulse.sqlite', 'database/seed.sql', 'app/bootstrap.php', '.git/config']:
                try: client.open(base + blocked); raise AssertionError('Private file was served: ' + blocked)
                except urllib.error.HTTPError as e: assert e.code == 404
            rooms = query('rooms')[1]['data']
            assert len(rooms) == 13 and isinstance(rooms[0]['amenities'], list)
            assert query('profiles')[0] == 401
            assert query('reviews')[1]['data'] and all(r['approved'] for r in query('reviews')[1]['data'])
            assert query('reviews', 'insert', values={'guest_name': 'Student', 'rating': 5, 'review_text': 'Test', 'approved': True})[0] == 200
            assert not any(r['guest_name'] == 'Student' for r in query('reviews')[1]['data'])
            room = next(r for r in rooms if r['status'] == 'available')
            booking = dict(guest_name="Student O'Neil", guest_email='student@example.test', room_id=room['id'], check_in='2026-11-01', check_out='2026-11-03', guest_count=1)
            assert call('book', reservation=dict(booking, check_out='2026-10-30'))[0] == 400
            result = call('book', reservation=booking)
            assert result[0] == 200, result
            assert call('book', reservation=booking)[0] == 400
            spare = next(r for r in rooms if r['status'] == 'available' and r['id'] != room['id'])
            assert call('book', reservation=dict(booking, room_id=spare['id']), receipt='invalid')[0] == 400
            assert next(r for r in query('rooms')[1]['data'] if r['id'] == spare['id'])['status'] == 'available'
            payload = "admin@innpulse.com' -- "
            status, result = call('login', email=payload, password='wrong')
            assert status == (200 if lab else 401), result
            if lab: assert result['data']['role'] == 'admin'; call('logout')
            for _ in range(11): last = call('login', email='missing@example.test', password='wrong')
            assert last[0] == (401 if lab else 429), last
            with contextlib.closing(sqlite3.connect(path)) as db: db.execute('DELETE FROM login_attempts'); db.commit()
            status, admin = call('login', email='admin@innpulse.com', password='admin@innpulse')
            assert status == 200 and 'password_hash' not in admin['data']
            for page in ['admin', 'audit-logs', 'schema', 'system-config', 'front-desk']:
                assert client.open(base + page + '.php').status == 200
            users = query('profiles')[1]['data']; assert len(users) == 2
            status, created = query('profiles', 'insert', values=dict(username='student', email='student@example.test', password='classroom123', role='staff'))
            assert status == 200, created
            uid = created['data']['id']
            assert query('profiles', 'update', filters=[['id', '=', uid]], values={'password': 'newpass123'})[0] == 200
            assert query('profiles', 'update', filters=[['id', '=', admin['data']['id']]], values={'locked': True})[0] == 400
            temporary = query('profiles', 'insert', values=dict(username='temporary', email='temporary@example.test', password='classroom123', role='staff'))[1]['data']
            assert query('profiles', 'delete', filters=[['id', '=', temporary['id']]])[0] == 200
            assert query('profiles', filters=[['id', '=', temporary['id']]])[1]['data'] == []
            assert query('system_config', 'upsert', values={'config_key': 'hotel_name', 'config_value': 'SQLite Hotel', 'updated_at': '2026-10-12T00:00:00Z'})[0] == 200
            assert query('system_config', filters=[['config_key', '=', 'hotel_name']])[1]['data'][0]['config_value'] == 'SQLite Hotel'
            joined = query('reservations', columns='*, rooms(room_number, room_name), deposits(status, receipt_url, amount)')[1]['data']
            saved = next(r for r in joined if r['guest_name'] == booking['guest_name'])
            assert saved['rooms']['room_number'] == room['room_number'] and saved['deposits'][0]['status'] == 'pending'
            assert any('Failed login' in r['event'] for r in query('audit_logs')[1]['data'])
            call('logout')
            assert call('login', email='student@example.test', password='newpass123')[0] == 200
            assert query('profiles')[0] == 403 and query('audit_logs')[0] == 403
            try: client.open(base + 'admin.php'); raise AssertionError('Staff accessed admin page')
            except urllib.error.HTTPError as e: assert e.code == 403
            call('logout')
            assert query('reservations')[0] == 401
            # A manually locked account must also lose its existing session.
            call('login', email='student@example.test', password='newpass123')
            with contextlib.closing(sqlite3.connect(path)) as db: db.execute('UPDATE profiles SET locked=1 WHERE id=?', [uid]); db.commit()
            assert call('session')[1]['data'] is None
            assert call('login', email='student@example.test', password='newpass123')[0] == 401
            print(('Lab' if lab else 'Protected') + ' mode: integration checks passed')
        except Exception:
            log.flush(); log.seek(0); print(log.read()); raise
        finally:
            server.terminate(); server.wait(timeout=10); log.close()

for php in [*ROOT.glob('*.php'), *ROOT.glob('app/*.php')]:
    subprocess.run(['php', '-l', str(php)], check=True, capture_output=True)
for page in ROOT.glob('*.php'):
    for script in re.findall(r'<script\b[^>]*>(.*?)</script>', page.read_text(encoding='utf-8'), re.S):
        result = subprocess.run(['node', '--input-type=module', '--check'], input=script, text=True, encoding='utf-8', capture_output=True)
        assert result.returncode == 0, f'{page.name}: {result.stderr}'
for script in ROOT.glob('js/*.js'):
    result = subprocess.run(['node', '--input-type=module', '--check'], input=script.read_text(encoding='utf-8'), text=True, encoding='utf-8', capture_output=True)
    assert result.returncode == 0, f'{script.name}: {result.stderr}'
run_mode(False)
run_mode(True)
print('PHP and JavaScript syntax checks passed')

