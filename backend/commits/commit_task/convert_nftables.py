import os
import ipaddress
import json
import re


# //////////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////    Import Json to to consult  ///////////////////////////////////////
# //////////////////////////////////////////////////////////////////////////////////////////////////////

# Importa el archivo de alias y lo devuelve como array
# Imports the alias file and returns it as an array
def import_alias_json():
    json_path = '/var/www/config/alias.json'

    if not os.path.exists(json_path):
        return False

    with open(json_path, 'r', encoding='utf-8') as f:
        raw = f.read()

    try:
        alias_json_data = json.loads(raw)
    except json.JSONDecodeError:
        return False

    return alias_json_data

# Importa el archivo de reglas actual para consultas
# Imports the current rules file for queries
def import_policy_nft_json():
    json_path = '/var/www/config/rules_nftables.json'

    if not os.path.exists(json_path):
        return False

    with open(json_path, 'r', encoding='utf-8') as f:
        raw = f.read()

    try:
        alias_json_data = json.loads(raw)
    except json.JSONDecodeError:
        return False

    return alias_json_data

# importa el archivo de formulario para validar los datos del resto de campos
# import the form file to validate the data in the remaining fields
def import_forms_nft_json():
    json_path = '/var/www/backend/checks/system_data/default_forms/forms_policies_nft.json'

    if not os.path.exists(json_path):
        return False

    with open(json_path, 'r', encoding='utf-8') as f:
        raw = f.read()

    try:
        alias_json_data = json.loads(raw)
    except json.JSONDecodeError:
        return False

    return alias_json_data

# importa la lista de interfaces en array 
# imports the list of interfaces into array
def import_all_interfaces():
    path = '/var/www/backend/checks/system_data/data_interfaces/all_interfaces_list.json'

    if not os.path.exists(path):
        return []

    with open(path, 'r', encoding='utf-8') as f:
        raw = f.read()

    try:
        data = json.loads(raw)
    except json.JSONDecodeError:
        return []

    return data.get('all_interfaces', [])

# //////////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////    form field review        /////////////////////////////////////////
# //////////////////////////////////////////////////////////////////////////////////////////////////////

# revisa los campos que contienen formularios
# check the fields that contain forms
def validation_form_field_review(rule):
    form_config = import_forms_nft_json()
    if not form_config:
        #print(json.dumps({"error": "No se pudo cargar la configuración del formulario interfaces"}))
        exit()

    interfaces = import_all_interfaces()
    if 'meta.iifname' in form_config.get('select', {}):
        form_config['select']['meta.iifname'] += interfaces
    if 'meta.oifname' in form_config.get('select', {}):
        form_config['select']['meta.oifname'] += interfaces

    if 'select' in form_config:
        for key, valid_values in form_config['select'].items():
            if key in rule:
                value = rule[key]
                if str(value).strip() == '':
                    continue
                if value not in valid_values:
                    #print(json.dumps({"error": f"value in validation_form_field_review_select '{value}' not found"}))
                    exit()

    if 'checkbox' in form_config:
        for key, options in form_config['checkbox'].items():
            if key in rule:
                value = rule[key]
                if str(value).strip() == '':
                    continue
                if value != options.get("checked") and value != options.get("unchecked"):
                    #print(json.dumps({"error": f"alias port validation_form_field_review_checkbox '{value}' not found"}))
                    exit()

    if 'not_editable' in form_config:
        for key, valid_values in form_config['not_editable'].items():
            if key == 'id':
                continue
            if key in rule:
                value = rule[key]
                if str(value).strip() == '':
                    continue
                if value not in valid_values:
                    #print(json.dumps({"error": f"alias port validation_form_field_review_not_editable '{value}' not found"}))
                    exit()

# genera la entrada log compatible con nftables si es true, si es false borra "log" de la regla
# Generates the nftables-compatible log entry if true, if false deletes "log" from the rule
def log_format_nft(rule: dict) -> dict:
    if 'log' in rule:
        if rule['log'] == 'true':
            id_ = rule.get('id', '')
            chain = rule.get('chain', '')
            action = rule.get('action', '')
            rule['log'] = f"nftables {id_} {chain} {action}"
        elif rule['log'] == 'false':
            rule.pop('log', None)
    return rule


# //////////////////////////////////////////////////////////////////////////////////////////////////////
# //////////////////////////////// ID and name section     /////////////////////////////////////////////
# //////////////////////////////////////////////////////////////////////////////////////////////////////

