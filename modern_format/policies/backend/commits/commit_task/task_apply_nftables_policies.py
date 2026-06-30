import subprocess
from pathlib import Path
from task_update_json import task_update_json


################################################################################################################################
###################################### Backup / Rollback helpers ###############################################################
################################################################################################################################

# Generates a rollback backup of the currently running nftables ruleset before applying a new configuration.
# Genera un backup de rollback del ruleset actual de nftables antes de aplicar una nueva configuración.
def backup_nftables_ruleset(date):
    backup_path = f"/var/www/config_running/nftables_rollback_{date}.json"

    try:
        result = subprocess.run(
            ["sudo", "nft", "-j", "list", "ruleset"],
            check=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )
        Path(backup_path).write_text(result.stdout, encoding="utf-8")
        task_update_json(date, "backup_nftables_ruleset", "success")
        return backup_path
    except (subprocess.CalledProcessError, OSError):
        task_update_json(date, "backup_nftables_ruleset", "fail")
        return None


# Restores the rollback backup when the new nftables ruleset cannot be applied.
# Restaura el backup de rollback cuando no se puede aplicar el nuevo ruleset de nftables.
def rollback_nftables_ruleset(date, backup_path):
    if not backup_path or not Path(backup_path).exists():
        task_update_json(date, "rollback_nftables_ruleset", "missing_backup")
        return False

    try:
        subprocess.run(["sudo", "nft", "-j", "-f", backup_path], check=True)
        task_update_json(date, "rollback_nftables_ruleset", "success")
        return True
    except subprocess.CalledProcessError:
        task_update_json(date, "rollback_nftables_ruleset", "fail")
        return False


################################################################################################################################
###################################### Verify / Flush / Apply ##################################################################
################################################################################################################################

def apply_nftables_json(date, json_path, backup_path=None):
    # Applies a nftables rules file in JSON format using the nft command.
    # Aplica un archivo de reglas nftables en formato JSON usando el comando nft.
    try:
        subprocess.run(["sudo", "nft", "-j", "-f", json_path], check=True)
        task_update_json(date, "apply_nftables_json", "success")
    except subprocess.CalledProcessError:
        task_update_json(date, "apply_nftables_json", "fail")
        rollback_nftables_ruleset(date, backup_path)
        exit()



def verify_nftables_json(date,json_path):
    # verifies that the nftables file has no errors, contains properly formed rules, and is syntactically correct
    # verifica que el archivo nftables no tiene errores, tiene reglas correctamente formadas y está correcto sintacticamente
    try:
        subprocess.run(["sudo", "nft", "-j", "-f", json_path, "--check"], check=True)
        task_update_json(date, "verify_nftables_json", "success")
    except subprocess.CalledProcessError:
        task_update_json(date, "verify_nftables_json", "fail")
        exit()


def flush_nftables_policies(date):

    # clears/removes the currently running rules so the new rules file can be applied cleanly
    # borra/limpia las reglas actuales que están en ejecucion para poder ejecutar el nuevo archivo de reglas de forma limpia
    try:
        subprocess.run(["sudo", "nft", "flush", "ruleset"], check=True)
        task_update_json(date, "flush_nftables_json", "success")
    except subprocess.CalledProcessError:
        task_update_json(date, "flush_nftables_json", "fail")
        exit()


def apply_nftables_policies(user, date):
    json_path="/var/www/config_running/nftables_format.json"
    verify_nftables_json(date,json_path)
    backup_path = backup_nftables_ruleset(date)
    flush_nftables_policies(date)
    apply_nftables_json(date, json_path, backup_path)
