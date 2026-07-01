#!/usr/bin/env python3
"""
Test: test_services_structure_presence.py

Objetivo:
    Verificar que los archivos principales del modulo `services` existen en el repo.

Tipo:
    safe / modulo / no destructivo

Modulo protegido:
    services

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

expected = ['web/system/services/services.php', 'web/system/services/services_table', 'backend/checks/system_data/default_forms/forms_services.json', 'backend/checks/system_data/default_tables_structure/structure_table_services.json', 'data/services.json', 'data_running/services.json', 'backend/commits/commit_task/task_apply_services.py']
missing = [rel for rel in expected if not module_rel('services', rel).exists()]
if missing:
    fail('services structure presence', missing)
pass_('services structure presence', f'checked={len(expected)}')
