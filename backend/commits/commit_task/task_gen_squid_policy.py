import json
import subprocess
import shutil
import os
from collections import defaultdict
from task_update_json import task_update_json



def gen_01_acl_profiles(user, date, src_json):
    # Ruta del archivo fuente JSON  
    # Source JSON file path  

    # Ruta del archivo destino de configuración ACL  
    # Destination ACL config file path  
    dst = '/var/www/config_running/squid_config/squid_folder/conf.d/01_acl_profiles.conf'

    try:
        # Leer el archivo JSON  
        # Read the JSON file  
        with open(src_json, 'r') as f:
            data = json.load(f)

        # Extraer la lista de perfiles de URL  
        # Extract url_profile list  
        profiles = data.get('squid', {}).get('url_profile', [])

        # Encabezado del archivo de configuración  
        # Header for the config file  
        header = (
            "###############################################################\n"
            "####################### acl_profiles.conf #####################\n"
            "###############################################################\n\n"
        )

        acl_lines = []
        for profile in profiles:
            rule = profile.get('rule', {})
            name = rule.get('name')
            type_ = rule.get('type')
            file = rule.get('file')

            # Generar línea ACL  
            # Generate ACL line  
            acl_line = f'acl {name} {type_} "/etc/squid/conf.d/domain_list/{file}"'
            acl_lines.append(acl_line)

        # Escribir en el archivo de destino  
        # Write to destination file  
        with open(dst, 'w') as f:
            f.write(header)
            f.write('\n'.join(acl_lines) + '\n')

    except Exception as e:
        task_update_json(date, "gen_squid_acl_profiles", "fail")
        exit()

def gen_02_listen_ports(user, date, src_json):
    dst = '/var/www/config_running/squid_config/squid_folder/conf.d/02_listen_ports.conf'

    try:
        with open(src_json, 'r') as f:
            data = json.load(f)

        ports = data.get('squid', {}).get('url_listen_ports', [])

        header = (
            "###############################################################\n"
            "####################### listen_ports.conf #####################\n"
            "###############################################################\n\n"
        )

        lines = []
        for entry in ports:
            rule = entry.get('rule', {})
            port = rule.get('port')
            cert = rule.get('cert')
            key = rule.get('key')
            iface_ip = rule.get('iface_ip')

            # Construir dirección de escucha  
            # Build listen address  
            if iface_ip:
                listen = f'{iface_ip}:{port}'
            else:
                listen = port

            line = f'http_port {listen} ssl-bump cert=/etc/squid/conf.d/certs/{cert} key=/etc/squid/conf.d/certs/{key}'
            lines.append(line)

        with open(dst, 'w') as f:
            f.write(header)
            f.write('\n'.join(lines) + '\n')

    except Exception as e:
        task_update_json(date, "gen_squid_listen_ports", "fail")
        exit()

def gen_03_ssl_bump(user, date, src_json):
    # Ruta del archivo destino  
    # Destination config file path  
    dst = '/var/www/config_running/squid_config/squid_folder/conf.d/03_ssl_bump.conf'

    try:
        # Encabezado del archivo de configuración  
        # Header for the config file  
        header = (
            "###############################################################\n"
            "####################### SSL_bump.conf #########################\n"
            "###############################################################\n\n"
        )

        # Línea de configuración SSL-Bump  
        # SSL-Bump configuration line  
        line = "ssl_bump bump all          # descifra todo"

        # Escribir en el archivo destino  
        # Write to the destination file  
        with open(dst, 'w') as f:
            f.write(header)
            f.write(line + '\n')

    except Exception as e:
        task_update_json(date, "gen_squid_ssl_bump", "fail")
        exit()

def gen_04_safe_ports(user, date, src_json):
    # Ruta del archivo destino  
    # Destination config file path  
    dst = '/var/www/config_running/squid_config/squid_folder/conf.d/04_safe_ports.conf'

    try:
        # Leer el archivo JSON fuente  
        # Read the source JSON file  
        with open(src_json, 'r') as f:
            data = json.load(f)

        # Extraer la lista de perfiles de puerto  
        # Extract port profile list  
        profiles = data.get('squid', {}).get('url_port_profile', [])

        # Encabezado del archivo de configuración  
        # Header for the config file  
        header = (
            "###############################################################\n"
            "####################### Safe_ports.conf ######################\n"
            "###############################################################\n\n"
        )

        lines = []
        for entry in profiles:
            rule = entry.get('rule', {})
            name = rule.get('name')
            ports = rule.get('Port', '')

            for port in ports.split(','):
                port = port.strip()
                if port == '80':
                    comment = '          # http'
                elif port == '443':
                    comment = '         # https'
                else:
                    comment = ''
                line = f'acl {name} port {port}{comment}'
                lines.append(line)

        # Escribir en el archivo destino  
        # Write to the destination file  
        with open(dst, 'w') as f:
            f.write(header)
            f.write('\n'.join(lines) + '\n')

    except Exception as e:
        task_update_json(date, "gen_squid_safe_ports", "fail")
        exit()

