#!/usr/bin/env python3
"""
Test: test_services_bpfilter_systemd_contract.py

Objetivo:
    Asegurar que la WebGUI Sistema -> Servicios trata bpfilter como unidad
    systemd normal tras la mejora del instalador.

Tipo:
    safe / no destructivo

Seguridad:
    Solo inspecciona archivos versionados.
"""
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'lib'))
from repo_paths import repo_root
from report import fail, pass_

root = repo_root()
common = (root / 'web/services/services_table/services_common.php').read_text(encoding='utf-8')
apply = (root / 'backend/commits/commit_task/task_apply_services.py').read_text(encoding='utf-8')
errors = []

bpfilter_block_start = common.find("'bpfilter' => [")
bpfilter_block_end = common.find("],", bpfilter_block_start)
bpfilter_block = common[bpfilter_block_start:bpfilter_block_end]

if bpfilter_block_start == -1:
    errors.append('bpfilter no aparece en services_catalog')
else:
    if "'checker' => 'systemctl'" not in bpfilter_block:
        errors.append('bpfilter debe usar checker systemctl en services_common.php')
    if "'unit' => 'bpfilter'" not in bpfilter_block:
        errors.append('bpfilter debe apuntar a la unidad bpfilter')
    if "'default_enabled' => 'true'" not in bpfilter_block:
        errors.append('bpfilter debe tener default_enabled true')

for forbidden in ['bpfilter_daemon', 'services_bpfilter_runtime_status', 'pgrep -x bpfilter', 'daemon.sock']:
    if forbidden in common:
        errors.append(f'services_common.php conserva logica antigua de bpfilter: {forbidden}')

if "'bpfilter': 'bpfilter'" not in apply:
    errors.append('task_apply_services.py debe incluir bpfilter en CONFIGURABLE_UNITS')
if "CONFIGURABLE_SPECIAL = {'forwarding_ipv4', 'forwarding_ipv6'}" not in apply:
    errors.append('task_apply_services.py debe dejar solo forwarding en CONFIGURABLE_SPECIAL')
for forbidden in ['_start_bpfilter', '_stop_bpfilter', '_bpfilter_is_active', 'subprocess.Popen', "'bpfilter', 'forwarding_ipv4'"]:
    if forbidden in apply:
        errors.append(f'task_apply_services.py conserva arranque/parada especial antigua: {forbidden}')

if errors:
    fail('services bpfilter systemd contract', errors)
pass_('services bpfilter systemd contract')
