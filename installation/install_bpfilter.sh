#!/bin/bash

check_requirements() {
    echo "🔍 Verificando requisitos para bpfilter..."

    # Verificar versión del kernel (mínimo 6.6)
    kernel_version=$(uname -r | cut -d '-' -f1)
    major=$(echo "$kernel_version" | cut -d '.' -f1)
    minor=$(echo "$kernel_version" | cut -d '.' -f2)

    if [ "$major" -lt 6 ] || { [ "$major" -eq 6 ] && [ "$minor" -lt 6 ]; }; then
        echo "❌ Kernel $kernel_version no compatible. Se requiere 6.6 o superior."
        exit 1
    else
        echo "✅ Kernel $kernel_version compatible."
    fi

    # Verificar si libbpf está instalado y su versión (mínimo 1.2)
    if command -v pkg-config >/dev/null 2>&1 && pkg-config --exists libbpf; then
        libbpf_version=$(pkg-config --modversion libbpf)
        libbpf_major=$(echo "$libbpf_version" | cut -d '.' -f1)
        libbpf_minor=$(echo "$libbpf_version" | cut -d '.' -f2)

        if [ "$libbpf_major" -lt 1 ] || { [ "$libbpf_major" -eq 1 ] && [ "$libbpf_minor" -lt 2 ]; }; then
            echo "❌ libbpf versión $libbpf_version no compatible. Se requiere 1.2 o superior."
            exit 1
        else
            echo "✅ libbpf versión $libbpf_version compatible."
        fi
    else
        echo "❌ libbpf no está instalado o no se detecta con pkg-config."
        exit 1
    fi

    # Verificar dependencias adicionales
    echo "🔍 Verificando dependencias adicionales..."

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
        echo "✅ Todas las dependencias están instaladas."
    else
        echo "❌ Faltan los siguientes paquetes:"
        for pkg in "${missing[@]}"; do
            echo "   - $pkg"
        done
        exit 1
    fi

    echo "✔️ Requisitos cumplidos."
}

check_requirements
