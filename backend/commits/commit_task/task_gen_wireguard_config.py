import ipaddress
import json
import re
import shutil
import subprocess
from pathlib import Path
from task_update_json import task_update_json

WIREGUARD_JSON = Path('/var/www/config_running/wireguard.json')
OUTPUT_DIR = Path('/var/www/config_running/wireguard')
GENERATED_DIR = OUTPUT_DIR / 'generated'
MANIFEST = OUTPUT_DIR / 'manifest.json'

KEY_RE = re.compile(r'^[A-Za-z0-9+/]{43}=$')
IFACE_RE = re.compile(r'^[A-Za-z0-9_.:-]{1,15}$')
ENDPOINT_RE = re.compile(r'^(\[[0-9A-Fa-f:.]+\]|[^:\s]+):(\d{1,5})$')
EXPECTED_SECTIONS = {'site_to_site', 'remote_access', 'remote_clients'}


def _fail(date, task):
    task_update_json(date, task, 'fail')
    raise SystemExit(1)


def _success(date, task):
    task_update_json(date, task, 'success')


def _as_dict(value):
    if value in ({}, [], None):
        return {}
    if not isinstance(value, dict):
        raise ValueError('section must be an object')
    return value


def _load_json(date):
    if not WIREGUARD_JSON.exists():
        _fail(date, 'wireguard_json_exist')
    try:
        data = json.loads(WIREGUARD_JSON.read_text(encoding='utf-8'))
    except json.JSONDecodeError:
        _fail(date, 'wireguard_json_format')
    if not isinstance(data, dict) or not EXPECTED_SECTIONS.issubset(data.keys()):
        _fail(date, 'wireguard_json_format')
    try:
        normalized = {
            'site_to_site': _as_dict(data.get('site_to_site')),
            'remote_access': _as_dict(data.get('remote_access')),
            'remote_clients': _as_dict(data.get('remote_clients')),
        }
    except ValueError:
        _fail(date, 'wireguard_json_format')
    _success(date, 'wireguard_json_exist')
    _success(date, 'wireguard_json_format')
    return normalized


def _bool(value, field):
    value = str(value or '').strip().lower()
    if value not in ('true', 'false'):
        raise ValueError(f'{field} must be true or false')
    return value == 'true'


def _required(rule, fields):
    for field in fields:
        if str(rule.get(field, '')).strip() == '':
            raise ValueError(f'{field} is required')


def _name(value, field='name'):
    value = str(value or '').strip()
    if not re.match(r'^[A-Za-z0-9_.-]{1,64}$', value):
        raise ValueError(f'{field} has invalid characters')
    return value


def _iface(value):
    value = str(value or '').strip()
    if not IFACE_RE.match(value):
        raise ValueError('interface name is invalid')
    return value


def _port(value):
    value = str(value or '').strip()
    if not value.isdigit() or not (1 <= int(value) <= 65535):
        raise ValueError('port out of range')
    return int(value)


def _int_range(value, field, minimum, maximum, default=None):
    value = str(value or '').strip()
    if value == '' and default is not None:
        return default
    if not value.isdigit() or not (minimum <= int(value) <= maximum):
        raise ValueError(f'{field} out of range')
    return int(value)


def _key(value, field):
    value = str(value or '').strip()
    if not KEY_RE.match(value):
        raise ValueError(f'{field} is not a valid WireGuard key')
    return value


def _csv(value):
    return [x.strip() for x in str(value or '').split(',') if x.strip()]


def _cidrs(value, field, required=False):
    items = _csv(value)
    if required and not items:
        raise ValueError(f'{field} is required')
    nets = []
    for item in items:
        try:
            nets.append(ipaddress.ip_interface(item))
        except ValueError as exc:
            raise ValueError(f'{field} contains invalid CIDR') from exc
    return nets


def _networks(value, field, required=False):
    items = _csv(value)
    if required and not items:
        raise ValueError(f'{field} is required')
    nets = []
    for item in items:
        try:
            nets.append(ipaddress.ip_network(item, strict=False))
        except ValueError as exc:
            raise ValueError(f'{field} contains invalid network') from exc
    return nets


