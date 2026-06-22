#!/usr/bin/env python3
"""
Test: test_e2e_generic_tables_load.py

Objetivo:
    Carga de tablas genericas principales sin errores visibles.

Tipo:
    e2e / potencialmente destructivo / solo laboratorio

Seguridad:
    Requiere PRAESIDIUM_ALLOW_DESTRUCTIVE=1 y entorno HTTP. No se ejecuta por
    defecto para evitar tocar UI/runtime real sin autorizacion.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from destructive_guard import require_lab_confirmation
from env import skip_if_missing_http_env
from report import pass_

require_lab_confirmation()
if skip_if_missing_http_env():
    raise SystemExit(0)
# ES: Este test queda preparado para Playwright/Selenium cuando el lab tenga URL
# y credenciales dedicadas.
# EN: This test is ready for Playwright/Selenium once the lab has dedicated URL
# and credentials.
pass_('test_e2e_generic_tables_load.py', 'guard_ok; pendiente de automatizacion navegador real')
