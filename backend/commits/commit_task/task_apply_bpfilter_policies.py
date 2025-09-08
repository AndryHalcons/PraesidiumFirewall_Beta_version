import subprocess
from task_update_json import task_update_json
from collections import defaultdict

def apply_bpfilter_policies(user, date):
    loadPolicyPath = "/var/www/config_running/bpfilter_machine_format.txt"
    try:
        # Ejecutar el comando bfcli con el archivo de políticas cargado
        result = subprocess.run(
            ["/usr/local/bin/bfcli", "ruleset", "set", "--from-file", loadPolicyPath],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )

        if result.returncode == 0:
            task_update_json(date, "apply_bpfilter_policy", "success")
        else:
            task_update_json(date, "apply_bpfilter_policy", "fail")

    except Exception as e:
        task_update_json(date, "apply_bpfilter_policy", "fail")