def _ips(value, field):
    ips = []
    for item in _csv(value):
        try:
            ips.append(ipaddress.ip_address(item))
        except ValueError as exc:
            raise ValueError(f'{field} contains invalid IP') from exc
    return ips


def _endpoint(value):
    value = str(value or '').strip()
    match = ENDPOINT_RE.match(value)
    if not match:
        raise ValueError('endpoint must be host:port')
    host, port = match.groups()
    _port(port)
    if host.startswith('[') and host.endswith(']'):
        ipaddress.IPv6Address(host[1:-1])
    elif not re.match(r'^[A-Za-z0-9.-]+$', host):
        raise ValueError('endpoint host is invalid')
    return value


def _overlap(left, right):
    return any(a.version == b.version and a.overlaps(b) for a in left for b in right)


def _validate_unique_listener(seen_interfaces, seen_ports, name, iface, port):
    if iface in seen_interfaces:
        raise ValueError(f'duplicate WireGuard interface {iface}')
    if port in seen_ports:
        raise ValueError(f'duplicate WireGuard listen_port {port}')
    seen_interfaces[iface] = name
    seen_ports[port] = name


def _validate_site_to_site(name, rule, seen_interfaces, seen_ports):
    _name(name)
    enabled = _bool(rule.get('enabled', 'false'), 'enabled')
    if enabled:
        _required(rule, ['interface', 'local_tunnel_ip', 'remote_tunnel_ip', 'local_networks', 'remote_networks', 'listen_port', 'remote_endpoint', 'private_key', 'remote_public_key'])
    iface = _iface(rule.get('interface')) if str(rule.get('interface', '')).strip() else ''
    port = _port(rule.get('listen_port')) if str(rule.get('listen_port', '')).strip() else None
    if iface and port is not None:
        _validate_unique_listener(seen_interfaces, seen_ports, name, iface, port)
    local_tunnel = _cidrs(rule.get('local_tunnel_ip'), 'local_tunnel_ip')
    remote_tunnel = _cidrs(rule.get('remote_tunnel_ip'), 'remote_tunnel_ip')
    if local_tunnel and remote_tunnel:
        if len(local_tunnel) != 1 or len(remote_tunnel) != 1:
            raise ValueError('site_to_site tunnel must have one local and one remote tunnel IP')
        if local_tunnel[0].version != remote_tunnel[0].version or local_tunnel[0].network != remote_tunnel[0].network:
            raise ValueError('site_to_site tunnel IPs must belong to the same network')
    local_networks = _networks(rule.get('local_networks'), 'local_networks')
    remote_networks = _networks(rule.get('remote_networks'), 'remote_networks')
    if local_networks and remote_networks and _overlap(local_networks, remote_networks):
        raise ValueError('site_to_site local and remote networks overlap')
    if str(rule.get('remote_endpoint', '')).strip():
        _endpoint(rule.get('remote_endpoint'))
    if str(rule.get('private_key', '')).strip():
        _key(rule.get('private_key'), 'private_key')
    if str(rule.get('remote_public_key', '')).strip():
        _key(rule.get('remote_public_key'), 'remote_public_key')
    if str(rule.get('keepalive', '')).strip():
        _int_range(rule.get('keepalive'), 'keepalive', 0, 65535)
    mtu = _int_range(rule.get('mtu'), 'mtu', 576, 9000, None) if str(rule.get('mtu', '')).strip() else None
    return {'name': name, 'enabled': enabled, 'rule': rule, 'interface': iface, 'port': port, 'mtu': mtu}


