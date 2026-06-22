#!/usr/bin/env python3
"""
Test: test_http_mutating_endpoints_reject_without_csrf.py

Objetivo:
    Probar que endpoints mutantes rechazan POST sin CSRF cuando hay entorno HTTP.

Tipo:
    security / no destructivo; envia payload invalido y sin CSRF

Seguridad:
    No se ejecuta sin PRAESIDIUM_TEST_BASE_URL/ADMIN_USER/ADMIN_PASS.
"""
from pathlib import Path
import json, sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from env import base_url, admin_user, admin_pass, skip_if_missing_http_env
from http_client import PraesidiumHttpClient
from report import fail, pass_

if skip_if_missing_http_env():
    raise SystemExit(0)
client = PraesidiumHttpClient(base_url())
if not client.login(admin_user(), admin_pass()):
    fail('HTTP CSRF reject', ['login fallo'])
endpoints = ['/web/services/services_table/get_update.php', '/services/services_table/get_update.php']
accepted = []
for endpoint in endpoints:
    status, _, body = client.post_json(endpoint, json.dumps({'invalid': True}), csrf=False)
    if status < 400 and 'csrf' not in body.lower() and 'error' not in body.lower():
        accepted.append(f'{endpoint}: status={status}')
if accepted:
    fail('HTTP mutating endpoints reject without CSRF', accepted)
pass_('HTTP mutating endpoints reject without CSRF')
