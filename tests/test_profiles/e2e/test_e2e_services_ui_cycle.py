#!/usr/bin/env python3
"""
E2E WebGUI real: services UI read cycle.

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


status, _, body = client.get('/services/services.php')
if status != 200 or 'services' not in body.lower():
    fail('e2e services UI cycle', [f'HTTP {status}', body[:800]])
status, _, body = client.get('/services/services_table/get_table_content.php')
if status != 200:
    fail('e2e services table content', [f'HTTP {status}', body[:800]])
pass_('e2e services UI read cycle', 'pagina y contenido de servicios accesibles')

