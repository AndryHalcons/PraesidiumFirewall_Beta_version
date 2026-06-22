#!/usr/bin/env python3
"""
Test: test_installer_shell_safety_static.py

Objetivo:
    Auditoria estatica minima de scripts de instalacion: detectar scripts sin
    `set -e` y comandos peligrosos que requieren revision.

Tipo:
    security / no destructivo

Riesgo que cubre:
    Instaladores que continuan tras errores o borran rutas amplias sin guardas.

Seguridad:
    Solo lee scripts .sh.
"""
from pathlib import Path
import re
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

root = repo_root()
errors = []
for path in sorted((root / 'installation').glob('*.sh')):
    text = path.read_text(encoding='utf-8', errors='ignore')
    rel = str(path.relative_to(root))
    if 'set -e' not in text and path.name not in {'dev_installer.sh'}:
        errors.append(f'{rel}: no contiene set -e visible')
    if re.search(r'rm\s+-rf\s+/(\s|$)', text):
        errors.append(f'{rel}: contiene rm -rf / literal')
if errors:
    fail('installer shell safety static', errors)
pass_('installer shell safety static')
