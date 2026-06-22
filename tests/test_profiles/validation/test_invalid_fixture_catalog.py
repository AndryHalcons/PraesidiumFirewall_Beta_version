#!/usr/bin/env python3
"""
Test: test_invalid_fixture_catalog.py

Objetivo:
    Verificar que todos los modulos tienen fixtures invalidos suficientes.

Tipo:
    validation / no destructivo

Riesgo que cubre:
    Evita que los tests de validacion sean decorativos o esten vacios.

Seguridad:
    Solo lee fixtures dentro de tests/.
"""
from pathlib import Path
import json, sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from module_metadata import MODULES
from report import fail, pass_

root = repo_root()
errors = []
for module, cfg in MODULES.items():
    path = root / 'tests/test_modules' / cfg['dir'] / 'fixtures/invalid_payloads.json'
    if not path.exists():
        errors.append(f'{module}: falta invalid_payloads.json')
        continue
    data = json.loads(path.read_text(encoding='utf-8'))
    if len(data) < 2:
        errors.append(f'{module}: menos de 2 casos invalidos')
if errors:
    fail('invalid fixture catalog', errors)
pass_('invalid fixture catalog', f'modules={len(MODULES)}')
