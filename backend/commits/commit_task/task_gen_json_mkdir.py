import os
import shutil
from task_update_json import task_update_json



"""
def copy_to_running(date):
    #directorio origen configuracion  
    #configuration source directory  
    source_dir = '/var/www/config'

    #directorio destino ejecución  
    #destination directory for runtime  
    running_dir = '/var/www/config_running'

    #proceso de copia de archivos  
    #file copy process  
    try:
        os.makedirs(running_dir, exist_ok=True)

        files_to_copy = [
            'interfaces.json',
            'routes.json',
            'rules_nftables_human_viewer.json',
            'rules_bpfilter_human_viewer.json',
            'users.json',
            'alias.json',
            'system_config.json',
        ]

        for filename in files_to_copy:
            source_file = os.path.join(source_dir, filename)
            running_file = os.path.join(running_dir, filename)
            if os.path.exists(source_file):
                shutil.copy2(source_file, running_file)
        task_update_json(date, "gen_json_mkdir_copy_to_running", "success")

    except Exception:
        task_update_json(date, "gen_json_mkdir_copy_to_running", "fail")
"""
"""
def gen_json_mkdir(user, date):
    #directorio origen configuracion  
    #configuration source directory  
    source_dir = '/var/www/config'

    #directorio destino archivos commit  
    #destination directory for commit files  
    target_dir = f'/var/www/config/commit_history/commit_{user}_{date}'

    #proceso de copia de archivos  
    #file copy process  
    try:
        os.makedirs(target_dir, exist_ok=True)

        files_to_copy = [
            'interfaces.json',
            'routes.json',
            'rules_nftables_human_viewer.json',
            'rules_bpfilter_human_viewer.json',
            'users.json',
            'alias.json',
            'system_config.json',
            
        ]

        for filename in files_to_copy:
            source_file = os.path.join(source_dir, filename)
            target_file = os.path.join(target_dir, filename)
            if os.path.exists(source_file):
                shutil.copy2(source_file, target_file)

        # Si todo va bien actualizamos el commit_history.json añadiendo a la entrada success  
        # If everything goes well, update commit_history.json adding success to the entry  
        task_update_json(date, "gen_json_mkdir", "success")
        #funcion que pone los archivos de configuracion tambien en el directorio config_running
        copy_to_running(date)
    except Exception:
        # Si algo falla actualizamos el commit_history.json añadiendo a la entrada fail  
        # If something fails, update commit_history.json adding fail to the entry  
        task_update_json(date, "gen_json_mkdir", "fail")
"""
def copy_to_running(date):
    # Directorio origen configuración  
    # Configuration source directory  
    source_dir = '/var/www/config'

    # Directorio destino ejecución  
    # Destination directory for runtime  
    running_dir = '/var/www/config_running'

    try:
        # Crear el directorio destino si no existe  
        # Create destination directory if it doesn't exist  
        os.makedirs(running_dir, exist_ok=True)

        # Copiar todo el contenido del directorio origen al destino  
        # Copy all contents from source to destination  
        for item in os.listdir(source_dir):
            s = os.path.join(source_dir, item)
            d = os.path.join(running_dir, item)

            # Si es archivo, copiar directamente  
            # If it's a file, copy directly  
            if os.path.isfile(s):
                shutil.copy2(s, d)

            # Si es carpeta, copiar recursivamente  
            # If it's a folder, copy recursively  
            elif os.path.isdir(s):
                if os.path.exists(d):
                    shutil.rmtree(d)
                shutil.copytree(s, d)

        # Actualizar estado como éxito  
        # Update status as success  
        task_update_json(date, "gen_json_mkdir_copy_to_running", "success")

    except Exception:
        # Actualizar estado como fallo  
        # Update status as failure  
        task_update_json(date, "gen_json_mkdir_copy_to_running", "fail")

def gen_json_mkdir(user, date):
    # Directorio origen configuración  
    # Configuration source directory  
    source_dir = '/var/www/config'

    # Directorio destino archivos commit  
    # Destination directory for commit files  
    target_dir = f'/var/www/config/commit_history/commit_{user}_{date}'

    # Proceso de copia de archivos  
    # File copy process  
    try:
        # Crear el directorio destino si no existe  
        # Create destination directory if it doesn't exist  
        os.makedirs(target_dir, exist_ok=True)

        # Copiar todo el contenido del directorio origen al destino  
        # Copy all contents from source to destination  
        for item in os.listdir(source_dir):
            s = os.path.join(source_dir, item)
            d = os.path.join(target_dir, item)

            # Si es archivo, copiar directamente  
            # If it's a file, copy directly  
            if os.path.isfile(s):
                shutil.copy2(s, d)

            # Si es carpeta, copiar recursivamente  
            # If it's a folder, copy recursively  
            elif os.path.isdir(s):
                if os.path.exists(d):
                    shutil.rmtree(d)
                shutil.copytree(s, d)

        # Actualizar commit_history.json como éxito  
        # Update commit_history.json as success  
        task_update_json(date, "gen_json_mkdir", "success")

        # También copiar a config_running  
        # Also copy to config_running  
        copy_to_running(date)

    except Exception:
        # Actualizar commit_history.json como fallo  
        # Update commit_history.json as failure  
        task_update_json(date, "gen_json_mkdir", "fail")


