import os
import ipaddress
import json
import re
from task_update_json import task_update_json

###########################################################################
###################   Import Json to to consult  #########################
###########################################################################

# Importa el archivo de alias y lo devuelve como array
# Imports the alias file and returns it as an array
def import_alias_json():
    json_path = '/var/www/config_running/alias.json'

    if not os.path.exists(json_path):
        return False

    with open(json_path, 'r', encoding='utf-8') as f:
        raw = f.read()

    try:
        alias_json_data = json.loads(raw)
    except json.JSONDecodeError:
        return False

    return alias_json_data

###########################################################################
############################# hook & chain SECTION ########################
###########################################################################
# Verifica si el valor recibido como hook es uno de los tres tipos válidos: BF_HOOK_XDP, BF_HOOK_TC_INGRESS o BF_HOOK_TC_EGRESS.
# Si el hook es válido, lo devuelve en mayúsculas; en caso contrario, devuelve una cadena vacía.

# Verifies whether the provided hook value is one of the three valid types: BF_HOOK_XDP, BF_HOOK_TC_INGRESS, or BF_HOOK_TC_EGRESS.
# If the hook is valid, it returns it in uppercase; otherwise, it returns an empty string.

def verify_hook(hook):
    valid_hooks = {"BF_HOOK_XDP", "BF_HOOK_TC_INGRESS", "BF_HOOK_TC_EGRESS"}

    if not hook:
        return ""

    hook = hook.strip().upper()

    return hook if hook in valid_hooks else ""


# Verifica si el nombre de cadena (chain) recibido es válido para el hook especificado.
# Si la cadena es válida para el hook, se devuelve tal cual; en caso contrario, se devuelve una cadena vacía.

# Verifies whether the provided chain name is valid for the specified hook.
# If the chain is valid for the given hook, it returns the chain name; otherwise, it returns an empty string.

def verify_chain(hook, chain_name, interface):
    if not hook or not chain_name or not interface:
        result = ""
        return result

    hook_clean = hook.strip().lower()
    chain_clean = chain_name.strip()
    expected_chain = f"{interface}_{hook_clean}"

    if chain_clean != expected_chain:
        result = ""
        return result

    result = chain_clean
    return result





###########################################################################
############################# l3 & l4  SECTION ########################
###########################################################################

# Transforma el protocolo de capa 3 (ej. IPv4, IPv6) al formato bpfilter.
# Devuelve 'meta.l3_proto eq <valor>' si es válido, o cadena vacía si no lo es.
#
# Transforms layer 3 protocol (e.g., IPv4, IPv6) into bpfilter format.
# Returns 'meta.l3_proto eq <value>' if valid, or empty string if invalid.

def transform_l3_proto(locate, proto):
    if not proto:
        return ""

    proto = proto.strip().lower()
    valid_l3 = {"ipv4", "ipv6"}

    if proto in valid_l3:
        return f"{locate} eq {proto}"
    return ""

# Transforma el protocolo de capa 4 (ej. TCP, UDP, ICMP, ICMPv6) al formato bpfilter.
# Devuelve 'meta.l4_proto eq <valor>' si es válido, o cadena vacía si no lo es.
#
# Transforms layer 4 protocol (e.g., TCP, UDP, ICMP, ICMPv6) into bpfilter format.
# Returns 'meta.l4_proto eq <value>' if valid, or empty string if invalid.

def transform_l4_proto(locate, proto):
    if not proto:
        return ""

    proto = proto.strip().lower()
    valid_l4 = {"tcp", "udp", "icmp", "icmpv6"}

    if proto in valid_l4:
        return f"{locate} eq {proto}"
    return ""


###########################################################################
############################# INTERFACE  SECTION ##########################
###########################################################################
# // Recibe el nombre de una interfaz y devuelve su ifindex desde el archivo JSON del sistema
# // Receives an interface name and returns its ifindex from the system JSON file
def transform_iface(iface_name):
    if not iface_name:
        return ""

    path = "/var/www/backend/checks/system_data/data_interfaces/physical_interfaces_list.json"
    try:
        with open(path, "r") as f:
            data = json.load(f)
        interfaces = data.get("physical_interfaces", [])
        for iface in interfaces:
            if iface.get("name") == iface_name:
                return iface.get("ifindex", "")
    except Exception:
        pass  # Silencia errores de lectura

    return ""

