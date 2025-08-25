import subprocess
from task_update_json import task_update_json

def apply_nftables_json(date, json_path):
    #Applies a nftables rules file in JSON format using the nft command.
    #Aplica un archivo de reglas nftables en formato JSON usando el comando nft.
    try:
        subprocess.run(["sudo", "nft", "-j", "-f", json_path], check=True)
        task_update_json(date, "apply_nftables_json", "success")
    except subprocess.CalledProcessError as e:
        task_update_json(date, "apply_nftables_json", "fail")
        exit()



def verify_nftables_json(date,json_path):
    #verifies that the nftables file has no errors, contains properly formed rules, and is syntactically correct
    #verifica que el archivo nftables no tiene errores, tiene reglas correctamente formadas y está correcto sintacticamente
    try:
        subprocess.run(["sudo", "nft", "-j", "-f", json_path, "--check"], check=True)
        task_update_json(date, "verify_nftables_json", "success")
    except subprocess.CalledProcessError as e:
        task_update_json(date, "verify_nftables_json", "fail")
        exit()

def flush_nftables_policies(date):

    #clears/removes the currently running rules so the new rules file can be applied cleanly
    #borra/limpia las reglas actuales que están en ejecucion para poder ejecutar el nuevo archivo de reglas de forma limpia
    try:
        subprocess.run(["sudo", "nft", "flush", "ruleset"], check=True)
        task_update_json(date, "flush_nftables_json", "success")
    except subprocess.CalledProcessError as e:
        task_update_json(date, "flush_nftables_json", "fail")

def gen_nftables_policies(user, date):
    json_path="/var/www/config_running/rules_nftables.json"
    verify_nftables_json(date,json_path)
    flush_nftables_policies(date)
    apply_nftables_json(date, json_path)
    

