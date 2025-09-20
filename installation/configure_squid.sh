#!/bin/bash

set -e

copy_config() {
  echo "🔧 Configurando Squid (reemplazo completo)..."

  SRC_DIR="$(dirname "$0")/configure_squid"
  DEST_DIR="/etc/squid"

  # Verificar permisos
  if [ "$EUID" -ne 0 ]; then
    echo "❌ Este script debe ejecutarse como root."
    exit 1
  fi

  # Borrar contenido previo
  echo "🧹 Limpiando $DEST_DIR..."
  rm -rf "$DEST_DIR"/*

  # Copiar nuevo contenido
  echo "📁 Copiando archivos desde $SRC_DIR a $DEST_DIR..."
  cp -r "$SRC_DIR/"* "$DEST_DIR/"

  # Reiniciar servicio
  echo "🔄 Reiniciando Squid..."
  systemctl restart squid

  echo "✅ Configuración de Squid aplicada correctamente."
}

# Ejecutar la función
copy_config
