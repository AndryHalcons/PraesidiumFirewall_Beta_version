#!/bin/bash

set -e

# Verifica los requisitos del sistema / Check system requirements
check_requirements() {
    echo " Verificando requisitos para bpfilter... / Checking requirements for bpfilter..."

    local issues=0

    # Verificar versión del kernel / Check kernel version
    kernel_version=$(uname -r | cut -d '-' -f1)
    major=$(echo "$kernel_version" | cut -d '.' -f1)
    minor=$(echo "$kernel_version" | cut -d '.' -f2)

    if [ "$major" -lt 6 ] || { [ "$major" -eq 6 ] && [ "$minor" -lt 6 ]; }; then
        echo " Kernel $kernel_version no compatible. Se recomienda 6.6 o superior. / Kernel $kernel_version not compatible. 6.6 or higher recommended."
        issues=1
    else
        echo " Kernel $kernel_version compatible. / Compatible kernel."
    fi

    # Verificar libbpf / Check libbpf
    if command -v pkg-config >/dev/null 2>&1 && pkg-config --exists libbpf; then
        libbpf_version=$(pkg-config --modversion libbpf)
        libbpf_major=$(echo "$libbpf_version" | cut -d '.' -f1)
        libbpf_minor=$(echo "$libbpf_version" | cut -d '.' -f2)

        if [ "$libbpf_major" -lt 1 ] || { [ "$libbpf_major" -eq 1 ] && [ "$libbpf_minor" -lt 2 ]; }; then
            echo " libbpf versión $libbpf_version no compatible. Se recomienda 1.2 o superior. / libbpf version $libbpf_version not compatible. 1.2 or higher recommended."
            issues=1
        else
            echo " libbpf versión $libbpf_version compatible. / Compatible libbpf version."
        fi
    else
        echo " libbpf no está instalado o no se detecta con pkg-config. / libbpf not installed or not detected via pkg-config."
        issues=1
    fi

    # Verificar dependencias / Check dependencies
    echo " Verificando dependencias adicionales... / Checking additional dependencies..."

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
        echo " Todas las dependencias están instaladas. / All dependencies are installed."
    else
        echo " Faltan los siguientes paquetes: / Missing the following packages:"
        for pkg in "${missing[@]}"; do
            echo "   - $pkg"
        done
        issues=1
    fi

    echo " Verificación completada. / Verification completed."

    # Preguntar si continuar / Ask to continue
    if [ "$issues" -eq 1 ]; then
        echo ""
        read -p " Se detectaron problemas. ¿Deseas continuar de todos modos? / Problems were detected. Do you want to continue anyway? (y/n): " choice
        case "$choice" in
            y|Y ) echo " Continuando con la instalación... / Proceeding with installation..." ;;
            n|N ) echo " Instalación cancelada por el usuario. / Installation cancelled by user."; exit 1 ;;
            * ) echo " Opción inválida. Instalación cancelada. / Invalid option. Installation cancelled."; exit 1 ;;
        esac
    fi
}

# Compila bpfilter desde código fuente en Ubuntu 24.04+ usando rutas absolutas
install_bpfilter() {
    echo " Iniciando compilación de bpfilter desde código fuente..."

    # Ruta absoluta donde se clonará y compilará bpfilter
    local BPFILTER_DIR="/home/praesidium/bpfilter"
    local BUILD_DIR="/home/praesidium/bpfilter/build"
    local REPO_URL="https://github.com/AndryHalcons/bpfilter"
    local BFCLI_PATH="$BUILD_DIR/output/sbin/bfcli"
    local BPFILTER_PATH="$BUILD_DIR/output/sbin/bpfilter"

    # Verificar si ya está compilado
    if [ -f "$BFCLI_PATH" ] && [ -f "$BPFILTER_PATH" ]; then
        echo " bpfilter ya está compilado en: $BFCLI_PATH"
        echo " Copiando binarios a /usr/local/bin/..."
        sudo cp "$BFCLI_PATH" /usr/local/bin/
        sudo cp "$BPFILTER_PATH" /usr/local/bin/
        echo " Binarios instalados en /usr/local/bin/"
        return
    fi

    # Clonar el repositorio si no existe
    if [ ! -d "$BPFILTER_DIR" ]; then
        echo " Clonando repositorio bpfilter desde $REPO_URL..."
        git clone "$REPO_URL" "$BPFILTER_DIR"
    else
        echo " Actualizando repositorio bpfilter..."
        git -C "$BPFILTER_DIR" pull
    fi

    # Crear directorio de compilación si no existe
    mkdir -p "$BUILD_DIR"

    # Generar sistema de compilación con CMake
    echo " Generando sistema de compilación con CMake..."
    cmake -S "$BPFILTER_DIR" -B "$BUILD_DIR"

    # Compilar con salida detallada
    echo " Compilando bpfilter con salida verbose..."
    cmake --build "$BUILD_DIR" --verbose

    # Verificar compilación
    if [ -f "$BFCLI_PATH" ] && [ -f "$BPFILTER_PATH" ]; then
        echo " bpfilter compilado correctamente en: $BFCLI_PATH"
        echo " Copiando binarios a /usr/local/bin/..."
        sudo cp "$BFCLI_PATH" /usr/local/bin/
        sudo cp "$BPFILTER_PATH" /usr/local/bin/
        echo " Binarios instalados en /usr/local/bin/"
    else
        echo " Error al compilar bpfilter. Revisa la salida detallada arriba."
    fi
}











# Ejecutar verificación e instalación / Run verification and installation
check_requirements
install_bpfilter
