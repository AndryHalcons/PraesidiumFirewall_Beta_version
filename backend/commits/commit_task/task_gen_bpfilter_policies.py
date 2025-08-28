import subprocess
from task_update_json import task_update_json
import json


def get_ifindex(iface_name, iface_system_path):
    # obtiene el ifindex de las interfaces físicas para la chain
    # retrieves the ifindex of physical interfaces for the chain
    try:
        with open(iface_system_path, "r") as f:
            data = json.load(f)
            interfaces = data.get("physical_interfaces", [])
            for iface in interfaces:
                if iface.get("name") == iface_name:
                    return iface.get("ifindex")
    except Exception as e:
        pass  # silenciar errores si el archivo no existe o está mal formado
              # suppress errors if the file doesn't exist or is malformed

    return None  # si no se encuentra la interfaz o hay error
                 # if the interface is not found or an error occurs


def format_match_fields(match):
    # Si el valor de 'match' es la cadena "any", no se aplica ningún filtro
    # y por lo tanto no se genera ninguna condición.
    # If the value of 'match' is the string "any", no filter is applied
    # and therefore no condition is generated.
    if match == "any":
        return []

    parts = []  # Lista donde se acumularán las condiciones válidas para la regla
                # List where valid conditions for the rule will be accumulated

    # Lista de campos que, según el parser, deben llevar el prefijo 'meta.'
    # y conservar el guion bajo (no convertir a punto).
    # List of fields that, according to the parser, must be prefixed with 'meta.'
    # and keep the underscore (not converted to dot notation).
    meta_fields = {"iface", "l3_proto", "l4_proto", "probability"}

    # Iteramos por cada par clave-valor del diccionario 'match'
    # We iterate over each key-value pair in the 'match' dictionary
    for key, value in match.items():
        # Ignoramos el campo si:
        # - Está vacío
        # - Tiene el valor "any"
        # - Contiene la palabra "example" (usado como marcador de plantilla)
        # We ignore the field if:
        # - It's empty
        # - Has the value "any"
        # - Contains the word "example" (used as a template marker)
        if not value or value == "any" or "example" in value:
            continue

        # Determinamos el nombre del campo que se usará en la regla:
        # - Si es un campo 'meta', se conserva el guion bajo y se antepone 'meta.'
        # - Si no, se convierte de snake_case a dot.notation (ej. ip4_saddr → ip4.saddr)
        # We determine the field name to be used in the rule:
        # - If it's a 'meta' field, keep the underscore and prefix with 'meta.'
        # - Otherwise, convert from snake_case to dot.notation (e.g. ip4_saddr → ip4.saddr)
        if key in meta_fields:
            field_name = f"meta.{key}"  # Ejemplo: l3_proto → meta.l3_proto
                                        # Example: l3_proto → meta.l3_proto
        else:
            field_name = key.replace("_", ".")  # Ejemplo: ip4_saddr → ip4.saddr
                                                # Example: ip4_saddr → ip4.saddr

        # Si el campo es 'probability', el parser espera un valor en porcentaje (ej. "100%")
        # If the field is 'probability', the parser expects a percentage value (e.g. "100%")
        if key == "probability":
            # Si el valor está vacío o nulo → asignamos "100%"
            # If the value is empty or null → assign "100%"
            if value is None or value == "":
                value = "100%"
            else:
                # Validamos que sea un entero entre 1 y 100 terminado en "%"
                # Validate it's an integer between 1 and 100 ending with "%"
                if isinstance(value, str) and value.endswith("%"):
                    try:
                        numeric = int(value.strip('%'))
                        if not (1 <= numeric <= 100):
                            value = "100%"
                    except ValueError:
                        value = "100%"
                else:
                    # Si no termina en "%", lo consideramos inválido
                    # If it doesn't end with "%", treat as invalid
                    value = "100%"

        # Añadimos la condición al listado, usando el operador 'eq' por defecto
        # Ejemplo: meta.l3_proto eq IPv4
        # We add the condition to the list, using the 'eq' operator by default
        # Example: meta.l3_proto eq IPv4
        parts.append(f"{field_name} eq {value}")

    # Devolvemos la lista de condiciones válidas que se usarán en la regla
    # Return the list of valid conditions to be used in the rule
    return parts


