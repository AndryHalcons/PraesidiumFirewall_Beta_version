#!/usr/bin/env python3
"""
Test: test_installer_initial_config.py

Objetivo:
    Asegurar que el instalador ejecuta la configuración inicial dependiente
    del sistema real, especialmente el bootstrap de interfaces.

Tipo:
    safe / no destructivo

Seguridad:
    Solo inspecciona archivos versionados.
"""
from pathlib import Path
import json
import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

root = repo_root()
installer = (root / 'installation/installer.sh').read_text(encoding='utf-8')
initial = root / 'installation/initial_config.sh'
errors = []

if not initial.exists():
    errors.append('installation/initial_config.sh no existe')
else:
    content = initial.read_text(encoding='utf-8')
    required = [
        'set -euo pipefail',
        '/var/www/backend/checks/check_interfaces/main_interfaces_check.py',
        'INTERFACES_JSON="/var/www/config/interfaces.json"',
        'RUNNING_INTERFACES_JSON="/var/www/config_running/interfaces.json"',
        'python3 "$INTERFACES_CHECK"',
        'python3 -m json.tool "$INTERFACES_JSON"',
        'python3 -m json.tool "$RUNNING_INTERFACES_JSON"',
        'cp "$INTERFACES_JSON" "$RUNNING_INTERFACES_JSON"',
        'BPFILTER_RULES_JSON="/var/www/config/rules_bpfilter_human_viewer.json"',
        'MANAGEMENT_INTERFACE="$(ip -o -4 route show default',
        'Only adapt old default rules bound to ens21.',
        "rule['interface'] = management_interface",
        "rule['chain'] = f'{management_interface}_{hook}'",
        'already_adapted = any(',
        'python3 -m json.tool "$BPFILTER_RULES_JSON"',
        'all_interfaces_list.json',
        'physical_interfaces_list.json',
        'chown -R :www-data',
        'chmod -R g+rw',
    ]
    for text in required:
        if text not in content:
            errors.append(f'initial_config.sh falta: {text}')

if 'chmod +x initial_config.sh' not in installer:
    errors.append('installer.sh no da permisos de ejecución a initial_config.sh')
if './initial_config.sh' not in installer:
    errors.append('installer.sh no ejecuta initial_config.sh')
if installer.find('./permissions.sh') > installer.find('./initial_config.sh'):
    errors.append('initial_config.sh debe ejecutarse después de permissions.sh')
if installer.find('./initial_config.sh') > installer.find('./install_bpfilter.sh'):
    errors.append('initial_config.sh debe ejecutarse antes de install_bpfilter.sh')

running_template = root / 'data_running/interfaces.json'
try:
    data = json.loads(running_template.read_text(encoding='utf-8'))
    network = data.get('network', {})
    if network.get('version') != '2':
        errors.append('data_running/interfaces.json debe mantener network.version=2')
    for section in ['ethernets', 'wifis', 'bonds', 'bridges', 'vlans', 'tunnels', 'wireguard']:
        if network.get(section) != {}:
            errors.append(f'data_running/interfaces.json debe arrancar vacío en {section}')
    serialized = json.dumps(data)
    for stale in ['ens18', 'ens21', 'bond0', 'br0', 'br1', 'vlan10', 'tun0']:
        if stale in serialized:
            errors.append(f'data_running/interfaces.json conserva dato demo: {stale}')
except Exception as exc:
    errors.append(f'data_running/interfaces.json inválido: {exc}')


bpfilter = root / 'data' / 'rules_bpfilter_human_viewer.json'
try:
    data = json.loads(bpfilter.read_text(encoding='utf-8'))
    default_rules = [item.get('rule', {}) for item in data.get('bpfilter', [])]
    ens21_rules = [rule for rule in default_rules if rule.get('interface') == 'ens21']
    if not ens21_rules:
        errors.append('data/rules_bpfilter_human_viewer.json debe conservar reglas default ens21 para que initial_config.sh las adapte')
    enabled_ens21 = [rule for rule in ens21_rules if rule.get('enable') == 'true']
    if len(enabled_ens21) < 2:
        errors.append('se esperan al menos dos reglas bpfilter default enable=true para bootstrap de desarrollo')
except Exception as exc:
    errors.append(f'data/rules_bpfilter_human_viewer.json inválido: {exc}')

if errors:
    fail('installer initial config bootstrap', errors)
pass_('installer initial config bootstrap')
