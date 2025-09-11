import shutil
import subprocess
import os

def apply_netplan_config(path):
    # Aplica el archivo YAML especificado como configuración de red  
    # Applies the specified YAML file as network configuration
    try:
        # Copia el archivo al directorio de Netplan  
        # Copies the file to Netplan directory
        destination = "/etc/netplan/interfaces2.yml"
        shutil.copy2(path, destination)

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
    path = "/var/www/config_running/interfaces2.yml"
    apply_netplan_config(path)


main()