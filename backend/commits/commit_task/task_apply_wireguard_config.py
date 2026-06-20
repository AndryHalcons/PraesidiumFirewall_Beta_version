import json
import shutil
import subprocess
from pathlib import Path
from task_update_json import task_update_json

OUTPUT_DIR = Path('/var/www/config_running/wireguard')
GENERATED_DIR = OUTPUT_DIR / 'generated'
MANIFEST = OUTPUT_DIR / 'manifest.json'
ACTIVE_DIR = Path('/etc/wireguard')
MANAGED_MARKER = 'Managed by PraesidiumFirewall'


def _fail(date, task):
    task_update_json(date, task, 'fail')
    raise SystemExit(1)


def _success(date, task):
    task_update_json(date, task, 'success')


def _run(cmd):
    return subprocess.run(cmd, check=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)


def _run_allow_failure(cmd):
    return subprocess.run(cmd, check=False, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)


def _load_manifest(date):
    if not MANIFEST.exists():
        _fail(date, 'wireguard_apply_manifest_exist')
    try:
        data = json.loads(MANIFEST.read_text(encoding='utf-8'))
    except json.JSONDecodeError:
        _fail(date, 'wireguard_apply_manifest_format')
    interfaces = data.get('managed_interfaces')
    if not isinstance(interfaces, list):
        _fail(date, 'wireguard_apply_manifest_format')
    _success(date, 'wireguard_apply_manifest_exist')
    _success(date, 'wireguard_apply_manifest_format')
    return interfaces


def _is_managed_active_conf(path):
    try:
        return path.exists() and MANAGED_MARKER in path.read_text(encoding='utf-8', errors='ignore')[:512]
    except OSError:
        return False


def _active_managed_interfaces():
    if not ACTIVE_DIR.exists():
        return set()
    return {p.stem for p in ACTIVE_DIR.glob('*.conf') if _is_managed_active_conf(p)}


def _backup_active(date, interfaces):
    backup_dir = OUTPUT_DIR / f'rollback_{date}'
    backup_dir.mkdir(parents=True, exist_ok=True)
    metadata = {}
    try:
        for iface in interfaces:
            active = ACTIVE_DIR / f'{iface}.conf'
            if active.exists():
                shutil.copy2(active, backup_dir / f'{iface}.conf')
                metadata[iface] = 'present'
            else:
                metadata[iface] = 'missing'
        (backup_dir / 'metadata.json').write_text(json.dumps(metadata, indent=2), encoding='utf-8')
        _success(date, 'wireguard_backup_config')
        return backup_dir, metadata
    except OSError:
        task_update_json(date, 'wireguard_backup_config', 'fail')
        return None, {}


def _rollback(date, backup_dir, metadata, interfaces):
    try:
        for iface in interfaces:
            _run(['sudo', 'systemctl', 'stop', f'wg-quick@{iface}'])
            if metadata.get(iface) == 'present' and backup_dir and (backup_dir / f'{iface}.conf').exists():
                _run(['sudo', 'cp', str(backup_dir / f'{iface}.conf'), str(ACTIVE_DIR / f'{iface}.conf')])
                _run(['sudo', 'chmod', '0600', str(ACTIVE_DIR / f'{iface}.conf')])
                _run(['sudo', 'systemctl', 'start', f'wg-quick@{iface}'])
            else:
                _run(['sudo', 'rm', '-f', str(ACTIVE_DIR / f'{iface}.conf')])
                _run(['sudo', 'systemctl', 'disable', f'wg-quick@{iface}'])
        _success(date, 'wireguard_rollback_config')
    except subprocess.CalledProcessError:
        task_update_json(date, 'wireguard_rollback_config', 'fail')


def _verify_conf(date, conf_path):
    try:
        _run(['wg-quick', 'strip', str(conf_path)])
        task_update_json(date, f'wireguard_apply_verify_{conf_path.stem}', 'success')
    except subprocess.CalledProcessError:
        task_update_json(date, f'wireguard_apply_verify_{conf_path.stem}', 'fail')
        raise SystemExit(1)


def apply_wireguard_config(user, date):
    interfaces = _load_manifest(date)
    desired = {item['name']: Path(item['source']) for item in interfaces if isinstance(item, dict) and item.get('name') and item.get('source')}
    active_managed = _active_managed_interfaces()
    all_managed = sorted(set(desired.keys()) | active_managed)

    if not desired and not active_managed:
        _success(date, 'wireguard_apply_config')
        _success(date, 'wireguard_verify_services')
        return

    for iface, source in desired.items():
        if not source.exists():
            _fail(date, 'wireguard_apply_generated_exist')
        _verify_conf(date, source)

    backup_dir, metadata = _backup_active(date, all_managed)
    if backup_dir is None:
        raise SystemExit(1)

    try:
        _run(['sudo', 'mkdir', '-p', str(ACTIVE_DIR)])
        for iface in active_managed - set(desired.keys()):
            _run(['sudo', 'systemctl', 'stop', f'wg-quick@{iface}'])
            _run(['sudo', 'systemctl', 'disable', f'wg-quick@{iface}'])
            _run(['sudo', 'rm', '-f', str(ACTIVE_DIR / f'{iface}.conf')])
        for iface, source in desired.items():
            _run(['sudo', 'cp', str(source), str(ACTIVE_DIR / f'{iface}.conf')])
            _run(['sudo', 'chown', 'root:root', str(ACTIVE_DIR / f'{iface}.conf')])
            _run(['sudo', 'chmod', '0600', str(ACTIVE_DIR / f'{iface}.conf')])
            _run(['sudo', 'systemctl', 'enable', f'wg-quick@{iface}'])
            _run(['sudo', 'systemctl', 'restart', f'wg-quick@{iface}'])
            _run(['systemctl', 'is-active', '--quiet', f'wg-quick@{iface}'])
        _success(date, 'wireguard_apply_config')
        _success(date, 'wireguard_verify_services')
    except subprocess.CalledProcessError:
        task_update_json(date, 'wireguard_apply_config', 'fail')
        _rollback(date, backup_dir, metadata, all_managed)
        raise SystemExit(1)
