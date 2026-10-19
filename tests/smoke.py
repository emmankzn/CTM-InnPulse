"""Syntax, stored-XSS rendering and localhost defense integration suite."""
from pathlib import Path
import subprocess
from security_integration import main
ROOT = Path(__file__).resolve().parents[1]
for php in [*ROOT.glob('*.php'), *ROOT.glob('app/*.php')]:
    result = subprocess.run(['php', '-l', str(php)], capture_output=True, text=True, encoding='utf-8')
    assert result.returncode == 0, result.stdout + result.stderr
for script in ROOT.glob('js/*.js'):
    result = subprocess.run(['node', '--input-type=module', '--check'], input=script.read_text(encoding='utf-8'), text=True, encoding='utf-8', capture_output=True)
    assert result.returncode == 0, f'{script.name}: {result.stderr}'
subprocess.run(['node', str(ROOT / 'tests/render-security.mjs')], cwd=ROOT, check=True)
subprocess.run(['node', str(ROOT / 'tests/login-countdown.mjs')], cwd=ROOT, check=True)
subprocess.run(['php', str(ROOT / 'tests/mailer.php')], cwd=ROOT, check=True)
main()
print('PHP and JavaScript syntax checks passed.')
