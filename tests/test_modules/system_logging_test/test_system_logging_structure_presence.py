#!/usr/bin/env python3
"""
Test: test_system_logging_structure_presence.py

Objetivo:
    Verificar que los archivos principales del modulo `system_logging` existen en el repo.

Tipo:
    safe / modulo / no destructivo

Modulo protegido:
    system_logging

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
expected = ['web/system/logging', 'backend/checks/system_data/default_forms/forms_system_logging.json', 'backend/checks/system_data/default_tables_structure/structure_table_system_logging.json', 'data/system_logging.json', 'data_running/system_logging.json', 'backend/commits/commit_task/task_apply_system_logging.py']
missing = [rel for rel in expected if not (root / rel).exists()]
if missing:
    fail('system_logging structure presence', missing)
pass_('system_logging structure presence', f'checked={len(expected)}')
