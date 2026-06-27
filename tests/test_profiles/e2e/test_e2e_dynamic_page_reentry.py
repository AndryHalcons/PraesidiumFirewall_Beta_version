#!/usr/bin/env python3
"""
E2E WebGUI real: dynamic page reentry.

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


page='/alias/address_alias_group.php'
for i in range(3):
    status, _, body = client.get(page)
    if status != 200 or 'alias_addr_group' not in body:
        fail('e2e dynamic page reentry', [f'iter={i} status={status}', body[:500]])
pass_('e2e dynamic page reentry', 'misma pagina dinamica carga repetidamente')

