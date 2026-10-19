"""Exercise defenses on a disposable localhost server. No public website is attacked."""
from pathlib import Path
import contextlib
import http.cookiejar
import json
import os
import socket
import sqlite3
import subprocess
import tempfile
import time
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
ADMIN = 'qemespena@tip.edu.ph'
STRONG = 'Cedar!River7Moonlight'

def main():
    with tempfile.TemporaryDirectory(prefix='innpulse-security-') as temp:
        temp = Path(temp)
        database = temp / 'security.sqlite'
        # Start with the former schema and an already-poisoned reservation, rather than an empty DB.
        hashes = json.loads(subprocess.check_output(['php', '-r', 'echo json_encode([password_hash("admin@innpulse",PASSWORD_DEFAULT),password_hash("staff@innpulse",PASSWORD_DEFAULT)]);'], text=True))
        with contextlib.closing(sqlite3.connect(database)) as legacy:
            legacy.executescript((ROOT/'database/schema.sql').read_text(encoding='utf-8'))
            legacy.executescript((ROOT/'database/seed.sql').read_text(encoding='utf-8'))
            for name, email, role, hashed in [('admin.root','admin@innpulse.com','admin',hashes[0]),('jane.staff','staff@innpulse.com','staff',hashes[1])]:
                legacy.execute('INSERT INTO profiles(username,email,role,password_hash) VALUES(?,?,?,?)', [name,email,role,hashed])
            legacy.execute('UPDATE reservations SET guest_name=? WHERE booking_ref=?', ['<img src=x onerror=alert(1)>','INP-2026-0001'])
            legacy.commit()
        outbox = temp / 'outbox'; outbox.mkdir()
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0)); port = sock.getsockname()[1]
        env = dict(os.environ, INNPULSE_DB_PATH=str(database), INNPULSE_TEST_MODE='1', INNPULSE_TEST_MAIL_DIR=str(outbox))
        log = open(temp / 'server.log', 'w+', encoding='utf-8')
        server = subprocess.Popen(['php', '-S', f'127.0.0.1:{port}', 'router.php'], cwd=ROOT, env=env, stdout=log, stderr=log,
            creationflags=subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0)
        base = f'http://127.0.0.1:{port}/'
        class Client:
            def __init__(self):
                self.jar = http.cookiejar.CookieJar(); self.csrf = ''
                self.http = urllib.request.build_opener(urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(self.jar))
            def call(self, action, csrf=True, **payload):
                if not self.csrf and action != 'csrf': self.call('csrf')
                headers = {'Content-Type': 'application/json', 'X-InnPulse-Request': '1'}
                if csrf: headers['X-CSRF-Token'] = self.csrf
                req = urllib.request.Request(base + 'api.php', json.dumps(dict(action=action, **payload)).encode(), headers)
                try: response = self.http.open(req, timeout=20)
                except urllib.error.HTTPError as error: response = error
                with response:
                    result = json.load(response)
                    self.csrf = result.get('csrf', self.csrf)
                    return response.status, result
            def query(self, table, operation='select', **payload): return self.call('query', table=table, operation=operation, **payload)
        def sql(statement, args=()):
            with contextlib.closing(sqlite3.connect(database)) as conn:
                rows = conn.execute(statement, args).fetchall(); conn.commit(); return rows
        def code(challenge): return json.loads((outbox / (challenge + '.json')).read_text())['code']
        def clear_limits():
            sql('DELETE FROM auth_limits'); sql('DELETE FROM security_requests')
        def authorized_mutation(client, mutation, password='admin@innpulse'):
            status, challenge = client.call('authorize_account_change', password=password, mutation=mutation)
            assert status == 200, challenge
            cid = challenge['data']['challenge_id']
            return client.call('query', **mutation, change_id=cid, change_code=code(cid))
        try:
            public = Client()
            for _ in range(100):
                try:
                    with public.http.open(base, timeout=1) as response:
                        html = response.read().decode(); headers = response.headers
                    break
                except urllib.error.URLError: time.sleep(.05)
            else: raise AssertionError('Local PHP server unavailable')
            assert "script-src-attr 'none'" in headers['Content-Security-Policy']
            assert 'unsafe-eval' not in headers['Content-Security-Policy']
            assert headers['X-Content-Type-Options'] == 'nosniff'
            assert '<script type="module" src=' in html and 'onclick=' not in html
            assert public.call('session', csrf=False)[0] == 403
            assert public.query('profiles')[0] == 401
            assert sql('SELECT COUNT(*) FROM reservations')[0][0] == 8
            assert sql('SELECT guest_name FROM reservations WHERE booking_ref=?',['INP-2026-0001'])[0][0].startswith('<img')
            for path in ['app/config.local.php', 'database/schema.sql', 'storage/innpulse.sqlite', 'deployment/HOSTED-LOGIN-PRIVATE.txt', '.git/config']:
                try: public.http.open(base + path); raise AssertionError('Private path exposed: ' + path)
                except urllib.error.HTTPError as error: assert error.code == 404
            # SQL injection is rejected even if the old lab environment flag is present.
            status, _ = public.call('login', email="qemespena@tip.edu.ph' --", password='wrong')
            assert status == 401
            assert public.call('auth_options', email=ADMIN)[1]['data']['captcha_required']
            clear_limits()
            # Injection payloads are password/email data, never executable SQL.
            for email, password in [("' OR 1=1 --", 'wrong'), (ADMIN, "' OR '1'='1' --"),
                                    ("' UNION SELECT * FROM profiles --", 'wrong'),
                                    (ADMIN, "'; DROP TABLE profiles; --")]:
                probe = Client()
                assert probe.call('login', email=email, password=password)[0] == 401
                assert probe.call('session')[1]['data'] is None
                clear_limits()
            assert Client().call('login', email=['admin'], password={'sql': 'OR 1=1'})[0] == 400
            # No admin session exists between password and second factor; codes are session-bound.
            admin = Client()
            status, pending = admin.call('login', email=ADMIN, password='admin@innpulse')
            assert status == 200, pending
            cid = pending['data']['challenge_id']
            assert json.loads((outbox/(cid+'.json')).read_text())['recipient'] == ADMIN
            assert admin.call('session')[1]['data'] is None and admin.query('profiles')[0] == 401
            stranger = Client()
            assert stranger.call('verify_login', challenge_id=cid, code=code(cid))[0] == 401
            assert admin.call('verify_login', challenge_id=cid, code='000000')[0] == 401
            assert admin.call('verify_login', challenge_id=cid, code=code(cid))[1]['data']['role'] == 'admin'
            assert admin.call('verify_login', challenge_id=cid, code=code(cid))[0] == 401
            assert admin.call('change_initial_password', password='Short1!')[0] == 410
            assert 'password_hash' not in admin.query('profiles')[1]['data'][0]
            # Dynamic query identifiers, operators and sort directions must be allowlisted.
            before_schema = sql("SELECT name,sql FROM sqlite_master WHERE type='table' ORDER BY name")
            before_rooms = sql('SELECT * FROM rooms ORDER BY id')
            for payload in [
                {'table': 'rooms; DROP TABLE profiles; --'},
                {'table': 'rooms', 'filters': [["id\" OR 1=1 --", '=', 'x']]},
                {'table': 'rooms', 'filters': [['id', '= ? OR 1=1 --', 'x']]},
                {'table': 'rooms', 'order': ['id; DROP TABLE rooms; --', True]},
                {'table': 'rooms', 'order': ['id', 'DESC; DROP TABLE rooms; --']},
                {'table': 'rooms', 'limit': '1; DROP TABLE rooms; --'},
                {'table': 'rooms', 'filters': [['id', 'IN', [['nested']]]]},
                {'table': 'rooms', 'filters': [['id']]},
                {'table': 'rooms', 'filters': 'id=1 OR 1=1'},
            ]:
                status, rejected = public.call('query', **payload)
                assert status == 400, rejected
                assert 'SQLSTATE' not in json.dumps(rejected)
            for operator, value in [('=', "' OR 1=1 --"), ('IN', ["x') OR 1=1 --", "'; DROP TABLE rooms; --"])]:
                assert public.query('rooms', filters=[['id', operator, value]])[1]['data'] == []
            # An injection in a write filter must not turn a targeted mutation into a bulk write.
            assert admin.query('rooms', 'update', filters=[['id', '=', "' OR 1=1 --"]], values={'status': 'maintenance'})[0] == 200
            assert admin.query('reviews', 'delete', filters=[['id', '=', "' OR 1=1 --"]])[0] == 200
            assert admin.query('rooms', 'update', filters=[['id', '=', 'none']], values={'status" = ?; DROP TABLE profiles; --': 'x'})[0] == 403
            # Apostrophes/SQL-looking text stay intact in storage and subsequent reads.
            literal = "O'Brien'); DROP TABLE profiles; --"
            status, saved = public.query('reviews', 'insert', values={'guest_name': "O'Brien", 'rating': 5, 'review_text': literal})
            assert status == 200 and saved['data']['review_text'] == literal, saved
            assert admin.query('reviews', filters=[['id', '=', saved['data']['id']]])[1]['data'][0]['review_text'] == literal
            assert sql('SELECT * FROM rooms ORDER BY id') == before_rooms
            assert sql("SELECT name,sql FROM sqlite_master WHERE type='table' ORDER BY name") == before_schema
            assert sql('SELECT COUNT(*) FROM profiles')[0][0] == 2
            print('SQL injection checks passed: login, identifiers, operators, sorting, limits, IN filters, write scope and stored literal text.')
            # Cookie theft cannot authorize a new account without password + a separate, payload-bound email code.
            mutation = dict(table='profiles', operation='insert', values=dict(username='student', email='student@example.test', role='staff', password='Glacier!Orbit7Lantern'))
            assert admin.call('query', **mutation)[0] == 401
            assert admin.call('authorize_account_change', password='wrong', mutation=mutation)[0] == 401
            challenge = admin.call('authorize_account_change', password='admin@innpulse', mutation=mutation)[1]['data']['challenge_id']
            tampered = dict(mutation, values=dict(mutation['values'], role='admin'))
            assert admin.call('query', **tampered, change_id=challenge, change_code=code(challenge))[0] == 401
            status, created = admin.call('query', **mutation, change_id=challenge, change_code=code(challenge))
            assert status == 200, created
            uid = created['data']['id']
            assert admin.call('query', **mutation, change_id=challenge, change_code=code(challenge))[0] == 401
            assert authorized_mutation(admin, dict(table='profiles', operation='update', filters=[['id', '=', uid]], values={'password_hash': 'evil'}))[0] == 403
            assert admin.query('audit_logs', 'delete', filters=[['id', '!=', '']])[0] == 403
            # Public validation rejects new HTML; existing poisoned records remain raw in storage, encoded at render time.
            rooms = public.query('rooms')[1]['data']; room = next(r for r in rooms if r['status'] == 'available')
            booking = dict(guest_name='Student', guest_email='student@example.test', guest_phone='123', room_id=room['id'], check_in='2026-11-01', check_out='2026-11-03', guest_count=1)
            assert public.call('book', reservation=dict(booking, guest_name='<img src=x onerror=alert(1)>'))[0] == 400
            assert public.call('book', reservation=dict(booking, special_requests='<script>alert(1)</script>'))[0] == 400
            assert public.call('book', reservation=booking)[0] == 200
            assert public.call('book', reservation=booking)[0] == 400
            assert public.query('reviews', 'insert', values={'guest_name': '<svg/onload=alert(1)>', 'rating': 5, 'review_text': 'x'})[0] == 400
            assert public.query('reviews', 'insert', values={'guest_name': 'Student', 'rating': 5, 'review_text': 'Review', 'approved': True})[0] == 200
            assert not any(r['guest_name'] == 'Student' for r in public.query('reviews')[1]['data'])
            assert admin.query('system_config', 'upsert', values={'config_key': 'hotel_name', 'config_value': 'Protected InnPulse', 'updated_at': '2026-10-19T00:00:00Z'})[0] == 200
            # Staff cannot promote themselves, inspect users, or write privileged settings.
            staff = Client()
            sql("DELETE FROM security_requests WHERE category='email-codes'")
            status, pending = staff.call('login', email='student@example.test', password='Glacier!Orbit7Lantern', recipient='attacker@example.test')
            assert status == 200 and pending['data']['mfa_required'], pending
            staff_challenge = pending['data']['challenge_id']
            assert json.loads((outbox/(staff_challenge+'.json')).read_text())['recipient'] == 'student@example.test'
            assert staff.call('session')[1]['data'] is None
            assert staff.call('change_initial_password', password='Compass!Forest8Silver')[0] == 410
            assert staff.call('verify_login', challenge_id=staff_challenge, code=code(staff_challenge))[1]['data']['role'] == 'staff'
            assert staff.query('profiles')[0] == 403
            assert staff.query('system_config')[0] == 403
            assert staff.query('audit_logs')[0] == 403
            try: staff.http.open(base + 'admin.php'); raise AssertionError('Staff reached admin page')
            except urllib.error.HTTPError as error: assert error.code == 403
            assert authorized_mutation(admin, dict(table='profiles', operation='update', filters=[['id', '=', uid]], values={'locked': True}))[0] == 200
            assert staff.call('session')[1]['data'] is None
            # Account lockout persists across new browser sessions and records the reason.
            clear_limits()
            for number in range(5):
                sql('UPDATE auth_limits SET next_allowed=0')
                attacker = Client()
                status, failure = attacker.call('login', email='locked@example.test', password='wrong', captcha_token='test-captcha-pass')
                assert status == (429 if number == 4 else 401), failure
                assert failure['error']['attempts_remaining'] == max(0,4-number), failure
                if number == 4: assert 175 <= failure['error']['retry_after'] <= 180
                if number == 0:
                    assert attacker.call('login', email='locked@example.test', password='wrong')[0] == 429
            row = sql("SELECT failures, blocked_until FROM auth_limits WHERE scope='account'")[0]
            assert row[0] == 5 and time.time() + 170 < row[1] <= time.time() + 181
            assert Client().call('login', email='locked@example.test', password='anything', captcha_token='test-captcha-pass')[0] == 429
            assert any('lockout' in r['event'] for r in admin.query('audit_logs')[1]['data'])
            sql('UPDATE auth_limits SET blocked_until=?,next_allowed=0', [int(time.time())-1])
            options = Client().call('auth_options', email='locked@example.test')[1]['data']
            assert options['retry_after'] == 0 and options['attempts_remaining'] == 5 and not options['locked']
            status, restarted = Client().call('login', email='locked@example.test', password='wrong', captcha_token='test-captcha-pass')
            assert status == 401 and restarted['error']['attempts_remaining'] == 4
            clear_limits()
            sql("UPDATE auth_limits SET failures=1")
            # Persistent IP request limiter ignores fresh cookies.
            for number in range(40):
                sql('DELETE FROM auth_limits')
                assert Client().call('login', email=f'guess{number}@example.test', password='wrong', captcha_token='test-captcha-pass')[0] == 401
            assert Client().call('login', email=ADMIN, password='admin@innpulse')[0] == 429
            clear_limits()
            sql('UPDATE auth_challenges SET expires=0')
            expired = Client()
            pending = expired.call('login', email=ADMIN, password='admin@innpulse')[1]['data']['challenge_id']
            sql('UPDATE auth_challenges SET expires=0 WHERE id=?', [pending])
            assert expired.call('verify_login', challenge_id=pending, code=code(pending))[0] == 401
            # Legacy PHP session IDs lack the new version/MFA markers and are rejected by current_profile.
            admin.call('logout'); assert admin.query('reservations')[0] == 401
            print('Security integration passed: MFA, code replay/expiry/binding, CSRF, throttling, lockout, account-change approval, field permissions, XSS input validation, bookings and session revocation.')
        except Exception:
            log.flush(); log.seek(0); print(log.read()[-4000:]); raise
        finally:
            server.terminate(); server.wait(timeout=10); log.close()

if __name__ == '__main__': main()