def gen_05_ip_list(user, date, src_json):
    # Ruta del archivo destino  
    # Destination config file path  
    dst = '/var/www/config_running/squid_config/squid_folder/conf.d/05_ip_list.conf'

    try:
        # Leer el archivo JSON fuente  
        # Read the source JSON file  
        with open(src_json, 'r') as f:
            data = json.load(f)

        # Extraer la lista de perfiles de red  
        # Extract network profile list  
        profiles = data.get('squid', {}).get('url_networks_list_profile', [])

        # Encabezado del archivo de configuración  
        # Header for the config file  
        header = (
            "###############################################################\n"
            "####################### ip_list.conf #########################\n"
            "###############################################################\n\n"
        )

        lines = []
        for entry in profiles:
            rule = entry.get('rule', {})
            name = rule.get('name')
            type_ = rule.get('type')
            file = rule.get('file')

            # Construir línea ACL  
            # Build ACL line  
            acl_line = f'acl {name} {type_} "/etc/squid/conf.d/ip_list/{file}"'
            lines.append(acl_line)

        # Escribir en el archivo destino  
        # Write to the destination file  
        with open(dst, 'w') as f:
            f.write(header)
            f.write('\n'.join(lines) + '\n')

    except Exception as e:
        task_update_json(date, "gen_squid_ip_list", "fail")
        exit()

def gen_06_policies(user, date, src_json):
    # Ruta del archivo destino  
    # Destination config file path  
    dst = '/var/www/config_running/squid_config/squid_folder/conf.d/06_policies.conf'

    try:
        # Leer el archivo JSON fuente  
        # Read the source JSON file  
        with open(src_json, 'r') as f:
            data = json.load(f)

        # Extraer la lista de políticas  
        # Extract policy list  
        policies = data.get('squid', {}).get('url_policies', [])

        # Encabezado del archivo de configuración  
        # Header for the config file  
        header = (
            "###############################################################\n"
            "####################### policies.conf #########################\n"
            "###############################################################\n\n"
        )

        lines = []

        for entry in policies:
            rule = entry.get('rule', {})
            if rule.get('enable') != "true":
                continue  # Ignorar reglas desactivadas / Skip disabled rules

            action = rule.get('action', 'deny').strip()
            options = rule.get('options', '').strip()
            ip_group = rule.get('ip_addr_group', '').strip()
            profile = rule.get('profile', '').strip()

            negate_ip = rule.get('negate_ip_addr', '').strip().lower() == "true"
            negate_profile = rule.get('negate_profile', '').strip().lower() == "true"

            # Construir condiciones  
            # Build conditions
            conditions = []

            if ip_group:
                ip_cond = f"!{ip_group}" if negate_ip else ip_group
                conditions.append(ip_cond)

            if profile:
                profile_cond = f"!{profile}" if negate_profile else profile
                conditions.append(profile_cond)

            # Construir línea http_access  
            # Build http_access line
            condition_str = ' '.join(conditions)
            line = f"http_access {action} {options} {condition_str}".strip()
            lines.append(line)

        # Escribir en el archivo destino  
        # Write to destination file  
        with open(dst, 'w') as f:
            f.write(header)
            f.write('\n'.join(lines) + '\n')

    except Exception as e:
        task_update_json(date, "gen_squid_policies", "fail")
        exit()

def copy_certs(user, date, src_json):
    # Directorio de origen de los certificados  
    # Source directory for certs and keys  
    src_dir = '/var/www/config/certs/'

    # Directorio de destino  
    # Destination directory  
    dst_dir = '/var/www/config_running/squid_config/squid_folder/conf.d/certs/'

    try:
        # Leer el archivo JSON fuente  
        # Read the source JSON file  
        with open(src_json, 'r') as f:
            data = json.load(f)

        # Extraer la lista de puertos de escucha  
        # Extract listen ports list  
        ports = data.get('squid', {}).get('url_listen_ports', [])

        for entry in ports:
            rule = entry.get('rule', {})
            cert = rule.get('cert')
            key = rule.get('key')

            # Copiar archivo de certificado si existe  
            # Copy cert file if present  
            if cert:
                src_cert_path = os.path.join(src_dir, cert)
                dst_cert_path = os.path.join(dst_dir, cert)
                if os.path.isfile(src_cert_path):
                    shutil.copy2(src_cert_path, dst_cert_path)
                else:
                    print(f"Certificado no encontrado: {src_cert_path}")
                    task_update_json(date, "gen_squid_certs_exist", "fail")
                    exit()

            # Copiar archivo de clave si existe  
            # Copy key file if present  
            if key:
                src_key_path = os.path.join(src_dir, key)
                dst_key_path = os.path.join(dst_dir, key)
                if os.path.isfile(src_key_path):
                    shutil.copy2(src_key_path, dst_key_path)
                else:
                    task_update_json(date, "gen_squid_key_exist", "fail")
                    exit()

    except Exception as e:
        task_update_json(date, "gen_squid_mv_certs", "fail")
        exit()
