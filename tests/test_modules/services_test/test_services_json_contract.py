#!/usr/bin/env python3
"""
Test: test_services_json_contract.py

Objetivo:
    Verificar contratos JSON declarativos para el modulo `services`.

Tipo:
    modulo / no destructivo

Modulo protegido:
    services

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
from module_assertions import check_json_contract

check_json_contract('services')
