#!/usr/bin/env python3
"""
E2E WebGUI real: generic tables load.

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


pages = ['/alias/address_alias.php','/alias/address_alias_group.php','/interfaces/ethernets.php','/policies/policies_nftables_input.php','/services/services.php']
errors=[]
for page in pages:
    status, _, body = client.get(page)
    if status != 200 or ('tabla-' not in body and 'renderTableGeneric' not in body and '<table' not in body):
        errors.append(f'{page}: HTTP {status}, cuerpo inesperado')
if errors:
    fail('e2e generic tables load', errors)
pass_('e2e generic tables load', f'pages={len(pages)}')

