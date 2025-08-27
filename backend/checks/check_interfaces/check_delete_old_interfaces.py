#!/usr/bin/env python3

import subprocess
import yaml
import os

#Este script borra las interfaces fisicas que han sido desconectadas/quitadas del sistema
#This script removes physical interfaces that have been disconnected or removed from the system.
# Obtener interfaces físicas actuales del sistema
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

# Cargar archivo YAML
def load_netplan_file(path):
    if not os.path.exists(path):
        return {}
    with open(path, "r") as f:
        return yaml.safe_load(f)

# Guardar archivo YAML actualizado
def save_netplan_file(path, netplan_data):
    with open(path, "w") as f:
        yaml.dump(netplan_data, f, default_flow_style=False)

# Eliminar interfaces que ya no existen
def remove_missing_interfaces(netplan_data, current_ifaces):
    ethernets = netplan_data.get("network", {}).get("ethernets", {})
    to_remove = [iface for iface in ethernets if iface not in current_ifaces]

    for iface in to_remove:
        del netplan_data["network"]["ethernets"][iface]

    return netplan_data

# Ejecutar el proceso
def check_delete_old_interfaces():
    netplan_path = "/var/www/config/interfaces.yml"

    current_ifaces = get_system_interfaces()
    netplan_data = load_netplan_file(netplan_path)

    if "network" in netplan_data and "ethernets" in netplan_data["network"]:
        updated_data = remove_missing_interfaces(netplan_data, current_ifaces)
        save_netplan_file(netplan_path, updated_data)

check_delete_old_interfaces()