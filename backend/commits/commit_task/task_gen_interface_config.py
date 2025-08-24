import yaml
import shutil
import subprocess

def check_yml_syntax(path):
    # Verifica si el archivo YAML tiene estructura compatible con Netplan  
    # Checks if the YAML file has a structure compatible with Netplan
    try:
        with open(path, 'r') as f:
            config = yaml.safe_load(f)

        return (
            isinstance(config, dict) and
            "network" in config and
            isinstance(config["network"], dict) and
            config["network"].get("version") == 2 and
            any(k in config["network"] for k in ["ethernets", "wifis"])
        )
    except Exception:
        return False

def validate_netplan_file(path):
    try:
        temp_path = "/etc/netplan/temp_interfaces.yml"
        shutil.copy2(path, temp_path)

        result = subprocess.run(
            ["netplan", "--debug", "generate"],
            capture_output=True,
            text=True
        )

        # Elimina el archivo temporal
        os.remove(temp_path)

        # Si hay errores en stderr, no es válido
        if result.stderr:
            return False
        return True

    except Exception:
        return False

def gen_interface_config(user, date):
    # Ruta del archivo YAML
    # Path to the YAML file
    path = "/var/www/config_running/interfaces.yml"

    # Verifica si el archivo YAML tiene estructura compatible con Netplan  
    # Checks if the YAML file has a structure compatible with Netplan
    if check_yml_syntax(path):
        print("es true")
    else:
        print("es false")

    validate_netplan_file(path)
    
gen_interface_config("praesidium","20250824142408")