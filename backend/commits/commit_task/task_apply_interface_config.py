import shutil
import subprocess
import os

def get_existing_netplan_file():
    # Busca el archivo YAML existente en /etc/netplan  
    # Finds the existing YAML file in /etc/netplan
    try:
        files = [f for f in os.listdir("/etc/netplan") if f.endswith(".yaml") or f.endswith(".yml")]
        if len(files) == 1:
            return files[0]
        elif len(files) > 1:
            # Si hay más de uno, elige el primero (puedes ajustar esto si quieres lógica más precisa)  
            # If more than one, pick the first (you can adjust this if needed)
            return files[0]
        else:
            return None
    except Exception:
        return None

def apply_netplan_config(source_path):
    # Aplica el archivo YAML especificado como configuración de red  
    # Applies the specified YAML file as network configuration
    try:
        existing_file = get_existing_netplan_file()
        if not existing_file:
            print("❌ No se encontró ningún archivo YAML en /etc/netplan / No YAML file found in /etc/netplan")
            return False

        destination_path = os.path.join("/etc/netplan", existing_file)

        # Sobrescribe el archivo existente con el nuevo  
        # Overwrites the existing file with the new one
        shutil.copy2(source_path, destination_path)

        # Aplica la configuración con netplan  
        # Applies the configuration using netplan
        result = subprocess.run(
            ["netplan", "apply"],
            capture_output=True,
            text=True
        )

        if result.returncode != 0:
            print(f"❌ Error al aplicar Netplan / Netplan apply failed:\n{result.stderr.strip()}")
            return False

        print("✅ Configuración aplicada correctamente / Configuration applied successfully")
        return True

    except Exception as e:
        print(f"❌ Fallo inesperado / Unexpected failure:\n{str(e)}")
        return False

def main():
    source_path = "/var/www/config_running/interfaces2.yml"
    apply_netplan_config(source_path)


main()