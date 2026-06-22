#!/usr/bin/env python3
"""
Test: test_bpfilter_backend_static_coverage.py

Objetivo:
    Verificar cobertura estatica de validacion/backend para el modulo `bpfilter`.

Tipo:
    modulo / no destructivo

Modulo protegido:
    bpfilter

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
from module_assertions import check_backend_static_coverage

check_backend_static_coverage('bpfilter')
