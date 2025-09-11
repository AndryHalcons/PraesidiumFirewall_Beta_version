import yaml
import shutil
import subprocess
import os
import json
from task_update_json import task_update_json


import yaml
import json
import os

# Inicializa la estructura base compatible con Netplan
# Initialize the base structure compatible with Netplan
def initial_yaml_format():
    return {
        "network": {
            "version": 2,
            "ethernets": {},
            "bonds": {},
            "bridges": {},
            "vlans": {},
            "tunnels": {},
            "wifis": {},
            "wireguard": {}
        }
    }

# Función vacía para procesar interfaces tipo bond
# Empty function to process bond-type interfaces
# Procesa las interfaces tipo bond y las convierte al formato Netplan
# Processes bond-type interfaces and converts them to Netplan format
def parser_bonds(data):
    # Accede a la estructura global de salida
    # Access the global output structure
    global_netplan = initial_yaml_format()

    # Itera sobre cada interfaz bond definida en el JSON
    # Iterate over each bond interface defined in the JSON
    for name, config in data.items():
        bond = {}

        # Asigna las interfaces esclavas
        # Assign slave interfaces
        if config.get("interfaces"):
            bond["interfaces"] = [i.strip() for i in config["interfaces"].split(",") if i.strip()]

        # Configura DHCP
        # Configure DHCP
        if config.get("dhcp4", "false") == "true":
            bond["dhcp4"] = True
        if config.get("dhcp6", "false") == "true":
            bond["dhcp6"] = True

        # Direcciones estáticas
        # Static addresses
        if config.get("addresses"):
            bond["addresses"] = [a.strip() for a in config["addresses"].split(",") if a.strip()]

        # Puertas de enlace
        # Gateways
        if config.get("gateway4"):
            bond["gateway4"] = config["gateway4"]
        if config.get("gateway6"):
            bond["gateway6"] = config["gateway6"]

        # MTU
        # MTU
        if config.get("mtu"):
            bond["mtu"] = int(config["mtu"])

        # Dirección MAC
        # MAC address
        if config.get("macaddress"):
            bond["macaddress"] = config["macaddress"]

        # Servidores DNS
        # DNS servers
        nameservers = {}
        if config.get("nameservers.addresses"):
            ns = [ns.strip() for ns in config["nameservers.addresses"].split(",") if ns.strip()]
            if ns:
                nameservers["addresses"] = ns
        if config.get("nameservers.search"):
            search = [s.strip() for s in config["nameservers.search"].split(",") if s.strip()]
            if search:
                nameservers["search"] = search
        if nameservers:
            bond["nameservers"] = nameservers

        # Parámetros específicos del bond
        # Bond-specific parameters
        parameters = {}
        for key, value in config.items():
            if key.startswith("parameters.") and value:
                param_name = key.split(".", 1)[1]
                parameters[param_name] = value
        if parameters:
            bond["parameters"] = parameters

        # Añade la interfaz bond al YAML global
        # Add the bond interface to the global YAML
        global_netplan["network"]["bonds"][name] = bond


