"""Build the hardened InfinityFree update without changing an existing release's credentials."""
from pathlib import Path
import json
import secrets
import subprocess
import zipfile

root = Path(__file__).resolve().parents[1]
output = root / 'deployment' / 'security-update'
output.mkdir(parents=True, exist_ok=True)
(root / 'deployment/.htaccess').write_text('Require all denied\n')
manifest_path = output / 'release-private.json'
credentials = output / 'HOSTED-LOGIN-PRIVATE.txt'
archive = output / 'innpulse-security-update.zip'
if manifest_path.exists():
    manifest = json.loads(manifest_path.read_text())
    if not credentials.exists():
        raise SystemExit('Private credential file is missing. Recover it before rebuilding this release.')
else:
    passwords = {role: secrets.token_urlsafe(22) + '!A7z' for role in ['admin', 'staff']}
    result = subprocess.run(['php', '-r', '$v=json_decode(stream_get_contents(STDIN),true); foreach($v as &$p){$p=password_hash($p,PASSWORD_DEFAULT);} echo json_encode($v);'],
                            input=json.dumps(passwords), text=True, capture_output=True, check=True)
    manifest = {'release_id': secrets.token_hex(12), 'hashes': json.loads(result.stdout)}
    manifest_path.write_text(json.dumps(manifest, indent=2))
    credentials.write_text('PRIVATE - NEVER UPLOAD THIS FILE OR THE RELEASE MANIFEST.\n\n' +
        'This release replaces the passwords of the original admin.root and jane.staff accounts on first use, even on an existing hosted database. Other accounts are retained for your review.\n\n' +
        '\n\n'.join(f'{role.title()}\nEmail: {"qemespena@tip.edu.ph" if role == "admin" else "staff@innpulse.com"}\nTemporary password: {password}' for role,password in passwords.items()) +
        '\n\nAdmin email verification must be configured before you can sign in. Login requires the email verification code; there is no mandatory password-change screen. Change account passwords through approved account management when needed.\n', encoding='utf-8')
hashes = manifest['hashes']
bootstrap = (root/'app/bootstrap.php').read_text(encoding='utf-8')
constants = "\ndefine('INNPULSE_PUBLIC_BUILD', true);\ndefine('INNPULSE_RELEASE_ID', '" + manifest['release_id'] + "');\ndefine('INNPULSE_SEED_HASHES', ['admin'=>'" + hashes['admin'] + "', 'staff'=>'" + hashes['staff'] + "']);\n"
bootstrap = bootstrap.replace('declare(strict_types=1);', 'declare(strict_types=1);'+constants, 1)
bootstrap = bootstrap.replace("password_hash($role . '@innpulse', PASSWORD_DEFAULT)", "INNPULSE_SEED_HASHES[$role]")
files = [p for p in root.glob('*.php') if p.name != 'router.php']
for folder in ['js','app','database','assets']:
    files += [p for p in (root/folder).rglob('*') if p.is_file() and p.name != 'config.local.php' and p.name != 'input.css']
files += [root/'storage/.htaccess']
with zipfile.ZipFile(archive,'w',zipfile.ZIP_DEFLATED) as z:
    for p in sorted(set(files)):
        if p == root/'app/bootstrap.php': continue
        z.write(p,p.relative_to(root).as_posix())
    z.writestr('app/bootstrap.php',bootstrap)
    # Force HTTPS only in the public build, after SSL is installed.
    htaccess=(root/'.htaccess').read_text()+"\nRewriteEngine On\nRewriteCond %{HTTPS} !=on\nRewriteRule ^ https://innpulse.free.nf%{REQUEST_URI} [R=308,L]\n"
    z.writestr('.htaccess',htaccess)
with zipfile.ZipFile(archive) as z:
    assert not any(n.endswith('.sqlite') or 'PRIVATE' in n or n.endswith('config.local.php') for n in z.namelist())
    assert 'sqliteCreateFunction' not in z.read('api.php').decode()
    assert 'INNPULSE_PUBLIC_BUILD' in z.read('app/bootstrap.php').decode()
print('Hardened upload ZIP: '+str(archive))
print('Private credentials: '+str(credentials))
print('Existing database is excluded. Provider secrets must be configured separately.')
