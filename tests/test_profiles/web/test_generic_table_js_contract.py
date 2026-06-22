#!/usr/bin/env python3
"""
Test: test_generic_table_js_contract.py

Objetivo:
    Verificar que generic_table.js conserva funciones y comportamientos clave
    que usan los JSON declarativos.

Tipo:
    web / no destructivo

Riesgo que cubre:
    Detecta cambios en el componente generico que romperian muchas secciones.

Seguridad:
    Solo lee JavaScript versionado.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

path = repo_root() / 'web/my_js/generic_table.js'
text = path.read_text(encoding='utf-8', errors='ignore')
required = ['renderTableGeneric', 'get_forms_from_table', 'fetch', 'checkbox', 'not_editable']
missing = [item for item in required if item not in text]
if missing:
    fail('generic_table.js contract', [f'falta {item}' for item in missing])
pass_('generic_table.js contract')