# Función vacía para procesar interfaces tipo bridge
# Empty function to process bridge-type interfaces
# Procesa las interfaces tipo bridge y las convierte al formato Netplan
# Processes bridge-type interfaces and converts them to Netplan format
def parser_bridges(data):
    # Accede a la estructura global de salida
    # Access the global output structure
    global_netplan = initial_yaml_format()

    # Itera sobre cada interfaz bridge definida en el JSON
    # Iterate over each bridge interface defined in the JSON
    for name, config in data.items():
        bridge = {}

        # Asigna las interfaces esclavas
        # Assign slave interfaces
        if config.get("interfaces"):
            bridge["interfaces"] = [i.strip() for i in config["interfaces"].split(",") if i.strip()]

        # Configura DHCP
        # Configure DHCP
        if config.get("dhcp4", "false") == "true":
            bridge["dhcp4"] = True
        if config.get("dhcp6", "false") == "true":
            bridge["dhcp6"] = True

        # Direcciones estáticas
        # Static addresses
        if config.get("addresses"):
            bridge["addresses"] = [a.strip() for a in config["addresses"].split(",") if a.strip()]

        # Puertas de enlace
        # Gateways
        if config.get("gateway4"):
            bridge["gateway4"] = config["gateway4"]
        if config.get("gateway6"):
            bridge["gateway6"] = config["gateway6"]

        # MTU
        # MTU
        if config.get("mtu"):
            bridge["mtu"] = int(config["mtu"])

        # Dirección MAC
        # MAC address
        if config.get("macaddress"):
            bridge["macaddress"] = config["macaddress"]

        # Servidores DNS
        # DNS servers
        nameservers = {}
        if config.get("nameservers.addresses"):
            ns = [ns.strip() for ns in config["nameservers.addresses"].split(",") if ns.strip()]
            if ns:
                nameservers["addresses"] = ns
        if config.get("nameservers.search"):
            search = [s.strip() for s in config["nameservers.search"].split(",") if s.strip()]
            if search:
                nameservers["search"] = search
        if nameservers:
            bridge["nameservers"] = nameservers

        # Parámetros específicos del bridge
        # Bridge-specific parameters
        parameters = {}
        for key, value in config.items():
            if key.startswith("parameters.") and value:
                param_name = key.split(".", 1)[1]
                parameters[param_name] = value
        if parameters:
            bridge["parameters"] = parameters

        # Añade la interfaz bridge al YAML global
        # Add the bridge interface to the global YAML
        global_netplan["network"]["bridges"][name] = bridge


# Función vacía para procesar interfaces tipo ethernet
# Empty function to process ethernet-type interfaces
# Procesa las interfaces tipo ethernet y las convierte al formato Netplan
# Processes ethernet-type interfaces and converts them to Netplan format
def parser_ethernets(data):
    # Accede a la estructura global de salida
    # Access the global output structure
    global_netplan = initial_yaml_format()

    # Itera sobre cada interfaz ethernet definida en el JSON
    # Iterate over each ethernet interface defined in the JSON
    for name, config in data.items():
        ethernet = {}

        # Configura DHCP
        # Configure DHCP
        if config.get("dhcp4", "false") == "true":
            ethernet["dhcp4"] = True
        if config.get("dhcp6", "false") == "true":
            ethernet["dhcp6"] = True

        # Direcciones estáticas
        # Static addresses
        if config.get("addresses"):
            ethernet["addresses"] = [a.strip() for a in config["addresses"].split(",") if a.strip()]

        # Puertas de enlace
        # Gateways
        if config.get("gateway4"):
            ethernet["gateway4"] = config["gateway4"]
        if config.get("gateway6"):
            ethernet["gateway6"] = config["gateway6"]

        # MTU
        # MTU
        if config.get("mtu"):
            try:
                ethernet["mtu"] = int(config["mtu"])
            except ValueError:
                pass

        # Dirección MAC
        # MAC address
        if config.get("macaddress"):
            ethernet["macaddress"] = config["macaddress"]

        # Servidores DNS
        # DNS servers
        nameservers = {}
        if config.get("nameservers.addresses"):
            ns = [ns.strip() for ns in config["nameservers.addresses"].split(",") if ns.strip()]
            if ns:
                nameservers["addresses"] = ns
        if config.get("nameservers.search"):
            search = [s.strip() for s in config["nameservers.search"].split(",") if s.strip()]
            if search:
                nameservers["search"] = search
        if nameservers:
            ethernet["nameservers"] = nameservers

        # Añade la interfaz ethernet al YAML global
        # Add the ethernet interface to the global YAML
        global_netplan["network"]["ethernets"][name] = ethernet


