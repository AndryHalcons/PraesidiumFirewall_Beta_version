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



download_squid() {
  echo "🌐 Descargando Squid 6.14..."

  SCRIPT_DIR="$(dirname "$(realpath "$0")")"
  TARGET_DIR="$(realpath "$SCRIPT_DIR/../../01_squid")"
  FILE_URL="https://github.com/squid-cache/squid/releases/download/SQUID_6_14/squid-6.14.tar.gz"
  FILE_NAME="squid-6.14.tar.gz"

  mkdir -p "$TARGET_DIR"
  echo "📁 Guardando en: $TARGET_DIR"

  wget -q --show-progress -O "$TARGET_DIR/$FILE_NAME" "$FILE_URL"

  echo "✅ Descarga completada: $TARGET_DIR/$FILE_NAME"
}


extract_squid() {
  echo "📦 Descomprimiendo Squid 6.14..."

  SCRIPT_DIR="$(dirname "$(realpath "$0")")"
  TARGET_DIR="$(realpath "$SCRIPT_DIR/../../01_squid")"
  FILE_NAME="squid-6.14.tar.gz"

  tar -xvzf "$TARGET_DIR/$FILE_NAME" -C "$TARGET_DIR"

  echo "✅ Extracción completada en: $TARGET_DIR"
}


make_squid() {
  echo "🛠️ Compilando e instalando Squid 6.14 con OpenSSL..."

  SCRIPT_DIR="$(dirname "$(realpath "$0")")"
  BUILD_DIR="$(realpath "$SCRIPT_DIR/../../01_squid/squid-6.14")"

  # Verificar permisos
  if [ "$EUID" -ne 0 ]; then
    echo "❌ Este script debe ejecutarse como root."
    exit 1
  fi

  # Instalar dependencias
  apt update
  apt install -y build-essential libssl-dev libcppunit-dev \
                 libexpat1-dev libxml2-dev libnetfilter-conntrack-dev \
                 pkg-config

  # Entrar al directorio
  cd "$BUILD_DIR"

  # Configurar con OpenSSL
  ./configure --with-openssl --enable-ssl-crtd

  # Compilar
  make -j"$(nproc)"

  # Instalar directamente en el sistema
  make install

  echo "✅ Squid instalado con soporte OpenSSL."
}


create_squid_service() {
  echo "🧩 Creando servicio systemd para Squid..."

  SERVICE_PATH="/etc/systemd/system/squid.service"

  cat > "$SERVICE_PATH" <<EOF
[Unit]
Description=Squid Web Proxy
After=network.target

[Service]
ExecStart=/usr/local/squid/sbin/squid -f /etc/squid/squid.conf
ExecReload=/usr/local/squid/sbin/squid -k reconfigure
ExecStop=/usr/local/squid/sbin/squid -k shutdown
Restart=on-failure

[Install]
WantedBy=multi-user.target
EOF

  echo "🔄 Recargando systemd..."
  systemctl daemon-reexec
  systemctl daemon-reload
  systemctl enable squid

  echo "✅ Servicio Squid creado y habilitado."
}




download_squid
extract_squid
make_squid
create_squid_service
copy_config
