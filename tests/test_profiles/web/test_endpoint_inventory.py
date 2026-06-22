#!/usr/bin/env python3
"""
Test: test_endpoint_inventory.py

Objetivo:
    Generar una comprobacion estatica de endpoints PHP importantes y fallar si
    no existe ningun endpoint por modulo funcional esperado.

Tipo:
    web / no destructivo

Riesgo que cubre:
    Detecta borrados accidentales de familias de endpoints Web UI.

Seguridad:
    Solo lee el arbol de archivos versionado.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from module_metadata import MODULES
from report import fail, pass_

root = repo_root()
errors = []
for module, cfg in MODULES.items():
    endpoints = cfg.get('endpoints', [])
    existing = [rel for rel in endpoints if (root / rel).exists()]
    if not existing:
        errors.append(f'{module}: sin endpoints existentes')
if errors:
    fail('endpoint inventory', errors)
pass_('endpoint inventory', f'modules={len(MODULES)}')
