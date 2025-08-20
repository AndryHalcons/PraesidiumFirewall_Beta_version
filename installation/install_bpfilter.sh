#!/bin/bash

set -e

# 🧪 Verifica los requisitos del sistema / Check system requirements
check_requirements() {
    echo "🔍 Verificando requisitos para bpfilter... / Checking requirements for bpfilter..."

    # Verificar versión del kernel (mínimo 6.6) / Check kernel version (minimum 6.6)
    kernel_version=$(uname -r | cut -d '-' -f1)
    major=$(echo "$kernel_version" | cut -d '.' -f1)
    minor=$(echo "$kernel_version" | cut -d '.' -f2)

    if [ "$major" -lt 6 ] || { [ "$major" -eq 6 ] && [ "$minor" -lt 6 ]; }; then
        echo "❌ Kernel $kernel_version no compatible. Se requiere 6.6 o superior. / Incompatible kernel. 6.6 or higher required."
        exit 1
    else
        echo "✅ Kernel $kernel_version compatible. / Compatible kernel."
    fi

    # Verificar si libbpf está instalado y su versión (mínimo 1.2) / Check if libbpf is installed and version (minimum 1.2)
    if command -v pkg-config >/dev/null 2>&1 && pkg-config --exists libbpf; then
        libbpf_version=$(pkg-config --modversion libbpf)
        libbpf_major=$(echo "$libbpf_version" | cut -d '.' -f1)
        libbpf_minor=$(echo "$libbpf_version" | cut -d '.' -f2)

        if [ "$libbpf_major" -lt 1 ] || { [ "$libbpf_major" -eq 1 ] && [ "$libbpf_minor" -lt 2 ]; }; then
            echo "❌ libbpf versión $libbpf_version no compatible. Se requiere 1.2 o superior. / Incompatible libbpf version. 1.2 or higher required."
            exit 1
        else
            echo "✅ libbpf versión $libbpf_version compatible. / Compatible libbpf version."
        fi
    else
        echo "❌ libbpf no está instalado o no se detecta con pkg-config. / libbpf not installed or not detected via pkg-config."
        exit 1
    fi

    # Verificar dependencias adicionales / Check additional dependencies
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
        echo "❌ Faltan los siguientes paquetes: / Missing the following packages:"
        for pkg in "${missing[@]}"; do
            echo "   - $pkg"
        done
        exit 1
    fi

    echo "✔️ Requisitos cumplidos. / Requirements fulfilled."
}

# ⚙️ Instala y compila bpfilter / Install and compile bpfilter
install_bpfilter() {
    local INSTALL_DIR="/home/bpfilter"
    local REPO_URL="https://github.com/facebook/bpfilter.git"

    echo "🚀 Iniciando instalación de bpfilter en $INSTALL_DIR... / Starting bpfilter installation in $INSTALL_DIR..."

    # Crear directorio limpio / Create clean directory
    if [ -d "$INSTALL_DIR" ]; then
        echo "🧹 Eliminando instalación previa... / Removing previous installation..."
        sudo rm -rf "$INSTALL_DIR"
    fi
    mkdir -p "$INSTALL_DIR"
    cd "$INSTALL_DIR"

    # Clonar repositorio / Clone repository
    echo "📥 Clonando repositorio bpfilter... / Cloning bpfilter repository..."
    git clone "$REPO_URL" src
    cd src

    # Configurar con CMake / Configure with CMake
    echo "🔧 Configurando proyecto con CMake... / Configuring project with CMake..."
    cmake -S . -B "$INSTALL_DIR/build" \
        -DCMAKE_BUILD_TYPE=Release \
        -DNO_DOCS=ON \
        -DNO_TESTS=ON \
        -DNO_CHECKS=ON \
        -DNO_BENCHMARKS=ON

    # Compilar proyecto / Build project
    echo "🔨 Compilando bpfilter... / Building bpfilter..."
    cmake --build "$INSTALL_DIR/build" --target install

    echo "✅ Instalación completada. Binarios disponibles en: $INSTALL_DIR/build/output / Installation complete. Binaries available at: $INSTALL_DIR/build/output"
}

# 🧪 Ejecutar verificación e instalación / Run verification and installation
check_requirements
install_bpfilter
