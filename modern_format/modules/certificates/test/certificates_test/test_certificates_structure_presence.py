#!/usr/bin/env python3
"""
Test: test_certificates_structure_presence.py

Objetivo:
    Verificar que los archivos principales del modulo `certificates` existen en el repo.

Tipo:
    safe / modulo / no destructivo

Modulo protegido:
    certificates

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

expected = ['web/certificates', 'backend/checks/system_data/default_forms/forms_certificates.json', 'backend/checks/system_data/default_tables_structure/structure_table_certificates.json']
missing = [rel for rel in expected if not module_rel('certificates', rel).exists()]
if missing:
    fail('certificates structure presence', missing)
pass_('certificates structure presence', f'checked={len(expected)}')
