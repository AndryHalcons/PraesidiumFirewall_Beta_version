import ipaddress
import json

# // Recibe el nombre de una interfaz y devuelve su ifindex desde el archivo JSON del sistema
# // Receives an interface name and returns its ifindex from the system JSON file
def transform_iface(iface_name):
    path = "/var/www/backend/checks/system_data/data_interfaces/physical_interfaces_list.json"
    try:
        with open(path, "r") as f:
            data = json.load(f)
        interfaces = data.get("physical_interfaces", [])
        for iface in interfaces:
            if iface.get("name") == iface_name:
                return iface.get("ifindex")
    except Exception:
        pass  # // Silencia errores de lectura // Suppress read errors

    return None  # // Devuelve null si no se encuentra // Returns null if not found


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
            return f"{locate} in {{{','.join(valid_ips)}}}"
        else:
            return ""

    # Si es una sola IP, validamos y devolvemos formato 'eq'
    # If it's a single IP, validate and return 'eq' format
    try:
        ip_obj = ipaddress.IPv4Address(source)
        return f"{locate} eq {ip_obj}"
    except ValueError:
        return ""


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
            return f"{locate} in {{{','.join(valid_nets)}}}"
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
        # Ignoramos si contiene máscara → es una red
        # Skip if it contains a mask → it's a network
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
        return f"{locate} in {{{','.join(valid_ips)}}}"


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
            return f"{locate} in {{{','.join(valid_nets)}}}"
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
