#!/bin/bash

# Dar permisos de ejecución / Make scripts executable
echo "Dando permisos a los scripts... / Granting execution permissions..."
chmod +x system_requirements.sh
chmod +x web_installation.sh
chmod +x initial_data.sh

# Ejecutar system_requirements.sh / Run system_requirements.sh
echo "Instalando dependencias del sistema... / Installing system dependencies..."
./system_requirements.sh
echo "✅ Instalación system_requirements.sh completada / Installation system_requirements.sh completed"

# Ejecutar web_installation.sh / Run web_installation.sh
echo "Instalando archivos web... / Installing web files..."
./web_installation.sh
echo "✅ Instalación web_installation.sh completada / Installation web_installation.sh completed"

# Ejecutar initial_data.sh / Run initial_data.sh
echo "Instalando initial_data... / Installing initial_data..."
./initial_data.sh
echo "✅ Instalación initial_data.sh completada / Installation initial_data.sh completed"

