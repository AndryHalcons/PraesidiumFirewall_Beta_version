#!/usr/bin/env python3
"""
Test: test_initial_config_vmbr_transform.py

Objetivo:
    Validar que el paso post-instalación convierte ethernets físicas en
    bridges vmbrN, mueve la configuración al bridge y es idempotente.
"""
from pathlib import Path
import importlib.util
import json
import tempfile
import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

root = repo_root()
script = root / 'installation/initial_config/03_generate_vmbr_bridges.py'
errors = []

if not script.exists():
    fail('initial config vmbr transform', ['03_generate_vmbr_bridges.py no existe'])

try:
    spec = importlib.util.spec_from_file_location('generate_vmbr_bridges', script)
    mod = importlib.util.module_from_spec(spec)
    assert spec and spec.loader
    spec.loader.exec_module(mod)

    sample = {
        'network': {
            'version': '2',
            'ethernets': {
                'ens18': {
                    'dhcp4': 'True',
                    'addresses': '192.168.10.2/24',
                    'gateway4': '192.168.10.1',
                    'nameservers.addresses': '1.1.1.1,8.8.8.8',
                },
                'ens19': {
                    'dhcp4': 'False',
                    'mtu': '9000',
                },
            },
            'bridges': {},
            'vlans': {},
            'bonds': {},
            'wifis': {},
            'tunnels': {},
            'wireguard': {},
        }
    }
    transformed, mapping = mod.transform(json.loads(json.dumps(sample)))
    net = transformed['network']
    if mapping != {'ens18': 'vmbr0', 'ens19': 'vmbr1'}:
        errors.append(f'mapping inesperado: {mapping}')
    if net['ethernets']['ens18'] != {} or net['ethernets']['ens19'] != {}:
        errors.append('las ethernets no quedaron limpias')
    if net['bridges']['vmbr0'].get('interfaces') != 'ens18':
        errors.append('vmbr0 no referencia ens18')
    if net['bridges']['vmbr0'].get('addresses') != '192.168.10.2/24':
        errors.append('vmbr0 no preserva addresses')
    if net['bridges']['vmbr0'].get('gateway4') != '192.168.10.1':
        errors.append('vmbr0 no preserva gateway4')
    if net['bridges']['vmbr1'].get('mtu') != '9000':
        errors.append('vmbr1 no preserva mtu')

    second, second_mapping = mod.transform(json.loads(json.dumps(transformed)))
    if second != transformed:
        errors.append('transform no es idempotente')
    if second_mapping != mapping:
        errors.append(f'mapping idempotente inesperado: {second_mapping}')

    prebridged = {
        'network': {
            'version': '2',
            'ethernets': {'ens18': {}},
            'bridges': {'br_mgmt': {'interfaces': 'ens18', 'dhcp4': 'True'}},
        }
    }
    pre_result, pre_mapping = mod.transform(json.loads(json.dumps(prebridged)))
    if 'vmbr0' in pre_result['network']['bridges']:
        errors.append('creó vmbr duplicado para ethernet ya asociada a bridge')
    if pre_mapping != {'ens18': 'br_mgmt'}:
        errors.append(f'mapping prebridged inesperado: {pre_mapping}')

    with tempfile.TemporaryDirectory() as tmp:
        interfaces = Path(tmp) / 'interfaces.json'
        mapping_path = Path(tmp) / 'vmbr_bridge_map.json'
        interfaces.write_text(json.dumps(sample), encoding='utf-8')
        old_interfaces = mod.INTERFACES_JSON
        old_mapping = mod.MAPPING_JSON
        try:
            mod.INTERFACES_JSON = interfaces
            mod.MAPPING_JSON = mapping_path
            mod.main()
            written = json.loads(interfaces.read_text(encoding='utf-8'))
            written_mapping = json.loads(mapping_path.read_text(encoding='utf-8'))
            if written['network']['bridges']['vmbr0']['interfaces'] != 'ens18':
                errors.append('main() no escribió vmbr0')
            if written_mapping.get('ethernet_to_bridge') != {'ens18': 'vmbr0', 'ens19': 'vmbr1'}:
                errors.append('main() no escribió mapping correcto')
        finally:
            mod.INTERFACES_JSON = old_interfaces
            mod.MAPPING_JSON = old_mapping

    bpfilter_script = root / 'installation/initial_config/05_adapt_bpfilter_management.py'
    spec2 = importlib.util.spec_from_file_location('adapt_bpfilter_management', bpfilter_script)
    bp = importlib.util.module_from_spec(spec2)
    assert spec2 and spec2.loader
    spec2.loader.exec_module(bp)
    with tempfile.TemporaryDirectory() as tmp:
        tmp_path = Path(tmp)
        rules = tmp_path / 'rules_bpfilter_human_viewer.json'
        mapping_file = tmp_path / 'vmbr_bridge_map.json'
        rules.write_text(json.dumps({'bpfilter': [
            {'rule': {'interface': 'ens21', 'hook': 'BF_HOOK_XDP', 'chain': 'ens21_bf_hook_xdp'}},
            {'rule': {'interface': 'other0', 'hook': 'BF_HOOK_XDP', 'chain': 'other0_bf_hook_xdp'}},
        ]}), encoding='utf-8')
        mapping_file.write_text(json.dumps({'ethernet_to_bridge': {'ens18': 'vmbr0'}}), encoding='utf-8')
        old_rules = bp.BPFILTER_RULES_JSON
        old_mapping = bp.VMBR_MAPPING_JSON
        try:
            bp.BPFILTER_RULES_JSON = rules
            bp.VMBR_MAPPING_JSON = mapping_file
            management = bp.bridge_for_interface('ens18')
            if management != 'vmbr0':
                errors.append(f'bridge_for_interface no devolvió vmbr0: {management}')
            bp.adapt_bpfilter_rules(management)
            written = json.loads(rules.read_text(encoding='utf-8'))
            rule = written['bpfilter'][0]['rule']
            if rule.get('interface') != 'vmbr0':
                errors.append('bpfilter no adaptó interface a vmbr0')
            if rule.get('chain') != 'vmbr0_bf_hook_xdp':
                errors.append(f"bpfilter no adaptó chain a vmbr0: {rule.get('chain')}")
        finally:
            bp.BPFILTER_RULES_JSON = old_rules
            bp.VMBR_MAPPING_JSON = old_mapping

except Exception as exc:
    errors.append(f'excepción durante test: {exc}')

if errors:
    fail('initial config vmbr transform', errors)
pass_('initial config vmbr transform')
