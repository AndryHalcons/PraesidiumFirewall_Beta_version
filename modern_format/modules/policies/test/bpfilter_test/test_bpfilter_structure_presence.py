#!/usr/bin/env python3
"""
Test: test_bpfilter_structure_presence.py

Objetivo:
    Verificar que los archivos principales del modulo `bpfilter` existen en el repo.

Tipo:
    safe / modulo / no destructivo

Modulo protegido:
    bpfilter

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

expected = ['web/policies/common_policy_actions_bpf', 'backend/checks/system_data/default_forms/forms_policies_bpf.json', 'backend/checks/system_data/default_tables_structure/structure_tables_policies_bpf.json', 'data/rules_bpfilter_human_viewer.json', 'backend/commits/commit_task/convert_bpfilter.py', 'backend/commits/commit_task/task_gen_bpfilter_policies.py', 'backend/commits/commit_task/task_apply_bpfilter_policies.py']
missing = [rel for rel in expected if not module_rel('bpfilter', rel).exists()]
if missing:
    fail('bpfilter structure presence', missing)
pass_('bpfilter structure presence', f'checked={len(expected)}')
