import os
import json
import gzip

# Parámetros de entrada
data = {
    'user': "andrespraesidium",
    'init_date': '2025-08-29',
    'init_time': '14:24',
    'end_date': '2025-08-29',
    'end_time': '15:24',
    'ip_addr': '',
    'ip_dest': '',
    'sport': '',
    'dport': '',
    'proto': '',
    'action': '',
    'firewall': 'NFTABLES',
    'max_record': '100'
}

def parse_log_line(line):
    timestamp = line.split()[0]
    parsed = {}

    # Extraer handle y acción
    if "nftables" in line:
        parts = line.split("nftables")[1].strip().split()
        if len(parts) >= 3:
            parsed["handle"] = f"{parts[0]} {parts[1]}"
            parsed["action"] = parts[2]

    # Extraer campos IP y puertos
    for key in ["IN", "OUT", "SPT", "DPT"]:
        token = f"{key}="
        if token in line:
            value = line.split(token)[1].split()[0]
            parsed[key] = value

    # Extraer solo IPs y protocolo, ignorando MACSRC/MACDST y MACPROTO
    for part in line.split():
        if part.startswith("SRC=") and not part.startswith("MACSRC="):
            parsed["SRC"] = part.split("=")[1]
        elif part.startswith("DST=") and not part.startswith("MACDST="):
            parsed["DST"] = part.split("=")[1]
        elif part.startswith("PROTO="):
            parsed["PROTO"] = part.split("=")[1]

    return timestamp, parsed

def leer_lineas_archivo(path):
    # Detectar si el archivo está comprimido
    if path.endswith(".gz"):
        with gzip.open(path, "rt", encoding="utf-8", errors="ignore") as f:
            for line in f:
                yield line
    else:
        with open(path, "r", encoding="utf-8", errors="ignore") as f:
            for line in f:
                yield line

def extraer_logs_formateados(data):
    log_dir = "/var/log/praesidium"
    output_path = f"/var/www/backend/checks/system_data/data_monitor_logs/{data['user']}_log_view.json"


    start_str = f"{data['init_date']}T{data['init_time']}"
    end_str = f"{data['end_date']}T{data['end_time']}"
    max_record = int(data['max_record'])

    os.makedirs(os.path.dirname(output_path), exist_ok=True)

    resultado = {}
    count = 0

    # Buscar todos los archivos que empiecen por nftables.log
    for filename in sorted(os.listdir(log_dir)):
        if not filename.startswith("nftables.log"):
            continue

        full_path = os.path.join(log_dir, filename)

        for line in leer_lineas_archivo(full_path):
            ts_prefix = line[:16]
            if not (start_str <= ts_prefix <= end_str):
                continue

            # Filtros condicionales
            if data['ip_addr'] and f"SRC={data['ip_addr']}" not in line:
                continue
            if data['ip_dest'] and f"DST={data['ip_dest']}" not in line:
                continue
            if data['sport'] and f"SPT={data['sport']}" not in line:
                continue
            if data['dport'] and f"DPT={data['dport']}" not in line:
                continue
            if data['proto'] and f"PROTO={data['proto'].upper()}" not in line:
                continue
            if data['action'] and data['action'].upper() not in line:
                continue

            ts, parsed = parse_log_line(line)
            resultado[ts] = parsed
            count += 1

            if count >= max_record:
                break
        if count >= max_record:
            break

    with open(output_path, "w") as out_file:
        json.dump(resultado, out_file, indent=4)

# Ejecutar
extraer_logs_formateados(data)
