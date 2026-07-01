#!/usr/bin/env python3
"""
Test: test_dnsmasq_structure_presence.py

Objetivo:
    Verificar que los archivos principales del modulo `dnsmasq` existen en el repo.

Tipo:
    safe / modulo / no destructivo

Modulo protegido:
    dnsmasq

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

expected = ['web/networking/dhcp_table', 'backend/checks/system_data/default_forms/forms_dhcp.json', 'backend/checks/system_data/default_tables_structure/structure_table_dhcp.json', 'data/dhcp.json', 'data_running/dhcp.json', 'backend/commits/commit_task/task_gen_dhcp_config.py', 'backend/commits/commit_task/task_apply_dhcp_config.py']
missing = [rel for rel in expected if not module_rel('dnsmasq', rel).exists()]
if missing:
    fail('dnsmasq structure presence', missing)
pass_('dnsmasq structure presence', f'checked={len(expected)}')
