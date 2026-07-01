#!/usr/bin/env python3
"""
Test: test_alias_structure_presence.py

Objetivo:
    Verificar que los archivos principales del modulo `alias` existen en el repo.

Tipo:
    safe / modulo / no destructivo

Modulo protegido:
    alias

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

expected = ['web/alias', 'backend/checks/system_data/default_forms/forms_alias.json', 'backend/checks/system_data/default_tables_structure/structure_tables_alias.json', 'data/alias.json']
missing = [rel for rel in expected if not module_rel('alias', rel).exists()]
if missing:
    fail('alias structure presence', missing)
pass_('alias structure presence', f'checked={len(expected)}')
