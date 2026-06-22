#!/usr/bin/env python3
"""
Test: test_php_json_endpoints_no_closing_html_noise.py

Objetivo:
    Detectar endpoints PHP de JSON con HTML accidental fuera de PHP.

Tipo:
    web / no destructivo

Riesgo que cubre:
    Evita respuestas JSON contaminadas con HTML/whitespace visible accidental.

Seguridad:
    Solo lee endpoints PHP.
"""
from pathlib import Path
import sys, re
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root, tracked_files
from report import fail, pass_

root = repo_root()
errors = []
for rel in tracked_files():
    if not rel.startswith('web/') or not rel.endswith('.php'):
        continue
    if not any(marker in rel for marker in ['get_table', 'get_forms', 'get_update', 'get_delete', 'validation_', 'get_runtime_status']):
        continue
    text = (root / rel).read_text(encoding='utf-8', errors='ignore')
    if 'json_encode' in text and re.search(r'\?>\s*<[^?]', text):
        errors.append(f'{rel}: HTML tras cierre PHP en endpoint JSON')
if errors:
    fail('PHP JSON endpoints no HTML noise', errors)
pass_('PHP JSON endpoints no HTML noise')
