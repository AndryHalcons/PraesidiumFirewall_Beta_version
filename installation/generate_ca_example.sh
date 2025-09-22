#!/bin/bash

cd ../data/certs || exit 1

# =========================
# CA raíz - Generación
# =========================
openssl genrsa -out rootCA.key 4096
openssl req -x509 -new -nodes -key rootCA.key -sha256 -days 3650 \
  -out rootCA.pem \
  -subj "/C=ES/O=Praesidium/OU=RootCA/CN=Praesidium Root CA"

# =========================
# CA intermedia - Generación
# =========================
openssl genrsa -out intermediateCA.key 4096
openssl req -new -key intermediateCA.key -out intermediateCA.csr \
  -subj "/C=ES/O=Praesidium/OU=IntermediateCA/CN=Praesidium Intermediate CA"

# =========================
# Extensiones para firmar como CA
# =========================
cat > ca_ext.cnf <<EOF
[ v3_ca ]
basicConstraints = CA:TRUE
keyUsage = keyCertSign, cRLSign
subjectKeyIdentifier = hash
authorityKeyIdentifier = keyid:always,issuer
EOF

# =========================
# CA intermedia - Firma por la raíz
# =========================
openssl x509 -req -in intermediateCA.csr -CA rootCA.pem -CAkey rootCA.key \
  -CAcreateserial -out intermediateCA.pem -days 1825 -sha256 \
  -extfile ca_ext.cnf -extensions v3_ca

# =========================
# CA emisora para Squid - Generación
# =========================
openssl genrsa -out emisor_squid.key 4096
openssl req -new -key emisor_squid.key -out emisor_squid.csr \
  -subj "/C=ES/O=Praesidium/OU=SquidCA/CN=Praesidium Squid Issuing CA"

# =========================
# CA emisora para Squid - Firma por la intermedia
# =========================
openssl x509 -req -in emisor_squid.csr -CA intermediateCA.pem -CAkey intermediateCA.key \
  -CAcreateserial -out emisor_squid.pem -days 1825 -sha256 \
  -extfile ca_ext.cnf -extensions v3_ca

# =========================
# CA para clientes - Generación
# =========================
openssl genrsa -out clientes_squid.key 4096
openssl req -new -key clientes_squid.key -out clientes_squid.csr \
  -subj "/C=ES/O=Praesidium/OU=ClientCA/CN=Praesidium Client CA"

# =========================
# CA para clientes - Firma por la intermedia
# =========================
openssl x509 -req -in clientes_squid.csr -CA intermediateCA.pem -CAkey intermediateCA.key \
  -CAcreateserial -out clientes_squid.pem -days 1825 -sha256 \
  -extfile ca_ext.cnf -extensions v3_ca
