#!/bin/bash

# Ruta al directorio donde están los archivos de configuración
# Path to the directory containing the configuration files
SOURCE_DIR="./configure_logs"

configure_nftables_logging() {
    # Nombres de los archivos de configuración
    # Configuration file names
    RSYSLOG_CONF="nftables_rsyslog.conf"
    LOGROTATE_CONF="nftables_logrotate.conf"

    # Destinos donde deben copiarse los archivos
    # Destination paths for system configuration
    RSYSLOG_DEST="/etc/rsyslog.d/$RSYSLOG_CONF"
    LOGROTATE_DEST="/etc/logrotate.d/$LOGROTATE_CONF"

    # Directorio de logs personalizado
    # Custom log directory
    LOG_DIR="/var/log/praesidium"

    echo "🔧 Copiando archivos de configuración... / Copying configuration files..."
    sudo cp "$SOURCE_DIR/$RSYSLOG_CONF" "$RSYSLOG_DEST"
    sudo cp "$SOURCE_DIR/$LOGROTATE_CONF" "$LOGROTATE_DEST"

    echo " Creando directorio de logs en $LOG_DIR... / Creating log directory at $LOG_DIR..."
    sudo mkdir -p "$LOG_DIR"

    echo " Asignando permisos para rsyslog y logrotate... / Setting permissions for rsyslog and logrotate..."
    sudo chown syslog:adm "$LOG_DIR"
    sudo chmod 750 "$LOG_DIR"

    echo " Configuración completada. Reiniciando rsyslog... / Configuration complete. Restarting rsyslog..."
    sudo systemctl restart rsyslog

    echo " Todo listo. Los logs de nftables irán a $LOG_DIR/nftables.log / All set. nftables logs will go to $LOG_DIR/nftables.log"
}

# Ejecutar la función principal
# Run the main function
configure_nftables_logging