def _validate_remote_access(name, rule, seen_interfaces, seen_ports):
    _name(name)
    enabled = _bool(rule.get('enabled', 'false'), 'enabled')
    if enabled:
        _required(rule, ['interface', 'server_vpn_ip', 'vpn_network', 'listen_port', 'internal_networks', 'private_key'])
    iface = _iface(rule.get('interface')) if str(rule.get('interface', '')).strip() else ''
    port = _port(rule.get('listen_port')) if str(rule.get('listen_port', '')).strip() else None
    if iface and port is not None:
        _validate_unique_listener(seen_interfaces, seen_ports, name, iface, port)
    server_ips = _cidrs(rule.get('server_vpn_ip'), 'server_vpn_ip')
    vpn_networks = _networks(rule.get('vpn_network'), 'vpn_network')
    if server_ips:
        if len(server_ips) != 1:
            raise ValueError('remote_access server must have one VPN IP')
        if vpn_networks and not any(server_ips[0].ip.version == net.version and server_ips[0].ip in net for net in vpn_networks):
            raise ValueError('server_vpn_ip must belong to vpn_network')
    internal_networks = _networks(rule.get('internal_networks'), 'internal_networks')
    if vpn_networks and internal_networks and _overlap(vpn_networks, internal_networks):
        raise ValueError('vpn_network overlaps internal_networks')
    if str(rule.get('dns', '')).strip():
        _ips(rule.get('dns'), 'dns')
    if str(rule.get('private_key', '')).strip():
        _key(rule.get('private_key'), 'private_key')
    mtu = _int_range(rule.get('mtu'), 'mtu', 576, 9000, None) if str(rule.get('mtu', '')).strip() else None
    return {'name': name, 'enabled': enabled, 'rule': rule, 'interface': iface, 'port': port, 'mtu': mtu, 'vpn_networks': vpn_networks}


def _validate_remote_client(name, rule, servers, seen_client_ips, seen_client_keys):
    _name(name)
    enabled = _bool(rule.get('enabled', 'false'), 'enabled')
    if enabled:
        _required(rule, ['vpn', 'client_vpn_ip', 'client_public_key', 'allowed_ips'])
    vpn = str(rule.get('vpn', '')).strip()
    if vpn:
        _name(vpn, 'vpn')
        if vpn not in servers:
            raise ValueError('remote client references missing VPN server')
    client_ips = _cidrs(rule.get('client_vpn_ip'), 'client_vpn_ip')
    if client_ips:
        if len(client_ips) != 1:
            raise ValueError('remote client must have one VPN IP')
        raw_ip = str(client_ips[0])
        if raw_ip in seen_client_ips:
            raise ValueError('duplicate remote client VPN IP')
        seen_client_ips.add(raw_ip)
        if vpn and servers[vpn]['vpn_networks'] and not any(client_ips[0].ip.version == net.version and client_ips[0].ip in net for net in servers[vpn]['vpn_networks']):
            raise ValueError('client_vpn_ip must belong to selected server vpn_network')
    if str(rule.get('client_public_key', '')).strip():
        key = _key(rule.get('client_public_key'), 'client_public_key')
        if key in seen_client_keys:
            raise ValueError('duplicate client public key')
        seen_client_keys.add(key)
    _networks(rule.get('allowed_ips'), 'allowed_ips')
    if str(rule.get('keepalive', '')).strip():
        _int_range(rule.get('keepalive'), 'keepalive', 0, 65535)
    return {'name': name, 'enabled': enabled, 'rule': rule, 'vpn': vpn}


def _validate_model(date, data):
    try:
        seen_interfaces = {}
        seen_ports = {}
        site_to_site = {
            name: _validate_site_to_site(name, rule, seen_interfaces, seen_ports)
            for name, rule in data['site_to_site'].items()
        }
        remote_access = {
            name: _validate_remote_access(name, rule, seen_interfaces, seen_ports)
            for name, rule in data['remote_access'].items()
        }
        seen_client_ips = set()
        seen_client_keys = set()
        remote_clients = {
            name: _validate_remote_client(name, rule, remote_access, seen_client_ips, seen_client_keys)
            for name, rule in data['remote_clients'].items()
        }
    except Exception:
        _fail(date, 'wireguard_validate_model')
    _success(date, 'wireguard_validate_model')
    return site_to_site, remote_access, remote_clients


def _line_csv(value):
    return ', '.join(_csv(value))


