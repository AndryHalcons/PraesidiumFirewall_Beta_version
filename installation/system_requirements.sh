#!/bin/bash
set -e

# Función para instalar dependencias del sistema
# Function to install system dependencies
instalar_dependencias() {
    echo "Actualizando repositorios..."
    # Updating repositories...
    apt update

    echo "Instalando paquetes desde requirements_ubuntu.txt..."
    # Installing packages from requirements_ubuntu.txt...
    xargs -a requirements_ubuntu.txt apt install -y
}

######################FUNCIONES AUXILIARES#######################
# Funcion para instalar la extensión YAML en PHP
# Function to install YAML extension for PHP
instalar_php_yaml() {
    echo "Instalando extensión YAML para PHP..."

    # Verificar si ya está instalada
    if php -m | grep -q yaml; then
        echo "La extensión YAML ya está instalada."
        return
    fi

    # Instalar vía PECL
    pecl install yaml

    # Activar la extensión
    echo "extension=yaml.so" | tee /etc/php/8.3/mods-available/yaml.ini
    phpenmod yaml

    # Reiniciar Apache
    systemctl restart apache2

    echo "Extensión YAML instalada y activada correctamente."
}
enable_services() {
    echo "Habilitando y arrancando el servicio nftables..."
    sudo systemctl enable nftables
    sudo systemctl start nftables
    sudo systemctl enable squid
    sudo systemctl start squid
}

# Ejecutar la función
# Run the function
instalar_dependencias
instalar_php_yaml
enable_services
