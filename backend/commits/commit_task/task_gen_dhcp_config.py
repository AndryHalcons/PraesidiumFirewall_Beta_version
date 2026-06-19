import ipaddress
import json
import os
import subprocess
from pathlib import Path
from task_update_json import task_update_json

DHCP_JSON = Path('/var/www/config_running/dhcp.json')
OUTPUT_DIR = Path('/var/www/config_running/dnsmasq')
OUTPUT_FILE = OUTPUT_DIR / 'praesidium-dhcp.conf'


def _fail(date, task):
    task_update_json(date, task, 'fail')
    raise SystemExit(1)


def _load_json(date):
    if not DHCP_JSON.exists():
        _fail(date, 'dhcp_json_exist')
    try:
        data = json.loads(DHCP_JSON.read_text(encoding='utf-8'))
    except json.JSONDecodeError:
        _fail(date, 'dhcp_json_format')
    if not isinstance(data, dict) or not isinstance(data.get('dhcp'), list):
        _fail(date, 'dhcp_json_format')
    task_update_json(date, 'dhcp_json_exist', 'success')
    task_update_json(date, 'dhcp_json_format', 'success')
    return data


def _ipv4(value, field, required=True):
    value = str(value or '').strip()
    if not value:
        if required:
            raise ValueError(f'{field} is required')
        return ''
    try:
        ipaddress.IPv4Address(value)
        return value
    except ValueError as exc:
        raise ValueError(f'{field} must be a valid IPv4 address') from exc


def _netmask(value):
    value = _ipv4(value, 'netmask', True)
    try:
        ipaddress.IPv4Network(f'0.0.0.0/{value}')
    except ValueError as exc:
        raise ValueError('netmask must be a valid contiguous IPv4 netmask') from exc
    return value


def _lease(value):
    value = str(value or '').strip() or '12h'
    import re
    if not re.match(r'^[1-9][0-9]*[mhdw]$', value):
        raise ValueError('lease_time must look like 30m, 12h, 7d or 1w')
    return value


def _network_contains(ip, gateway, netmask):
    network = ipaddress.IPv4Network(f'{gateway}/{netmask}', strict=False)
    return ipaddress.IPv4Address(ip) in network


def _range_tuple(rule):
    return (int(ipaddress.IPv4Address(rule['range_start'])), int(ipaddress.IPv4Address(rule['range_end'])))


def _overlap(a, b):
    a1, a2 = _range_tuple(a)
    b1, b2 = _range_tuple(b)
    return a1 <= b2 and b1 <= a2


