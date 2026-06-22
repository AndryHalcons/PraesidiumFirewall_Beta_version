#!/bin/bash
set -euo pipefail

# Genera la configuración inicial dependiente del sistema real recién instalado.
# Generates the initial configuration that depends on the freshly installed real system.
echo "Generando configuración inicial del sistema... / Generating initial system configuration..."

INTERFACES_CHECK="/var/www/backend/checks/check_interfaces/main_interfaces_check.py"
INTERFACES_JSON="/var/www/config/interfaces.json"
RUNNING_INTERFACES_JSON="/var/www/config_running/interfaces.json"
BPFILTER_RULES_JSON="/var/www/config/rules_bpfilter_human_viewer.json"
ALL_INTERFACES_JSON="/var/www/backend/checks/system_data/data_interfaces/all_interfaces_list.json"
PHYSICAL_INTERFACES_JSON="/var/www/backend/checks/system_data/data_interfaces/physical_interfaces_list.json"
DATA_INTERFACES_DIR="/var/www/backend/checks/system_data/data_interfaces"

# Ejecuta el mismo refresco de interfaces que usa la WebGUI, pero durante instalación.
# Runs the same interface refresh used by the WebGUI, but during installation.
python3 "$INTERFACES_CHECK"

# Valida que los JSON críticos existen y son sintácticamente correctos.
# Validates that critical JSON files exist and are syntactically correct.
python3 -m json.tool "$INTERFACES_JSON" >/dev/null
python3 -m json.tool "$ALL_INTERFACES_JSON" >/dev/null
python3 -m json.tool "$PHYSICAL_INTERFACES_JSON" >/dev/null

# Sincroniza el estado running inicial con las interfaces reales detectadas.
# Synchronizes the initial running state with the detected real interfaces.
cp "$INTERFACES_JSON" "$RUNNING_INTERFACES_JSON"
python3 -m json.tool "$RUNNING_INTERFACES_JSON" >/dev/null

# Detecta la interfaz de gestión a partir de la ruta por defecto del sistema.
# Detects the management interface from the system default route.
MANAGEMENT_INTERFACE="$(ip -o -4 route show default 2>/dev/null | awk '{for (i=1; i<=NF; i++) if ($i == "dev") {print $(i+1); exit}}')"
if [ -z "$MANAGEMENT_INTERFACE" ]; then
    MANAGEMENT_INTERFACE="$(python3 - <<'PY'
import json
with open('/var/www/config/interfaces.json', encoding='utf-8') as handle:
    data = json.load(handle)
ethernets = data.get('network', {}).get('ethernets', {})
print(next(iter(ethernets), ''))
PY
)"
fi
if [ -z "$MANAGEMENT_INTERFACE" ]; then
    echo "No se pudo detectar la interfaz de gestión. / Could not detect management interface." >&2
    exit 1
fi
export MANAGEMENT_INTERFACE

# Adapta las reglas default de bpfilter a la interfaz real de gestión.
# Adapts the default bpfilter rules to the real management interface.
python3 - <<'PY'
import json
import os
from pathlib import Path

path = Path('/var/www/config/rules_bpfilter_human_viewer.json')
management_interface = os.environ['MANAGEMENT_INTERFACE']
if not path.exists():
    raise SystemExit(f'{path} not found')

data = json.loads(path.read_text(encoding='utf-8'))
updated = 0
for item in data.get('bpfilter', []):
    rule = item.get('rule') if isinstance(item, dict) else None
    if not isinstance(rule, dict):
        continue
    # Solo adapta reglas default antiguas atadas a ens21.
    # Only adapt old default rules bound to ens21.
    if rule.get('interface') != 'ens21':
        continue
    rule['interface'] = management_interface
    hook = str(rule.get('hook', '')).lower()
    if hook and rule.get('chain'):
        rule['chain'] = f'{management_interface}_{hook}'
    updated += 1

if updated == 0:
    already_adapted = any(
        isinstance(item, dict)
        and isinstance(item.get('rule'), dict)
        and item['rule'].get('interface') == management_interface
        for item in data.get('bpfilter', [])
    )
    if not already_adapted:
        raise SystemExit('No bpfilter default rules were adapted')
path.write_text(json.dumps(data, indent=4, ensure_ascii=False) + '\n', encoding='utf-8')
PY
python3 -m json.tool "$BPFILTER_RULES_JSON" >/dev/null

# Asegura permisos de escritura para Apache en los datos generados.
# Ensures Apache has write permissions on generated data.
chown -R :www-data /var/www/config /var/www/config_running "$DATA_INTERFACES_DIR"
chmod -R g+rw /var/www/config /var/www/config_running "$DATA_INTERFACES_DIR"

echo "Configuración inicial completada. / Initial configuration completed."
