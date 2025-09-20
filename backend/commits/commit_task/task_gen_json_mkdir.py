import os
import shutil
from task_update_json import task_update_json




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


