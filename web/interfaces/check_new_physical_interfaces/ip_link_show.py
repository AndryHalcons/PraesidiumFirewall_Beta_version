#!/usr/bin/env python3
import subprocess  # Used to run system commands / Usado para ejecutar comandos del sistema
import re          # Used for regular expressions / Usado para expresiones regulares

# Function to get interface names from 'ip link show'
# Función para obtener los nombres de las interfaces desde 'ip link show'
def get_ip_link_names():
    # Run the 'ip link show' command
    # Ejecuta el comando 'ip link show'
    result = subprocess.run(
        ['ip', 'link', 'show'],
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True
    )

    output = result.stdout.strip()  # Clean standard output / Limpia la salida estándar
    error = result.stderr.strip()   # Clean error output / Limpia la salida de error
    code = result.returncode        # Get exit code / Obtiene el código de salida

    # If the command failed, print and return empty list
    # Si el comando falla, imprime y devuelve lista vacía
    if code != 0:
        print({"error": error})
        return []

    # Use regex to find interface names at the beginning of each block
    # Usa expresiones regulares para encontrar los nombres de interfaz al inicio de cada bloque
    # Example line: "2: ens18: <BROADCAST,...>"
    matches = re.findall(r'^\d+: ([^:]+):', output, re.MULTILINE)

    # Print the list of interface names / Imprime la lista de nombres de interfaz format ['lo', 'ens18', 'ens19', 'ens20', 'ens21']
    return matches  # Return the list / Devuelve la lista

