#!/bin/bash

# Directorios de destino / Destination directories
DEST_CONFIG="/var/www/config"
DEST_RUNNING="/var/www/config_running"

# 1 Preparar /var/www/config
# Prepare /var/www/config
if [ ! -d "$DEST_CONFIG" ]; then
  echo "Creating directory $DEST_CONFIG... / Creando directorio $DEST_CONFIG..."
  mkdir -p "$DEST_CONFIG"
else
  echo "Directory $DEST_CONFIG already exists. Deleting contents... / El directorio $DEST_CONFIG ya existe. Borrando contenido..."
  rm -rf "$DEST_CONFIG"/*
fi

# 2 Preparar /var/www/config_running
# Prepare /var/www/config_running
if [ ! -d "$DEST_RUNNING" ]; then
  echo "Creating directory $DEST_RUNNING... / Creando directorio $DEST_RUNNING..."
  mkdir -p "$DEST_RUNNING"
else
  echo "Directory $DEST_RUNNING already exists. Deleting contents... / El directorio $DEST_RUNNING ya existe. Borrando contenido..."
  rm -rf "$DEST_RUNNING"/*
fi

# 3 Copiar ../data a /var/www/config
# Copy ../data to /var/www/config
echo "Copying ../data to $DEST_CONFIG... / Copiando ../data a $DEST_CONFIG..."
cp -r ../data/* "$DEST_CONFIG/"

# 4 Copiar ../data_running a /var/www/config_running
# Copy ../data_running to /var/www/config_running
echo "Copying ../data_running to $DEST_RUNNING... / Copiando ../data_running a $DEST_RUNNING..."
cp -r ../data_running/* "$DEST_RUNNING/"

# 5 Finalizado
# Done
echo "Script completed. / Script completado."
