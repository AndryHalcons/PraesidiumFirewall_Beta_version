#!/bin/bash
set -e

# 🛡️ Verifica que el script se ejecute como root / Ensure the script is run as root
if [ "$EUID" -ne 0 ]; then
    echo "⛔ Este script debe ejecutarse como root. Usa: sudo ./nombre_script.sh"
    echo "⛔ This script must be run as root. Use: sudo ./script_name.sh"
    exit 1
fi

# 🔍 Verifica que el binario bpfilter esté disponible / Check that the bpfilter binary is available
if ! command -v bpfilter >/dev/null 2>&1; then
    echo "❌ El binario 'bpfilter' no está disponible en el PATH. ¿Está instalado correctamente?"
    echo "❌ The 'bpfilter' binary is not available in the PATH. Is it installed correctly?"
    exit 1
fi

# 🚀 Inicia el daemon de bpfilter con configuración personalizada / Start the bpfilter daemon with custom configuration
start_bpfilter_daemon() {
    echo "🚀 Iniciando daemon de bpfilter..."
    echo "🚀 Starting bpfilter daemon..."

    # 📁 Rutas de configuración / Configuration paths
    local BPFFS_PATH="/sys/fs/bpf"                         # Ruta del sistema de archivos BPF / BPF filesystem path
    local BPFILTER_PIN_PATH="$BPFFS_PATH/bpfilter"         # Ruta para pinnear objetos BPF / Path to pin BPF objects
    local RUN_PATH="/run/bpfilter"                         # Ruta para datos de ejecución / Runtime data path

    # ⚙️ Flags de configuración / Configuration flags
    local USE_TRANSIENT=false                              # Si es true, no se guarda nada en disco / If true, nothing is saved to disk
    local USE_BPF_TOKEN=true                               # Usa tokens BPF (requiere kernel ≥ 6.9) / Use BPF tokens (requires kernel ≥ 6.9)
    local VERBOSE_FLAG="debug"                             # Nivel de verbosidad / Verbosity level

    # 📦 Montar bpffs si no está montado / Mount bpffs if not already mounted
    if ! mountpoint -q "$BPFFS_PATH"; then
        echo "📦 Montando bpffs en $BPFFS_PATH..."
        echo "📦 Mounting bpffs at $BPFFS_PATH..."
        mount -t bpf bpf "$BPFFS_PATH"
    else
        echo "✅ bpffs ya está montado en $BPFFS_PATH"
        echo "✅ bpffs is already mounted at $BPFFS_PATH"
    fi

    # 📁 Crear directorios necesarios / Create required directories
    echo "📁 Preparando directorios..."
    echo "📁 Preparing directories..."
    mkdir -p "$BPFILTER_PIN_PATH"
    mkdir -p "$RUN_PATH"


    # 🧪 Construir comando del daemon / Build daemon command
    local DAEMON_CMD="bpfilter"  # Comando base / Base command
    #  Desactivar soporte para iptables y nftables / Disable iptables and nftables support
    DAEMON_CMD+=" --no-iptables --no-nftables"
    # Activar logs detallados para depuración / Enable verbose logging for debugging
    DAEMON_CMD+=" --verbose=$VERBOSE_FLAG"


    echo "🧪 Comando generado:"
    echo "🧪 Generated command:"
    echo "   $DAEMON_CMD"

    # ⚙️ Ejecutar daemon / Run daemon
    echo "⚙️ Ejecutando daemon..."
    echo "⚙️ Running daemon..."
    $DAEMON_CMD
}

# 🧨 Llamar a la función principal / Call main function
start_bpfilter_daemon
