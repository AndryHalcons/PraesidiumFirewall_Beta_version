# Aplica la configuración running de la sección Servicios durante commit/apply.
# Applies the running configuration for the Services section during commit/apply.
#
# Los servicios normales usan systemctl; bpfilter y forwarding usan caminos
# especiales porque no son unidades systemd ordinarias.
# Normal services use systemctl; bpfilter and forwarding use special paths
# because they are not ordinary systemd units.
import json
import os
import subprocess
import time
from pathlib import Path
from task_update_json import task_update_json

SERVICES_JSON = '/var/www/config_running/services.json'
BPFILTER_BIN = '/usr/local/bin/bpfilter'
BPFILTER_SOCKET = '/run/bpfilter/daemon.sock'
BPFILTER_LOG = '/var/log/praesidium/bpfilter.log'
# Unidades systemd que el usuario puede controlar desde Servicios.
# Systemd units the user can control from Services.
CONFIGURABLE_UNITS = {
    'dnsmasq': 'dnsmasq',
    'squid': 'squid',
    'nftables': 'nftables',
    'rsyslog': 'rsyslog',
}
# Controles especiales que necesitan lógica propia y no deben pasar por systemctl.
# Special controls needing their own logic and not going through systemctl.
CONFIGURABLE_SPECIAL = {'bpfilter', 'forwarding_ipv4', 'forwarding_ipv6'}
# Mapa de filas Forwarding a claves sysctl permitidas.
# Map from Forwarding rows to allowed sysctl keys.
SYSCTL_KEYS = {
    'forwarding_ipv4': 'net.ipv4.ip_forward',
    'forwarding_ipv6': 'net.ipv6.conf.all.forwarding',
}


def _load_services_config():
    # Carga la configuración running de servicios.
    # Loads the running services configuration.
    if not os.path.exists(SERVICES_JSON):
        return {'services': {}}
    with open(SERVICES_JSON, 'r', encoding='utf-8') as handle:
        data = json.load(handle)
    if not isinstance(data, dict) or not isinstance(data.get('services'), dict):
        raise ValueError('services.json malformed')
    return data


def _run_systemctl(action, unit):
    # Ejecuta systemctl de forma estricta y captura salida para errores claros.
    # Runs systemctl strictly and captures output for clear errors.
    result = subprocess.run(
        ['/usr/bin/systemctl', action, unit],
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        check=False,
    )
    if result.returncode != 0:
        raise RuntimeError(f'systemctl {action} {unit} failed: {result.stderr.strip() or result.stdout.strip()}')


def _is_active(unit):
    # Verifica si una unidad está activa tras aplicar la acción solicitada.
    # Checks whether a unit is active after applying the requested action.
    return subprocess.run(
        ['/usr/bin/systemctl', 'is-active', '--quiet', unit],
        check=False,
    ).returncode == 0


def _bpfilter_is_active():
    # Comprueba bpfilter como daemon, no como unidad systemd.
    # Checks bpfilter as a daemon, not as a systemd unit.
    if not os.path.exists(BPFILTER_BIN):
        return False
    process = subprocess.run(
        ['/usr/bin/pgrep', '-x', 'bpfilter'],
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        check=False,
    )
    return process.returncode == 0 and os.path.exists(BPFILTER_SOCKET)


def _start_bpfilter():
    # Arranca bpfilter en segundo plano con los mismos flags usados por el instalador.
    # Starts bpfilter in the background with the same flags used by the installer.
    if _bpfilter_is_active():
        return

    Path('/var/log/praesidium').mkdir(parents=True, exist_ok=True)
    log_handle = open(BPFILTER_LOG, 'ab')
    try:
        subprocess.Popen(
            [BPFILTER_BIN, '--no-iptables', '--no-nftables', '--verbose=debug'],
            stdin=subprocess.DEVNULL,
            stdout=log_handle,
            stderr=subprocess.STDOUT,
            start_new_session=True,
            close_fds=True,
        )
    finally:
        log_handle.close()

    for _ in range(20):
        if _bpfilter_is_active():
            return
        time.sleep(0.25)

    raise RuntimeError('bpfilter did not become active after start')


def _stop_bpfilter():
    # Para el daemon bpfilter por nombre exacto y verifica que el socket desaparezca.
    # Stops the bpfilter daemon by exact name and verifies the socket disappears.
    subprocess.run(['/usr/bin/pkill', '-x', 'bpfilter'], check=False)

    for _ in range(20):
        if not _bpfilter_is_active():
            return
        time.sleep(0.25)

    raise RuntimeError('bpfilter is still active after stop')


