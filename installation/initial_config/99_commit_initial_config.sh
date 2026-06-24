#!/bin/bash
set -euo pipefail

# Ejecuta un commit/apply inicial al final de la post-instalación.
# Runs an initial commit/apply at the end of post-installation.
COMMIT_APPLY="${PRAESIDIUM_COMMIT_APPLY:-/var/www/backend/commits/commit_apply.py}"
COMMIT_USER="${PRAESIDIUM_INITIAL_COMMIT_USER:-initial_config}"
CONFIG_DIR="${PRAESIDIUM_CONFIG_DIR:-/var/www/config}"
CONFIG_RUNNING_DIR="${PRAESIDIUM_CONFIG_RUNNING_DIR:-/var/www/config_running}"
DATA_INTERFACES_DIR="${PRAESIDIUM_DATA_INTERFACES_DIR:-/var/www/backend/checks/system_data/data_interfaces}"

if [[ ! -f "$COMMIT_APPLY" ]]; then
    echo "No existe commit_apply.py: $COMMIT_APPLY" >&2
    exit 1
fi

# Usa una marca temporal compatible con commit_history.
# Use a timestamp compatible with commit_history.
COMMIT_DATE="$(date -u +%Y%m%d%H%M%S%3N)"
COMMIT_PAYLOAD="{\"commit\":{\"date\":\"${COMMIT_DATE}\",\"user\":\"${COMMIT_USER}\"}}"

python3 "$COMMIT_APPLY" "$COMMIT_PAYLOAD"

# El commit/apply puede crear nuevos artefactos como root; deja los datos editables por Apache.
# Commit/apply can create new artifacts as root; keep data writable by Apache.
chown -R :www-data "$CONFIG_DIR" "$CONFIG_RUNNING_DIR" "$DATA_INTERFACES_DIR"
chmod -R g+rw "$CONFIG_DIR" "$CONFIG_RUNNING_DIR" "$DATA_INTERFACES_DIR"

python3 -m json.tool "$CONFIG_DIR/commit_history/commit_history.json" >/dev/null
python3 -m json.tool "$CONFIG_RUNNING_DIR/interfaces.json" >/dev/null
