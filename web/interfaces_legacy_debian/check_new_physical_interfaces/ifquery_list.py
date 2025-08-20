#!/usr/bin/env python3
import subprocess
import json        

# Function to get the names of all network interfaces using 'ifquery'
# Función para obtener los nombres de todas las interfaces de red usando 'ifquery'
def get_ifquery_names():
    # Run the 'ifquery' command with JSON output
    # Ejecuta el comando 'ifquery' con salida en formato JSON
    result = subprocess.run(
        ['ifquery', '-a', '--format=json'],
        stdout=subprocess.PIPE,   # Capture standard output / Captura la salida estándar
        stderr=subprocess.PIPE,   # Capture error output / Captura la salida de error
        text=True                 # Return output as string / Devuelve la salida como cadena
    )

    output = result.stdout.strip()  # Clean standard output / Limpia la salida estándar
    error = result.stderr.strip()   # Clean error output / Limpia la salida de error
    code = result.returncode        # Get exit code / Obtiene el código de salida

    # If the command failed, print and return the error
    # Si el comando falla, imprime y devuelve el error
    if code != 0:
        print(json.dumps({"error": error}))
        return []

    try:
        # Parse the JSON output into a Python object
        # Analiza la salida JSON en un objeto de Python
        interfaces = json.loads(output)

        # Extract only the 'name' field from each interface
        # Extrae solo el campo 'name' de cada interfaz
        names = [iface["name"] for iface in interfaces if "name" in iface]

        # Print the list of names / Imprime la lista de nombres
        return names  # Return the list / Devuelve la lista
    except json.JSONDecodeError as e:
        # If JSON parsing fails, print and return the error
        # Si falla el análisis del JSON, imprime y devuelve el error
        #si todo sale bien devuelve las interfaces en este formato ['lo', 'ens18', 'ens20']
        print(json.dumps({"error": str(e)}))
        return []

