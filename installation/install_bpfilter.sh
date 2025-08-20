#!/bin/bash

set -e

# 🧪 Verifica los requisitos del sistema / Check system requirements
check_requirements() {
    echo "🔍 Verificando requisitos para bpfilter... / Checking requirements for bpfilter..."

    local issues=0

    # Verificar versión del kernel / Check kernel version
    kernel_version=$(uname -r | cut -d '-' -f1)
    major=$(echo "$kernel_version" | cut -d '.' -f1)
    minor=$(echo "$kernel_version" | cut -d '.' -f2)

    if [ "$major" -lt 6 ] || { [ "$major" -eq 6 ] && [ "$minor" -lt 6 ]; }; then
        echo "⚠️ Kernel $kernel_version no compatible. Se recomienda 6.6 o superior. / Kernel $kernel_version not compatible. 6.6 or higher recommended."
        issues=1
    else
        echo "✅ Kernel $kernel_version compatible. / Compatible kernel."
    fi

    # Verificar libbpf / Check libbpf
    if command -v pkg-config >/dev/null 2>&1 && pkg-config --exists libbpf; then
        libbpf_version=$(pkg-config --modversion libbpf)
        libbpf_major=$(echo "$libbpf_version" | cut -d '.' -f1)
        libbpf_minor=$(echo "$libbpf_version" | cut -d '.' -f2)

        if [ "$libbpf_major" -lt 1 ] || { [ "$libbpf_major" -eq 1 ] && [ "$libbpf_minor" -lt 2 ]; }; then
            echo "⚠️ libbpf versión $libbpf_version no compatible. Se recomienda 1.2 o superior. / libbpf version $libbpf_version not compatible. 1.2 or higher recommended."
            issues=1
        else
            echo "✅ libbpf versión $libbpf_version compatible. / Compatible libbpf version."
        fi
    else
        echo "⚠️ libbpf no está instalado o no se detecta con pkg-config. / libbpf not installed or not detected via pkg-config."
        issues=1
    fi

    # Verificar dependencias / Check dependencies
    echo "🔍 Verificando dependencias adicionales... / Checking additional dependencies..."

    required_packages=(
        autoconf automake bison clang clang-tidy clang-format cmake doxygen flex furo g++ git iproute2 iputils-ping
        lcov libbenchmark-dev libbpf-dev libc-dev libcmocka-dev libgit2-dev libnl-3-dev libtool linux-tools-common
        make pkgconf procps python3-breathe python3-dateutil python3-git python3-pip python3-scapy python3-sphinx
        sed xxd
    )

    missing=()

    for pkg in "${required_packages[@]}"; do
        dpkg -s "$pkg" >/dev/null 2>&1 || missing+=("$pkg")
    done

    if [ ${#missing[@]} -eq 0 ]; then
        echo "✅ Todas las dependencias están instaladas. / All dependencies are installed."
    else
        echo "⚠️ Faltan los siguientes paquetes: / Missing the following packages:"
        for pkg in "${missing[@]}"; do
            echo "   - $pkg"
        done
        issues=1
    fi

    echo "✔️ Verificación completada. / Verification completed."

    # Preguntar si continuar / Ask to continue
    if [ "$issues" -eq 1 ]; then
        echo ""
        read -p "❓ Se detectaron problemas. ¿Deseas continuar de todos modos? / Problems were detected. Do you want to continue anyway? (y/n): " choice
        case "$choice" in
            y|Y ) echo "➡️ Continuando con la instalación... / Proceeding with installation..." ;;
            n|N ) echo "⛔ Instalación cancelada por el usuario. / Installation cancelled by user."; exit 1 ;;
            * ) echo "⛔ Opción inválida. Instalación cancelada. / Invalid option. Installation cancelled."; exit 1 ;;
        esac
    fi
}

# ⚙️ Instala bpfilter en Ubuntu 24.04+ / Install bpfilter on Ubuntu 24.04+
install_bpfilter() {
    echo "🚀 Iniciando instalación de bpfilter desde repositorios oficiales... / Starting bpfilter installation from official repositories..."

    # Verificar si el paquete ya está instalado
    if dpkg -l | grep -q "^ii  bpfilter "; then
        echo "✅ bpfilter ya está instalado en el sistema. / bpfilter is already installed on the system."
        return
    fi

    # Instalar bpfilter
    sudo apt update
    sudo apt install -y bpfilter

    # Verificar instalación
    if dpkg -l | grep -q "^ii  bpfilter "; then
        echo "✅ bpfilter ha sido instalado correctamente. / bpfilter has been successfully installed."
    else
        echo "❌ Error al instalar bpfilter. / Failed to install bpfilter."
    fi
}


# 🧪 Ejecutar verificación e instalación / Run verification and installation
check_requirements
install_bpfilter