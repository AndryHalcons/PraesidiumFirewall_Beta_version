import os
import shutil
import glob

# Ruta de destino donde se guardará el archivo
# Destination path where the file will be saved
destination_path = "/var/www/config/interfaces.yml"

# Comprobar si el archivo ya existe
# Check if the file already exists
if not os.path.exists(destination_path):
    # Buscar archivos YAML en /etc/netplan/
    # Search for YAML files in /etc/netplan/
    netplan_files = glob.glob("/etc/netplan/*.yaml")

    # Si hay al menos uno, copiar el primero
    # If there's at least one, copy the first one
    if netplan_files:
        source_path = netplan_files[0]
        shutil.copy(source_path, destination_path)

        # Ejecutar comandos Bash para asignar propietario y permisos
        # Run Bash commands to set owner and permissions
        os.system(f"chown root:www-data {destination_path}")
        os.system(f"chmod 664 {destination_path}")


