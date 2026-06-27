#!/usr/bin/env python3
"""
E2E WebGUI real: localisation ES/EN.

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


status, _, body = client.get('/mainpage.php')
if status != 200:
    fail('e2e localisation', [f'HTTP {status}'])
if not any(token in body for token in ['Bienvenido', 'Welcome']):
    fail('e2e localisation', ['no se encontro texto de bienvenida ES/EN'])
pass_('e2e localisation ES/EN', 'shell contiene texto localizado')

