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
for parent in Path(__file__).resolve().parents:
    test_lib = parent / 'tests' / 'lib'
    if test_lib.is_dir():
        sys.path.insert(0, str(test_lib))
        break
else:
    raise RuntimeError('tests/lib not found')
from module_assertions import module_rel
from report import fail, pass_

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
missing = [rel for rel in expected if not module_rel('system_logging', rel).exists()]
if missing:
    fail('system_logging structure presence', missing)

manifest = module_rel('system_logging', 'install/route_install.json').read_text(errors='ignore')
if '/system/logging/system_logging.php' not in manifest:
    fail('system_logging menu route', ['route_install.json no declara /system/logging/system_logging.php'])

pass_('system_logging structure presence', f'checked={len(expected)}')