# Función vacía para procesar interfaces tipo tunnel
# Empty function to process tunnel-type interfaces
# Procesa las interfaces tipo túnel y las convierte al formato Netplan
# Processes tunnel-type interfaces and converts them to Netplan format
def parser_tunnels(data):
    # Accede a la estructura global de salida
    # Access the global output structure
    global_netplan = initial_yaml_format()

    # Itera sobre cada interfaz túnel definida en el JSON
    # Iterate over each tunnel interface defined in the JSON
    for name, config in data.items():
        tunnel = {}

        # Modo del túnel (por ejemplo, gre, ipip, sit)
        # Tunnel mode (e.g., gre, ipip, sit)
        if config.get("mode"):
            tunnel["mode"] = config["mode"]

        # Dirección local del extremo del túnel
        # Local address of the tunnel endpoint
        if config.get("local"):
            tunnel["local"] = config["local"]

        # Dirección remota del extremo del túnel
        # Remote address of the tunnel endpoint
        if config.get("remote"):
            tunnel["remote"] = config["remote"]

        # Direcciones IP asignadas
        # Assigned IP addresses
        if config.get("addresses"):
            tunnel["addresses"] = [a.strip() for a in config["addresses"].split(",") if a.strip()]

        # Puertas de enlace
        # Gateways
        if config.get("gateway4"):
            tunnel["gateway4"] = config["gateway4"]
        if config.get("gateway6"):
            tunnel["gateway6"] = config["gateway6"]

        # MTU
        # MTU
        if config.get("mtu"):
            try:
                tunnel["mtu"] = int(config["mtu"])
            except ValueError:
                pass

        # Dirección MAC (aunque no suele aplicarse en túneles)
        # MAC address (rarely used in tunnels)
        if config.get("macaddress"):
            tunnel["macaddress"] = config["macaddress"]

        # Servidores DNS
        # DNS servers
        nameservers = {}
        if config.get("nameservers.addresses"):
            ns = [ns.strip() for ns in config["nameservers.addresses"].split(",") if ns.strip()]
            if ns:
                nameservers["addresses"] = ns
        if config.get("nameservers.search"):
            search = [s.strip() for s in config["nameservers.search"].split(",") if s.strip()]
            if search:
                nameservers["search"] = search
        if nameservers:
            tunnel["nameservers"] = nameservers

        # Añade la interfaz túnel al YAML global
        # Add the tunnel interface to the global YAML
        global_netplan["network"]["tunnels"][name] = tunnel


# Función vacía para procesar interfaces tipo wireguard
# Empty function to process wireguard-type interfaces
# Procesa las interfaces tipo wireguard y las convierte al formato Netplan
# Processes wireguard-type interfaces and converts them to Netplan format
def parser_wireguard(data):
    # Accede a la estructura global de salida
    # Access the global output structure
    global_netplan = initial_yaml_format()

    # Itera sobre cada interfaz wireguard definida en el JSON
    # Iterate over each wireguard interface defined in the JSON
    for name, config in data.items():
        wg = {}

        # Direcciones IP asignadas
        # Assigned IP addresses
        if config.get("addresses"):
            wg["addresses"] = [a.strip() for a in config["addresses"].split(",") if a.strip()]

        # Puerto de escucha
        # Listening port
        if config.get("port"):
            try:
                wg["port"] = int(config["port"])
            except ValueError:
                pass

        # Clave privada
        # Private key
        if config.get("key.private"):
            wg["private-key"] = config["key.private"]

        # Configuración de peers
        # Peer configuration
        peers = {}
        if config.get("peers.keys.public"):
            peers["public-key"] = config["peers.keys.public"]
        if config.get("peers.allowed-ips"):
            peers["allowed-ips"] = [ip.strip() for ip in config["peers.allowed-ips"].split(",") if ip.strip()]
        if config.get("peers.keepalive"):
            try:
                peers["persistent-keepalive"] = int(config["peers.keepalive"])
            except ValueError:
                pass
        if config.get("peers.endpoint"):
            peers["endpoint"] = config["peers.endpoint"]
        if peers:
            wg["peers"] = [peers]

        # Rutas asociadas
        # Associated routes
        routes = {}
        if config.get("routes.to"):
            routes["to"] = config["routes.to"]
        if config.get("routes.via"):
            routes["via"] = config["routes.via"]
        if config.get("routes.table"):
            routes["table"] = config["routes.table"]
        if routes:
            wg["routes"] = [routes]

        # Política de enrutamiento
        # Routing policy
        policy = {}
        if config.get("routing-policy.from"):
            policy["from"] = config["routing-policy.from"]
        if config.get("routing-policy.table"):
            policy["table"] = config["routing-policy.table"]
        if policy:
            wg["routing-policy"] = policy

        # Marca de tráfico
        # Traffic mark
        if config.get("mark"):
            wg["mark"] = config["mark"]

        # MTU
        # MTU
        if config.get("mtu"):
            try:
                wg["mtu"] = int(config["mtu"])
            except ValueError:
                pass

        # Añade la interfaz wireguard al YAML global
        # Add the wireguard interface to the global YAML
        global_netplan["network"]["wireguard"][name] = wg


