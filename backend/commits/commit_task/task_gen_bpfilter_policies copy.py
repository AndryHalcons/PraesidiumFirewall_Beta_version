import subprocess
from task_update_json import task_update_json
import json



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


def process_rules(user, date, json_path, output_path):
    try:
        # Abrimos y cargamos el archivo JSON que contiene las reglas
        with open(json_path, "r") as f:
            data = json.load(f)

        lines = []  # Lista donde se almacenarán las líneas del archivo de salida

        # Iteramos sobre cada hook definido en el JSON (por ejemplo: BF_HOOK_XDP)
        for hook_name, hook_data in data.items():
            # Obtenemos el nombre de la cadena, o generamos uno por defecto si no está definido
            chain_name = hook_data.get("chain", f"chain_{hook_name.lower()}")

            policy = "ACCEPT"  # Política por defecto para la cadena
            rules = hook_data.get("rules", [])  # Lista de reglas asociadas al hook

            # Iteramos sobre cada regla dentro del hook
            for rule in rules:
                # Ignoramos reglas que no estén habilitadas
                if not rule.get("enabled", False):
                    continue

                match = rule.get("match", {})  # Obtenemos los criterios de coincidencia
                action = rule.get("action", "DROP")  # Acción por defecto si no se especifica

                # Generamos las condiciones de filtrado usando la función anterior
                match_lines = format_match_fields(match)

                # Si no hay condiciones válidas, omitimos la regla (evita errores de sintaxis)
                if not match_lines:
                    continue

                # Unimos todas las condiciones en una sola cadena
                match_str = " ".join(match_lines)

                # Construimos la línea completa de la regla en el formato requerido por bpfilter
                rule_line = f"chain {chain_name} {hook_name}{{}} {policy} rule {match_str} counter {action}"
                lines.append(rule_line)  # Añadimos la línea al archivo de salida

        # Escribimos todas las líneas generadas en el archivo de salida
        with open(output_path, "w") as f_out:
            for line in lines:
                f_out.write(line + "\n")

        # Registramos que la tarea se completó con éxito
        task_update_json(date, "flush_bpfilter_json_to_txt", "success")

    except Exception as e:
        # Si ocurre un error, registramos el fallo de la tarea
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
    process_rules(user, date, json_path, output_path)
    #apply_bpfilter_policies(output_path, user, date)



task_gen_bpfilter_policies("praesidium", "20250825134059")