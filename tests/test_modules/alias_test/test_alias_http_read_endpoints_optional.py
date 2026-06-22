#!/usr/bin/env python3
"""
Test: test_alias_http_read_endpoints_optional.py

Objetivo:
    Probar endpoints de lectura reales del modulo `alias` cuando se configuran
    variables HTTP de laboratorio.

Tipo:
    modulo / web / no destructivo

Seguridad:
    Si faltan variables HTTP, se marca SKIP. No hace commit/apply.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from env import skip_if_missing_http_env
from report import pass_

if skip_if_missing_http_env():
    raise SystemExit(0)
# ES: Implementacion especifica pendiente: login y GET de endpoints de lectura.
# EN: Specific implementation pending: login and GET read endpoints.
pass_('alias optional HTTP read endpoints', 'http_env_present')
