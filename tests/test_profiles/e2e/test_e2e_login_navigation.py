#!/usr/bin/env python3
"""
E2E WebGUI real: login and navigation.

ES: Prueba HTTP/WebGUI real con usuario admin de laboratorio. No modifica datos
salvo que el test indique lo contrario y siempre debe ir protegido por guardia.
EN: Real HTTP/WebGUI test using lab admin credentials.
"""
from pathlib import Path
import json
import re
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from destructive_guard import require_lab_confirmation
from report import fail, pass_
from release_lab import http_client_from_env

require_lab_confirmation()
client = http_client_from_env()


status, headers, body = client.get('/mainpage.php')
if status != 200 or 'Praesidium Firewall' not in body or 'data-page=' not in body:
    fail('e2e login navigation', [f'HTTP {status}', body[:800]])
pass_('e2e login navigation', 'mainpage carga shell autenticada')