###########################################################################
############################# IP SECTION ##################################
###########################################################################
# Procesa una entrada de direcciones IPv4 individuales.
# Si se recibe una lista o cadena separada por comas, valida cada IP.
# Devuelve el formato bpfilter con 'eq' para una sola IP o 'in {…}' para múltiples.
# Si ninguna IP es válida, devuelve cadena vacía.
#
# Processes input containing individual IPv4 addresses.
# If a list or comma-separated string is provided, each IP is validated.
# Returns bpfilter format: 'eq' for a single IP or 'in {…}' for multiple.
# Returns an empty string if no valid IPs are found.
"""
def transform_ip4(locate, source):

    # Si no hay fuente, devolvemos cadena vacía
    # If no source is provided, return empty string
    if not source:
        return ""

    # Si es una cadena con varias IPs separadas por coma, la convertimos en lista
    # If it's a comma-separated string of IPs, convert it to a list
    if isinstance(source, str) and "," in source:
        source = [ip.strip() for ip in source.split(",")]

    # Si es una lista de IPs, procesamos cada una
    # If it's a list of IPs, process each one
    if isinstance(source, list):
        valid_ips = []
        for ip in source:
            # Validamos que cada IP sea IPv4 válida
            # Validate that each IP is a valid IPv4 address
            try:
                ip_obj = ipaddress.IPv4Address(ip)
                valid_ips.append(str(ip_obj))
            except ValueError:
                continue

        # Si hay IPs válidas, devolvemos formato bpfilter con 'in'
        # If valid IPs exist, return bpfilter format with 'in'
        if valid_ips:
            return f"({locate}) in {{ {'; '.join(valid_ips)} }}"
            #return f"{locate} in {{{','.join(valid_ips)}}}" old
            
        else:
            return ""

    # Si es una sola IP, validamos y devolvemos formato 'eq'
    # If it's a single IP, validate and return 'eq' format
    try:
        ip_obj = ipaddress.IPv4Address(source)
        return f"{locate} eq {ip_obj}"
    except ValueError:
        return ""
"""
def transform_ip4(locate, source):

    # Si no hay fuente, devolvemos cadena vacía
    # If no source is provided, return empty string
    if not source:
        return ""

    # Si es una cadena con varias IPs separadas por coma, la convertimos en lista
    # If it's a comma-separated string of IPs, convert it to a list
    if isinstance(source, str) and "," in source:
        source = [ip.strip() for ip in source.split(",")]

    # Si es una lista de IPs, procesamos cada una
    # If it's a list of IPs, process each one
    if isinstance(source, list):
        valid_ips = []
        for ip in source:
            # Si viene con /32, lo tratamos como IP individual
            # If it comes with /32, treat it as an individual IP
            if isinstance(ip, str) and "/32" in ip:
                ip = ip.split("/")[0]
            # Validamos que cada IP sea IPv4 válida
            # Validate that each IP is a valid IPv4 address
            try:
                ip_obj = ipaddress.IPv4Address(ip)
                valid_ips.append(str(ip_obj))
            except ValueError:
                continue

        # Si hay IPs válidas, devolvemos formato bpfilter con 'in'
        # If valid IPs exist, return bpfilter format with 'in'
        if valid_ips:
            return f"({locate}) in {{ {'; '.join(valid_ips)} }}"
        else:
            return ""

    # Si es una sola IP, validamos y devolvemos formato 'eq'
    # If it's a single IP, validate and return 'eq' format
    try:
        if isinstance(source, str) and "/32" in source:
            source = source.split("/")[0]
        ip_obj = ipaddress.IPv4Address(source)
        return f"{locate} eq {ip_obj}"
    except ValueError:
        return ""

# Procesa una entrada de redes IPv4 con máscara.
# Ignora redes /32 (equivalentes a direcciones individuales).
# Devuelve 'eq' para una sola red válida o 'in {…}' para múltiples.
# Si no hay redes válidas, devuelve cadena vacía.
#
# Processes input containing IPv4 networks with masks.
# Skips /32 networks (treated as individual addresses).
# Returns 'eq' for a single valid network or 'in {…}' for multiple.
# Returns an empty string if no valid networks are found.

def transform_ip4_net(locate, source):
    # Si no hay fuente, devolvemos cadena vacía
    # If no source is provided, return empty string
    if not source:
        return ""

    # Si es una cadena con varias redes separadas por coma, la convertimos en lista
    # If it's a comma-separated string of networks, convert it to a list
    if isinstance(source, str) and "," in source:
        source = [ip.strip() for ip in source.split(",")]

    # Si es una lista de redes, procesamos cada una
    # If it's a list of networks, process each one
    if isinstance(source, list):
        valid_nets = []
        for net in source:
            # Validamos que cada red sea IPv4 válida con máscara
            # Validate that each network is a valid IPv4 with mask
            try:
                net_obj = ipaddress.IPv4Network(net, strict=False)
                # Ignoramos redes con máscara /32 (son direcciones individuales)
                # Skip networks with /32 mask (they're individual addresses)
                if net_obj.prefixlen != 32:
                    valid_nets.append(str(net_obj))
            except ValueError:
                continue

        # Si hay redes válidas, devolvemos formato bpfilter con 'in'
        # If valid networks exist, return bpfilter format with 'in'
        if valid_nets:
            return f"({locate}) in {{ {'; '.join(valid_nets)} }}"
            #return f"{locate} in {{{','.join(valid_nets)}}}" old
        else:
            return ""

    # Si es una sola red, validamos y devolvemos formato 'eq'
    # If it's a single network, validate and return 'eq' format
    try:
        net_obj = ipaddress.IPv4Network(source, strict=False)
        # Ignoramos si es /32
        # Skip if it's /32
        if net_obj.prefixlen != 32:
            return f"{locate} eq {net_obj}"
    except ValueError:
        pass

    return ""

# Procesa direcciones IPv6 individuales (sin máscara).
# Ignora entradas que contengan máscara (/).
# Devuelve 'eq' si hay una sola IP válida o 'in {…}' si hay varias.
# Si no hay direcciones válidas, devuelve cadena vacía.
#
# Processes individual IPv6 addresses (without masks).
# Skips entries containing a mask (/).
# Returns 'eq' for a single valid IP or 'in {…}' for multiple.
# Returns an empty string if no valid addresses are found.
"""
def transform_ip6(locate, source):
    # Si no hay fuente, devolvemos cadena vacía
    # If no source is provided, return empty string
    if not source:
        return ""

    # Convertimos en lista si es una cadena separada por comas
    # Convert to list if it's a comma-separated string
    if isinstance(source, str):
        source = [ip.strip() for ip in source.split(",")]

    # Procesamos cada entrada y filtramos solo direcciones IPv6 válidas (sin máscara)
    # Process each entry and keep only valid IPv6 addresses (no mask)
    valid_ips = []
    for ip in source:
        # Ignoramos si contiene máscara -> es una red
        # Skip if it contains a mask -> it's a network
        if "/" in ip:
            continue
        try:
            ip_obj = ipaddress.IPv6Address(ip)
            valid_ips.append(str(ip_obj))
        except ValueError:
            continue

    # Devolvemos el formato adecuado según cantidad
    # Return appropriate format based on count
    if not valid_ips:
        return ""
    elif len(valid_ips) == 1:
        return f"{locate} eq {valid_ips[0]}"
    else:
        return f"({locate}) in {{ {'; '.join(valid_ips)} }}"
        #return f"{locate} in {{{','.join(valid_ips)}}}" old
"""

