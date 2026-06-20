#!/bin/bash

# Directorio de destino
DEST_DIR="/var/www/backend"

# 1 Crear el directorio si no existe
if [ ! -d "$DEST_DIR" ]; then
  echo "Creando directorio $DEST_DIR..."
  mkdir -p "$DEST_DIR"
else
  # Si existe, eliminarlo completamente y volver a crearlo
  echo "El directorio $DEST_DIR ya existe. Eliminando y recreando..."
  rm -rf "$DEST_DIR"
  mkdir -p "$DEST_DIR"
fi

# 2 Copiar el contenido de ../backend al destino
echo "Copiando ../backend a $DEST_DIR..."
cp -r ../backend/* "$DEST_DIR/"

# 3 Finalizado
echo "Instalación del backend completada."

# solo desarrollo
bash permissions.sh
