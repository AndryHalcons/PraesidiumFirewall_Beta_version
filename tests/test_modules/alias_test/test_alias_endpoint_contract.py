#!/usr/bin/env python3
"""
Test: test_alias_endpoint_contract.py

Objetivo:
    Verificar contrato estatico de endpoints PHP para el modulo `alias`.

Tipo:
    modulo / no destructivo

Modulo protegido:
    alias

Riesgo que cubre:
    Detecta contratos incompletos, endpoints ausentes o falta de datos de prueba
    antes de ejecutar pruebas destructivas de laboratorio.

Seguridad:
    Este test solo lee archivos versionados. No modifica candidate, running,
    servicios, firewall, red ni runtime del sistema.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from module_assertions import check_endpoint_contract

check_endpoint_contract('alias')