def transform_ip6(locate, source):
    # Si no hay fuente, devolvemos cadena vacía
    # If no source is provided, return empty string
    if not source:
        return ""

    # Convertimos en lista si es una cadena separada por comas
    # Convert to list if it's a comma-separated string
    if isinstance(source, str):
        source = [ip.strip() for ip in source.split(",")]

    # Procesamos cada entrada y filtramos solo direcciones IPv6 válidas (sin máscara o /128)
    # Process each entry and keep only valid IPv6 addresses (no mask or /128)
    valid_ips = []
    for ip in source:
        # Si contiene máscara, solo aceptamos /128 -> es una IP individual
        # If it contains a mask, only accept /128 -> it's an individual IP
        if "/" in ip:
            try:
                net_obj = ipaddress.IPv6Network(ip, strict=False)
                if net_obj.prefixlen == 128:
                    valid_ips.append(str(net_obj.network_address))
            except ValueError:
                continue
        else:
            try:
                ip_obj = ipaddress.IPv6Address(ip)
                valid_ips.append(str(ip_obj))
            except ValueError:
                continue

    # Devolvemos el formato adecuado según cantidad
    # Return appropriate format based on count
    if not valid_ips:
        return ""
    elif len(valid_ips) == 1:
        return f"{locate} eq {valid_ips[0]}"
    else:
        return f"({locate}) in {{ {'; '.join(valid_ips)} }}"
        #return f"{locate} in {{{','.join(valid_ips)}}}" old

# Procesa redes IPv6 con máscara.
# Ignora redes /128 (equivalentes a direcciones individuales).
# Devuelve 'eq' para una sola red válida o 'in {…}' para múltiples.
# Si no hay redes válidas, devuelve cadena vacía.
#
# Processes IPv6 networks with masks.
# Skips /128 networks (treated as individual addresses).
# Returns 'eq' for a single valid network or 'in {…}' for multiple.
# Returns an empty string if no valid networks are found.

def transform_ip6_net(locate, source):
    # Si no hay fuente, devolvemos cadena vacía
    # If no source is provided, return empty string
    if not source:
        return ""

    # Si es una cadena con varias redes separadas por coma, la convertimos en lista
    # If it's a comma-separated string of networks, convert it to a list
    if isinstance(source, str) and "," in source:
        source = [ip.strip() for ip in source.split(",")]

    # Si es una lista de redes, procesamos cada una
    # If it's a list of networks, process each one
    if isinstance(source, list):
        valid_nets = []
        for net in source:
            # Validamos que cada red sea IPv6 válida con máscara
            # Validate that each network is a valid IPv6 with mask
            try:
                net_obj = ipaddress.IPv6Network(net, strict=False)
                # Ignoramos redes con máscara /128 (son direcciones individuales)
                # Skip networks with /128 mask (they're individual addresses)
                if net_obj.prefixlen != 128:
                    valid_nets.append(str(net_obj))
            except ValueError:
                continue

        # Si hay redes válidas, devolvemos formato bpfilter con 'in'
        # If valid networks exist, return bpfilter format with 'in'
        if valid_nets:
            return f"({locate}) in {{ {'; '.join(valid_nets)} }}"
            #return f"{locate} in {{{','.join(valid_nets)}}}" old
        else:
            return ""

    # Si es una sola red, validamos y devolvemos formato 'eq'
    # If it's a single network, validate and return 'eq' format
    try:
        net_obj = ipaddress.IPv6Network(source, strict=False)
        if net_obj.prefixlen != 128:
            return f"{locate} eq {net_obj}"
    except ValueError:
        pass

    return ""


######################################################################################################
################################## PORT SECTION #####################################################
######################################################################################################

# Procesa los campos de puertos TCP si el protocolo de capa 4 es TCP.
# Acepta puertos individuales o rangos (ej. "80", "1000-2000"), en cadena o lista.
# Devuelve el formato bpfilter correspondiente: 'eq', 'in {…}' o 'range …'.
# Si el protocolo no es TCP o los valores son inválidos, devuelve cadena vacía.
#
# Processes TCP port fields if the layer 4 protocol is TCP.
# Accepts individual ports or ranges (e.g., "80", "1000-2000"), as string or list.
# Returns the appropriate bpfilter format: 'eq', 'in {…}', or 'range …'.
# If the protocol is not TCP or values are invalid, returns an empty string.

