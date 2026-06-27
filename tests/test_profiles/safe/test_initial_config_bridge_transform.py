#!/usr/bin/env python3
"""
Test: test_initial_config_bridge_transform.py

Objetivo:
    Validar que el paso post-instalación convierte ethernets físicas en
    bridges brN, mueve la configuración al bridge y es idempotente.
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
script = root / 'installation/initial_config/03_generate_bridges.py'
errors = []

if not script.exists():
    fail('initial config bridge transform', ['03_generate_bridges.py no existe'])


def skip_(title: str, reason: str) -> None:
    print(f'SKIP: {title}')
    print(f'  - {reason}')
    raise SystemExit(0)


def discover_physical_interfaces(mod) -> list[str]:
    """Descubre interfaces físicas reales para que el test no dependa de nombres hardcodeados.

    Discover real physical interfaces so the test does not depend on hardcoded names.
    """
    names: list[str] = []

    if mod.PHYSICAL_INTERFACES_JSON.exists():
        try:
            data = json.loads(mod.PHYSICAL_INTERFACES_JSON.read_text(encoding='utf-8'))
            for item in sorted(data.get('physical_interfaces', []), key=lambda i: (int(i.get('ifindex', 999999)), str(i.get('name', '')))):
                name = str(item.get('name', '')).strip()
                if name and name not in names:
                    names.append(name)
        except Exception:
            pass

    sys_net = Path('/sys/class/net')
    if sys_net.exists():
        candidates = []
        for iface in sys_net.iterdir():
            if iface.name == 'lo':
                continue
            if not (iface / 'device').exists():
                continue
            try:
                ifindex = int((iface / 'ifindex').read_text(encoding='utf-8').strip())
            except Exception:
                ifindex = 999999
            candidates.append((ifindex, iface.name))
        for _, name in sorted(candidates):
            if name not in names:
                names.append(name)

    return names


try:
    spec = importlib.util.spec_from_file_location('generate_bridges', script)
    mod = importlib.util.module_from_spec(spec)
    assert spec and spec.loader
    spec.loader.exec_module(mod)

    discovered = discover_physical_interfaces(mod)
    if len(discovered) < 2:
        skip_('initial config bridge transform', 'hacen falta al menos 2 interfaces físicas reales para validar br')

    ordered = mod.physical_order({name: {} for name in discovered})
    if len(ordered) < 2:
        skip_('initial config bridge transform', 'el inventario físico no devuelve al menos 2 interfaces ordenadas')

    primary, secondary = ordered[0], ordered[1]
    sample = {
        'network': {
            'version': '2',
            'ethernets': {
                primary: {
                    'dhcp4': 'True',
                    'addresses': '192.168.10.2/24',
                    'gateway4': '192.168.10.1',
                    'nameservers.addresses': '1.1.1.1,8.8.8.8',
                    'match.macaddress': 'aa:bb:cc:dd:ee:ff',
                    'set-name': primary,
                    'routes': "{'to': 'default', 'via': '192.168.10.1', 'metric': 100}",
                },
                secondary: {
                    'dhcp4': 'False',
                    'mtu': '9000',
                },
            },
            'bridges': {},
            'vlans': {},
            'bonds': {},
            'wifis': {},
            'wireguard': {},
        }
    }
    transformed, mapping = mod.transform(json.loads(json.dumps(sample)))
    net = transformed['network']
    expected_mapping = {primary: 'br0', secondary: 'br1'}
    if mapping != expected_mapping:
        errors.append(f'mapping inesperado: {mapping}; esperado: {expected_mapping}')
    if net['ethernets'][primary] != {'match.macaddress': 'aa:bb:cc:dd:ee:ff', 'set-name': primary}:
        errors.append(f"{primary} no conservó solo campos físicos: {net['ethernets'][primary]}")
    if net['ethernets'][secondary] != {}:
        errors.append(f'{secondary} no quedó limpia')
    if net['bridges']['br0'].get('interfaces') != primary:
        errors.append(f'br0 no referencia {primary}')
    if net['bridges']['br0'].get('addresses') != '192.168.10.2/24':
        errors.append('br0 no preserva addresses')
    if net['bridges']['br0'].get('gateway4') != '192.168.10.1':
        errors.append('br0 no preserva gateway4')
    if net['bridges']['br1'].get('mtu') != '9000':
        errors.append('br1 no preserva mtu')
    if 'match.macaddress' in net['bridges']['br0'] or 'set-name' in net['bridges']['br0']:
        errors.append('br0 contiene campos físicos inválidos para bridge')
    if net['bridges']['br0'].get('routes.to') != 'default':
        errors.append('br0 no normaliza routes.to')
    if net['bridges']['br0'].get('routes.via') != '192.168.10.1':
        errors.append('br0 no normaliza routes.via')
    if net['bridges']['br0'].get('routes.metric') != '100':
        errors.append('br0 no normaliza routes.metric')
    if 'routes' in net['bridges']['br0']:
        errors.append('br0 conserva routes raw en vez de routes.to/routes.via')

    gen_script = root / 'backend/commits/commit_task/task_gen_interface_config.py'
    sys.path.insert(0, str(gen_script.parent))
    spec_gen = importlib.util.spec_from_file_location('task_gen_interface_config', gen_script)
    gen = importlib.util.module_from_spec(spec_gen)
    assert spec_gen and spec_gen.loader
    spec_gen.loader.exec_module(gen)
    netplan = gen.convert(transformed)
    primary_config = netplan['network']['ethernets'][primary]
    br0_config = netplan['network']['bridges']['br0']
    if primary_config.get('match', {}).get('macaddress') != 'aa:bb:cc:dd:ee:ff':
        errors.append('Netplan no conserva match.macaddress en ethernet física')
    if primary_config.get('set-name') != primary:
        errors.append('Netplan no conserva set-name en ethernet física')
    if 'match' in br0_config or 'set-name' in br0_config:
        errors.append('Netplan generado contiene match/set-name dentro del bridge')
    if br0_config.get('routes') != [{'to': 'default', 'via': '192.168.10.1', 'metric': 100}]:
        errors.append(f"Netplan no preserva ruta default en bridge: {br0_config.get('routes')}")

    second, second_mapping = mod.transform(json.loads(json.dumps(transformed)))
    if second != transformed:
        errors.append('transform no es idempotente')
    if second_mapping != mapping:
        errors.append(f'mapping idempotente inesperado: {second_mapping}')

    prebridged = {
        'network': {
            'version': '2',
            'ethernets': {primary: {}},
            'bridges': {'br_mgmt': {'interfaces': primary, 'dhcp4': 'True'}},
        }
    }
    pre_result, pre_mapping = mod.transform(json.loads(json.dumps(prebridged)))
    if 'br0' in pre_result['network']['bridges']:
        errors.append('creó bridge duplicado para ethernet ya asociada a bridge')
    if pre_mapping != {primary: 'br_mgmt'}:
        errors.append(f'mapping prebridged inesperado: {pre_mapping}')

    with tempfile.TemporaryDirectory() as tmp:
        interfaces = Path(tmp) / 'interfaces.json'
        mapping_path = Path(tmp) / 'br_bridge_map.json'
        interfaces.write_text(json.dumps(sample), encoding='utf-8')
        old_interfaces = mod.INTERFACES_JSON
        old_mapping = mod.MAPPING_JSON
        try:
            mod.INTERFACES_JSON = interfaces
            mod.MAPPING_JSON = mapping_path
            mod.main()
            written = json.loads(interfaces.read_text(encoding='utf-8'))
            written_mapping = json.loads(mapping_path.read_text(encoding='utf-8'))
            if written['network']['bridges']['br0']['interfaces'] != primary:
                errors.append('main() no escribió br0')
            if 'match.macaddress' in written['network']['bridges']['br0']:
                errors.append('main() escribió match.macaddress en bridge')
            if written['network']['bridges']['br0'].get('routes.via') != '192.168.10.1':
                errors.append('main() no preservó ruta default normalizada')
            if written_mapping.get('ethernet_to_bridge') != expected_mapping:
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
        rules.write_text(json.dumps({'bpfilter': [
            {'rule': {'interface': 'ens21', 'hook': 'BF_HOOK_XDP', 'chain': 'ens21_bf_hook_xdp'}},
            {'rule': {'interface': 'other0', 'hook': 'BF_HOOK_XDP', 'chain': 'other0_bf_hook_xdp'}},
        ]}), encoding='utf-8')
        old_rules = bp.BPFILTER_RULES_JSON
        try:
            bp.BPFILTER_RULES_JSON = rules
            bp.adapt_bpfilter_rules(primary)
            written = json.loads(rules.read_text(encoding='utf-8'))
            rule = written['bpfilter'][0]['rule']
            if rule.get('interface') != primary:
                errors.append(f'bpfilter no conservó la interfaz física {primary}')
            if rule.get('chain') != f'{primary}_bf_hook_xdp':
                errors.append(f"bpfilter no adaptó chain física {primary}: {rule.get('chain')}")
        finally:
            bp.BPFILTER_RULES_JSON = old_rules

except SystemExit:
    raise
except Exception as exc:
    errors.append(f'excepción durante test: {exc}')

if errors:
    fail('initial config bridge transform', errors)
pass_('initial config bridge transform')
