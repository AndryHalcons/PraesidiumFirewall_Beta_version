#!/usr/bin/env python3
"""
Test: test_installer_bpfilter_service.py

Objetivo:
    Verificar que la instalacion de bpfilter sea no interactiva y que la
    configuracion use systemd en vez de bloquear el instalador en foreground.

Tipo:
    safe / no destructivo

Seguridad:
    Solo inspecciona archivos versionados del instalador.
"""
from pathlib import Path
import re
import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

root = repo_root()
install_script = root / 'installation' / 'install_bpfilter.sh'
configure_script = root / 'installation' / 'configure_bpfilter.sh'
installer = root / 'installation' / 'installer.sh'
errors = []

install_content = install_script.read_text(encoding='utf-8')
configure_content = configure_script.read_text(encoding='utf-8')
installer_content = installer.read_text(encoding='utf-8')

if 'read -p' in install_content or 'read ' in install_content:
    errors.append('install_bpfilter.sh no debe tener prompts interactivos')
if re.search(r'(^|[\s(])libc-dev([\s)]|$)', install_content):
    errors.append('install_bpfilter.sh no debe comprobar libc-dev virtual; usar libc6-dev/linux-libc-dev')
if 'libc6-dev' not in install_content or 'linux-libc-dev' not in install_content:
    errors.append('install_bpfilter.sh debe comprobar libc6-dev y linux-libc-dev')
if 'cmake --build' not in install_content:
    errors.append('install_bpfilter.sh debe compilar con cmake --build')
if 'install -m 0755' not in install_content:
    errors.append('install_bpfilter.sh debe instalar binarios con permisos ejecutables')
if 'command -v bfcli' not in install_content or 'command -v bpfilter' not in install_content:
    errors.append('install_bpfilter.sh debe verificar bfcli y bpfilter en PATH')

if 'bpfilter.service' not in configure_content:
    errors.append('configure_bpfilter.sh debe crear/gestionar bpfilter.service')
if 'systemctl daemon-reload' not in configure_content:
    errors.append('configure_bpfilter.sh debe recargar systemd')
if 'systemctl reset-failed bpfilter.service' not in configure_content:
    errors.append('configure_bpfilter.sh debe limpiar fallos previos de bpfilter.service')
if 'systemctl enable bpfilter.service' not in configure_content:
    errors.append('configure_bpfilter.sh debe habilitar bpfilter.service')
if 'systemctl restart bpfilter.service' not in configure_content:
    errors.append('configure_bpfilter.sh debe reiniciar bpfilter.service')
if 'systemctl is-active --quiet bpfilter.service' not in configure_content:
    errors.append('configure_bpfilter.sh debe verificar servicio activo')
if '--no-iptables --no-nftables' not in configure_content:
    errors.append('bpfilter.service debe conservar flags --no-iptables --no-nftables')
if '--verbose=$VERBOSE_LEVEL' not in configure_content or 'BPFILTER_VERBOSE_LEVEL:-debug' not in configure_content:
    errors.append('bpfilter.service debe usar verbose debug por defecto, no info')
if '$DAEMON_CMD' in configure_content or '\n    bpfilter --' in configure_content:
    errors.append('configure_bpfilter.sh no debe ejecutar bpfilter directamente en foreground')

if './install_bpfilter.sh' not in installer_content or './configure_bpfilter.sh' not in installer_content:
    errors.append('installer.sh debe ejecutar install_bpfilter.sh y configure_bpfilter.sh')

if errors:
    fail('installer bpfilter service', errors)
pass_('installer bpfilter service')