def _apply_systemd_service(service_name, unit, desired):
    # Aplica servicios normales gestionados por systemd.
    # Applies normal services managed by systemd.
    if desired == 'true':
        _run_systemctl('enable', unit)
        _run_systemctl('start', unit)
        if not _is_active(unit):
            raise RuntimeError(f'{unit} is not active after enable/start')
    else:
        _run_systemctl('stop', unit)
        _run_systemctl('disable', unit)
        if _is_active(unit):
            raise RuntimeError(f'{unit} is still active after stop/disable')


def _sysctl_value(key):
    # Lee el valor actual de una clave sysctl permitida.
    # Reads the current value for an allowed sysctl key.
    result = subprocess.run(
        ['/usr/sbin/sysctl', '-n', key],
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        check=False,
    )
    if result.returncode != 0:
        raise RuntimeError(f'sysctl read {key} failed: {result.stderr.strip() or result.stdout.strip()}')
    return result.stdout.strip()


def _set_sysctl_value(key, value):
    # Aplica en runtime una clave sysctl y verifica el resultado.
    # Applies a sysctl key at runtime and verifies the result.
    result = subprocess.run(
        ['/usr/sbin/sysctl', '-w', f'{key}={value}'],
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        check=False,
    )
    if result.returncode != 0:
        raise RuntimeError(f'sysctl write {key}={value} failed: {result.stderr.strip() or result.stdout.strip()}')
    current = _sysctl_value(key)
    if current != value:
        raise RuntimeError(f'{key} is {current} after setting {value}')


def _apply_sysctl_forwarding(service_name, desired):
    # Aplica forwarding IPv4/IPv6 como control especial de Servicios.
    # Applies IPv4/IPv6 forwarding as a special Services control.
    key = SYSCTL_KEYS.get(service_name)
    if key is None:
        raise ValueError(f'unsupported sysctl service {service_name}')
    _set_sysctl_value(key, '1' if desired == 'true' else '0')


def _apply_special_service(service_name, desired):
    # Aplica servicios especiales que no son unidades systemd normales.
    # Applies special services that are not normal systemd units.
    if service_name == 'bpfilter':
        if desired == 'true':
            _start_bpfilter()
        else:
            _stop_bpfilter()
        return

    if service_name in SYSCTL_KEYS:
        _apply_sysctl_forwarding(service_name, desired)
        return

    raise ValueError(f'unsupported special service {service_name}')


def apply_services_config(user, date):
    # Aplica desired_enabled para los servicios configurables de Praesidium.
    # Applies desired_enabled for Praesidium configurable services.
    try:
        data = _load_services_config()
        service_config = data.get('services', {})

        # Acumula errores para intentar aplicar todos los controles antes de fallar.
        # Accumulates errors so every control is attempted before failing.
        errors = []

        for service_name, unit in CONFIGURABLE_UNITS.items():
            desired = str(service_config.get(service_name, {}).get('desired_enabled', 'true')).lower()
            try:
                if desired not in {'true', 'false'}:
                    raise ValueError(f'invalid desired_enabled for {service_name}')
                _apply_systemd_service(service_name, unit, desired)
            except Exception as exc:
                errors.append(f'{service_name}: {exc}')

        for service_name in CONFIGURABLE_SPECIAL:
            # Valor por defecto seguro: bpfilter conserva su histórico false; forwarding debe estar activo.
            # Safe default: bpfilter keeps its historical false; forwarding should be active.
            default = 'false' if service_name == 'bpfilter' else 'true'
            desired = str(service_config.get(service_name, {}).get('desired_enabled', default)).lower()
            try:
                if desired not in {'true', 'false'}:
                    raise ValueError(f'invalid desired_enabled for {service_name}')
                _apply_special_service(service_name, desired)
            except Exception as exc:
                errors.append(f'{service_name}: {exc}')

        if errors:
            raise RuntimeError('; '.join(errors))

        task_update_json(date, 'apply_services_config', 'success')
    except Exception:
        # Marca la tarea como fallo para commit_history aunque se relance la excepción.
        # Marks the task as failed for commit_history even when re-raising the exception.
        task_update_json(date, 'apply_services_config', 'fail')
        raise
