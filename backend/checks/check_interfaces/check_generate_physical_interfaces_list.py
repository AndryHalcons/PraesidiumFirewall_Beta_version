#!/usr/bin/env python3

import subprocess
import json
import os

# Este script genera un listado de interfaces físicas en formato JSON, será el que se use en los formularios de la GUI
# This script generates a list of physical interfaces in JSON format; it will be used in the GUI forms

def get_physical_interfaces():
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

def save_interfaces_to_json(path, interfaces):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w") as f:
        json.dump({"physical_interfaces": interfaces}, f, indent=2)

def check_generate_physical_interfaces_list():
    output_path = "/var/www/backend/checks/system_data/data_interfaces/physical_interfaces_list.json"
    interfaces = get_physical_interfaces()
    save_interfaces_to_json(output_path, interfaces)

check_generate_physical_interfaces_list()