#!/usr/bin/env python3
"""
Test: test_shell_exec_escape_static.py

Objetivo:
    Revisar usos PHP de shell_exec/exec y exigir senales de escape/allowlist.

Tipo:
    security / no destructivo

Riesgo que cubre:
    Inyeccion de comandos desde endpoints web.

Seguridad:
    Solo lee PHP.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root, tracked_files
from report import fail, pass_

root = repo_root()
errors = []
for rel in tracked_files():
    if not rel.endswith('.php'):
        continue
    text = (root / rel).read_text(encoding='utf-8', errors='ignore')
    if 'shell_exec' in text or 'exec(' in text:
        if not any(marker in text for marker in ['escapeshellarg', 'escapeshellcmd', 'allowlist', 'allowed', 'sudo /usr/bin/python3']):
            errors.append(f'{rel}: shell_exec/exec sin escape/allowlist visible')
if errors:
    fail('PHP shell exec escape static', errors)
pass_('PHP shell exec escape static')
