#!/usr/bin/env python3

import subprocess
import json
import os

# Este script genera un listado de TODAS las interfaces  en formato JSON, será el que se use en los formularios de la GUI
# This script generates a list of ALL interfaces in JSON format; it will be used in the GUI forms


def get_all_interfaces():
    result = subprocess.run(["ip", "-o", "link", "show"], capture_output=True, text=True)
    lines = result.stdout.splitlines()
    interfaces = []

    for line in lines:
        # El formato de salida es: <index>: <interface_name>: <flags> ...
        parts = line.split(": ")
        if len(parts) >= 2:
            name = parts[1].split("@")[0].strip()
            interfaces.append(name)
    return interfaces

def save_interfaces_to_json(path, interfaces):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w") as f:
        json.dump({"all_interfaces": interfaces}, f, indent=2)

def check_generate_all_interfaces_list():
    output_path = "/var/www/backend/checks/system_data/data_interfaces/all_interfaces_list.json"
    interfaces = get_all_interfaces()
    save_interfaces_to_json(output_path, interfaces)

check_generate_all_interfaces_list()
