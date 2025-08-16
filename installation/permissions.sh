#!/bin/bash
set -euo pipefail

EXCEPCIONES_FILE="/etc/sudoers.d/praesidium_excepciones"

registrar_excepciones_python() {
    local excepciones=(
        "/var/www/html/interfaces/check_new_physical_interfaces/replace_allow-hotplug.py"
        "/var/www/html/interfaces/check_new_physical_interfaces/check_interfacesJSON.py"
        "/var/www/html/interfaces/check_new_physical_interfaces/ifquery_list.py"
        "/var/www/html/interfaces/check_new_physical_interfaces/ip_link_show.py"
        "/var/www/html/interfaces/check_new_physical_interfaces/compare_ifquery_iplinkshow.py"
        "/var/www/html/interfaces/table_interfaces/table_interfaces.py"
        "/var/www/html/interfaces/table_interfaces/get_interfaces.py"
        "/var/www/html/interfaces/table_interfaces/update_interfaces.py"
    )

    # Crear el archivo si no existe
    if [ ! -f "$EXCEPCIONES_FILE" ]; then
        touch "$EXCEPCIONES_FILE"
        chmod 440 "$EXCEPCIONES_FILE"
    fi

    for ruta in "${excepciones[@]}"; do
        local linea="www-data ALL=(ALL) NOPASSWD: /usr/bin/python3 $ruta"
        grep -qxF "$linea" "$EXCEPCIONES_FILE" || echo "$linea" >> "$EXCEPCIONES_FILE"
    done

    # Validar sintaxis
    visudo -cf "$EXCEPCIONES_FILE"
}
registrar_excepciones_php() {
    local excepciones=(
        "/var/www/html/interfaces/table_interfaces/update_interfaces.php"
    )

    if [ ! -f "$EXCEPCIONES_FILE" ]; then
        touch "$EXCEPCIONES_FILE"
        chmod 440 "$EXCEPCIONES_FILE"
    fi

    for ruta in "${excepciones[@]}"; do
        local linea="www-data ALL=(ALL) NOPASSWD: /usr/bin/php $ruta"
        grep -qxF "$linea" "$EXCEPCIONES_FILE" || echo "$linea" >> "$EXCEPCIONES_FILE"
    done

    visudo -cf "$EXCEPCIONES_FILE"
}
grant_apache_permissions() {
    local target_dir="/var/www/config"

    # Asignar el grupo www-data a todos los archivos y directorios
    # Assign www-data group to all files and directories
    echo "Asignando grupo www-data a todos los archivos y directorios..."
    echo "Assigning www-data group to all files and directories..."
    sudo chown -R :www-data "$target_dir"

    # Dar permisos de lectura y escritura al grupo www-data
    # Grant read and write permissions to www-data group
    echo "Dando permisos de lectura y escritura al grupo www-data..."
    echo "Granting read and write permissions to www-data group..."
    sudo chmod -R g+rw "$target_dir"

    # Confirmación final
    # Final confirmation
    echo "Permisos aplicados correctamente a $target_dir"
    echo "Permissions successfully applied to $target_dir"
}


# Llamada directa si quieres ejecutarla al cargar
registrar_excepciones_python
registrar_excepciones_php
grant_apache_permissions