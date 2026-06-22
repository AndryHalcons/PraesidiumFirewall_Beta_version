#!/usr/bin/env python3
"""
Test: test_squid_structure_presence.py

Objetivo:
    Verificar que los archivos principales del modulo `squid` existen en el repo.

Tipo:
    safe / modulo / no destructivo

Modulo protegido:
    squid

Riesgo que cubre:
    Detecta borrados accidentales o refactors incompletos que rompen el mapa basico del modulo.

Seguridad:
    Solo comprueba existencia de rutas. No modifica candidate, running, servicios ni runtime.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

root = repo_root()
expected = ['web/url_filter', 'backend/checks/system_data/default_forms/forms_squid.json', 'backend/checks/system_data/default_tables_structure/structure_table_squid.json', 'data/squid_config/squid_policies.json', 'backend/commits/commit_task/task_gen_squid_policy.py', 'backend/commits/commit_task/task_apply_squid_policy.py']
missing = [rel for rel in expected if not (root / rel).exists()]
if missing:
    fail('squid structure presence', missing)
pass_('squid structure presence', f'checked={len(expected)}')