def transform_tcp_port(l4_proto, locate, source):
    # Si no hay fuente o el protocolo no es TCP, devolvemos cadena vacía
    if not source or l4_proto.lower() != "tcp":
        return ""

    # Si es una cadena con varias entradas separadas por coma, la convertimos en lista
    if isinstance(source, str) and "," in source:
        source = [p.strip() for p in source.split(",")]

    # Si es una lista, procesamos cada entrada
    if isinstance(source, list):
        valid_ports = []
        valid_ranges = []
        for item in source:
            # Si es un rango tipo "1000-2000"
            if "-" in item:
                try:
                    start, end = map(int, item.split("-"))
                    if 0 <= start <= 65535 and 0 <= end <= 65535 and start < end:
                        valid_ranges.append(f"{start}-{end}")
                except ValueError:
                    continue
            else:
                # Si es un puerto individual
                try:
                    port = int(item)
                    if 0 <= port <= 65535:
                        valid_ports.append(str(port))
                except ValueError:
                    continue

        # Construimos la salida según lo que haya
        output = []
        if valid_ports:
            output.append(f"{locate} in {{{','.join(valid_ports)}}}")
        if valid_ranges:
            for r in valid_ranges:
                output.append(f"{locate} range {r}")

        return " and ".join(output) if output else ""

    # Si es una sola entrada
    if isinstance(source, str):
        # Rango tipo "1000-2000"
        if "-" in source:
            try:
                start, end = map(int, source.split("-"))
                if 0 <= start <= 65535 and 0 <= end <= 65535 and start < end:
                    return f"{locate} range {start}-{end}"
            except ValueError:
                return ""
        else:
            # Puerto individual
            try:
                port = int(source)
                if 0 <= port <= 65535:
                    return f"{locate} eq {port}"
            except ValueError:
                return ""

    return ""


# Procesa los flags TCP si el protocolo de capa 4 es TCP.
# Acepta una cadena separada por comas con flags válidos (ej. "syn,ack").
# Devuelve el formato bpfilter con 'eq' o 'eq {…}' según la cantidad de flags válidos.
# Si el protocolo no es TCP o no hay flags válidos, devuelve cadena vacía.
#
# Processes TCP flags if the layer 4 protocol is TCP.
# Accepts a comma-separated string of valid flags (e.g., "syn,ack").
# Returns bpfilter format using 'eq' or 'eq {…}' depending on the number of valid flags.
# If the protocol is not TCP or no valid flags are found, returns an empty string.

def transform_tcp_port_flags(l4_proto, locate, source):
    # Si no hay fuente o el protocolo no es TCP, devolvemos cadena vacía
    if not source or l4_proto.lower() != "tcp":
        return ""

    # Lista de flags válidos (case-insensitive)
    valid_flags_set = {"fin", "syn", "rst", "psh", "ack", "urg", "ece", "cwr"}

    # Convertimos la fuente en lista si es cadena
    if isinstance(source, str):
        source = [flag.strip().lower() for flag in source.split(",")]

    # Filtramos solo los flags válidos
    valid_flags = [flag for flag in source if flag in valid_flags_set]

    # Devolvemos el formato adecuado
    if not valid_flags:
        return ""
    elif len(valid_flags) == 1:
        return f"{locate} eq {valid_flags[0]}"
    else:
        return f"{locate} eq {{{','.join(valid_flags)}}}"


# Procesa los campos de puertos UDP si el protocolo de capa 4 es UDP.
# Acepta puertos individuales o rangos (ej. "53", "1000-2000"), en cadena o lista.
# Devuelve el formato bpfilter correspondiente: 'eq', 'in {…}' o 'range …'.
# Si el protocolo no es UDP o los valores son inválidos, devuelve cadena vacía.
#
# Processes UDP port fields if the layer 4 protocol is UDP.
# Accepts individual ports or ranges (e.g., "53", "1000-2000"), as string or list.
# Returns the appropriate bpfilter format: 'eq', 'in {…}', or 'range …'.
# If the protocol is not UDP or values are invalid, returns an empty string.

def transform_udp_port(l4_proto, locate, source):
    # Validamos que el protocolo sea UDP y que haya fuente
    if not source or l4_proto.lower() != "udp":
        return ""

    # Si es una cadena con varias entradas separadas por coma, la convertimos en lista
    if isinstance(source, str) and "," in source:
        source = [p.strip() for p in source.split(",")]

    # Si es una lista, procesamos cada entrada
    if isinstance(source, list):
        valid_ports = []
        valid_ranges = []
        for item in source:
            # Si es un rango tipo "1000-2000"
            if "-" in item:
                try:
                    start, end = map(int, item.split("-"))
                    if 0 <= start <= 65535 and 0 <= end <= 65535 and start < end:
                        valid_ranges.append(f"{start}-{end}")
                except ValueError:
                    continue
            else:
                # Si es un puerto individual
                try:
                    port = int(item)
                    if 0 <= port <= 65535:
                        valid_ports.append(str(port))
                except ValueError:
                    continue

        # Construimos la salida según lo que haya
        output = []
        if valid_ports:
            output.append(f"{locate} in {{{','.join(valid_ports)}}}")
        if valid_ranges:
            for r in valid_ranges:
                output.append(f"{locate} range {r}")

        return " and ".join(output) if output else ""

    # Si es una sola entrada
    if isinstance(source, str):
        # Rango tipo "1000-2000"
        if "-" in source:
            try:
                start, end = map(int, source.split("-"))
                if 0 <= start <= 65535 and 0 <= end <= 65535 and start < end:
                    return f"{locate} range {start}-{end}"
            except ValueError:
                return ""
        else:
            # Puerto individual
            try:
                port = int(source)
                if 0 <= port <= 65535:
                    return f"{locate} eq {port}"
            except ValueError:
                return ""

    return ""



######################################################################################################
################################## ICMP SECTION #####################################################
######################################################################################################

# Procesa el campo ICMP type si el protocolo de capa 4 es ICMP.
# Acepta valores decimales, hexadecimales (ej. "0x08") o nombres simbólicos (ej. "echo-reply").
# Devuelve el formato bpfilter 'eq' con el valor correspondiente.
# Si el protocolo no es ICMP o el valor es inválido, devuelve cadena vacía.
#
# Processes the ICMP type field if the layer 4 protocol is ICMP.
# Accepts decimal values, hexadecimal (e.g., "0x08"), or symbolic names (e.g., "echo-reply").
# Returns bpfilter 'eq' format with the corresponding value.
# If the protocol is not ICMP or the value is invalid, returns an empty string.

