#!/bin/bash

# Directorio de destino
DEST_DIR="/var/www/backend"

# 1️ Crear el directorio si no existe
if [ ! -d "$DEST_DIR" ]; then
  echo "Creando directorio $DEST_DIR..."
  mkdir -p "$DEST_DIR"
else
  # Si existe, borrar su contenido
  echo "El directorio $DEST_DIR ya existe. Borrando contenido..."
  rm -rf "$DEST_DIR"/*
fi

# 2️ Copiar el contenido de ../backend al destino
echo "Copiando ../backend a $DEST_DIR..."
cp -r ../backend/* "$DEST_DIR/"

# 3️ Finalizado
echo "Instalación del backend completada."
