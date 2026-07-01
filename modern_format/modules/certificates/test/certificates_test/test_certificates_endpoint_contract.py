#!/usr/bin/env python3
"""
Test: test_certificates_endpoint_contract.py

Objetivo:
    Verificar contrato estatico de endpoints PHP para el modulo `certificates`.

Tipo:
    modulo / no destructivo

Modulo protegido:
    certificates

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
from module_assertions import check_endpoint_contract

check_endpoint_contract('certificates')
