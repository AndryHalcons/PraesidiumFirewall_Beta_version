#!/usr/bin/env python3
"""
Test: test_installer_initial_config.py

Objetivo:
    Asegurar que el instalador ejecuta la configuración inicial dependiente
    del sistema real, especialmente el bootstrap de interfaces.

Tipo:
    safe / no destructivo

Seguridad:
    Solo inspecciona archivos versionados.
"""
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

root = repo_root()
installer = (root / 'installation/installer.sh').read_text(encoding='utf-8')
initial = root / 'installation/initial_config.sh'
errors = []

if not initial.exists():
    errors.append('installation/initial_config.sh no existe')
else:
    content = initial.read_text(encoding='utf-8')
    required = [
        'set -euo pipefail',
        '/var/www/backend/checks/check_interfaces/main_interfaces_check.py',
        'python3 "$INTERFACES_CHECK"',
        'python3 -m json.tool "$INTERFACES_JSON"',
        'all_interfaces_list.json',
        'physical_interfaces_list.json',
        'chown -R :www-data',
        'chmod -R g+rw',
    ]
    for text in required:
        if text not in content:
            errors.append(f'initial_config.sh falta: {text}')

if 'chmod +x initial_config.sh' not in installer:
    errors.append('installer.sh no da permisos de ejecución a initial_config.sh')
if './initial_config.sh' not in installer:
    errors.append('installer.sh no ejecuta initial_config.sh')
if installer.find('./permissions.sh') > installer.find('./initial_config.sh'):
    errors.append('initial_config.sh debe ejecutarse después de permissions.sh')
if installer.find('./initial_config.sh') > installer.find('./install_bpfilter.sh'):
    errors.append('initial_config.sh debe ejecutarse antes de install_bpfilter.sh')

if errors:
    fail('installer initial config bootstrap', errors)
pass_('installer initial config bootstrap')
