import os
import sys
import json
import gzip

#  Leer el JSON como argumento desde sys.argv[1]
#  Read the JSON input passed as a command-line argument
if len(sys.argv) < 2:
    sys.exit("No se recibió ningún argumento JSON")  # Exit if no argument was provided
    # Exit if no JSON argument was received

try:
    data = json.loads(sys.argv[1])  # Convertir el argumento en un diccionario Python
    # Parse the JSON string into a Python dictionary
except json.JSONDecodeError:
    sys.exit("Error al decodificar el JSON recibido")  # Salir si el JSON está mal formado
    # Exit if the JSON is malformed

#  Función para analizar una línea del log y extraer campos relevantes
#  Function to parse a log line and extract relevant fields
def parse_log_line(line):
    timestamp = line.split()[0]  # Obtener el timestamp inicial (formato YYYY-MM-DDTHH:MM)
    # Extract the timestamp from the beginning of the line
    parsed = {}

    # Extraer el identificador y la acción del log de nftables
    # Extract handle and action from nftables log line
    if "nftables" in line:
        parts = line.split("nftables")[1].strip().split()
        if len(parts) >= 3:
            parsed["handle"] = f"{parts[0]} {parts[1]}"  # Ejemplo: "rule 123"
            parsed["action"] = parts[2]  # Ejemplo: "ACCEPT" o "DROP"

    # Extraer campos de red como interfaz de entrada/salida y puertos
    # Extract network fields like IN/OUT interfaces and ports
    for key in ["IN", "OUT", "SPT", "DPT"]:
        token = f"{key}="
        if token in line:
            value = line.split(token)[1].split()[0]
            parsed[key] = value

    # Extraer solo direcciones IP y protocolo, ignorando MAC y otros campos
    # Extract only IP addresses and protocol, ignoring MAC-related fields
    for part in line.split():
        if part.startswith("SRC=") and not part.startswith("MACSRC="):
            parsed["SRC"] = part.split("=")[1]
        elif part.startswith("DST=") and not part.startswith("MACDST="):
            parsed["DST"] = part.split("=")[1]
        elif part.startswith("PROTO="):
            parsed["PROTO"] = part.split("=")[1]

    return timestamp, parsed  # Devuelve el timestamp y los datos extraídos
    # Return timestamp and parsed data

#  Función para leer líneas de un archivo, incluyendo los comprimidos (.gz)
#  Function to read lines from a file, including compressed ones (.gz)
def leer_lineas_archivo(path):
    if path.endswith(".gz"):
        # Abrir archivo comprimido en modo texto
        # Open compressed file in text mode
        with gzip.open(path, "rt", encoding="utf-8", errors="ignore") as f:
            for line in f:
                yield line
    else:
        # Abrir archivo normal en modo texto
        # Open regular file in text mode
        with open(path, "r", encoding="utf-8", errors="ignore") as f:
            for line in f:
                yield line

#  Función principal que filtra y extrae los logs según los parámetros recibidos
#  Main function that filters and extracts logs based on input parameters
def extraer_logs_formateados(data):
    log_dir = "/var/log/praesidium"  # Directorio donde están los logs rotados
    # Directory containing rotated log files

    # Ruta de salida personalizada por usuario
    # Output path customized by user
    output_path = f"/var/www/backend/checks/system_data/data_monitor_logs/{data['user']}_log_view.json"

    # Construir los rangos de tiempo como strings para comparación
    # Build time range strings for filtering
    start_str = f"{data['init_date']}T{data['init_time']}"
    end_str = f"{data['end_date']}T{data['end_time']}"
    max_record = int(data['max_record'])  # Límite máximo de registros a extraer
    # Maximum number of records to extract

    os.makedirs(os.path.dirname(output_path), exist_ok=True)  # Crear carpeta si no existe
    # Create output directory if it doesn't exist

    resultado = {}  # Diccionario donde se guardarán los logs filtrados
    # Dictionary to store filtered logs
    count = 0  # Contador de registros extraídos
    # Record counter

    # Recorrer todos los archivos que empiezan por nftables.log
    # Iterate over all files starting with nftables.log
    for filename in sorted(os.listdir(log_dir)):
        if not filename.startswith("nftables.log"):
            continue

        full_path = os.path.join(log_dir, filename)

        # Leer línea por línea del archivo
        # Read each line from the file
        for line in leer_lineas_archivo(full_path):
            ts_prefix = line[:16]  # Obtener el timestamp de la línea
            # Extract timestamp prefix from line

            # Filtrar por rango de tiempo
            # Filter by time range
            if not (start_str <= ts_prefix <= end_str):
                continue

            # Aplicar filtros condicionales si están definidos
            # Apply conditional filters if defined
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

            # Parsear la línea y guardar el resultado
            # Parse the line and store the result
            ts, parsed = parse_log_line(line)
            resultado[ts] = parsed
            count += 1

            # Detener si se alcanza el límite
            # Stop if max_record is reached
            if count >= max_record:
                break
        if count >= max_record:
            break

    # Guardar los resultados en un archivo JSON
    # Save results to a JSON file
    with open(output_path, "w") as out_file:
        json.dump(resultado, out_file, indent=4)

#  Ejecutar la función principal con los datos recibidos
#  Run the main function with the received data
extraer_logs_formateados(data)
