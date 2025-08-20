#!/bin/bash

# 🔧 Desinstalando ifupdown / Uninstalling ifupdown
if dpkg -l | grep -q "^ii  ifupdown "; then
    apt remove -y ifupdown
    echo "✅ ifupdown ha sido desinstalado correctamente. / ifupdown has been successfully uninstalled."
else
    echo "❌ ifupdown no está instalado en este sistema. / ifupdown is not installed on this system."
fi

# 🔧 Desinstalando iptables / Uninstalling iptables
if dpkg -l | grep -q "^ii  iptables "; then
    apt remove -y iptables
    echo "✅ iptables ha sido desinstalado correctamente. / iptables has been successfully uninstalled."
else
    echo "❌ iptables no está instalado en este sistema. / iptables is not installed on this system."
fi

# 🔧 Desinstalando nftables / Uninstalling nftables
if dpkg -l | grep -q "^ii  nftables "; then
    apt remove -y nftables
    echo "✅ nftables ha sido desinstalado correctamente. / nftables has been successfully uninstalled."
else
    echo "❌ nftables no está instalado en este sistema. / nftables is not installed on this system."
fi

# 🔧 Desinstalando netplan / Uninstalling netplan
if dpkg -l | grep -q "^ii  netplan.io "; then
    apt purge -y netplan.io
    echo "✅ netplan ha sido desinstalado correctamente. / netplan has been successfully uninstalled."
else
    echo "❌ netplan no está instalado en este sistema. / netplan is not installed on this system."
fi