# Función vacía para procesar interfaces tipo wifi
# Empty function to process wifi-type interfaces
# Procesa las interfaces tipo wifi y las convierte al formato Netplan
# Processes wifi-type interfaces and converts them to Netplan format
def parser_wifis(data):
    # Accede a la estructura global de salida
    # Access the global output structure
    global_netplan = initial_yaml_format()

    # Itera sobre cada interfaz wifi definida en el JSON
    # Iterate over each wifi interface defined in the JSON
    for name, config in data.items():
        wifi = {}

        # Configura DHCP
        # Configure DHCP
        if config.get("dhcp4", "false") == "true":
            wifi["dhcp4"] = True
        if config.get("dhcp6", "false") == "true":
            wifi["dhcp6"] = True

        # Direcciones IP estáticas
        # Static IP addresses
        if config.get("addresses"):
            wifi["addresses"] = [a.strip() for a in config["addresses"].split(",") if a.strip()]

        # Puertas de enlace
        # Gateways
        if config.get("gateway4"):
            wifi["gateway4"] = config["gateway4"]
        if config.get("gateway6"):
            wifi["gateway6"] = config["gateway6"]

        # MTU
        # MTU
        if config.get("mtu"):
            try:
                wifi["mtu"] = int(config["mtu"])
            except ValueError:
                pass

        # Dirección MAC
        # MAC address
        if config.get("macaddress"):
            wifi["macaddress"] = config["macaddress"]

        # Servidores DNS
        # DNS servers
        nameservers = {}
        if config.get("nameservers.addresses"):
            ns = [ns.strip() for ns in config["nameservers.addresses"].split(",") if ns.strip()]
            if ns:
                nameservers["addresses"] = ns
        if config.get("nameservers.search"):
            search = [s.strip() for s in config["nameservers.search"].split(",") if s.strip()]
            if search:
                nameservers["search"] = search
        if nameservers:
            wifi["nameservers"] = nameservers

        # Puntos de acceso WiFi
        # WiFi access points
        access_points = {}
        for key, value in config.items():
            if key.startswith("access-points.") and value:
                parts = key.split(".")
                if len(parts) == 3:
                    ssid, field = parts[1], parts[2]
                    if ssid not in access_points:
                        access_points[ssid] = {}
                    access_points[ssid][field] = value
        if access_points:
            wifi["access-points"] = access_points

        # Añade la interfaz wifi al YAML global
        # Add the wifi interface to the global YAML
        global_netplan["network"]["wifis"][name] = wifi


