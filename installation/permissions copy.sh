#!/bin/bash
set -euo pipefail

EXCEPCIONES_FILE="/etc/sudoers.d/praesidium_excepciones"

# Crear el archivo si no existe
touch "$EXCEPCIONES_FILE"
chmod 440 "$EXCEPCIONES_FILE"

# Lista de excepciones
EXCEPCIONES=(
  "/var/www/html/interfaces/check_new_physical_interfaces/replace_allow-hotplug.py"
  "/var/www/html/interfaces/check_new_physical_interfaces/check_interfacesJSON.py"
  "/var/www/html/interfaces/check_new_physical_interfaces/ifquery_list.py"
  "/var/www/html/interfaces/check_new_physical_interfaces/ip_link_show.py"
  "/var/www/html/interfaces/check_new_physical_interfaces/compare_ifquery_iplinkshow.py"
  "/var/www/html/interfaces/table_interfaces/update_interfaces.php"
  "/var/www/html/interfaces/table_interfaces/table_interfaces.py"
  "/var/www/html/interfaces/table_interfaces/get_interfaces.py"
  "/var/www/html/interfaces/table_interfaces/update_interfaces.py"

)

for ruta in "${EXCEPCIONES[@]}"; do
  LINEA="www-data ALL=(ALL) NOPASSWD: /usr/bin/python3 $ruta"
  grep -qxF "$LINEA" "$EXCEPCIONES_FILE" || echo "$LINEA" >> "$EXCEPCIONES_FILE"
done

# Validar sintaxis
visudo -cf "$EXCEPCIONES_FILE"

