#!/usr/bin/env python3
"""
###############################################################################
  Extractor XML de sesiones conntrack por usuario
  Per-user conntrack sessions XML extractor

  Este script se ejecuta desde la WebGUI mediante una excepción sudo controlada.
  This script is executed from the WebGUI through a controlled sudo exception.

  Responsabilidades / Responsibilities:
    - Ejecutar conntrack en modo listado XML.
      Run conntrack in XML listing mode.
    - Validar que el usuario recibido tiene formato seguro.
      Validate that the received username has a safe format.
    - Escribir un snapshot XML independiente por usuario Praesidium.
      Write an independent XML snapshot per Praesidium user.
    - Reemplazar el fichero de forma atómica para evitar lecturas parciales.
      Replace the file atomically to avoid partial reads.

  Límites de seguridad / Security boundaries:
    - No acepta comandos arbitrarios.
      It does not accept arbitrary commands.
    - No usa shell=True ni concatena comandos de shell.
      It does not use shell=True or concatenate shell commands.
    - No escribe fuera del directorio fijo OUTPUT_DIR.
      It does not write outside the fixed OUTPUT_DIR directory.
###############################################################################
"""

import argparse
import os
import re
import subprocess
import sys
import tempfile
import xml.etree.ElementTree as ET

# Directorio runtime donde la WebGUI puede leer snapshots generados.
# Runtime directory where the WebGUI can read generated snapshots.
OUTPUT_DIR = "/var/www/config_running/monitor_session"

# Ruta absoluta del binario conntrack para no depender de PATH.
# Absolute conntrack binary path so execution does not depend on PATH.
CONNTRACK = "/usr/sbin/conntrack"

# Sólo permitimos nombres simples de usuario para construir el nombre del fichero.
# Only simple usernames are allowed to build the output filename.
USERNAME_RE = re.compile(r"^[A-Za-z0-9_-]{1,64}$")


# Lee y valida los argumentos permitidos del extractor.
# Reads and validates the extractor allowed arguments.
def parse_args():
    parser = argparse.ArgumentParser(description="Extract conntrack sessions as per-user XML")
    parser.add_argument("--user", required=True, help="Praesidium username")
    return parser.parse_args()


# Construye la ruta de salida a partir de un usuario previamente validado.
# Builds the output path from a previously validated username.
def safe_output_path(username: str) -> str:
    # Validación defensiva: evita traversal, espacios y caracteres especiales.
    # Defensive validation: prevents traversal, spaces, and special characters.
    if not USERNAME_RE.fullmatch(username):
        raise ValueError("invalid username")

    # Cada usuario obtiene su propio XML para no pisar sesiones simultáneas.
    # Each user gets its own XML file to avoid overwriting simultaneous sessions.
    return os.path.join(OUTPUT_DIR, f"{username}_session_conntrack.xml")


# Punto principal: ejecuta conntrack, valida XML y escribe el snapshot atómico.
# Main entrypoint: runs conntrack, validates XML, and writes the atomic snapshot.
def main() -> int:
    args = parse_args()
    output_path = safe_output_path(args.user)

    # Asegura que existe el directorio runtime donde se publican los snapshots.
    # Ensures the runtime directory where snapshots are published exists.
    os.makedirs(OUTPUT_DIR, mode=0o755, exist_ok=True)

    # Ejecuta conntrack sin shell; los argumentos son fijos y cerrados.
    # Runs conntrack without shell; arguments are fixed and closed.
    result = subprocess.run(
        [CONNTRACK, "-L", "-o", "xml"],
        text=True,
        capture_output=True,
        timeout=15,
        check=False,
    )

    # conntrack emite el XML por stdout y el resumen por stderr.
    # conntrack emits XML on stdout and the summary on stderr.
    xml_data = result.stdout.strip()
    if result.returncode != 0 and not xml_data:
        sys.stderr.write(result.stderr or "conntrack failed\n")
        return result.returncode or 1

    # Si no hubiera salida, publicamos un documento XML vacío pero válido.
    # If there is no output, publish an empty but valid XML document.
    if not xml_data:
        xml_data = '<?xml version="1.0" encoding="utf-8"?>\n<conntrack>\n</conntrack>\n'

    # Validación estructural antes de reemplazar el fichero visible por la WebGUI.
    # Structural validation before replacing the file visible to the WebGUI.
    try:
        ET.fromstring(xml_data)
    except ET.ParseError as exc:
        sys.stderr.write(f"invalid conntrack XML: {exc}\n")
        if result.stderr:
            sys.stderr.write(result.stderr)
        return 1

    # Escritura atómica: primero temporal en el mismo directorio, luego os.replace().
    # Atomic write: first a temp file in the same directory, then os.replace().
    fd, tmp_path = tempfile.mkstemp(prefix=".session_conntrack.", suffix=".xml", dir=OUTPUT_DIR, text=True)
    try:
        with os.fdopen(fd, "w", encoding="utf-8") as handle:
            handle.write(xml_data)
            if not xml_data.endswith("\n"):
                handle.write("\n")

        # El XML final sólo necesita lectura para la WebGUI.
        # The final XML only needs read access for the WebGUI.
        os.chmod(tmp_path, 0o644)
        os.replace(tmp_path, output_path)
        os.chmod(output_path, 0o644)
    finally:
        # Limpieza defensiva si la operación falla antes del replace.
        # Defensive cleanup if the operation fails before replace.
        if os.path.exists(tmp_path):
            os.unlink(tmp_path)

    # Salida mínima para diagnóstico del endpoint, sin exponerla en la UI final.
    # Minimal output for endpoint diagnostics, not exposed in the final UI.
    print(output_path)
    if result.stderr:
        print(result.stderr.strip())
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
