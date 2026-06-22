#!/usr/bin/env python3
"""
Test: test_sensitive_download_headers_static.py

Objetivo:
    Verificar que endpoints de descarga sensible usan cabeceras de no-cache/no-store
    y controles de ruta.

Tipo:
    security / no destructivo

Riesgo que cubre:
    Descargas de certificados/configs cacheadas o con path traversal.

Seguridad:
    Solo lee endpoints PHP.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

root = repo_root()
files = ['web/certificates/certificates_table/get_download_certificate.php', 'web/interfaces/wireguard/remote_clients_table/get_client_config.php', 'web/interfaces/wireguard/remote_clients_table/get_client_qr.php']
errors = []
for rel in files:
    text = (root / rel).read_text(encoding='utf-8', errors='ignore').lower()
    if 'readfile' not in text and 'get_client_config' not in rel and 'qr' not in rel:
        errors.append(f'{rel}: descarga sin readfile visible')
    if 'no-store' not in text and 'no-cache' not in text:
        errors.append(f'{rel}: sin cabecera no-store/no-cache visible')
    if '..' not in text and 'basename' not in text and 'realpath' not in text:
        errors.append(f'{rel}: sin defensa traversal visible')
if errors:
    fail('sensitive download headers static', errors)
pass_('sensitive download headers static')
