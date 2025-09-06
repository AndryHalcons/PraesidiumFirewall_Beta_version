import json
from collections import defaultdict

# // Carga el JSON desde disco y lo devuelve como diccionario (array asociativo)
# // Loads JSON from disk and returns it as a dictionary (associative array)
def load_json_as_array():
    path = "/home/praesidium/PraesidiumFirewall/data/rules_bpfilter_human_viewer.json"
    try:
        with open(path, "r") as f:
            data = json.load(f)
        return data
    except Exception:
        return {}


# // Recibe el nombre de una interfaz y devuelve su ifindex desde el archivo JSON del sistema
# // Receives an interface name and returns its ifindex from the system JSON file
def load_ifindex_as_iface(iface_name):
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

##########################################################################################################
############################################## constructor ###############################################
##########################################################################################################

# // Extrae los campos de match desde una regla plana
# // Extracts match fields from a flat rule
def extract_match_from_rule(rule):
    return {
        "iface": rule.get("interface"),
        "l3_proto": rule.get("l3_protocol"),
        "l4_proto": rule.get("l4_protocol"),
        # IPv4
        "ip4_saddr": rule.get("source"),           # ← origen IPv4 individual
        "ip4_daddr": rule.get("destination"),      # ← destino IPv4 individual
        "ip4_snet": "",                            # ← origen red IPv4 (no presente en JSON)
        "ip4_dnet": "",                            # ← destino red IPv4 (no presente en JSON)
        "ip4_proto": "",                           # ← protocolo IPv4 (no presente en JSON)
        # IPv6
        "ip6_saddr": "",                           # ← origen IPv6 (no presente en JSON)
        "ip6_daddr": "",                           # ← destino IPv6 (no presente en JSON)
        "ip6_snet": "",                            # ← origen red IPv6 (no presente en JSON)
        "ip6_dnet": "",                            # ← destino red IPv6 (no presente en JSON)
        "ip6_nexthdr": rule.get("ipv6_next_header"),  # ← next header IPv6
        # TCP
        "tcp_sport": rule.get("sport"),
        "tcp_dport": rule.get("dport"),
        "tcp_flags": rule.get("tcp_flags"),
        # UDP (no presentes en JSON, pero definidos en match)
        "udp_sport": "",
        "udp_dport": "",
        # ICMP
        "icmp_type": rule.get("icmp_type"),
        "icmp_code": rule.get("icmp_code"),
        # ICMPv6
        "icmpv6_type": rule.get("icmpv6_type"),
        "icmpv6_code": rule.get("icmpv6_code"),
        # Meta
        "probability": rule.get("probability") or "100%"
    }


# // Convierte los campos de match en condiciones de texto para bfcli
# // Converts match fields into bfcli-compatible condition strings
def format_match_fields(match):
    meta_fields = {"iface", "l3_proto", "l4_proto", "probability"}
    parts = []

    for key, value in match.items():
        if not value or value == "any" or "example" in value:
            continue

        if key in meta_fields:
            field_name = f"meta.{key}"  # // Prefijo 'meta.' para campos especiales // 'meta.' prefix for special fields
        else:
            field_name = key.replace("_", ".")  # // Convierte snake_case a dot.notation // Converts snake_case to dot.notation

        if key == "probability" and not value.endswith("%"):
            value = "100%"  # // Valor por defecto si no es porcentaje válido // Default value if not a valid percentage

        parts.append(f"{field_name} eq {value}")  # // Condición con operador 'eq' // Condition with 'eq' operator

    return parts










































# // Convierte el JSON plano en texto compatible con bfcli agrupado por hook
# // Converts flat JSON into bfcli-compatible text grouped by hook
def saniticed_bpfilter_format():
    data = load_json_as_array()
    rules_list = data.get("bpfilter", [])
    grouped = defaultdict(list)

    # // Agrupa las reglas por hook
    # // Group rules by hook
    for entry in rules_list:
        rule = entry.get("rule", {})
        hook = rule.get("hook")
        if hook:
            grouped[hook].append(rule)

    lines = []

    # // Procesa cada grupo de reglas por hook
    # // Process each group of rules by hook
    for hook_name, rules in grouped.items():
        policy = "ACCEPT"
        iface = None

        # // Busca la primera interfaz válida
        # // Find the first valid interface
        for rule in rules:
            if rule.get("enable", "").lower() == "true":
                iface_candidate = rule.get("interface", "")
                if iface_candidate and iface_candidate != "any" and "example" not in iface_candidate:
                    iface = iface_candidate
                    break

        # // Construye el nombre de la cadena
        # // Build the chain name
        # // El nombre de la cadena se basa solo en el hook
        # // Chain name is based only on the hook
        chain_name = f"chain_{hook_name.lower()}"


        # obtiene el ifindex de la interfaz para añadirlo a la 
        #Get the ifindex of the interface to add to the chain
        iface_index = load_ifindex_as_iface(rule.get('interface'))
        chain_args = f"{{ifindex={iface_index}}}" if iface_index is not None else "{}"


        chain_header = f"chain {chain_name} {hook_name}{chain_args} {policy}"
        chain_lines = [chain_header]

        # // Construye cada regla habilitada
        # // Build each enabled rule
        for rule in rules:
            if rule.get("enable", "").lower() != "true":
                continue

            match = extract_match_from_rule(rule)
            action = rule.get("action", "DROP").upper()
            match_lines = format_match_fields(match)

            if not match_lines:
                continue

            rule_block = ["    rule"]
            for condition in match_lines:
                rule_block.append(f"        {condition}")
            rule_block.append("        counter")
            rule_block.append(f"        {action}")

            chain_lines.extend(rule_block)

        if len(chain_lines) > 1:
            lines.extend(chain_lines)

    return lines

# // Genera el archivo de salida con las reglas formateadas
# // Generates the output file with formatted rules
def task_gen_bpfilter_policies_json_format():
    outputPath = "/home/praesidium/PraesidiumFirewall/backend/commits/commit_task/pruebas/pruebas_machine.txt"
    lines = saniticed_bpfilter_format()
    try:
        with open(outputPath, "w") as f:
            for line in lines:
                f.write(line + "\n")
    except Exception:
        pass  # // Silencia errores de escritura // Suppress write errors
task_gen_bpfilter_policies_json_format()