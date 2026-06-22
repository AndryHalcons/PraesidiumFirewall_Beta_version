#!/usr/bin/env python3
"""
Test: test_installer_dnsmasq_dhcp_only.py

Objetivo:
    Verificar que el instalador configura dnsmasq para DHCP sin escuchar DNS/53.

Tipo:
    safe / no destructivo

Riesgo que cubre:
    Evita que una instalacion limpia deje dnsmasq fallando por conflicto con
    systemd-resolved o que Praesidium use dnsmasq como resolver DNS general.

Seguridad:
    Solo inspecciona archivos versionados del instalador.
"""
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

root = repo_root()
script = root / 'installation' / 'configure_dnsmasq.sh'
installer = root / 'installation' / 'installer.sh'
errors = []

if not script.exists():
    errors.append('falta installation/configure_dnsmasq.sh')
else:
    content = script.read_text(encoding='utf-8')
    if 'port=0' not in content:
        errors.append('configure_dnsmasq.sh no desactiva DNS con port=0')
    if 'dnsmasq --test' not in content:
        errors.append('configure_dnsmasq.sh no valida la configuracion con dnsmasq --test')
    if 'systemctl restart dnsmasq' not in content:
        errors.append('configure_dnsmasq.sh no reinicia dnsmasq tras aplicar la configuracion')
    if 'grep -Eq' not in content or ':53' not in content:
        errors.append('configure_dnsmasq.sh no comprueba que dnsmasq no escuche en puerto 53')

installer_content = installer.read_text(encoding='utf-8')
if 'chmod +x configure_dnsmasq.sh' not in installer_content:
    errors.append('installer.sh no da permisos a configure_dnsmasq.sh')
if './configure_dnsmasq.sh' not in installer_content:
    errors.append('installer.sh no ejecuta configure_dnsmasq.sh')
if './install_kea.sh' in installer_content:
    errors.append('installer.sh no debe ejecutar Kea; DHCP usa dnsmasq')

if errors:
    fail('installer dnsmasq DHCP-only', errors)
pass_('installer dnsmasq DHCP-only')
