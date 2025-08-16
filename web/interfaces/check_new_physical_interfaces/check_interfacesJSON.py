import os
import json

json_path = "/var/www/config/interfaces.json"
interfaces_path = "/etc/network/interfaces"

def is_file_empty(path):
    return not os.path.exists(path) or os.path.getsize(path) == 0

def parse_interfaces(path):
    interfaces = []
    current = None
    auto_set = set()

    with open(path, "r") as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith("#"):
                continue

            tokens = line.split()

            if tokens[0] == "auto":
                auto_set.update(tokens[1:])
            elif tokens[0] == "iface":
                if current:
                    interfaces.append(current)
                current = {
                    "name": tokens[1],
                    "auto": tokens[1] in auto_set,
                    "family": tokens[2] if len(tokens) > 2 else None,
                    "method": tokens[3] if len(tokens) > 3 else None,
                    "options": {}
                }
            elif current and len(tokens) >= 2:
                key, value = tokens[0], " ".join(tokens[1:])
                current["options"][key] = value

        if current:
            interfaces.append(current)

    return {"interfaces": interfaces}

def write_json(path, data):
    with open(path, "w") as f:
        json.dump(data, f, indent=4)

def main():
    if is_file_empty(json_path):
        print("Archivo JSON vacío. Parseando interfaces...")
        parsed_data = parse_interfaces(interfaces_path)
        write_json(json_path, parsed_data)
        print("Interfaces guardadas en formato JSON.")
    else:
        print("El archivo JSON ya contiene datos. No se ha modificado.")

if __name__ == "__main__":
    main()
