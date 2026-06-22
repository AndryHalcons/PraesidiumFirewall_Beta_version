#!/usr/bin/env python3
"""
Test: test_generic_table_contract.py

Objetivo:
    Comprobar contratos estaticos basicos entre JSON declarativos y el patron
    de tablas genericas de PraesidiumFirewall.

Tipo:
    web / no destructivo

Riesgo que cubre:
    Detecta forms/structures vacios o con forma inesperada antes de abrir la Web UI.

Seguridad:
    Solo lee JSON del repo; no llama endpoints ni modifica runtime.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from json_contracts import load_json, ensure_object
from report import fail, pass_

root = repo_root()
errors = []
json_dirs = [
    root / 'backend/checks/system_data/default_forms',
    root / 'backend/checks/system_data/default_tables_structure',
]
checked = 0
for directory in json_dirs:
    for path in sorted(directory.glob('*.json')):
        checked += 1
        try:
            data = load_json(path)
        except Exception as exc:
            errors.append(f'{path.relative_to(root)}: JSON invalido: {exc}')
            continue
        errors.extend(ensure_object(data, path.relative_to(root)))
        if isinstance(data, dict) and not data:
            errors.append(f'{path.relative_to(root)}: objeto JSON vacio')

if errors:
    fail('generic table contract', errors)
pass_('generic table contract', f'json_contract_files={checked}')
