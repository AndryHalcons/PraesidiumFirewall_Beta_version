#!/bin/bash

set -e  # Detener el script si ocurre un error / Stop the script if any error occurs

# Función para instalar paquetes esenciales de Kea / Function to install essential Kea packages
install_kea() {
    echo "Instalando paquetes esenciales de Kea DHCP..."  # Mensaje de inicio / Start message

    sudo apt update  # Actualizar lista de paquetes / Update package list
    sudo apt install -y \
        kea-dhcp4-server \
        kea-dhcp6-server \
        kea-common \
        kea-admin

    echo "Instalación completada."  # Mensaje de finalización / Completion message
}



# Función para habilitar e iniciar los servicios / Function to enable and start services
initial_kea_config() {
    echo "Habilitando e iniciando servicios Kea..."  # Mensaje de inicio / Start message

    sudo systemctl enable kea-dhcp4-server  # Habilitar DHCPv4 en el arranque / Enable DHCPv4 on boot
    sudo systemctl enable kea-dhcp6-server  # Habilitar DHCPv6 en el arranque / Enable DHCPv6 on boot

    sudo systemctl start kea-dhcp4-server   # Iniciar servicio DHCPv4 / Start DHCPv4 service
    sudo systemctl start kea-dhcp6-server   # Iniciar servicio DHCPv6 / Start DHCPv6 service

    echo "Servicios Kea habilitados e iniciados."  # Mensaje de finalización / Completion message
}

# Ejecutar funciones / Run functions
install_kea
initial_kea_config
