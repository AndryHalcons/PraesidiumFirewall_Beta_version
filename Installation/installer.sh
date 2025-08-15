#!/bin/bash

# Dar permisos de ejecución / Make scripts executable
echo "Dando permisos a los scripts... / Granting execution permissions..."
chmod +x system_requirements.sh
chmod +x web_installation.sh

# Ejecutar system_requirements.sh / Run system_requirements.sh
echo "Instalando dependencias del sistema... / Installing system dependencies..."
./system_requirements.sh

# Ejecutar web_installation.sh / Run web_installation.sh
echo "Instalando archivos web... / Installing web files..."
./web_installation.sh

echo "Instalación completada / Installation completed"

