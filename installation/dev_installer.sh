#!/bin/bash
set -e

# Dar permisos de ejecución / Make scripts executable
echo "Dando permisos a los scripts... / Granting execution permissions..."
chmod +x uninstall_unnecessary.sh
chmod +x system_requirements.sh
chmod +x praesidium_modules_installer/praesidium_modules_main.py
chmod +x praesidium_modules_installer/praesidium_modules_installer.py
chmod +x praesidium_modules_installer/praesidium_modules_lang_installer.py
chmod +x praesidium_modules_installer/praesidium_modules_web_installer.py
chmod +x system_configuration.sh
chmod +x uninstall_unnecessary.sh
chmod +x permissions.sh
chmod +x initial_config.sh
chmod +x install_bpfilter.sh
chmod +x configure_bpfilter.sh
chmod +x configure_logs.sh



# Ejecutar system_requirements.sh / Run system_requirements.sh
echo "desinstalando  dependencias innecesarias... / Installing system dependencies..."
#./uninstall_unnecessary.sh
echo "Desinstalando uninstall_unnecessary.shcompletada / uninstall uninstall_unnecessary.sh completed"

# Ejecutar system_requirements.sh / Run system_requirements.sh
echo "Instalando dependencias del sistema... / Installing system dependencies..."
#./system_requirements.sh
echo "Instalación system_requirements.sh completada / Installation system_requirements.sh completed"

# Ejecutar instalador modular Praesidium / Run Praesidium modular installer
echo "Instalando estructura modular Praesidium... / Installing Praesidium modular structure..."
python3 praesidium_modules_installer/praesidium_modules_main.py
echo "Instalación modular Praesidium completada / Praesidium modular installation completed"

# Ejecutar system_configuration.sh / Run system_configuration.sh
echo "Instalando system_configuration... / Installing system_configuration..."
./system_configuration.sh
echo "Instalación system_configuration.sh completada / Installation system_configuration.sh completed"


# Ejecutar system_configuration.sh / Run system_configuration.sh
echo "Generando permisos de ejecucion / Generating execution permissions..."
./permissions.sh
echo "Instalación permissions.sh completada / Installation permissions.sh completed"



# Ejecutar install_bpfilter.sh / Run install_bpfilter.sh
echo "instlaando bpfilter / Installing bpfilter"
#./install_bpfilter.sh
echo "Instalación install_bpfilter.sh completada / Installation install_bpfilter.sh completed"


# Ejecutar configure_bpfilter.sh / Run configure_bpfilter.sh
echo "Configurando bpfilter / Configuring bpfilter"
#./configure_bpfilter.sh
echo "Instalación install_bpfilter.sh completada / Installation install_bpfilter.sh completed"


# Ejecutar configure_bpfilter.sh / Run configure_bpfilter.sh
echo "Configurando configure_logs.sh / Configuring configure_logs.sh"
./configure_logs.sh
echo "Instalación configure_logs.sh completada / Installation configure_logs.sh completed"

# Ejecutar initial_config.sh al final para regenerar el estado runtime tras initial_data/install_backend.
# Run initial_config.sh at the end to regenerate runtime state after initial_data/install_backend.
echo "Generando configuración inicial de desarrollo... / Generating development initial configuration..."
./initial_config.sh
echo "Instalación initial_config.sh completada / Installation initial_config.sh completed"
