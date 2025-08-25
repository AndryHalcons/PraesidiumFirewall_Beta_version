import subprocess
from task_update_json import task_update_json

import json

def format_match_fields(match):
    if match == "any":
        return "match any"
    
    parts = []
    for key, value in match.items():
        if value and value != "any" and "example" not in value:
            parts.append(f"{key.replace('_', '.')} {value}")
    return " ".join(parts)

def process_rules(user, date, json_path, output_path):
    try:
        with open(json_path, "r") as f:
            data = json.load(f)

        lines = []

        for hook_name, hook_data in data.items():
            chain_name = hook_data.get("chain", f"chain_{hook_name.lower()}")
            rules = hook_data.get("rules", [])

            # Definir la cadena
            lines.append(f"chain {chain_name} {hook_name} ACCEPT")

            for rule in rules:
                if not rule.get("enabled", False):
                    continue

                match = rule.get("match", {})
                action = rule.get("action", "DROP")

                match_str = format_match_fields(match)

                # Ignorar reglas sin coincidencias válidas
                if not match_str.strip():
                    continue

                rule_line = f"rule {match_str} action {action}"
                lines.append(rule_line)

        with open(output_path, "w") as f_out:
            for line in lines:
                f_out.write(line + "\n")

        # Éxito
        task_update_json(date, "flush_bpfilter_json_to_txt", "success")

    except Exception as e:
        task_update_json(date, "flush_bpfilter_json_to_txt", "fail")


def task_gen_bpfilter_policies(user, date):
    json_path = "/var/www/config_running/rules.json"
    output_path = "/home/praesidium/PraesidiumFirewall/backend/commits/commit_task/rules_formatted.txt"
    process_rules(user, date, json_path, output_path)



task_gen_bpfilter_policies("praesidium", "20250825134059")