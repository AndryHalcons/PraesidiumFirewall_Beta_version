#!/bin/bash

# Destination directory / Directorio de destino
DEST_DIR="/var/www/config"

# 1️ Create the directory if it doesn't exist / Crear el directorio si no existe
if [ ! -d "$DEST_DIR" ]; then
  echo "Creating directory $DEST_DIR... / Creando directorio $DEST_DIR..."
  mkdir -p "$DEST_DIR"
else
  # If it exists, delete its contents / Si existe, borrar su contenido
  echo "Directory $DEST_DIR already exists. Deleting contents... / El directorio $DEST_DIR ya existe. Borrando contenido..."
  rm -rf "$DEST_DIR"/*
fi

#  Copy ../data contents to /var/www/config / Copiar el contenido de ../data a /var/www/config
echo "Copying ../data to $DEST_DIR... / Copiando ../data a $DEST_DIR..."
cp -r ../data/* "$DEST_DIR/"

#  Done / Hecho
echo " Script completed. / Script completado."