def _render_site_to_site(item):
    r = item['rule']
    lines = [
        '# Managed by PraesidiumFirewall. Do not edit manually.',
        '# Gestionado por PraesidiumFirewall. No editar manualmente.',
        f'# Scenario: site_to_site; name: {item["name"]}',
        '[Interface]',
        f'Address = {_line_csv(r.get("local_tunnel_ip"))}',
        f'ListenPort = {item["port"]}',
        f'PrivateKey = {r["private_key"]}',
    ]
    if item['mtu']:
        lines.append(f'MTU = {item["mtu"]}')
    lines.extend(['', '[Peer]', f'PublicKey = {r["remote_public_key"]}'])
    allowed = _csv(r.get('remote_tunnel_ip')) + _csv(r.get('remote_networks'))
    lines.append(f'AllowedIPs = {", ".join(allowed)}')
    if str(r.get('remote_endpoint', '')).strip():
        lines.append(f'Endpoint = {r["remote_endpoint"]}')
    if str(r.get('keepalive', '')).strip():
        lines.append(f'PersistentKeepalive = {r["keepalive"]}')
    lines.append('')
    return '\n'.join(lines)


def _render_remote_access(server, clients):
    r = server['rule']
    lines = [
        '# Managed by PraesidiumFirewall. Do not edit manually.',
        '# Gestionado por PraesidiumFirewall. No editar manualmente.',
        f'# Scenario: remote_access; name: {server["name"]}',
        '[Interface]',
        f'Address = {_line_csv(r.get("server_vpn_ip"))}',
        f'ListenPort = {server["port"]}',
        f'PrivateKey = {r["private_key"]}',
    ]
    if server['mtu']:
        lines.append(f'MTU = {server["mtu"]}')
    for client in clients:
        cr = client['rule']
        lines.extend(['', '[Peer]', f'# Client: {client["name"]}', f'PublicKey = {cr["client_public_key"]}', f'AllowedIPs = {_line_csv(cr.get("client_vpn_ip"))}'])
        if str(cr.get('keepalive', '')).strip():
            lines.append(f'PersistentKeepalive = {cr["keepalive"]}')
    lines.append('')
    return '\n'.join(lines)


def _verify_generated_config(date, conf_path):
    try:
        subprocess.run(['wg-quick', 'strip', str(conf_path)], check=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        task_update_json(date, f'wireguard_verify_{conf_path.stem}', 'success')
    except subprocess.CalledProcessError:
        task_update_json(date, f'wireguard_verify_{conf_path.stem}', 'fail')
        raise SystemExit(1)


def _generate(date, site_to_site, remote_access, remote_clients):
    if GENERATED_DIR.exists():
        shutil.rmtree(GENERATED_DIR)
    GENERATED_DIR.mkdir(parents=True, exist_ok=True)
    manifest = {'managed_interfaces': []}

    for item in site_to_site.values():
        if not item['enabled']:
            continue
        conf = GENERATED_DIR / f'{item["interface"]}.conf'
        conf.write_text(_render_site_to_site(item), encoding='utf-8')
        conf.chmod(0o600)
        _verify_generated_config(date, conf)
        manifest['managed_interfaces'].append({'name': item['interface'], 'source': str(conf), 'scenario': 'site_to_site'})

    for server in remote_access.values():
        if not server['enabled']:
            continue
        clients = [c for c in remote_clients.values() if c['enabled'] and c['vpn'] == server['name']]
        conf = GENERATED_DIR / f'{server["interface"]}.conf'
        conf.write_text(_render_remote_access(server, clients), encoding='utf-8')
        conf.chmod(0o600)
        _verify_generated_config(date, conf)
        manifest['managed_interfaces'].append({'name': server['interface'], 'source': str(conf), 'scenario': 'remote_access'})

    MANIFEST.write_text(json.dumps(manifest, indent=2, ensure_ascii=False), encoding='utf-8')
    _success(date, 'wireguard_generate_config')


def gen_wireguard_config(user, date):
    data = _load_json(date)
    site_to_site, remote_access, remote_clients = _validate_model(date, data)
    _generate(date, site_to_site, remote_access, remote_clients)
