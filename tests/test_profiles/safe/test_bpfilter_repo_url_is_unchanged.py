#!/usr/bin/env python3
"""
Test: test_bpfilter_repo_url_is_unchanged.py

Objetivo:
    Proteger la URL de bpfilter para no tocarla accidentalmente al actualizar
    instrucciones de PraesidiumFirewall.

Tipo:
    safe / no destructivo

Riesgo que cubre:
    Evita cambiar el repo de bpfilter cuando solo se esta actualizando Praesidium.

Seguridad:
    Solo lee installation/install_bpfilter.sh.
"""
from pathlib import Path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

path = repo_root() / 'installation/install_bpfilter.sh'
text = path.read_text(encoding='utf-8', errors='ignore')
expected = 'https://github.com/AndryHalcons/bpfilter'
if expected not in text:
    fail('bpfilter repo URL unchanged', [f'{path.relative_to(repo_root())}: falta {expected}'])
pass_('bpfilter repo URL unchanged')
