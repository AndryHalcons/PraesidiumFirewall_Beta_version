#!/bin/bash

echo "🔧 Desinstalando ifupdown..."

# Verifica si está instalado
if dpkg -l | grep -q "^ii  ifupdown "; then
    apt remove -y ifupdown
    echo "✅ ifupdown ha sido desinstalado correctamente."
else
    echo "X ifupdown no está instalado en este sistema."
fi
