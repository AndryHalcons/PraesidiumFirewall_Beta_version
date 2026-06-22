#!/bin/bash
set -e

# Elimina Squid si está instalado
apt purge -y squid squid-common squid-langpack squid-openssl
apt autoremove -y

# Elimina directorios residuales
rm -rf /etc/squid /var/spool/squid /var/log/squid

# Asegura que el entorno GPG de root existe
mkdir -p /root/.gnupg
chmod 700 /root/.gnupg

# Instala dirmngr y gnupg si faltan
apt install -y dirmngr gnupg

# Importa la clave GPG directamente desde el servidor de claves
rm -f /etc/apt/trusted.gpg.d/diladele.gpg
gpg --no-default-keyring \
    --keyring /etc/apt/trusted.gpg.d/diladele.gpg \
    --keyserver keyserver.ubuntu.com \
    --recv-keys 9BC5EB655A7AF0DE

# Añade el repositorio de Squid 7.1 compilado con OpenSSL
echo "deb [signed-by=/etc/apt/trusted.gpg.d/diladele.gpg] https://squid71.diladele.com/ubuntu/ noble main" \
    > /etc/apt/sources.list.d/squid71.diladele.com.list

# Prioridad absoluta al repositorio de Diladele
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

# Actualiza e instala Squid con soporte OpenSSL
apt update
apt install -y squid-openssl squid-common libecap3 libecap3-dev


# Inicializa el almacén de certificados dinámicos SOLO si Squid está instalado y el directorio no existe.
# Initialize the dynamic certificate store only when Squid is installed and the directory is missing.
if dpkg -l | grep -q squid-openssl && [ ! -d /var/lib/ssl_db ]; then
    sudo /usr/lib/squid/security_file_certgen -s /var/lib/ssl_db -M 4MB -c
fi

# Habilita y arranca Squid solo después de instalar su unidad systemd.
# Enable and start Squid only after installing its systemd unit.
systemctl enable squid
systemctl restart squid
systemctl is-active --quiet squid
