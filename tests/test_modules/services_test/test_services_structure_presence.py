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
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

root = repo_root()
expected = ['web/system/services/services.php', 'web/system/services/services_table', 'backend/checks/system_data/default_forms/forms_services.json', 'backend/checks/system_data/default_tables_structure/structure_table_services.json', 'data/services.json', 'data_running/services.json', 'backend/commits/commit_task/task_apply_services.py']
missing = [rel for rel in expected if not (root / rel).exists()]
if missing:
    fail('services structure presence', missing)
pass_('services structure presence', f'checked={len(expected)}')
