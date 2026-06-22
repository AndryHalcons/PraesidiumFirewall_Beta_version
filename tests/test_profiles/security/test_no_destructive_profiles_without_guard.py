#!/usr/bin/env python3
"""
Test: test_no_destructive_profiles_without_guard.py

Objetivo:
    Verificar que el runner mantiene los perfiles destructivos detras de la
    variable PRAESIDIUM_ALLOW_DESTRUCTIVE.

Tipo:
    security / no destructivo

Riesgo que cubre:
    Evita que `commit`, `e2e` o `installer` se ejecuten por accidente en una
    maquina real sin confirmacion de laboratorio.

Seguridad:
    Solo inspecciona el texto de tests/run_tests.sh.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

text = (repo_root() / 'tests/run_tests.sh').read_text(encoding='utf-8')
errors = []
if 'PRAESIDIUM_ALLOW_DESTRUCTIVE' not in text:
    errors.append('runner no menciona PRAESIDIUM_ALLOW_DESTRUCTIVE')
for profile in ['commit', 'e2e', 'installer', 'all-lab']:
    if f'{profile}) require_destructive' not in text:
        errors.append(f'perfil {profile} no llama require_destructive de forma visible')
if errors:
    fail('destructive profile guard', errors)
pass_('destructive profile guard')