"""
def verify_config(user, date):
    # Directorios involucrados  
    # Involved directories  
    etc_squid = "/etc/squid"
    temp_backup = f"/tmp/squid_backup_{user}_{date}"
    candidate_config = "/var/www/config_running/squid_config/squid_folder"

    try:
        # 1. Crear backup temporal  
        # 1. Create temporary backup  
        if os.path.exists(temp_backup):
            shutil.rmtree(temp_backup)
        shutil.copytree(etc_squid, temp_backup)

        # 2. Limpiar /etc/squid y copiar nueva config  
        # 2. Clean /etc/squid and copy new config  
        for item in os.listdir(etc_squid):
            path = os.path.join(etc_squid, item)
            if os.path.isdir(path):
                shutil.rmtree(path)
            else:
                os.remove(path)
        shutil.copytree(candidate_config, etc_squid, dirs_exist_ok=True)

        # 3. Validar configuración con squid -k parse  
        # 3. Validate configuration using squid -k parse  
        result = subprocess.run(
            ["squid", "-k", "parse", "-f", os.path.join(etc_squid, "squid.conf")],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )

        # 4. Restaurar backup independientemente del resultado  
        # 4. Restore backup regardless of result  
        for item in os.listdir(etc_squid):
            path = os.path.join(etc_squid, item)
            if os.path.isdir(path):
                shutil.rmtree(path)
            else:
                os.remove(path)
        shutil.copytree(temp_backup, etc_squid, dirs_exist_ok=True)

        if result.returncode != 0:
            # 5. Registrar fallo en la verificación  
            # 5. Register verification failure  
            task_update_json(date, "gen_squid_verify", "fail")
        else:
            # 6. Registrar éxito en la verificación  
            # 6. Register verification success  
            task_update_json(date, "gen_squid_verify", "success")

        # 7. Eliminar backup temporal  
        # 7. Delete temporary backup  
        shutil.rmtree(temp_backup)

    except Exception as e:
        # 8. Registrar error inesperado  
        # 8. Register unexpected error  
        task_update_json(date, "gen_squid_verify", "fail")
"""
def verify_config(user, date):
    # Directorios involucrados  
    # Involved directories  
    etc_squid = "/etc/squid"
    temp_backup = f"/tmp/squid_backup_{user}_{date}"
    candidate_config = "/var/www/config_running/squid_config/squid_folder"
    output_path = f"/var/www/config/commit_history/commit_{user}_{date}"
    output_file = os.path.join(output_path, "squid_parse.txt")

    try:
        # 1. Crear backup temporal  
        # 1. Create temporary backup  
        if os.path.exists(temp_backup):
            shutil.rmtree(temp_backup)
        shutil.copytree(etc_squid, temp_backup)

        # 2. Limpiar /etc/squid y copiar nueva config  
        # 2. Clean /etc/squid and copy new config  
        for item in os.listdir(etc_squid):
            path = os.path.join(etc_squid, item)
            if os.path.isdir(path):
                shutil.rmtree(path)
            else:
                os.remove(path)
        shutil.copytree(candidate_config, etc_squid, dirs_exist_ok=True)

        # 3. Validar configuración con squid -k parse  
        # 3. Validate configuration using squid -k parse  
        result = subprocess.run(
            ["squid", "-k", "parse", "-f", os.path.join(etc_squid, "squid.conf")],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )

        # 4. Guardar salida en archivo  
        # 4. Save output to file  
        os.makedirs(output_path, exist_ok=True)
        with open(output_file, "w") as f:
            f.write("STDOUT:\n")
            f.write(result.stdout)
            f.write("\nSTDERR:\n")
            f.write(result.stderr)

        # 5. Restaurar backup independientemente del resultado  
        # 5. Restore backup regardless of result  
        for item in os.listdir(etc_squid):
            path = os.path.join(etc_squid, item)
            if os.path.isdir(path):
                shutil.rmtree(path)
            else:
                os.remove(path)
        shutil.copytree(temp_backup, etc_squid, dirs_exist_ok=True)

        if result.returncode != 0:
            # 6. Registrar fallo en la verificación  
            # 6. Register verification failure  
            task_update_json(date, "gen_squid_verify", "fail")
        else:
            # 7. Registrar éxito en la verificación  
            # 7. Register verification success  
            task_update_json(date, "gen_squid_verify", "success")

        # 8. Eliminar backup temporal  
        # 8. Delete temporary backup  
        shutil.rmtree(temp_backup)

    except Exception as e:
        # 9. Registrar error inesperado  
        # 9. Register unexpected error  
        task_update_json(date, "gen_squid_verify", "fail")


def gen_squid_config(user, date):
    src_json = "/var/www/config_running/squid_config/squid_policies.json"
    gen_01_acl_profiles(user,date,src_json)
    gen_02_listen_ports(user,date,src_json)
    gen_03_ssl_bump(user,date,src_json)
    gen_04_safe_ports(user,date,src_json)
    gen_05_ip_list(user,date,src_json)
    gen_06_policies(user,date,src_json)
    copy_certs(user,date,src_json)
    verify_config(user, date)