def _validate_rules(date, entries):
    normalized = []
    active_by_interface = {}
    for entry in entries:
        rule = entry.get('rule') if isinstance(entry, dict) else None
        if not isinstance(rule, dict):
            _fail(date, 'dhcp_validate_model')
        item = {
            'id': str(rule.get('id', '')).strip(),
            'enable': str(rule.get('enable', 'true')).strip().lower(),
            'mode': str(rule.get('mode', 'server')).strip().lower(),
            'interface': str(rule.get('interface', '')).strip(),
            'range_start': '', 'range_end': '', 'lease_time': '', 'gateway': '', 'netmask': '',
            'dns_primary': '', 'dns_secondary': '', 'ntp_server': '',
            'relay_local_ip': '', 'relay_dest_server': ''
        }
        if item['enable'] not in ('true', 'false') or item['mode'] not in ('server', 'relay') or not item['interface']:
            _fail(date, 'dhcp_validate_model')
        if item['enable'] != 'true':
            normalized.append(item)
            continue
        try:
            if item['mode'] == 'server':
                if str(rule.get('relay_local_ip', '')).strip() or str(rule.get('relay_dest_server', '')).strip():
                    raise ValueError('server entries cannot contain relay fields')
                item['range_start'] = _ipv4(rule.get('range_start'), 'range_start')
                item['range_end'] = _ipv4(rule.get('range_end'), 'range_end')
                item['gateway'] = _ipv4(rule.get('gateway'), 'gateway')
                item['netmask'] = _netmask(rule.get('netmask'))
                item['lease_time'] = _lease(rule.get('lease_time'))
                item['dns_primary'] = _ipv4(rule.get('dns_primary'), 'dns_primary', False)
                item['dns_secondary'] = _ipv4(rule.get('dns_secondary'), 'dns_secondary', False)
                item['ntp_server'] = _ipv4(rule.get('ntp_server'), 'ntp_server', False)
                if int(ipaddress.IPv4Address(item['range_start'])) > int(ipaddress.IPv4Address(item['range_end'])):
                    raise ValueError('range_start cannot be greater than range_end')
                if not _network_contains(item['range_start'], item['gateway'], item['netmask']) or not _network_contains(item['range_end'], item['gateway'], item['netmask']):
                    raise ValueError('range must be inside gateway/netmask network')
            else:
                forbidden = ['range_start','range_end','gateway','netmask','dns_primary','dns_secondary','ntp_server']
                if any(str(rule.get(k, '')).strip() for k in forbidden):
                    raise ValueError('relay entries cannot contain server scope fields')
                item['relay_local_ip'] = _ipv4(rule.get('relay_local_ip'), 'relay_local_ip')
                item['relay_dest_server'] = _ipv4(rule.get('relay_dest_server'), 'relay_dest_server')
        except ValueError:
            _fail(date, 'dhcp_validate_model')

        iface_items = active_by_interface.setdefault(item['interface'], [])
        for other in iface_items:
            if other['mode'] != item['mode']:
                _fail(date, 'dhcp_validate_model')
            if item['mode'] == 'relay':
                _fail(date, 'dhcp_validate_model')
            if item['mode'] == 'server' and _overlap(item, other):
                _fail(date, 'dhcp_validate_model')
        iface_items.append(item)
        normalized.append(item)
    task_update_json(date, 'dhcp_validate_model', 'success')
    return normalized


def _render_dnsmasq(rules):
    lines = [
        '# Generated by PraesidiumFirewall. Do not edit manually.',
        '# Generado por PraesidiumFirewall. No editar manualmente.',
        'bind-interfaces',
        'except-interface=lo',
    ]
    for rule in rules:
        if rule['enable'] != 'true':
            continue
        iface = rule['interface']
        lines.append('')
        lines.append(f'# DHCP rule {rule["id"]} on {iface}')
        if rule['mode'] == 'server':
            lines.append(f'interface={iface}')
            lines.append(f'dhcp-range={iface},{rule["range_start"]},{rule["range_end"]},{rule["netmask"]},{rule["lease_time"]}')
            lines.append(f'dhcp-option={iface},option:router,{rule["gateway"]}')
            dns = [x for x in [rule['dns_primary'], rule['dns_secondary']] if x]
            if dns:
                lines.append(f'dhcp-option={iface},option:dns-server,{",".join(dns)}')
            if rule['ntp_server']:
                lines.append(f'dhcp-option={iface},option:ntp-server,{rule["ntp_server"]}')
        else:
            lines.append(f'dhcp-relay={rule["relay_local_ip"]},{rule["relay_dest_server"]},{iface}')
    lines.append('')
    return '\n'.join(lines)


def verify_dnsmasq_config(date, conf_file=OUTPUT_FILE):
    try:
        subprocess.run(['sudo', 'dnsmasq', '--test', f'--conf-file={conf_file}'], check=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        task_update_json(date, 'verify_dnsmasq_config', 'success')
    except subprocess.CalledProcessError:
        task_update_json(date, 'verify_dnsmasq_config', 'fail')
        raise SystemExit(1)


def gen_dhcp_config(user, date):
    data = _load_json(date)
    rules = _validate_rules(date, data['dhcp'])
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    OUTPUT_FILE.write_text(_render_dnsmasq(rules), encoding='utf-8')
    task_update_json(date, 'dhcp_convert_dnsmasq', 'success')
    verify_dnsmasq_config(date, OUTPUT_FILE)
