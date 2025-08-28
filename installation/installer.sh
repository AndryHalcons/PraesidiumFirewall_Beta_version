#!/bin/bash

# Dar permisos de ejecución / Make scripts executable
echo "Dando permisos a los scripts... / Granting execution permissions..."
chmod +x uninstall_unnecessary.sh
chmod +x system_requirements.sh
chmod +x web_installation.sh
chmod +x initial_data.sh
chmod +x install_backend.sh
chmod +x system_configuration.sh
chmod +x uninstall_unnecessary.sh
chmod +x permissions.sh
chmod +x install_bpfilter.sh
chmod +x configure_bpfilter.sh
chmod +x configure_logs.sh



# Ejecutar system_requirements.sh / Run system_requirements.sh
echo "desinstalando  dependencias innecesarias... / Installing system dependencies..."
./uninstall_unnecessary.sh
echo "✅ Desinstalando uninstall_unnecessary.shcompletada / uninstall uninstall_unnecessary.sh completed"

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

# Ejecutar install_backend.sh / Run install_backend.sh
echo "Instalando install_backend... / Installing install_backend..."
./install_backend.sh
echo "✅ Instalación install_backend.sh completada / Installation install_backend.sh completed"

# Ejecutar system_configuration.sh / Run system_configuration.sh
echo "Instalando system_configuration... / Installing system_configuration..."
./system_configuration.sh
echo "✅ Instalación system_configuration.sh completada / Installation system_configuration.sh completed"


# Ejecutar system_configuration.sh / Run system_configuration.sh
echo "Generando permisos de ejecucion / Generating execution permissions..."
./permissions.sh
echo "✅ Instalación permissions.sh completada / Installation permissions.sh completed"



# Ejecutar install_bpfilter.sh / Run install_bpfilter.sh
echo "instlaando bpfilter / Installing bpfilter"
#./install_bpfilter.sh
echo "✅ Instalación install_bpfilter.sh completada / Installation install_bpfilter.sh completed"


# Ejecutar configure_bpfilter.sh / Run configure_bpfilter.sh
echo "Configurando bpfilter / Configuring bpfilter"
#./configure_bpfilter.sh
echo "✅ Instalación install_bpfilter.sh completada / Installation install_bpfilter.sh completed"


# Ejecutar configure_bpfilter.sh / Run configure_bpfilter.sh
echo "Configurando configure_logs.sh / Configuring configure_logs.sh"
./configure_logs.sh
echo "✅ Instalación configure_logs.sh completada / Installation configure_logs.sh completed"