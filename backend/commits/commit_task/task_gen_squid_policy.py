
import json
import subprocess
import os
import convert_nftables
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
        print(f"Error al generar ACL profiles: {e}")










def gen_squid_config(user, date, src_json):
    src_json = "/var/www/config_running/squid_config/squid_policies.json"
    gen_01_acl_profiles(user,date)
    #gen_02_listen_ports(user,date)
    #gen_03_ssl_bump(user,date)
    #gen_04_safe_ports(user,date)
    #gen_05_ip_list(user,date)
    #gen_06_policies(user,date)


gen_squid_config("prueba1", "date1")