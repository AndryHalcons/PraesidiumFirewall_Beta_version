#!/bin/bash
set -euo pipefail

CONFIG_DIR="${PRAESIDIUM_CONFIG_DIR:-/var/www/config}"
CONFIG_RUNNING_DIR="${PRAESIDIUM_CONFIG_RUNNING_DIR:-/var/www/config_running}"
DATA_INTERFACES_DIR="${PRAESIDIUM_DATA_INTERFACES_DIR:-/var/www/backend/checks/system_data/data_interfaces}"

# Asegura permisos de escritura para Apache en los datos generados.
# Ensures Apache has write permissions on generated data.
chown -R :www-data "$CONFIG_DIR" "$CONFIG_RUNNING_DIR" "$DATA_INTERFACES_DIR"
chmod -R g+rw "$CONFIG_DIR" "$CONFIG_RUNNING_DIR" "$DATA_INTERFACES_DIR"
