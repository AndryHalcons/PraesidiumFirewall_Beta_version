#!/usr/bin/env python3
"""
Test: test_nftables_structure_presence.py

Objetivo:
    Verificar que los archivos principales del modulo `nftables` existen en el repo.

Tipo:
    safe / modulo / no destructivo

Modulo protegido:
    nftables

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
expected = ['web/policies/common_policy_actions_nft', 'backend/checks/system_data/default_forms/forms_policies_nft.json', 'backend/checks/system_data/default_tables_structure/structure_tables_policies.json', 'data/rules_nftables_human_viewer.json', 'backend/commits/commit_task/convert_nftables.py', 'backend/commits/commit_task/task_gen_nftables_policies.py', 'backend/commits/commit_task/task_apply_nftables_policies.py']
missing = [rel for rel in expected if not (root / rel).exists()]
if missing:
    fail('nftables structure presence', missing)
pass_('nftables structure presence', f'checked={len(expected)}')
