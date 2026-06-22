#!/usr/bin/env python3
"""
Test: test_common_invalid_payloads.py

Objetivo:
    Verificar que existen fixtures comunes de payloads invalidos para reutilizar
    en tests por modulo.

Tipo:
    validation / no destructivo

Riesgo que cubre:
    Evita que cada modulo invente cadenas de ataque distintas y dificiles de mantener.

Seguridad:
    Solo lee fixtures; no toca endpoints ni runtime.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

root = repo_root()
required = [
    'tests/fixtures/common_injection_strings.txt',
    'tests/fixtures/common_path_traversal_strings.txt',
]
missing = [rel for rel in required if not (root / rel).exists()]
if missing:
    fail('common invalid payload fixtures', missing)
pass_('common invalid payload fixtures', f'fixtures={len(required)}')
