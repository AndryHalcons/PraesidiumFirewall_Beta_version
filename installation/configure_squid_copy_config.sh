#!/bin/bash
copy_config() {
  echo "🔧 Configurando Squid (reemplazo completo)..."

  SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
  SRC_DIR="$SCRIPT_DIR/configure_squid"
  DEST_DIR="/etc/squid"

  # Verificar permisos
  if [ "$EUID" -ne 0 ]; then
    echo "❌ Este script debe ejecutarse como root."
    exit 1
  fi

  # Crear destino si no existe
  mkdir -p "$DEST_DIR"

  # Borrar contenido previo
  echo "🧹 Limpiando $DEST_DIR..."
  rm -rf "$DEST_DIR"/*

  # Copiar nuevo contenido
  echo "📁 Copiando archivos desde $SRC_DIR a $DEST_DIR..."
  cp -r "${SRC_DIR}/"* "$DEST_DIR/"

  # Propietario y permisos generales
  chown -R proxy:proxy "$DEST_DIR"
  chmod +x "$DEST_DIR"
  chmod +x "$DEST_DIR/conf.d"
  chmod +x "$DEST_DIR/conf.d/certs"

  # Reaplicar permisos de certificados justo antes de reiniciar
  echo "🔐 Reaplicando permisos de certificados..."
  chown proxy:proxy "$DEST_DIR/conf.d/certs/my_new_squid.key"
  chown proxy:proxy "$DEST_DIR/conf.d/certs/my_new_squid.pem"
  chmod 600 "$DEST_DIR/conf.d/certs/my_new_squid.key"
  chmod 644 "$DEST_DIR/conf.d/certs/my_new_squid.pem"

  # Reiniciar servicio
  echo "🔄 Reiniciando Squid..."
  systemctl restart squid

  echo "✅ Configuración de Squid aplicada correctamente."
}

copy_config
