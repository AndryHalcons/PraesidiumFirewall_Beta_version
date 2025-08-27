#!/bin/bash
set -euo pipefail

EXCEPCIONES_FILE="/etc/sudoers.d/praesidium_excepciones"

registrar_excepciones_python() {
    local excepciones=(
        "/var/www/backend/checks/check_routes/check_system_routes_running.py"
        "/var/www/backend/checks/check_interfaces/main_interfaces_check.py"
        "/var/www/backend/commits/commit_apply.py *"
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
grant_apache_permissions_running() {
    local target_dir="/var/www/config_running"

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
grant_backend_permissions() {
    local target_dir="/var/www/backend"

    # Asignar el grupo www-data a todos los archivos y directorios
    # Assign www-data group to all files and directories
    echo "Asignando grupo www-data a todos los archivos y directorios en $target_dir..."
    echo "Assigning www-data group to all files and directories in $target_dir..."
    sudo chown -R :www-data "$target_dir"

    # Dar permisos de lectura y escritura al grupo www-data
    # Grant read and write permissions to www-data group
    echo "Dando permisos de lectura y escritura al grupo www-data en $target_dir..."
    echo "Granting read and write permissions to www-data group in $target_dir..."
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
grant_apache_permissions_running
grant_backend_permissions