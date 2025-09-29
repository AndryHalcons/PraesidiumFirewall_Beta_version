
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
        print(f"Error al generar listen_ports: {e}")

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
        print(f"Error al generar SSL_bump: {e}")

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
        print(f"Error al generar Safe_ports: {e}")






def gen_squid_config(user, date):
    src_json = "/var/www/config_running/squid_config/squid_policies.json"
    gen_01_acl_profiles(user,date,src_json)
    gen_02_listen_ports(user,date,src_json)
    gen_03_ssl_bump(user,date,src_json)
    gen_04_safe_ports(user,date,src_json)
    #gen_05_ip_list(user,date,src_json)
    #gen_06_policies(user,date,src_json)


gen_squid_config("prueba1", "date1")