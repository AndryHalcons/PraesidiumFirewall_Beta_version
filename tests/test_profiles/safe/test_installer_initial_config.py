#!/usr/bin/env python3
"""
Test: test_installer_initial_config.py

Objetivo:
    Asegurar que el instalador ejecuta la configuración inicial como
    orquestador modular y mantiene el bootstrap de interfaces.

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
initial_dir = root / 'installation/initial_config'
errors = []

expected_scripts = [
    '01_refresh_interfaces.sh',
    '02_validate_interfaces_json.sh',
    '03_generate_vmbr_bridges.py',
    '04_sync_running_interfaces.sh',
    '05_adapt_bpfilter_management.py',
    '06_fix_initial_config_permissions.sh',
]

if not initial.exists():
    errors.append('installation/initial_config.sh no existe')
else:
    content = initial.read_text(encoding='utf-8')
    required = [
        'set -euo pipefail',
        'INITIAL_CONFIG_DIR="${SCRIPT_DIR}/initial_config"',
        'run_initial_config_step',
    ] + expected_scripts
    for text in required:
        if text not in content:
            errors.append(f'initial_config.sh falta: {text}')
    positions = [content.find(script) for script in expected_scripts]
    if any(pos < 0 for pos in positions) or positions != sorted(positions):
        errors.append('initial_config.sh no ejecuta los scripts initial_config en orden esperado')

if not initial_dir.is_dir():
    errors.append('installation/initial_config/ no existe')
else:
    for script in expected_scripts:
        path = initial_dir / script
        if not path.exists():
            errors.append(f'falta installation/initial_config/{script}')

refresh = initial_dir / '01_refresh_interfaces.sh'
if refresh.exists():
    text = refresh.read_text(encoding='utf-8')
    if '/var/www/backend/checks/check_interfaces/main_interfaces_check.py' not in text:
        errors.append('01_refresh_interfaces.sh no ejecuta main_interfaces_check.py')

validate = initial_dir / '02_validate_interfaces_json.sh'
if validate.exists():
    text = validate.read_text(encoding='utf-8')
    for expected in ['interfaces.json', 'all_interfaces_list.json', 'physical_interfaces_list.json']:
        if expected not in text:
            errors.append(f'02_validate_interfaces_json.sh no valida {expected}')

vmbr = initial_dir / '03_generate_vmbr_bridges.py'
if vmbr.exists():
    text = vmbr.read_text(encoding='utf-8')
    for expected in ['vmbr_bridge_map.json', 'ethernet_to_bridge', "bridge_config['interfaces'] = ethernet_name", "ethernets[ethernet_name] = {}"]:
        if expected not in text:
            errors.append(f'03_generate_vmbr_bridges.py falta contrato: {expected}')

sync = initial_dir / '04_sync_running_interfaces.sh'
if sync.exists():
    text = sync.read_text(encoding='utf-8')
    if 'cp "$INTERFACES_JSON" "$RUNNING_INTERFACES_JSON"' not in text:
        errors.append('04_sync_running_interfaces.sh no sincroniza config_running')
    if 'python3 -m json.tool "$RUNNING_INTERFACES_JSON"' not in text:
        errors.append('04_sync_running_interfaces.sh no valida running interfaces')

bpfilter = initial_dir / '05_adapt_bpfilter_management.py'
if bpfilter.exists():
    text = bpfilter.read_text(encoding='utf-8')
    for expected in [
        'Only adapt old default rules bound to ens21.',
        "rule['interface'] = management_interface",
        "rule['chain'] = f'{management_interface}_{hook}'",
        'already_adapted = any(',
        'bridge_for_interface',
        'vmbr_bridge_map.json',
    ]:
        if expected not in text:
            errors.append(f'05_adapt_bpfilter_management.py falta: {expected}')

perms = initial_dir / '06_fix_initial_config_permissions.sh'
if perms.exists():
    text = perms.read_text(encoding='utf-8')
    if 'chown -R :www-data' not in text:
        errors.append('06_fix_initial_config_permissions.sh no ajusta chown')
    if 'chmod -R g+rw' not in text:
        errors.append('06_fix_initial_config_permissions.sh no ajusta chmod')

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
except Exception as exc:
    errors.append(f'data_running/interfaces.json inválido: {exc}')

bpfilter_data = root / 'data' / 'rules_bpfilter_human_viewer.json'
try:
    data = json.loads(bpfilter_data.read_text(encoding='utf-8'))
    default_rules = [item.get('rule', {}) for item in data.get('bpfilter', [])]
    ens21_rules = [rule for rule in default_rules if rule.get('interface') == 'ens21']
    if not ens21_rules:
        errors.append('data/rules_bpfilter_human_viewer.json debe conservar reglas default ens21 para que initial_config las adapte')
    enabled_ens21 = [rule for rule in ens21_rules if rule.get('enable') == 'true']
    if len(enabled_ens21) < 2:
        errors.append('se esperan al menos dos reglas bpfilter default enable=true para bootstrap de desarrollo')
except Exception as exc:
    errors.append(f'data/rules_bpfilter_human_viewer.json inválido: {exc}')

if errors:
    fail('installer initial config bootstrap', errors)
pass_('installer initial config bootstrap')
