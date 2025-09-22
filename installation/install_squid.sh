#!/bin/bash

# Elimina Squid si está instalado
if dpkg -l | grep -q '^ii  squid'; then
    apt purge -y squid squid-common squid-langpack
    apt autoremove -y
fi

# Elimina directorios residuales
rm -rf /etc/squid /var/spool/squid /var/log/squid

# Descarga la clave GPG y la guarda en el directorio recomendado
wget -qO /etc/apt/trusted.gpg.d/diladele.gpg https://packages.diladele.com/diladele_pub.asc

# Añade (o sobrescribe) el repositorio de Squid 7.1 compilado con OpenSSL
echo "deb [signed-by=/etc/apt/trusted.gpg.d/diladele.gpg] https://squid71.diladele.com/ubuntu/ noble main" \
    > /etc/apt/sources.list.d/squid71.diladele.com.list

# Sobrescribe el archivo de pinning para dar prioridad absoluta al repositorio de Diladele
tee /etc/apt/preferences.d/squid-diladele.pref > /dev/null <<EOF
Package: squid-openssl
Pin: origin squid71.diladele.com
Pin-Priority: 1001

Package: squid-common
Pin: origin squid71.diladele.com
Pin-Priority: 1001

Package: libecap3
Pin: origin squid71.diladele.com
Pin-Priority: 1001

Package: libecap3-dev
Pin: origin squid71.diladele.com
Pin-Priority: 1001
EOF

# Actualiza la lista de paquetes e instala Squid con soporte OpenSSL
apt update
apt install -y squid-openssl squid-common libecap3 libecap3-dev
