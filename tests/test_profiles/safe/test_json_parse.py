#!/usr/bin/env python3
"""
Test: test_json_parse.py

Objetivo:
    Verificar que todos los JSON versionados del repo parsean correctamente.

Tipo:
    safe / no destructivo

Riesgo que cubre:
    Evita que templates candidate/running, forms o structures rotos lleguen a BETA.

Seguridad:
    Solo lee archivos .json trackeados; no modifica candidate, running ni runtime.
"""
from pathlib import Path
import json
import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root, tracked_files
from report import fail, pass_

root = repo_root()
errors: list[str] = []
count = 0
for rel in tracked_files():
    if not rel.endswith('.json'):
        continue
    count += 1
    path = root / rel
    try:
        json.loads(path.read_text(encoding='utf-8'))
    except Exception as exc:
        errors.append(f'{rel}: {exc}')

if errors:
    fail('JSON parse', errors)
pass_('JSON parse', f'json_files={count}')