def transform_icmp_type(l4_proto, locate, source):
    # Solo procesamos si el protocolo es ICMP
    if not source or l4_proto.lower() != "icmp":
        return ""

    # Normalizamos y limpiamos
    if isinstance(source, str):
        source = source.strip().lower()

    # Si es hexadecimal (ej. "0x08")
    try:
        if source.startswith("0x"):
            value = int(source, 16)
            return f"{locate} eq {value}"
        else:
            # Si es decimal
            value = int(source)
            return f"{locate} eq {value}"
    except ValueError:
        # Si no es número, asumimos que es nombre (ej. "echo-reply")
        return f"{locate} eq {source}"

    return ""


# Procesa el campo ICMP code si el protocolo de capa 4 es ICMP.
# Acepta valores decimales o hexadecimales (ej. "0x05").
# Devuelve el formato bpfilter 'eq' con el valor correspondiente.
# Si el protocolo no es ICMP o el valor es inválido, devuelve cadena vacía.
#
# Processes the ICMP code field if the layer 4 protocol is ICMP.
# Accepts decimal or hexadecimal values (e.g., "0x05").
# Returns bpfilter 'eq' format with the corresponding value.
# If the protocol is not ICMP or the value is invalid, returns an empty string.

def transform_icmp_code(l4_proto, locate, source):
    # Solo procesamos si el protocolo es ICMP
    if not source or l4_proto.lower() != "icmp":
        return ""

    # Normalizamos y limpiamos
    if isinstance(source, str):
        source = source.strip().lower()

    try:
        if source.startswith("0x"):
            value = int(source, 16)
        else:
            value = int(source)
        return f"{locate} eq {value}"
    except ValueError:
        return ""


# Procesa el campo ICMPv6 type si el protocolo de capa 4 es ICMPv6.
# Acepta valores decimales, hexadecimales o nombres simbólicos.
# Devuelve el formato bpfilter 'eq' con el valor correspondiente.
# Si el protocolo no es ICMPv6 o el valor es inválido, devuelve cadena vacía.
#
# Processes the ICMPv6 type field if the layer 4 protocol is ICMPv6.
# Accepts decimal values, hexadecimal, or symbolic names.
# Returns bpfilter 'eq' format with the corresponding value.
# If the protocol is not ICMPv6 or the value is invalid, returns an empty string.

def transform_icmpv6_type(l4_proto, locate, source):
    # Solo procesamos si el protocolo es ICMPv6
    if not source or l4_proto.lower() != "icmpv6":
        return ""

    if isinstance(source, str):
        source = source.strip().lower()

    try:
        if source.startswith("0x"):
            value = int(source, 16)
            return f"{locate} eq {value}"
        else:
            value = int(source)
            return f"{locate} eq {value}"
    except ValueError:
        return f"{locate} eq {source}"


# Procesa el campo ICMPv6 code si el protocolo de capa 4 es ICMPv6.
# Acepta valores decimales o hexadecimales.
# Devuelve el formato bpfilter 'eq' con el valor correspondiente.
# Si el protocolo no es ICMPv6 o el valor es inválido, devuelve cadena vacía.
#
# Processes the ICMPv6 code field if the layer 4 protocol is ICMPv6.
# Accepts decimal or hexadecimal values.
# Returns bpfilter 'eq' format with the corresponding value.
# If the protocol is not ICMPv6 or the value is invalid, returns an empty string.

def transform_icmpv6_code(l4_proto, locate, source):
    # Solo procesamos si el protocolo es ICMPv6
    if not source or l4_proto.lower() != "icmpv6":
        return ""

    if isinstance(source, str):
        source = source.strip().lower()

    try:
        if source.startswith("0x"):
            value = int(source, 16)
        else:
            value = int(source)
        return f"{locate} eq {value}"
    except ValueError:
        return ""


######################################################################################################
################################## Probability SECTION ###############################################
######################################################################################################
# Convierte el valor de probabilidad recibido en formato bpfilter.
# Acepta valores enteros entre 0 y 100, con o sin símbolo de porcentaje.
# Si el valor es inválido o está vacío, se asigna automáticamente "100%".
#
# Converts the received probability value into bpfilter format.
# Accepts integer values between 0 and 100, with or without a percent symbol.
# If the value is invalid or missing, it automatically assigns "100%".

def transform_probability(locate, source):
    # Si no hay fuente, devolvemos 100%
    if not source:
        return f"{locate} eq 100%"

    # Normalizamos y limpiamos
    if isinstance(source, str):
        source = source.strip().replace("%", "")

    try:
        value = int(source)
        if 0 <= value <= 100:
            return f"{locate} eq {value}%"
    except ValueError:
        pass

    # Si no es válido, devolvemos 100%
    return f"{locate} eq 100%"


######################################################################################################
################################## Action SECTION ###############################################
######################################################################################################
# Verifica y transforma el valor de acción recibido.
# Acepta "accept" o "drop" sin distinguir mayúsculas/minúsculas.
# Devuelve "ACCEPT" o "DROP" en mayúsculas según corresponda.
# Si el valor está vacío o no es válido, devuelve "ACCEPT" por defecto.
#
# Validates and transforms the received action value.
# Accepts "accept" or "drop" regardless of case sensitivity.
# Returns "ACCEPT" or "DROP" in uppercase accordingly.
# If the value is empty or invalid, defaults to returning "ACCEPT".

def transform_action(action):
    if not action:
        return "ACCEPT"

    action = action.strip().lower()

    if action == "accept":
        return "ACCEPT"
    elif action == "drop":
        return "DROP"

    return "ACCEPT"







