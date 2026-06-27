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
expected = [
    'web/system/logging',
    'web/system/logging/system_logging.php',
    'web/system/logging/system_logging_table/get_table_structure.php',
    'backend/checks/system_data/default_forms/forms_system_logging.json',
    'backend/checks/system_data/default_tables_structure/structure_table_system_logging.json',
    'data/system_logging.json',
    'data_running/system_logging.json',
    'backend/commits/commit_task/task_apply_system_logging.py',
]
missing = [rel for rel in expected if not (root / rel).exists()]
if missing:
    fail('system_logging structure presence', missing)

mainpage = (root / 'web/mainpage.php').read_text(errors='ignore')
if '/system/logging/system_logging.php' not in mainpage:
    fail('system_logging menu route', ['mainpage.php no apunta a /system/logging/system_logging.php'])

legacy = (root / 'web/settings.php').read_text(errors='ignore')
if '/system/logging/system_logging.php' not in legacy:
    fail('system_logging legacy wrapper', ['settings.php no conserva wrapper hacia la ruta nueva'])

pass_('system_logging structure presence', f'checked={len(expected)}')
