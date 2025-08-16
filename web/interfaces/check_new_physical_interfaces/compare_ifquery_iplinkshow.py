#!/usr/bin/env python3
import json       # Used to handle JSON data / Usado para manejar datos JSON
import os         # Used to check file existence / Usado para verificar existencia de archivos

from ifquery_list import get_ifquery_names
from ip_link_show import get_ip_link_names

# Path to the interfaces JSON file
# Ruta al archivo JSON de interfaces
CONFIG_PATH = "/var/www/config/interfaces.json"

# Get interface names from both sources
# Obtiene los nombres de interfaz desde ambas fuentes
ifquery_interfaces = get_ifquery_names()
iplink_interfaces = get_ip_link_names()

print("Interfaces from ifquery:")
print(ifquery_interfaces)

print("\nInterfaces from ip link show:")
print(iplink_interfaces)

# Find interfaces that are in ip link but not in ifquery
# Encuentra interfaces que están en ip link pero no en ifquery
new_interfaces = [iface for iface in iplink_interfaces if iface not in ifquery_interfaces]

print("\nNew interfaces to add:")
print(new_interfaces)

# Load existing JSON config
# Carga la configuración JSON existente
if os.path.exists(CONFIG_PATH):
    with open(CONFIG_PATH, "r") as f:
        config = json.load(f)
else:
    config = {"interfaces": []}

# Add new interfaces with default values
# Añade nuevas interfaces con valores por defecto
for iface_name in new_interfaces:
    new_entry = {
        "name": iface_name,
        "auto": True,
        "family": "inet",
        "method": "static",
        "options": {}
    }
    config["interfaces"].append(new_entry)

# Save updated config back to file
# Guarda la configuración actualizada en el archivo
with open(CONFIG_PATH, "w") as f:
    json.dump(config, f, indent=4)

print(f"\nUpdated {CONFIG_PATH} with new interfaces.")
