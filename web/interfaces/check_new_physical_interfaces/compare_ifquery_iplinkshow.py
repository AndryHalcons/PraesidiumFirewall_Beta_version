#!/usr/bin/env python3
import json
import os

from ifquery_list import get_ifquery_names
from ip_link_show import get_ip_link_names
from replace_allow_hotplug import replace_hotplug
from check_interfacesJSON import check_interfaces

CONFIG_PATH = "/var/www/config/interfaces.json"

# Ejecutar funciones previas check_interfacesJSON.py & replace_allow_hotplug.py
replace_hotplug()
check_interfaces()

# Obtener interfaces
ifquery_interfaces = get_ifquery_names()
iplink_interfaces = get_ip_link_names()

#print("Interfaces from ifquery:")
#print(ifquery_interfaces)

#print("\nInterfaces from ip link show:")
#print(iplink_interfaces)

# Cargar configuración existente
if os.path.exists(CONFIG_PATH):
    with open(CONFIG_PATH, "r") as f:
        config = json.load(f)
else:
    config = {"interfaces": []}

existing_names = {iface["name"] for iface in config["interfaces"]}

new_interfaces = [
    iface for iface in iplink_interfaces
    if iface not in ifquery_interfaces and iface not in existing_names
]

#print("\nNew interfaces to add:")
#print(new_interfaces)

for iface_name in new_interfaces:
    new_entry = {
        "name": iface_name,
        "auto": True,
        "family": "inet",
        "method": "static",
        "options": {}
    }
    config["interfaces"].append(new_entry)

with open(CONFIG_PATH, "w") as f:
    json.dump(config, f, indent=4)

#print(f"\n✅ Updated {CONFIG_PATH} with new interfaces.")
