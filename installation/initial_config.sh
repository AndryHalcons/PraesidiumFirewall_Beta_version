#!/bin/bash
set -euo pipefail

# Genera la configuración inicial dependiente del sistema real recién instalado.
# Generates the initial configuration that depends on the freshly installed real system.
echo "Generando configuración inicial del sistema... / Generating initial system configuration..."

INTERFACES_CHECK="/var/www/backend/checks/check_interfaces/main_interfaces_check.py"
INTERFACES_JSON="/var/www/config/interfaces.json"
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

# Asegura permisos de escritura para Apache en los datos generados.
# Ensures Apache has write permissions on generated data.
chown -R :www-data /var/www/config /var/www/config_running "$DATA_INTERFACES_DIR"
chmod -R g+rw /var/www/config /var/www/config_running "$DATA_INTERFACES_DIR"

echo "Configuración inicial completada. / Initial configuration completed."
