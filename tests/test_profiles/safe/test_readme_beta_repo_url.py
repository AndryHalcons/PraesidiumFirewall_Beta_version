#!/usr/bin/env python3
"""
Test: test_readme_beta_repo_url.py

Objetivo:
    Verificar que README.md apunta literalmente al repositorio beta actual y
    que el directorio `cd` corresponde al nombre que crea git clone por defecto.

Tipo:
    safe / no destructivo

Riesgo que cubre:
    Evita romper instrucciones de instalacion por usar el repositorio antiguo o
    por cambiar el nombre local del clon con iniciativa no solicitada.

Seguridad:
    Solo lee README.md.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

readme = (repo_root() / 'README.md').read_text(encoding='utf-8', errors='ignore')
errors = []
new = 'https://github.com/AndryHalcons/PraesidiumFirewall_Beta_version.git'
old = 'https://github.com/AndryHalcons/' + 'PraesidiumFirewall.git'
if new not in readme:
    errors.append('README no contiene la URL beta literal')
if old in readme:
    errors.append('README contiene la URL antigua de Praesidium')
if 'git clone https://github.com/AndryHalcons/PraesidiumFirewall_Beta_version.git PraesidiumFirewall' in readme:
    errors.append('README usa nombre local custom no solicitado para git clone')
if 'cd PraesidiumFirewall_Beta_version/installation' not in readme:
    errors.append('README no entra al directorio creado por git clone por defecto')
if errors:
    fail('README beta repo URL', errors)
pass_('README beta repo URL')
