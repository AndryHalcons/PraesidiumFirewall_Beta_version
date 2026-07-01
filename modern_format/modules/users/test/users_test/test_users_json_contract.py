#!/usr/bin/env python3
"""
Test: test_users_json_contract.py

Objetivo:
    Verificar contratos JSON declarativos para el modulo `users`.

Tipo:
    modulo / no destructivo

Modulo protegido:
    users

Riesgo que cubre:
    Detecta contratos incompletos, endpoints ausentes o falta de datos de prueba
    antes de ejecutar pruebas destructivas de laboratorio.

Seguridad:
    Este test solo lee archivos versionados. No modifica candidate, running,
    servicios, firewall, red ni runtime del sistema.
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
from module_assertions import check_json_contract

check_json_contract('users')
