#!/usr/bin/env python3
"""
Test: test_no_old_praesidium_repo_refs.py

Objetivo:
    Buscar referencias al repositorio antiguo de PraesidiumFirewall en archivos
    versionados, excluyendo referencias historicas deliberadas si se allowlistean.

Tipo:
    safe / no destructivo

Riesgo que cubre:
    Evita que README, instalador o docs clonen desde el repo antiguo.

Seguridad:
    Solo lee archivos versionados.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root, tracked_files
from report import fail, pass_

root = repo_root()
needles = [
    'https://github.com/AndryHalcons/' + 'PraesidiumFirewall.git',
    'git@github.com:AndryHalcons/' + 'PraesidiumFirewall.git',
]
findings = []
for rel in tracked_files():
    if rel.startswith('.git/') or rel.endswith('.pyc') or rel in {
        'tests/test_profiles/safe/test_no_old_praesidium_repo_refs.py',
        'tests/test_profiles/safe/test_readme_beta_repo_url.py',
    }:
        continue
    path = root / rel
    try:
        text = path.read_text(encoding='utf-8', errors='ignore')
    except Exception:
        continue
    for needle in needles:
        if needle in text:
            findings.append(f'{rel}: contiene {needle}')
if findings:
    fail('old Praesidium repo refs', findings)
pass_('old Praesidium repo refs')
