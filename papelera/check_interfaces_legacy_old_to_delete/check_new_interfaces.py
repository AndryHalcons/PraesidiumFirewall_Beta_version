#!/usr/bin/env python3

import subprocess
import yaml
import os

#Este script añade las interfaces FISICAS nuevas detectadas por el sistema al archivo interfaces
#This script adds newly detected physical interfaces from the system to the interfaces file.

# Ejecutar 'ip link show' y obtener nombres de interfaces físicas (excluyendo loopback)
# Run 'ip link show' and get physical interface names (excluding loopback)
def get_system_interfaces():
    result = subprocess.run(["ip", "link", "show"], capture_output=True, text=True)
    lines = result.stdout.splitlines()
    interfaces = []

    for line in lines:
        if ": " in line:
            name = line.split(": ")[1].split("@")[0].strip()
            if (
                name != "lo"
                and not name.startswith(("br", "bond", "docker", "veth", "vir", "tun"))
            ):
                interfaces.append(name)
    return interfaces


# Leer el archivo YAML existente
# Read the existing YAML file
def load_netplan_file(path):
    if not os.path.exists(path):
        return {}
    with open(path, "r") as f:
        return yaml.safe_load(f)

# Obtener las interfaces ya configuradas en el archivo Netplan
# Get the interfaces already configured in the Netplan file
def get_configured_interfaces(netplan_data):
    return netplan_data.get("network", {}).get("ethernets", {}) if netplan_data else {}

# Añadir nuevas interfaces al archivo Netplan con dhcp4: false
# Add new interfaces to the Netplan file with dhcp4: false
def add_new_interfaces(netplan_data, new_interfaces):
    if "network" not in netplan_data:
        netplan_data["network"] = {}
    if "ethernets" not in netplan_data["network"]:
        netplan_data["network"]["ethernets"] = {}

    for iface in new_interfaces:
        netplan_data["network"]["ethernets"][iface] = {"dhcp4": False}
    return netplan_data

# Guardar el archivo YAML actualizado
# Save the updated YAML file
def save_netplan_file(path, netplan_data):
    with open(path, "w") as f:
        yaml.dump(netplan_data, f, default_flow_style=False)

# Ejecutar el proceso completo directamente
# Execute the full process directly
def run_check_new_interfaces():
    netplan_path = "/var/www/config/interfaces.yml"

    system_ifaces = get_system_interfaces()
    netplan_data = load_netplan_file(netplan_path)
    configured_ifaces = get_configured_interfaces(netplan_data)
    configured_names = list(configured_ifaces.keys())

    # Filtrar interfaces nuevas / Filter out new interfaces
    new_ifaces = [iface for iface in system_ifaces if iface not in configured_names]

    # Si hay nuevas interfaces, actualizar el archivo
    # If there are new interfaces, update the file
    if new_ifaces:
        updated_data = add_new_interfaces(netplan_data, new_ifaces)
        save_netplan_file(netplan_path, updated_data)

run_check_new_interfaces()
