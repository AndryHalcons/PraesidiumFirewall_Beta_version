import json
import subprocess
import shutil
import os
from collections import defaultdict
from task_update_json import task_update_json





def apply_squid_config(user, date):
    # Directorios involucrados  
    # Involved directories  
    etc_squid = "/etc/squid"
    candidate_config = "/var/www/config_running/squid_config/squid_folder"
    temp_backup = f"/tmp/squid_apply_backup_{user}_{date}"
    output_path = f"/var/www/config/commit_history/commit_{user}_{date}"
    status_file = os.path.join(output_path, "service_squid.txt")

    try:
        # 1. Crear backup temporal de la configuración actual  
        # 1. Create temporary backup of current configuration  
        if os.path.exists(temp_backup):
            shutil.rmtree(temp_backup)
        shutil.copytree(etc_squid, temp_backup)

        # 2. Limpiar /etc/squid  
        # 2. Clean /etc/squid  
        for item in os.listdir(etc_squid):
            path = os.path.join(etc_squid, item)
            if os.path.isdir(path):
                shutil.rmtree(path)
            else:
                os.remove(path)

        # 3. Copiar nueva configuración  
        # 3. Copy new configuration  
        shutil.copytree(candidate_config, etc_squid, dirs_exist_ok=True)

        # 4. Reiniciar servicio Squid  
        # 4. Restart Squid service  
        subprocess.run(["systemctl", "restart", "squid"], check=True)

        # 5. Verificar estado del servicio  
        # 5. Check service status  
        status_check = subprocess.run(
            ["systemctl", "status", "squid"],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )

        # 6. Guardar salida del estado en archivo  
        # 6. Save service status output to file  
        os.makedirs(output_path, exist_ok=True)
        with open(status_file, "w") as f:
            f.write("STDOUT:\n")
            f.write(status_check.stdout)
            f.write("\nSTDERR:\n")
            f.write(status_check.stderr)

        if status_check.returncode != 0:
            # 7. Restaurar configuración anterior si el servicio falla  
            # 7. Restore previous configuration if service fails  
            for item in os.listdir(etc_squid):
                path = os.path.join(etc_squid, item)
                if os.path.isdir(path):
                    shutil.rmtree(path)
                else:
                    os.remove(path)
            shutil.copytree(temp_backup, etc_squid, dirs_exist_ok=True)
            subprocess.run(["systemctl", "restart", "squid"], check=True)
            task_update_json(date, "gen_squid_apply", "fail")
        else:
            # 8. Registrar éxito en la aplicación  
            # 8. Register successful application  
            task_update_json(date, "gen_squid_apply", "success")

        # 9. Eliminar backup temporal  
        # 9. Delete temporary backup  
        shutil.rmtree(temp_backup)

    except Exception as e:
        # 10. Registrar error inesperado  
        # 10. Register unexpected error  
        task_update_json(date, "gen_squid_apply", "fail")
