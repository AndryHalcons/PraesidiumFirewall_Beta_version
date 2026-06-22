#!/usr/bin/env python3
"""
Test: test_http_login_smoke.py

Objetivo:
    Probar login real por HTTP cuando se configuran variables de entorno.

Tipo:
    web / no destructivo si solo inicia sesion

Seguridad:
    Si faltan variables de entorno, se marca SKIP para no depender del lab.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from env import base_url, admin_user, admin_pass, skip_if_missing_http_env
from http_client import PraesidiumHttpClient
from report import fail, pass_

if skip_if_missing_http_env():
    raise SystemExit(0)
client = PraesidiumHttpClient(base_url())
if not client.login(admin_user(), admin_pass()):
    fail('HTTP login smoke', ['login fallo'])
pass_('HTTP login smoke')