def process_rules(user, date, json_path, output_path, iface_system_path):
    try:
        # Abrimos el archivo JSON que contiene las reglas definidas por hook
        # Open the JSON file that contains the rules defined per hook
        with open(json_path, "r") as f:
            data = json.load(f)

        lines = []  # Lista donde se acumularán las líneas del archivo de salida
                    # List to accumulate lines for the output file

        # Iteramos sobre cada hook definido en el JSON (por ejemplo: BF_HOOK_XDP)
        # Iterate over each hook defined in the JSON (e.g., BF_HOOK_XDP)
        for hook_name, hook_data in data.items():
            policy = "ACCEPT"  # Política por defecto para la cadena
                               # Default policy for the chain

            rules = hook_data.get("rules", [])  # Lista de reglas asociadas al hook
                                                # List of rules associated with the hook

            # Buscamos la primera interfaz válida en las reglas habilitadas
            # Look for the first valid interface in enabled rules
            iface = None
            for rule in rules:
                if rule.get("enabled", False):
                    match = rule.get("match", {})
                    iface_candidate = match.get("iface")
                    # Validamos que la interfaz no sea "any" ni un marcador de ejemplo
                    # Validate that the interface is not "any" or a template marker
                    if iface_candidate and iface_candidate != "any" and "example" not in iface_candidate:
                        iface = iface_candidate
                        break  # Usamos la primera interfaz válida encontrada
                               # Use the first valid interface found

            # Construimos el nombre de la cadena como <iface><hook>, todo en minúsculas
            # Build the chain name as <iface><hook>, all in lowercase
            if iface:
                chain_name = f"{iface}{hook_name}".lower()
            else:
                chain_name = f"chain_{hook_name.lower()}"  # Nombre genérico si no hay interfaz
                                                           # Generic name if no interface is found

            # Intentamos obtener el ifindex de la interfaz física si está disponible
            # Try to get the ifindex of the physical interface if available
            chain_args = "{}"
            if iface:
                ifindex = get_ifindex(iface, iface_system_path)
                if ifindex is not None:
                    chain_args = f"{{ifindex={ifindex}}}"  # Añadimos el ifindex en las llaves
                                                           # Add the ifindex inside the braces

            # Construimos el encabezado de la cadena con nombre, hook, argumentos y política
            # Build the chain header with name, hook, arguments, and policy
            chain_header = f"chain {chain_name} {hook_name}{chain_args} {policy}"
            chain_lines = [chain_header]

            # Iteramos sobre las reglas para construir sus bloques
            # Iterate over the rules to build their blocks
            for rule in rules:
                if not rule.get("enabled", False):
                    continue  # Ignoramos reglas deshabilitadas
                              # Skip disabled rules

                match = rule.get("match", {})  # Obtenemos los criterios de coincidencia
                                               # Get the match criteria
                action = rule.get("action", "DROP")  # Acción por defecto si no se especifica
                                                     # Default action if not specified

                match_lines = format_match_fields(match)  # Generamos condiciones de filtrado
                                                          # Generate filtering conditions

                if not match_lines:
                    continue  # Omitimos reglas sin condiciones válidas
                              # Skip rules with no valid conditions

                # Construimos el bloque de la regla con indentación
                # Build the rule block with indentation
                rule_block = ["    rule"]
                for condition in match_lines:
                    rule_block.append(f"        {condition}")
                rule_block.append("        counter")  # Añadimos contador por defecto
                                                      # Add default counter
                rule_block.append(f"        {action}")  # Añadimos la acción final
                                                        # Add the final action

                chain_lines.extend(rule_block)  # Añadimos el bloque a la cadena
                                                # Append the block to the chain

            # Solo añadimos la cadena si tiene al menos una regla válida
            # Only add the chain if it has at least one valid rule
            if len(chain_lines) > 1:
                lines.extend(chain_lines)

        # Escribimos todas las líneas generadas en el archivo de salida
        # Write all generated lines to the output file
        with open(output_path, "w") as f_out:
            for line in lines:
                f_out.write(line + "\n")

        # Registramos que la tarea se completó con éxito
        # Log that the task completed successfully
        task_update_json(date, "flush_bpfilter_json_to_txt", "success")

    except Exception as e:
        # Si ocurre un error, registramos el fallo de la tarea
        # If an error occurs, log the task failure
        task_update_json(date, "flush_bpfilter_json_to_txt", "fail")


def apply_bpfilter_policies(output_path, user, date):
    try:
        # Ejecutar el comando bfcli con el archivo generado
        result = subprocess.run(
            ["/usr/local/bin/bfcli", "ruleset", "set", "--from-file", output_path],
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



def gen_bpfilter_policies(user, date):
    json_path = "/var/www/config_running/rules.json"
    output_path = "/var/www/config_running/rules_formatted.txt"
    iface_system_path = "/var/www/backend/checks/system_data/data_interfaces/physical_interfaces_list.json"
    process_rules(user, date, json_path, output_path, iface_system_path)
    apply_bpfilter_policies(output_path, user, date)