# Genera un ID único buscando el primer número no usado en los comentarios
# Generates a unique ID by finding the first unused number in rule comments
def get_id_from_policy() -> str:
    data = import_policy_nft_json()
    if not data or 'nftables' not in data or not isinstance(data['nftables'], list):
        return "1"  # fallback si no se puede leer el archivo
                   # fallback if the file can't be read

    used_ids = []

    for entry in data['nftables']:
        if 'rule' in entry and 'comment' in entry['rule']:
            comment = entry['rule']['comment']
            import re
            match = re.search(r"id='(\d+)'", comment)
            if match:
                used_ids.append(int(match.group(1)))

    # Busca el primer ID libre empezando desde 1
    # Find the first free ID starting from 1
    id_ = 1
    while id_ in used_ids:
        id_ += 1

    return str(id_)

# convierte el campo name y el campo id en partes del campo comment de nftables
# si no hay id por que la regla por ejemplo es nueva, se llama a get_id_from_policy() que devuelve un id único
# makes the name field and id field parts of the nftables comment field
# if there is no id because the rule is new, for example, get_id_from_policy() is called which returns a unique id
def comment_convert_id_name(rule: dict) -> dict:
    # Si no hay id, se genera automáticamente
    # If 'id' is missing, generate it automatically
    id_ = rule.get('id', '').strip() or get_id_from_policy()

    # El name puede estar vacío, pero debe incluirse
    # 'name' can be empty, but must be included
    name = rule.get('name', '').strip()

    # Construye el campo comment con ambas claves
    # Builds the 'comment' field with both keys
    rule['comment'] = f"id='{id_}',name='{name}'"

    return rule




# //////////////////////////////////////////////////////////////////////////////////////////////////////
# //////////////////////////////// PORTS VALIDATION SECTION ///////////////////////////////////////////
# //////////////////////////////////////////////////////////////////////////////////////////////////////

