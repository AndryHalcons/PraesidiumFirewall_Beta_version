#!/usr/bin/env python3
"""
Test: test_sudoers_static_scope.py

Objetivo:
    Revisar estaticamente installation/permissions.sh para detectar sudoers con
    comodines amplios o NOPASSWD demasiado generico.

Tipo:
    security / no destructivo

Riesgo que cubre:
    Evita que www-data pueda ejecutar comandos arbitrarios como root por error.

Seguridad:
    Solo lee installation/permissions.sh.
"""
from pathlib import Path
import re
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

path = repo_root() / 'installation/permissions.sh'
text = path.read_text(encoding='utf-8', errors='ignore')
errors = []
for line in text.splitlines():
    if 'NOPASSWD' not in line:
        continue
    stripped = line.strip()
    if 'ALL' in stripped and re.search(r'NOPASSWD:\s*ALL', stripped):
        errors.append(f'Nopasswd amplio: {stripped}')
    if '*' in stripped:
        errors.append(f'Nopasswd con wildcard: {stripped}')
if errors:
    fail('sudoers static scope', errors)
pass_('sudoers static scope')
