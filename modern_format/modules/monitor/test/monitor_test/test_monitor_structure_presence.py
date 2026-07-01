#!/usr/bin/env python3
"""
Test: test_monitor_structure_presence.py

Objetivo:
    Verificar que los archivos principales del modulo `monitor` existen en el repo.

Tipo:
    safe / modulo / no destructivo

Modulo protegido:
    monitor

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

expected = ['web/monitor', 'backend/checks/system_data/default_forms/forms_monitor.json', 'backend/checks/system_data/default_tables_structure/structure_table_monitor.json', 'backend/checks/check_monitor_log_extract/extract_monitor_log_nftables_for_get_user.py']
missing = [rel for rel in expected if not module_rel('monitor', rel).exists()]
if missing:
    fail('monitor structure presence', missing)
pass_('monitor structure presence', f'checked={len(expected)}')
