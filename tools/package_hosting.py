"""Build a public-demo upload archive without local data or classroom vulnerabilities."""
from pathlib import Path
import json
import secrets
import subprocess
import zipfile

root = Path(__file__).resolve().parents[1]
output = root / 'deployment'
output.mkdir(exist_ok=True)
(output / '.htaccess').write_text('Require all denied\n', encoding='utf-8')
archive = output / 'innpulse-infinityfree.zip'
credentials = output / 'HOSTED-LOGIN-PRIVATE.txt'
# Avoid silently changing login details when rebuilding an existing release.
if archive.exists() or credentials.exists():
    raise SystemExit('A release already exists in deployment/. Keep its credentials with its ZIP before creating another release.')
passwords = {role: secrets.token_urlsafe(20) for role in ['admin', 'staff']}
result = subprocess.run(['php', '-r', '$v=json_decode(stream_get_contents(STDIN),true); foreach($v as &$p){$p=password_hash($p,PASSWORD_DEFAULT);} echo json_encode($v);'],
                        input=json.dumps(passwords), text=True, capture_output=True, check=True)
hashes = json.loads(result.stdout)
bootstrap = (root / 'app/bootstrap.php').read_text(encoding='utf-8')
bootstrap = bootstrap.replace("password_hash($role . '@innpulse', PASSWORD_DEFAULT)",
                              "($role === 'admin' ? '" + hashes['admin'] + "' : '" + hashes['staff'] + "')")
old = "return getenv('INNPULSE_LAB_MODE') === '1' && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);"
assert old in bootstrap
bootstrap = bootstrap.replace(old, 'return false; // Public hosting build: classroom lab mode is disabled.')
api = (root / 'api.php').read_text(encoding='utf-8')
start = api.index('        if (lab_mode()) {\n            // INTENTIONAL CLASSROOM VULNERABILITY:')
end = api.index('        if (!$user ||', start)
api = api[:start] + '''        $s = $pdo->prepare('SELECT * FROM profiles WHERE email = ?'); $s->execute([$email]); $user = $s->fetch();
        if ($user && !password_verify($password, $user['password_hash'])) $user = false;
''' + api[end:]
files = [p for p in root.glob('*.php') if p.name not in ['router.php', 'api.php']]
files += [root / '.htaccess']
for folder in ['js', 'database']:
    files += [p for p in (root / folder).rglob('*') if p.is_file()]
files += [root / 'assets/css/output.css', root / 'app/.htaccess', root / 'database/.htaccess', root / 'storage/.htaccess']
with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED) as z:
    for p in sorted(set(files)):
        z.write(p, p.relative_to(root).as_posix())
    z.writestr('app/bootstrap.php', bootstrap)
    z.writestr('api.php', api)
credentials.write_text('PRIVATE — keep this file on your computer; never upload it.\n\n' +
    '\n\n'.join(f'{role.title()}\nEmail: {role}@innpulse.com\nPassword: {password}' for role, password in passwords.items()) +
    '\n\nThese passwords apply to a fresh hosted database created by the matching upload ZIP. Local demo credentials are unchanged.\n', encoding='utf-8')
with zipfile.ZipFile(archive) as z:
    assert not any(name.endswith('.sqlite') or 'node_modules/' in name or 'PRIVATE' in name for name in z.namelist())
    assert 'sqliteCreateFunction' not in z.read('api.php').decode()
    assert 'admin@innpulse' not in z.read('app/bootstrap.php').decode().split('password_hash')[-1]  # no demo password expression
print(f'Upload archive: {archive}')
print(f'Private login file: {credentials}')
print('Lab login removed; fresh hosted passwords generated; local database excluded.')
