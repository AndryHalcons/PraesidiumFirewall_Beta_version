import yaml
import shutil
import subprocess
import os
from task_update_json import task_update_json

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
    # Valida si un archivo YAML es aceptado por Netplan sin aplicarlo  
    # Validates whether a YAML file is accepted by Netplan without applying it
    try:
        # Copia el archivo al directorio de Netplan con un nombre temporal  
        # Copies the file to Netplan's directory using a temporary name
        temp_path = "/etc/netplan/temp_interfaces.yml"
        shutil.copy2(path, temp_path)

        # Ejecuta Netplan en modo debug para simular la generación de configuración  
        # Runs Netplan in debug mode to simulate configuration generation
        result = subprocess.run(
            ["netplan", "--debug", "generate"],
            capture_output=True,
            text=True
        )

        # Elimina el archivo temporal después de la validación  
        # Deletes the temporary file after validation
        os.remove(temp_path)

        # Si el código de salida es distinto de 0, hubo un error  
        # If the return code is not 0, an error occurred
        if result.returncode != 0:
            return False, result.stderr.strip()

        # Si todo fue correcto, devuelve True y ningún mensaje  
        # If everything was successful, returns True and no message
        return True, None

    except Exception as e:
        # Si ocurre una excepción, devuelve False con el mensaje de error  
        # If an exception occurs, returns False with the error message
        return False, str(e)


def get_netplan_files():
    # Busca archivos YAML en /etc/netplan  
    # Looks for YAML files in /etc/netplan
    try:
        yaml_files = [f for f in os.listdir("/etc/netplan") if f.endswith(".yaml")]

        # Si hay exactamente uno, lo devuelve  
        # If exactly one YAML file is found, return it
        if len(yaml_files) == 1:
            return yaml_files[0]

        # Si hay cero o más de uno, devuelve None  
        # If zero or more than one YAML file is found, return None
        return None

    except:
        return None


def copy_netplan_file_config(path, current_file):
    # Construye la ruta completa del archivo actual en Netplan  
    # Builds the full path to the current Netplan file
    destination_path = os.path.join("/etc/netplan", current_file)

    try:
        # Copia el archivo personalizado al destino de Netplan  
        # Copies the custom file to Netplan's destination
        shutil.copy2(path, destination_path)
        return True, None
    except Exception as e:
        # Si ocurre un error, devuelve False y el mensaje  
        # If an error occurs, returns False and the error message
        return False, str(e)


def apply_netplan_config():
    # Aplica la configuración de red definida en los archivos YAML de Netplan  
    # Applies the network configuration defined in Netplan YAML files
    try:
        result = subprocess.run(
            ["netplan", "apply"],
            capture_output=True,
            text=True
        )

        # Si el código de salida es 0, fue exitoso  
        # If return code is 0, it was successful
        if result.returncode == 0:
            return True, None

        # Si hubo error, devuelve False y el mensaje  
        # If there was an error, return False and the message
        return False, result.stderr.strip()

    except Exception as e:
        # Si ocurre una excepción, devuelve False y el error  
        # If an exception occurs, return False and the error
        return False, str(e)


def gen_interface_config(user, date):
    try:
        path = "/var/www/config_running/interfaces.yml"

        # Verifica si el archivo YAML tiene estructura compatible con Netplan  
        # Checks if the YAML file has a structure compatible with Netplan
        syntax_ok = check_yml_syntax(path)

        # Verifica si la configuracion de red del YAML es aceptada por  Netplan  
        # Checks if the YAML network configuration is accepted by Netplan
        is_valid, error_msg = validate_netplan_file(path)

        # Si alguno devuelve None, False o falla, se marca como "fail" y se detiene el script  
        # If any returns None, False, or fails, mark as "fail" and stop the script
        if syntax_ok is None or is_valid is None or not syntax_ok or not is_valid:
            task_update_json(date, "gen_json_interface_config_verify", "fail")
            exit()

        # Si ambos pasan, se marca como "success"
        task_update_json(date, "gen_json_interface_config_verify", "success")

    except Exception:
        task_update_json(date, "gen_json_interface_config_verify", "fail")
        exit()

    try:
        #obtiene el archivo de configuracion actual de netplan
        # Retrieves the current Netplan configuration file
        current_file = get_netplan_files()

        #copia el archivo candidato que queremos aplicar sobre el archivo actual de configuracion en netplan
        # Copies the candidate file we want to apply over the current Netplan configuration file
        copy_netplan_file_config(path, current_file)

        # Aplica la configuración de red definida en los archivos YAML de Netplan  
        # Applies the network configuration defined in Netplan YAML files
        apply_netplan_config()

        # Si todo se ejecuta correctamente, se marca como "success"
        task_update_json(date, "gen_json_interface_config_netplanApply", "success")

    except Exception:
        # Si algo falla en esta parte, se marca como "fail"
        task_update_json(date, "gen_json_interface_config_netplanApply", "fail")
        exit()