##########################################################################################################
############################################## Alias Translate ###########################################
##########################################################################################################
# //////////////////////////////////////////////////////////////////////////////////////////////////////
# //////////////////////////////// PORTS VALIDATION SECTION ///////////////////////////////////////////
# //////////////////////////////////////////////////////////////////////////////////////////////////////

# elimina puertos de los campos puerto si el protocolo de la regla es icmp
# Remove ports from the port fields if the rule protocol is icmp
def validation_icmp_no_ports(rule: dict) -> dict:
    protocol = rule.get('l4_protocol', '').lower()

    if protocol in ['icmp', 'icmpv6']:
        fields_to_clear = [
            'sport',
            'dport',
        ]

        for field in fields_to_clear:
            if field in rule:
                rule[field] = ''

    return rule

# Elimina duplicados y solapamientos en una lista de puertos y rangos
# Removes duplicates and overlaps in a list of ports and ranges
def validation_not_duplicate_ports(value: str) -> str:
    items = [item.strip() for item in value.split(',')]
    all_ports = {}

    for item in items:
        # Si es un rango (ej. 22-50)
        # If it's a range (e.g. 22-50)
        match = re.match(r'^(\d+)-(\d+)$', item)
        if match:
            start = int(match.group(1))
            end = int(match.group(2))
            if start > end:
                start, end = end, start  # Corrige si el rango está invertido
            for i in range(start, end + 1):
                all_ports[i] = True
        # Si es un puerto individual
        # If it's a single port
        elif item.isdigit():
            all_ports[int(item)] = True

    # Ordena los puertos únicos
    # Sort unique ports
    sorted_ports = sorted(all_ports.keys())

    # Agrupa puertos contiguos en rangos
    # Group contiguous ports into ranges
    result = []
    start = end = None

    for port in sorted_ports:
        if start is None:
            start = end = port
        elif port == end + 1:
            end = port
        else:
            result.append(str(start) if start == end else f"{start}-{end}")
            start = end = port

    # Añade el último grupo
    if start is not None:
        result.append(str(start) if start == end else f"{start}-{end}")

    # Devuelve la lista final como cadena separada por comas
    # Return the final list as a comma-separated string
    return ','.join(result)

# Valida que los puertos o rangos estén dentro del rango permitido
# Validates that ports or ranges are within the allowed range
def validation_ports_range(value: str, date):
    items = [item.strip() for item in value.split(',')]
    min_port = 0
    max_port = 65535

    for item in items:
        # Si es un puerto individual
        # If it's a single port
        if item.isdigit():
            port = int(item)
            if port < min_port or port > max_port:
                #print(json.dumps({"error": f"port '{port}' out of range"}))
                task_update_json(date, "bpfilter_convert_port_out_of_range", "fail")
                exit()
            continue

        # Si es un rango de puertos (ej. 1000-2000)
        # If it's a port range (e.g. 1000-2000)
        match = re.match(r'^(\d+)-(\d+)$', item)
        if match:
            start = int(match.group(1))
            end = int(match.group(2))
            if start < min_port or start > max_port or end < min_port or end > max_port:
                #print(json.dumps({"error": f"port range '{item}' out of range"}))
                task_update_json(date, "bpfilter_convert_port_range_out", "fail")

                exit()
            continue

# Convierte un alias de puerto en su valor numérico real
# Converts a port alias into its actual numeric value
def convert_alias_port_to_network_port(value: str, date):
    alias_json_data = import_alias_json()

    # Verifica que se haya cargado correctamente el JSON
    # Check that the JSON was loaded successfully
    if not alias_json_data:
        #print(json.dumps({"error": "alias file not found or invalid"}))
        task_update_json(date, "bpfilter_convert_alias_file_invalid", "fail")
        exit()

    # Busca el alias en alias_service
    # Search for the alias in alias_service
    for entry in alias_json_data.get('alias_service', []):
        if entry.get('name') == value:
            return entry.get('content', [''])[0]

    # Si no se encuentra, se detiene el script y se devuelve error
    # If not found, stop the script and return error
    #print(json.dumps({"error": f"alias port no encontrado en ningun sitio '{value}' not found"}))
    task_update_json(date, "bpfilter_convert_alias_port_not_exist", "fail")
    exit()

# Convierte una lista de puertos, alias y grupos en puertos reales
# Converts a list of ports, aliases, and groups into real port numbers
def convert_alias_port_group_to_network_port(value: str, date):
    alias_json_data = import_alias_json()

    # Si el valor está vacío, no se procesa
    # If the value is empty, skip processing
    if value.strip() == '':
        return ''

    # Si no se pudo cargar el archivo, se detiene el script
    # If the file couldn't be loaded, stop the script
    if not alias_json_data:
        #print(json.dumps({"error": "alias file not found or invalid"}))
        task_update_json(date, "bpfilter_convert_alias_file_invalid", "fail")
        exit()

    final_ports = []
    items = [item.strip() for item in value.split(',')]

    for item in items:
        if item == '':
            continue  # Ignora elementos vacíos individuales
                     # Ignore individual empty elements

        if item.isdigit() or re.match(r'^\d+-\d+$', item):
            validation_ports_range(item, date)
            final_ports.append(item)
            continue

        found_group = False

        for group in alias_json_data.get('alias_service_group', []):
            if group.get('name') == item:
                for entry in group.get('content', []):
                    if entry.isdigit() or re.match(r'^\d+-\d+$', entry):
                        validation_ports_range(entry, date)
                        final_ports.append(entry)
                    else:
                        resolved = convert_alias_port_to_network_port(entry, date)
                        validation_ports_range(resolved, date)
                        final_ports.append(resolved)
                found_group = True
                break

        if not found_group:
            resolved = convert_alias_port_to_network_port(item, date)
            validation_ports_range(resolved, date)
            final_ports.append(resolved)

    cleaned = validation_not_duplicate_ports(','.join(final_ports))
    return cleaned


# ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////// IPV4 & IPV6 VALIDATION SECTION ///////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

# Verifica si una IP objetivo está contenida dentro de una red CIDR, compatible con IPv4 e IPv6.
# Checks whether a target IP is contained within a CIDR network, supporting both IPv4 and IPv6.
def cidr_contains(cidr: str, target: str) -> bool:
    # Extrae la IP base y la máscara del CIDR
    # Extracts the base IP and mask from the CIDR
    try:
        base_net = ipaddress.ip_network(cidr, strict=False)
        target_ip = ipaddress.ip_interface(target).ip
        return target_ip in base_net
    except ValueError:
        # Si la IP no es válida, retorna falso
        # If the IP is not valid, returns false
        return False

# Normaliza una lista de IPs y redes CIDR, valida su formato, elimina duplicados,
# ordena por máscara ascendente y filtra redes contenidas para retornar solo las más específicas.
# Normalizes a list of IPs and CIDR networks, validates format, removes duplicates,
# sorts by ascending mask, and filters out contained networks to return only the most specific ones.
def validation_ip_networks(value: str) -> str:
    #print(f"DEBUG validation_ip_networks: valor recibido =>{value}<")

    # Divide la cadena por comas, elimina espacios y filtra vacíos
    # Split the string by commas, trim spaces, and filter out empty entries
    items = [v.strip() for v in value.split(',') if v.strip()]
    normalized = []

    for idx, item in enumerate(items):
        #print(f"DEBUG iteración {idx}: item =>{item}<")

        try:
            # IP sin CIDR -> se normaliza como /32 (IPv4) o /128 (IPv6)
            # IP without CIDR -> normalize as /32 (IPv4) or /128 (IPv6)
            ip_obj = ipaddress.ip_address(item)
            suffix = '/32' if ip_obj.version == 4 else '/128'
            normalized.append(f"{item}{suffix}")
        except ValueError:
            # IP con CIDR -> se valida y se agrega si es válida
            # IP with CIDR -> validate and add if valid
            if re.match(r'^(.+)/(\d{1,3})$', item):
                try:
                    ip_net = ipaddress.ip_network(item, strict=False)
                    normalized.append(str(ip_net))
                except ValueError:
                    #print(json.dumps({"error": f"invalid CIDR '{item}'"}))
                    exit()
            else:
                # Formato inválido -> se muestra error y se detiene
                # Invalid format -> show error and stop
                #print(json.dumps({"error": f"invalid IP format '{item}'"}))
                exit()

    # Elimina duplicados exactos
    # Remove exact duplicates
    normalized = list(set(normalized))

    # Ordena por máscara ascendente (más amplias primero)
    # Sort by ascending mask (broader networks first)
    def mask_sort_key(entry):
        ip, mask = entry.split('/')
        return int(mask)

    normalized.sort(key=mask_sort_key)

    final = []

    for candidate in normalized:
        contained = False
        for existing in final:
            if cidr_contains(existing, candidate):
                #print(f"DEBUG {candidate} está contenido en {existing}, se omite")
                contained = True
                break
        if not contained:
            final.append(candidate)

    #print(f"DEBUG resultado final: {json.dumps(final)}")
    return ','.join(final)


# Valida que las IPs o CIDRs tengan formato correcto
# Validates that IPs or CIDRs have correct format
def validate_ip_or_cidr(value: str) -> bool:
    items = [item.strip() for item in value.split(',')]

    for item in items:
        # Si es una IP válida (sin CIDR)
        # If it's a valid IP (without CIDR)
        try:
            ipaddress.ip_address(item)
            continue
        except ValueError:
            pass

        # Si es una IP con CIDR
        # If it's an IP with CIDR
        match = re.match(r'^(.+)/(\d{1,3})$', item)
        if match:
            ip = match.group(1)
            cidr = int(match.group(2))

            try:
                ip_obj = ipaddress.ip_address(ip)
            except ValueError:
                return False

            if (ip_obj.version == 4 and 0 <= cidr <= 32) or (ip_obj.version == 6 and 0 <= cidr <= 128):
                continue

            return False

        # No es IP ni CIDR válido
        # Not a valid IP or CIDR
        return False

    return True

# Devuelve la primera IP o CIDR asociada a un alias definido en alias_address.
# Returns the first IP or CIDR linked to a named alias in alias_address.
def convert_alias_ip_to_ip(value: str, date):
    alias_json_data = import_alias_json()

    # Si el valor está vacío o solo contiene espacios, lo ignoramos
    # If the value is empty or just spaces, ignore it
    if value.strip() == '':
        #print(f"DEBUG convert_alias_ip_to_ip: valor vacío, se ignora")
        return ''

    # Verifica que se haya cargado correctamente el JSON
    # Check that the JSON was loaded successfully
    if not alias_json_data:
        #print(json.dumps({"error": "alias file not found or invalid"}))
        exit()

    # DEBUG: mostrar el valor recibido
    #print(f"DEBUG convert_alias_ip_to_ip: valor recibido = >{value}<")

    # DEBUG: listar todos los alias disponibles en alias_address
    #if 'alias_address' in alias_json_data:
    #    for entry in alias_json_data['alias_address']:
    #        print(f"DEBUG alias en JSON: >{entry.get('name', '')}<")

    # Busca el alias en alias_address
    # Search for the alias in alias_address
    for entry in alias_json_data.get('alias_address', []):
        if entry.get('name') == value:
            return entry.get('content', [''])[0]

    # Si no se encuentra, se detiene el script y se devuelve error
    # If not found, stop the script and return error
    #print(json.dumps({"error": f"alias IP '{value}' not found"}))
    task_update_json(date, f"bpfilter_convert_alias_IP_'{value}'_not_found", "fail")
    exit()

