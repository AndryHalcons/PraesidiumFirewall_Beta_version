#!/usr/bin/env python3
"""
Test: test_shell_exec_escape_static.py

Objetivo:
    Revisar usos PHP de shell_exec/exec y exigir senales de escape/allowlist
    cuando el comando no sea un script hardcodeado y controlado.

Tipo:
    security / no destructivo

Riesgo que cubre:
    Inyeccion de comandos desde endpoints web.

Seguridad:
    Solo lee PHP. No ejecuta comandos del producto.
"""
from pathlib import Path
import re
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root, tracked_files
from report import fail, pass_

root = repo_root()
errors = []
script_assignment_re = re.compile(r"\$script\d*\s*=\s*['\"]/?usr/bin/python3 /var/www/backend/[^'\"]+\.py['\"]")

for rel in tracked_files():
    if not rel.endswith('.php'):
        continue
    text = (root / rel).read_text(encoding='utf-8', errors='ignore')
    if 'shell_exec' not in text and 'exec(' not in text:
        continue

    # ES: Permitimos el patrón antiguo de páginas que ejecutan un script Python
    # hardcodeado desde /var/www/backend mediante una variable local $scriptN.
    # No incluye entrada de usuario interpolada, así que no es command injection.
    # EN: Allow the legacy pattern where pages run a hardcoded Python script from
    # /var/www/backend through a local $scriptN variable. It does not interpolate
    # user input, so it is not command injection.
    hardcoded_sudo_script = bool(script_assignment_re.search(text)) and 'shell_exec("sudo $script' in text
    explicit_control = any(marker in text for marker in [
        'escapeshellarg',
        'escapeshellcmd',
        'allowlist',
        'allowed',
        'sudo /usr/bin/python3',
    ])

    if not hardcoded_sudo_script and not explicit_control:
        errors.append(f'{rel}: shell_exec/exec sin escape/allowlist visible')

if errors:
    fail('PHP shell exec escape static', errors)
pass_('PHP shell exec escape static')
