#!/usr/bin/env python3
"""
Test: test_common_attack_strings_nonempty.py

Objetivo:
    Verificar que los diccionarios comunes de payloads maliciosos contienen
    variedad minima: XSS, traversal, shell injection y unicode.

Tipo:
    validation / no destructivo

Seguridad:
    Solo lee fixtures comunes.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

text = (repo_root() / 'tests/fixtures/common_injection_strings.txt').read_text(encoding='utf-8', errors='ignore')
required = ['<script>', '../../', 'rm -rf', 'unicode']
missing = [item for item in required if item not in text]
if missing:
    fail('common attack strings', [f'falta patron {item}' for item in missing])
pass_('common attack strings')