# Convierte IPs, alias y grupos de alias en una lista normalizada de redes IP únicas.
# Converts IPs, aliases, and alias groups into a normalized list of unique network addresses.
def convert_alias_group_to_Network_ips(value: str, date):
    alias_json_data = import_alias_json()

    # Verifica que se haya cargado correctamente el JSON
    # Check that the JSON was loaded successfully
    if not alias_json_data:
        #print(json.dumps({"error": "alias file not found or invalid"}))
        task_update_json(date, "bpfilter_convert_alias_file_invalid", "fail")
        exit()

    # Divide la cadena por comas y elimina espacios
    # Split the input string by commas and trim whitespace
    items = [item.strip() for item in value.split(',')]
    resolved_ips = []

    for item in items:
        # Ignorar valores vacíos o solo espacios
        # Ignore empty or whitespace-only values
        if item == '':
            continue

        # Si es IP o CIDR válida, se conserva
        # If it's a valid IP or CIDR, keep it as-is
        if validate_ip_or_cidr(item):
            resolved_ips.append(item)
            continue

        found_group = False

        # Verifica si el elemento es un grupo de alias
        # Check if the item is an alias group
        for group in alias_json_data.get('alias_addr_group', []):
            if group.get('name') == item:
                # Recorre cada alias dentro del grupo
                # Iterate over each alias inside the group
                for alias_name in group.get('content', []):
                    ip = convert_alias_ip_to_ip(alias_name, date)
                    if ip != '':
                        resolved_ips.append(ip)
                found_group = True
                break

        # Si no es grupo, lo tratamos como alias individual
        # If it's not a group, treat it as an individual alias
        if not found_group:
            ip = convert_alias_ip_to_ip(item, date)
            if ip != '':
                resolved_ips.append(ip)
                continue

            # Si no se pudo resolver, se lanza error
            # If resolution fails, throw an error
            #print(json.dumps({"error": f"alias or group '{item}' not found or invalid"}))
            task_update_json(date, "bpfilter_convert_alias_or_group_invalid", "fail")
            exit()

    # Normaliza y elimina duplicados antes de devolver
    # Normalize and remove duplicates before returning
    return validation_ip_networks(','.join(resolved_ips))

# Convierte alias en objetos de red reales usando funciones auxiliares
# Converts aliases into real network objects using helper functions
def Main_convert_alias_object_to_network_object(rule: dict, date):
    # Campos relacionados con puertos
    # Port-related fields
    port_fields = ['sport', 'dport']

    for field in port_fields:
        if field in rule:
            # Llama a la función de conversión de puertos
            # Call the port conversion function
            rule[field] = convert_alias_port_group_to_network_port(rule[field], date)

    # Campos relacionados con direcciones IP
    # IP-related fields
    ip_fields = ['source', 'destination']

    for field in ip_fields:
        if field in rule:
            # Llama a la función de conversión de grupos IP
            # Call the IP group conversion function
            rule[field] = convert_alias_group_to_Network_ips(rule[field], date)

    return rule



def separate_rules(rule):
    # Extraemos los campos relevantes de la regla original
    # Extract relevant fields from the original rule
    ip4_saddr = rule.get("ip4_saddr")  # IP origen individual
    ip4_snet = rule.get("ip4_snet")    # Red origen
    ip4_daddr = rule.get("ip4_daddr")  # IP destino individual
    ip4_dnet = rule.get("ip4_dnet")    # Red destino

    # Si no hay mezcla de IPs y redes en origen o destino, no hay conflicto
    # If there's no mix of IPs and networks in source or destination, no conflict
    if not (ip4_saddr and ip4_snet) and not (ip4_daddr and ip4_dnet):
        return [rule]  # Se devuelve la regla tal cual
                      # Return the rule as-is

    subrules = []  # Lista para almacenar las subreglas generadas
                   # List to store generated subrules

    # Combinación 1: IP origen + IP destino
    # Combination 1: Source IP + Destination IP
    if ip4_saddr and ip4_daddr:
        r1 = rule.copy()
        r1["ip4_snet"] = ""  # Se elimina la red origen
        r1["ip4_dnet"] = ""  # Se elimina la red destino
        subrules.append(r1)

    # Combinación 2: IP origen + Red destino
    # Combination 2: Source IP + Destination Network
    if ip4_saddr and ip4_dnet:
        r2 = rule.copy()
        r2["ip4_snet"] = ""  # Se elimina la red origen
        r2["ip4_daddr"] = ""  # Se elimina la IP destino
        subrules.append(r2)

    # Combinación 3: Red origen + IP destino
    # Combination 3: Source Network + Destination IP
    if ip4_snet and ip4_daddr:
        r3 = rule.copy()
        r3["ip4_saddr"] = ""  # Se elimina la IP origen
        r3["ip4_dnet"] = ""   # Se elimina la red destino
        subrules.append(r3)

    # Combinación 4: Red origen + Red destino
    # Combination 4: Source Network + Destination Network
    if ip4_snet and ip4_dnet:
        r4 = rule.copy()
        r4["ip4_saddr"] = ""  # Se elimina la IP origen
        r4["ip4_daddr"] = ""  # Se elimina la IP destino
        subrules.append(r4)

    # Se devuelve la lista de subreglas compatibles con bpfilter
    # Return the list of bpfilter-compatible subrules
    return subrules
