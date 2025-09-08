import json
import subprocess
import os
import convert_nftables
from collections import defaultdict
from task_update_json import task_update_json

from convert_nftables import (
    validation_icmp_no_ports,
    Main_convert_alias_object_to_network_object,
    comment_convert_id_name,
    validation_form_field_review,
    assign_position,
    log_format_nft,
    saniticed_nftables_policy,
    update_or_insert_nft_rule
)

# Aplica todas las validaciones necesarias a una regla nftables
# Applies all required validations to an nftables rule
def validate_nftables_policy(rule: dict) -> dict:
    rule = validation_icmp_no_ports(rule)
    rule = Main_convert_alias_object_to_network_object(rule)
    rule = comment_convert_id_name(rule)
    validation_form_field_review(rule)
    rule = assign_position(rule)
    rule = log_format_nft(rule)
    return rule

# Convierte las reglas del archivo human_viewer y actualiza el archivo backend
# Converts rules from human_viewer file and updates the backend rules file
def convert_update_policy_to_backend(date: str):
    try:
        json_path = "/var/www/config/rules_nftables.json"

        # Verifica si el archivo de reglas existe
        # Check if the rules file exists
        if not os.path.exists(json_path):
            task_update_json(date, "nftables_convert", "fail")
            return

        # Carga el archivo de reglas actuales
        # Load the current rules file
        with open(json_path, "r", encoding="utf-8") as f:
            rules_json = json.load(f)

        # Verifica que el JSON tenga la clave 'nftables'
        # Ensure the JSON contains the 'nftables' key
        if "nftables" not in rules_json:
            task_update_json(date, "nftables_convert", "fail")
            return

        # Elimina todas las entradas que contienen la clave 'rule'
        # Remove all entries that contain the 'rule' key
        rules_json["nftables"] = [
            entry for entry in rules_json["nftables"] if "rule" not in entry
        ]

        human_path = "/var/www/config/rules_nftables_human_viewer.json"

        # Verifica si el archivo human_viewer existe
        # Check if the human_viewer file exists
        if not os.path.exists(human_path):
            task_update_json(date, "nftables_convert", "fail")
            return

        # Carga el archivo human_viewer
        # Load the human_viewer file
        with open(human_path, "r", encoding="utf-8") as f:
            human_json = json.load(f)

        # Verifica que el JSON tenga la clave 'nftables'
        # Ensure the JSON contains the 'nftables' key
        if "nftables" not in human_json:
            task_update_json(date, "nftables_convert", "fail")
            return

        # Itera sobre cada regla habilitada en human_viewer
        # Iterate over each enabled rule in human_viewer
        for entry in human_json["nftables"]:
            rule = entry.get("rule")
            if not isinstance(rule, dict):
                continue
            if rule.get("enable") != "true":
                continue

            # Valida y sanitiza la regla
            # Validate and sanitize the rule
            validated = validate_nftables_policy(rule)
            sanitized = saniticed_nftables_policy(validated)

            # Inserta o actualiza la regla en el archivo backend
            # Insert or update the rule in the backend rules file
            rules_json = update_or_insert_nft_rule(sanitized["rule"], rules_json)

        # Guarda el archivo actualizado de reglas
        # Save the updated rules file
        with open(json_path, "w", encoding="utf-8") as f:
            json.dump(rules_json, f, indent=2, ensure_ascii=False)

        # Marca la tarea como exitosa
        # Mark the task as successful
        task_update_json(date, "nftables_convert", "success")
        print("finalizado por dios")

    except Exception as e:
        # Marca la tarea como fallida en caso de error
        # Mark the task as failed in case of error
        task_update_json(date, "nftables_convert", "fail")