# Función vacía para procesar interfaces tipo vlan
# Empty function to process vlan-type interfaces
# Procesa las interfaces tipo VLAN y las convierte al formato Netplan
# Processes VLAN-type interfaces and converts them to Netplan format
def parser_vlans(data):
    # Accede a la estructura global de salida
    # Access the global output structure
    global_netplan = initial_yaml_format()

    # Itera sobre cada interfaz VLAN definida en el JSON
    # Iterate over each VLAN interface defined in the JSON
    for name, config in data.items():
        vlan = {}

        # ID de la VLAN
        # VLAN ID
        if config.get("id"):
            try:
                vlan["id"] = int(config["id"])
            except ValueError:
                pass

        # Enlace físico al que se asocia la VLAN
        # Physical link associated with the VLAN
        if config.get("link"):
            vlan["link"] = config["link"]

        # Configura DHCP
        # Configure DHCP
        if config.get("dhcp4", "false") == "true":
            vlan["dhcp4"] = True
        if config.get("dhcp6", "false") == "true":
            vlan["dhcp6"] = True

        # Direcciones IP estáticas
        # Static IP addresses
        if config.get("addresses"):
            vlan["addresses"] = [a.strip() for a in config["addresses"].split(",") if a.strip()]

        # Puertas de enlace
        # Gateways
        if config.get("gateway4"):
            vlan["gateway4"] = config["gateway4"]
        if config.get("gateway6"):
            vlan["gateway6"] = config["gateway6"]

        # MTU
        # MTU
        if config.get("mtu"):
            try:
                vlan["mtu"] = int(config["mtu"])
            except ValueError:
                pass

        # Dirección MAC
        # MAC address
        if config.get("macaddress"):
            vlan["macaddress"] = config["macaddress"]

        # Servidores DNS
        # DNS servers
        nameservers = {}
        if config.get("nameservers.addresses"):
            ns = [ns.strip() for ns in config["nameservers.addresses"].split(",") if ns.strip()]
            if ns:
                nameservers["addresses"] = ns
        if config.get("nameservers.search"):
            search = [s.strip() for s in config["nameservers.search"].split(",") if s.strip()]
            if search:
                nameservers["search"] = search
        if nameservers:
            vlan["nameservers"] = nameservers

        # Añade la interfaz VLAN al YAML global
        # Add the VLAN interface to the global YAML
        global_netplan["network"]["vlans"][name] = vlan


# Convierte el JSON en estructura Netplan llamando a cada parser
# Converts the JSON into Netplan structure by calling each parser
def convert(json_data):
    netplan = initial_yaml_format()

    parser_bonds(json_data.get("network", {}).get("bonds", {}))
    parser_bridges(json_data.get("network", {}).get("bridges", {}))
    parser_ethernets(json_data.get("network", {}).get("ethernets", {}))
    parser_tunnels(json_data.get("network", {}).get("tunnels", {}))
    parser_wireguard(json_data.get("network", {}).get("wireguard", {}))
    parser_wifis(json_data.get("network", {}).get("wifis", {}))
    parser_vlans(json_data.get("network", {}).get("vlans", {}))

    return netplan

# Punto de entrada principal del script
# Main entry point of the script
def main():
    json_path = "/var/www/config_running/interfaces.json"
    yaml_output = "/var/www/config_running/interfaces2.yml"

    # Verifica si el archivo JSON existe
    # Check if the JSON file exists
    if not os.path.exists(json_path):
        print(f"❌ No se encontró el archivo: {json_path}")  # File not found
        return

    # Carga el contenido del archivo JSON
    # Load the content of the JSON file
    with open(json_path, "r") as f:
        json_data = json.load(f)

    # Convierte el JSON a formato Netplan
    # Convert the JSON to Netplan format
    netplan_data = convert(json_data)

    # Guarda el resultado en un archivo YAML
    # Save the result into a YAML file
    with open(yaml_output, "w") as f:
        yaml.dump(netplan_data, f, default_flow_style=False, sort_keys=False)

    print(f"✅ Netplan generado en: {yaml_output}")  # Netplan generated