# elimina puertos de los campos puerto si el protocolo de la regla es icmp
# Remove ports from the port fields if the rule protocol is icmp
def validation_icmp_no_ports(rule: dict) -> dict:
    protocol = rule.get('ip.protocol', '').lower()

    if protocol in ['icmp', 'icmpv6']:
        fields_to_clear = [
            'sport.op',
            'sport',
            'dport.op',
            'dport',
            'dnat.port'
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
def validation_ports_range(value: str) -> None:
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
                exit()
            continue

# Convierte un alias de puerto en su valor numérico real
# Converts a port alias into its actual numeric value
def convert_alias_port_to_network_port(value: str) -> str:
    alias_json_data = import_alias_json()

    # Verifica que se haya cargado correctamente el JSON
    # Check that the JSON was loaded successfully
    if not alias_json_data:
        #print(json.dumps({"error": "alias file not found or invalid"}))
        exit()

    # Busca el alias en alias_service
    # Search for the alias in alias_service
    for entry in alias_json_data.get('alias_service', []):
        if entry.get('name') == value:
            return entry.get('content', [''])[0]

    # Si no se encuentra, se detiene el script y se devuelve error
    # If not found, stop the script and return error
    #print(json.dumps({"error": f"alias port no encontrado en ningun sitio '{value}' not found"}))
    exit()

# Convierte una lista de puertos, alias y grupos en puertos reales
# Converts a list of ports, aliases, and groups into real port numbers
def convert_alias_port_group_to_network_port(value: str) -> str:
    alias_json_data = import_alias_json()

    # Si el valor está vacío, no se procesa
    # If the value is empty, skip processing
    if value.strip() == '':
        return ''

    # Si no se pudo cargar el archivo, se detiene el script
    # If the file couldn't be loaded, stop the script
    if not alias_json_data:
        #print(json.dumps({"error": "alias file not found or invalid"}))
        exit()

    final_ports = []
    items = [item.strip() for item in value.split(',')]

    for item in items:
        if item == '':
            continue  # Ignora elementos vacíos individuales
                     # Ignore individual empty elements

        if item.isdigit() or re.match(r'^\d+-\d+$', item):
            validation_ports_range(item)
            final_ports.append(item)
            continue

        found_group = False

        for group in alias_json_data.get('alias_service_group', []):
            if group.get('name') == item:
                for entry in group.get('content', []):
                    if entry.isdigit() or re.match(r'^\d+-\d+$', entry):
                        validation_ports_range(entry)
                        final_ports.append(entry)
                    else:
                        resolved = convert_alias_port_to_network_port(entry)
                        validation_ports_range(resolved)
                        final_ports.append(resolved)
                found_group = True
                break

        if not found_group:
            resolved = convert_alias_port_to_network_port(item)
            validation_ports_range(resolved)
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
            # IP sin CIDR → se normaliza como /32 (IPv4) o /128 (IPv6)
            # IP without CIDR → normalize as /32 (IPv4) or /128 (IPv6)
            ip_obj = ipaddress.ip_address(item)
            suffix = '/32' if ip_obj.version == 4 else '/128'
            normalized.append(f"{item}{suffix}")
        except ValueError:
            # IP con CIDR → se valida y se agrega si es válida
            # IP with CIDR → validate and add if valid
            if re.match(r'^(.+)/(\d{1,3})$', item):
                try:
                    ip_net = ipaddress.ip_network(item, strict=False)
                    normalized.append(str(ip_net))
                except ValueError:
                    #print(json.dumps({"error": f"invalid CIDR '{item}'"}))
                    exit()
            else:
                # Formato inválido → se muestra error y se detiene
                # Invalid format → show error and stop
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
def convert_alias_ip_to_ip(value: str) -> str:
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
    exit()

# Convierte IPs, alias y grupos de alias en una lista normalizada de redes IP únicas.
# Converts IPs, aliases, and alias groups into a normalized list of unique network addresses.
def convert_alias_group_to_Network_ips(value: str) -> str:
    alias_json_data = import_alias_json()

    # Verifica que se haya cargado correctamente el JSON
    # Check that the JSON was loaded successfully
    if not alias_json_data:
        #print(json.dumps({"error": "alias file not found or invalid"}))
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
                    ip = convert_alias_ip_to_ip(alias_name)
                    if ip != '':
                        resolved_ips.append(ip)
                found_group = True
                break

        # Si no es grupo, lo tratamos como alias individual
        # If it's not a group, treat it as an individual alias
        if not found_group:
            ip = convert_alias_ip_to_ip(item)
            if ip != '':
                resolved_ips.append(ip)
                continue

            # Si no se pudo resolver, se lanza error
            # If resolution fails, throw an error
            #print(json.dumps({"error": f"alias or group '{item}' not found or invalid"}))
            exit()

    # Normaliza y elimina duplicados antes de devolver
    # Normalize and remove duplicates before returning
    return validation_ip_networks(','.join(resolved_ips))

# Convierte alias en objetos de red reales usando funciones auxiliares
# Converts aliases into real network objects using helper functions
def Main_convert_alias_object_to_network_object(rule: dict) -> dict:
    # Campos relacionados con puertos
    # Port-related fields
    port_fields = ['sport', 'dport', 'dnat.port']

    for field in port_fields:
        if field in rule:
            # Llama a la función de conversión de puertos
            # Call the port conversion function
            rule[field] = convert_alias_port_group_to_network_port(rule[field])

    # Campos relacionados con direcciones IP
    # IP-related fields
    ip_fields = ['ip.daddr', 'ip.saddr', 'dnat.addr', 'snat.addr']

    for field in ip_fields:
        if field in rule:
            # Llama a la función de conversión de grupos IP
            # Call the IP group conversion function
            rule[field] = convert_alias_group_to_Network_ips(rule[field])

    return rule


# ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////// Assign position if empty /////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

# Asigna la posición 1 si no viene definida o está vacía
# Assigns position 1 if not defined or empty
def assign_position(rule: dict) -> dict:
    # Verifica si el campo 'position' está ausente o vacío
    # Checks if the 'position' field is missing or empty
    if 'position' not in rule or str(rule['position']).strip() == '':
        # Asigna la posición 1 por defecto
        # Assigns default position 1
        rule['position'] = 1

    # Devuelve la regla modificada
    # Returns the modified rule
    return rule



# //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////// Saniticed to nftables json format /////////////////////////////////////////////////////////////////
# //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

# Función para convertir la regla al formato de nftables
# Function to convert the rule to nftables format
# Genera la estructura base de una regla nftables
# Generates the base structure of an nftables rule
def saniticed_nftables_policy(rule):
    #print(rule)
    return {
        "rule": {
            "family": rule.get("family", ""),
            "table": rule.get("table", ""),
            "chain": rule.get("chain", ""),
            "position": rule.get("position", ""),
            "id": rule.get("id", ""),
            "name": rule.get("name", ""),
            "expr": build_expr(rule, rule.get("comment", "")),
            "comment": rule.get("comment", "")
        }
    }
    


# genera la estructura de expr en nftables
# generate the structure of expr in nftables
def build_expr(rule, comment):
    expr = []

    for field in ["snat.addr", "dnat.addr"]:
        if rule.get(field):
            rule[field] = re.sub(r"/(32|128)$", "", str(rule[field]))

    if rule.get("ip.protocol"):
        protocols = [str(p).strip() for p in str(rule["ip.protocol"]).split(",")]
        expr.append({
            "match": {
                "op": "==",
                "left": {"payload": {"protocol": "ip", "field": "protocol"}},
                "right": protocols[0] if len(protocols) == 1 else {"set": protocols}
            }
        })

    if rule.get("ip.saddr"):
        set_ = []
        for cidr in str(rule["ip.saddr"]).split(","):
            if "/" in cidr:
                addr, length = cidr.strip().split("/")
                if str(length).isdigit():
                    set_.append({"prefix": {"addr": addr, "len": int(length)}})
        if set_:
            expr.append({
                "match": {
                    "op": rule.get("ip.saddr.op", "=="),
                    "left": {"payload": {"protocol": "ip", "field": "saddr"}},
                    "right": {"set": set_}
                }
            })

    if rule.get("ip.daddr"):
        set_ = []
        for cidr in str(rule["ip.daddr"]).split(","):
            if "/" in cidr:
                addr, length = cidr.strip().split("/")
                if str(length).isdigit():
                    set_.append({"prefix": {"addr": addr, "len": int(length)}})
        if set_:
            expr.append({
                "match": {
                    "op": rule.get("ip.daddr.op", "=="),
                    "left": {"payload": {"protocol": "ip", "field": "daddr"}},
                    "right": {"set": set_}
                }
            })

    for port_type in ["sport", "dport"]:
        if rule.get(port_type):
            ports = [str(p).strip() for p in str(rule[port_type]).split(",")]
            items = []
            for p in ports:
                if re.match(r"^\d+-\d+$", p):
                    start, end = p.split("-")
                    if str(start).isdigit() and str(end).isdigit():
                        items.append({"range": [int(start), int(end)]})
                elif str(p).isdigit():
                    items.append(int(p))
            if items:
                right = items[0] if len(items) == 1 else {"set": items}
                proto_raw = str(rule.get("ip.protocol", "")).strip()
                is_tcp_udp = proto_raw == "tcp, udp"
                has_snat = bool(str(rule.get("snat.addr", "")).strip())
                has_dnat = bool(str(rule.get("dnat.addr", "")).strip())
                has_both_ports = bool(str(rule.get("sport", "")).strip()) and bool(str(rule.get("dport", "")).strip())
                proto = "th" if is_tcp_udp and (has_snat or has_dnat or has_both_ports) else proto_raw
                expr.append({
                    "match": {
                        "op": rule.get(f"{port_type}.op", "=="),
                        "left": {"payload": {"protocol": proto, "field": port_type}},
                        "right": right
                    }
                })

    if rule.get("meta.iifname"):
        expr.append({
            "match": {
                "op": "==",
                "left": {"meta": {"key": "iifname"}},
                "right": rule["meta.iifname"]
            }
        })

    if rule.get("meta.oifname"):
        expr.append({
            "match": {
                "op": "==",
                "left": {"meta": {"key": "oifname"}},
                "right": rule["meta.oifname"]
            }
        })

    if rule.get("ct.state"):
        states = [str(s).strip() for s in str(rule["ct.state"]).split(",") if str(s).strip()]
        if states:
            expr.append({
                "match": {
                    "op": "==",
                    "left": {"ct": {"key": "state"}},
                    "right": {"set": states}
                }
            })

    if "packets" in rule or "bytes" in rule:
        packets = str(rule.get("packets", "0")).strip()
        bytes_ = str(rule.get("bytes", "0")).strip()
        expr.append({
            "counter": {
                "packets": int(packets) if packets.isdigit() else 0,
                "bytes": int(bytes_) if bytes_.isdigit() else 0
            }
        })

    if rule.get("log"):
        expr.append({
            "log": {
                "prefix": str(rule["log"]) + " ",
                "flags": "all",
                "level": "info"
            }
        })

    if rule.get("snat.addr"):
        snat = {"addr": rule["snat.addr"]}
        port = str(rule.get("snat.port", "")).strip()
        if port.isdigit():
            snat["port"] = int(port)
        expr.append({"snat": snat})

    if rule.get("dnat.addr"):
        dnat = {"addr": rule["dnat.addr"]}
        port = str(rule.get("dnat.port", "")).strip()
        if port.isdigit():
            dnat["port"] = int(port)
        expr.append({"dnat": dnat})

    if rule.get("action"):
        expr.append({rule["action"]: None})

    return expr


# //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////// write and order policy /////////////////////////////////////////////////////////////////
# //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

# Reasigna la posición de una regla según su familia, tabla y cadena
# Reassigns the position of a rule based on its family, table, and chain
def reassign_position(rule: dict) -> dict:
    # Carga el JSON que contiene todas las reglas actuales
    # Loads the JSON containing all current rules
    json_data = import_policy_nft_json()

    # Si no se puede cargar o no contiene reglas, se devuelve la regla tal cual
    # If loading fails or there are no rules, return the rule as-is
    if not json_data or "nftables" not in json_data:
        return rule

    # Extrae los valores clave para identificar el grupo de reglas
    # Extracts key values to identify the rule group
    family = rule["family"]
    table = rule["table"]
    chain = rule["chain"]

    # Verifica si la posición viene definida o está vacía
    # Checks whether the position is defined or empty
    incoming_position = int(rule["position"]) if rule.get("position") not in [None, ""] else None

    # Si no viene posición, se asigna la posición 1
    # If no position is provided, assign position 1
    if incoming_position is None:
        rule["position"] = 1
        incoming_position = 1

        # Desplaza hacia adelante (+1) todas las reglas que coincidan en familia, tabla y cadena
        # Shift forward (+1) all rules that match family, table, and chain
        for entry in json_data["nftables"]:
            r = entry.get("rule")
            if r and all(k in r for k in ["family", "table", "chain", "position"]):
                if r["family"] == family and r["table"] == table and r["chain"] == chain:
                    r["position"] = int(r["position"]) + 1
    else:
        # Si ya viene una posición, se respeta
        # If a position is already provided, it is respected

        # Desplaza hacia adelante (+1) todas las reglas con posición igual o superior
        # Shift forward (+1) all rules with equal or higher position
        for entry in json_data["nftables"]:
            r = entry.get("rule")
            if r and all(k in r for k in ["family", "table", "chain", "position"]):
                if r["family"] == family and r["table"] == table and r["chain"] == chain:
                    if int(r["position"]) >= incoming_position:
                        r["position"] = int(r["position"]) + 1

    # Devuelve la regla con la posición ajustada
    # Returns the rule with the adjusted position
    return rule


# Inserta o actualiza una regla en el JSON de reglas
# Inserts or updates a rule in the rules JSON
def update_or_insert_nft_rule(rule: dict, rules_json: dict) -> dict:
    id_ = int(rule.get("id", 0))
    if not id_:
        return rules_json

    for index, entry in enumerate(rules_json.get("nftables", [])):
        existing_rule = entry.get("rule")
        if not existing_rule:
            continue
        existing_id = int(existing_rule.get("id", 0))
        if existing_id == id_:
            rules_json["nftables"][index]["rule"] = rule
            rules_json = reorder_policies(rules_json)
            return rules_json

    # Inserta como nueva
    # Insert as new
    rules_json.setdefault("nftables", []).append({"rule": rule})
    rules_json = reorder_policies(rules_json)
    return rules_json


# Ordena las reglas por posición
# Sorts rules by position
def reorder_policies(rules_json: dict) -> dict:
    # Extraer solo las reglas
    # Extract only rules
    rules = [entry for entry in rules_json.get("nftables", []) if "rule" in entry]

    # Ordenar solo las reglas por position
    # Sort rules by position
    rules.sort(key=lambda r: int(r["rule"].get("position", float("inf"))))

    # Reconstruir el array original, reemplazando solo las reglas
    # Rebuild the original array, replacing only the rules
    rule_index = 0
    for i, entry in enumerate(rules_json.get("nftables", [])):
        if "rule" in entry:
            rules_json["nftables"][i] = rules[rule_index]
            rule_index += 1

    return rules_json
