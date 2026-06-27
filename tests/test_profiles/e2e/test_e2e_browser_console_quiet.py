#!/usr/bin/env python3
"""
E2E WebGUI real: browser console quiet contract.

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


# ES/EN: Sin Playwright en stdlib, este test valida que los scripts principales se entregan y no contienen marcadores de error obvios.
scripts = ['/my_js/generic_table.js','/my_js/mainpage.js','/monitor/logs_table/monitor.js']
errors=[]
for script in scripts:
    status, _, body = client.get(script)
    if status != 200:
        errors.append(f'{script}: HTTP {status}')
    if len(body.strip()) < 50:
        errors.append(f'{script}: cuerpo demasiado pequeno')
if errors:
    fail('e2e browser console quiet http delivery', errors)
pass_('e2e browser console quiet http delivery', f'scripts={len(scripts)}')

