#!/usr/bin/env python3
"""
Test: test_test_readmes_complete.py

Objetivo:
    Verificar que las carpetas de tests mantienen README.md como mapa breve.

Tipo:
    safe / no destructivo

Riesgo que cubre:
    Evita que la suite crezca como un cajon de tests sin documentacion.

Seguridad:
    Solo lee README.md dentro de tests/.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

root = repo_root()
required_dirs = [root / 'tests', root / 'tests/test_profiles', root / 'tests/test_modules', root / 'tests/fixtures', root / 'tests/lib']
required_dirs += sorted((root / 'tests/test_modules').glob('*_test'))
errors = []
for directory in required_dirs:
    readme = directory / 'README.md'
    if not readme.exists():
        errors.append(f'{directory.relative_to(root)}: falta README.md')
        continue
    text = readme.read_text(encoding='utf-8', errors='ignore')
    if len(text.strip()) < 80:
        errors.append(f'{readme.relative_to(root)}: README demasiado corto')
if errors:
    fail('test README completeness', errors)
pass_('test README completeness', f'checked_dirs={len(required_dirs)}')
