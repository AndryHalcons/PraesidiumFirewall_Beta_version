#!/usr/bin/env python3
"""
Test: test_wireguard_structure_presence.py

Objetivo:
    Verificar que los archivos principales del modulo `wireguard` existen en el repo.

Tipo:
    safe / modulo / no destructivo

Modulo protegido:
    wireguard

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

expected = ['web/interfaces/wireguard', 'backend/checks/system_data/default_forms/forms_wireguard.json', 'backend/checks/system_data/default_tables_structure/structure_table_wireguard.json', 'data/wireguard.json', 'data_running/wireguard.json', 'backend/commits/commit_task/task_gen_wireguard_config.py', 'backend/commits/commit_task/task_apply_wireguard_config.py']
missing = [rel for rel in expected if not module_rel('wireguard', rel).exists()]
if missing:
    fail('wireguard structure presence', missing)
pass_('wireguard structure presence', f'checked={len(expected)}')
