import subprocess
from task_update_json import task_update_json
import json





def get_ifindex(iface_name, iface_system_path):
    #obtiene el ifindex de las interfaces fisicas para la chain
    # Retrieves the ifindex of physical interfaces for the chain
    try:
        with open(iface_system_path, "r") as f:
            data = json.load(f)
            interfaces = data.get("physical_interfaces", [])
            for iface in interfaces:
                if iface.get("name") == iface_name:
                    return iface.get("ifindex")
    except Exception as e:
        pass  # Silenciar errores si el archivo no existe o está mal formado

    return None  # Si no se encuentra la interfaz o hay error



def format_match_fields(match):
    # Si el valor de 'match' es la cadena "any", no se aplica ningún filtro
    # y por lo tanto no se genera ninguna condición.
    if match == "any":
        return []

    parts = []  # Lista donde se acumularán las condiciones válidas para la regla

    # Lista de campos que, según el parser, deben llevar el prefijo 'meta.'
    # y conservar el guion bajo (no convertir a punto).
    meta_fields = {"iface", "l3_proto", "l4_proto", "probability"}

    # Iteramos por cada par clave-valor del diccionario 'match'
    for key, value in match.items():
        # Ignoramos el campo si:
        # - Está vacío
        # - Tiene el valor "any"
        # - Contiene la palabra "example" (usado como marcador de plantilla)
        if not value or value == "any" or "example" in value:
            continue

        # Determinamos el nombre del campo que se usará en la regla:
        # - Si es un campo 'meta', se conserva el guion bajo y se antepone 'meta.'
        # - Si no, se convierte de snake_case a dot.notation (ej. ip4_saddr → ip4.saddr)
        if key in meta_fields:
            field_name = f"meta.{key}"  # Ejemplo: l3_proto → meta.l3_proto
        else:
            field_name = key.replace("_", ".")  # Ejemplo: ip4_saddr → ip4.saddr

        # Si el campo es 'probability', el parser espera un valor en porcentaje (ej. "100%")
        # Convertimos valores decimales como "1.0" → "100%"
        if key == "probability" and not value.endswith("%"):
            try:
                value_float = float(value)
                value = f"{int(value_float * 100)}%"
            except ValueError:
                continue  # Si no se puede convertir, ignoramos el campo

        # Añadimos la condición al listado, usando el operador 'eq' por defecto
        # Ejemplo: meta.l3_proto eq IPv4
        parts.append(f"{field_name} eq {value}")

    # Devolvemos la lista de condiciones válidas que se usarán en la regla
    return parts


def process_rules(user, date, json_path, output_path, iface_system_path):
    try:
        with open(json_path, "r") as f:
            data = json.load(f)

        lines = []

        for hook_name, hook_data in data.items():
            policy = "ACCEPT"
            rules = hook_data.get("rules", [])

            # Buscar la primera interfaz válida en las reglas habilitadas
            iface = None
            for rule in rules:
                if rule.get("enabled", False):
                    match = rule.get("match", {})
                    iface_candidate = match.get("iface")
                    if iface_candidate and iface_candidate != "any" and "example" not in iface_candidate:
                        iface = iface_candidate
                        break

            # Construir el nombre de la cadena como iface + hook, todo en minúsculas
            if iface:
                chain_name = f"{iface}{hook_name}".lower()
            else:
                chain_name = f"chain_{hook_name.lower()}"

            # Obtener el ifindex si hay interfaz válida
            chain_args = "{}"
            if iface:
                ifindex = get_ifindex(iface, iface_system_path)
                if ifindex is not None:
                    chain_args = f"{{ifindex={ifindex}}}"

            # Encabezado de la cadena
            chain_header = f"chain {chain_name} {hook_name}{chain_args} {policy}"
            chain_lines = [chain_header]

            for rule in rules:
                if not rule.get("enabled", False):
                    continue

                match = rule.get("match", {})
                action = rule.get("action", "DROP")
                match_lines = format_match_fields(match)

                if not match_lines:
                    continue

                # Construimos el bloque de la regla con indentación
                rule_block = ["    rule"]
                for condition in match_lines:
                    rule_block.append(f"        {condition}")
                rule_block.append("        counter")
                rule_block.append(f"        {action}")

                chain_lines.extend(rule_block)

            # Solo añadimos la cadena si tiene reglas válidas
            if len(chain_lines) > 1:
                lines.extend(chain_lines)

        # Escribimos el archivo
        with open(output_path, "w") as f_out:
            for line in lines:
                f_out.write(line + "\n")

        task_update_json(date, "flush_bpfilter_json_to_txt", "success")

    except Exception as e:
        task_update_json(date, "flush_bpfilter_json_to_txt", "fail")



def apply_bpfilter_policies(output_path, user, date):
    try:
        # Ejecutar el comando bfcli con el archivo generado
        result = subprocess.run(
            ["bfcli", "ruleset", "set", "--from-file", output_path],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )

        if result.returncode == 0:
            task_update_json(date, "apply_bpfilter_policies", "success")
        else:
            task_update_json(date, "apply_bpfilter_policies", "fail")
            print("Error al aplicar políticas:", result.stderr)

    except Exception as e:
        task_update_json(date, "apply_bpfilter_policies", "fail")
        print("Excepción al ejecutar bfcli:", str(e))



def task_gen_bpfilter_policies(user, date):
    json_path = "/var/www/config_running/rules.json"
    output_path = "/home/praesidium/PraesidiumFirewall/backend/commits/commit_task/rules_formatted.txt"
    iface_system_path = "/var/www/backend/checks/system_data/data_interfaces/physical_interfaces_list.json"
    process_rules(user, date, json_path, output_path, iface_system_path)
    #apply_bpfilter_policies(output_path, user, date)



task_gen_bpfilter_policies("praesidium", "20250825134059")