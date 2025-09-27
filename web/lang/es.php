<?php
return [
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   Generic      ///////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    'add'                 => '➕ Añadir',

    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   mainpage.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    'title'                 => 'Praesidium Firewall',
    'welcome'               => 'Bienvenido',
    'role'                  => 'Rol',
    'language'              => 'Idioma',
    // Top menu
    'menu_home'             => 'Inicio',
    'menu_monitor'          => 'Monitor',
    'menu_users'            => 'Usuarios',
    'menu_logout'           => 'Cerrar sesión',
    'menu_commit'           => 'Commit',
    // Sidebar
    'sidebar_dashboard'     => 'Panel',
    'sidebar_interfaces'    => 'Interfaces',
    'sidebar_ethernets'     => 'Ethernets',
    'sidebar_bridges'       => 'Bridges',
    'sidebar_vlans'         => 'VLANs',
    'sidebar_bonds'         => 'Bonds',
    'sidebar_tunnels'       => 'Tuneles',
    'sidebar_wireguard'     => 'WireGuard',
    'sidebar_wifis'         => 'Wi-Fi',
    'sidebar_XDP_policies'  => 'Reglas XDP',
    'sidebar_TC_Egress'     => 'Reglas TC Egress',
    'sidebar_TC_Ingress'    => 'Reglas TC Ingress',
    'sidebar_nftables_prerouting'    => 'Reglas Prerouting',
    'sidebar_nftables_forwarding'    => 'Reglas Forwarding',
    'sidebar_nftables_postrouting'   => 'Reglas Postrouting',
    'sidebar_nftables_input'   =>  'Reglas input Management',
    'sidebar_nftables_output'   => 'Reglas output Management',
    'sidebar_system'   => 'Sistema',
    'sidebar_logs'          => 'Registros',
    'sidebar_settings'      => 'Configuración',
    'sidebar_Routing'      => 'Enrutamiento',
    'sidebar_services'      => 'Servicios',
    'sidebar_AliasObjects'      => 'Alias de objetos',
    'sidebar_address_alias'      => 'Direcciones',
    'sidebar_address_group_alias'      => 'Grupos de direcciones',
    'sidebar_services_objects'      => 'Servicios',
    'sidebar_services_group_objects'      => 'Grupos de servicios',
    'sidebar_url_filtering'  => 'Filtros URL',
    'sidebar_url_list'  => 'Listas URL',
    'sidebar_url_policies'  => 'Reglas URL',
    'sidebar_url_profile'  => 'Perfil listas URL',
    'sidebar_url_port_profile'  => 'Perfil Puertos seguros URL',
    'sidebar_url_listen_ports'  => 'Puertos de escucha URL',
    'sidebar_certificates'  => 'Certificados',
    // Content
    'main_content'          => 'Contenido aquí...',
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   Dashboard.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    'cpu_total' => 'CPU Total',
    'cpu_average' => 'CPU Promedio',
    'ram_total' => 'Total',
    'ram_used' => 'En uso',
    'ram_free' => 'Libre',
    'ram_cached' => 'Reservada',
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   Interfaces.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //buttons
    "create_bridge" => "➕ Crear Bridge",
    "create_bond" => "➕ Crear Bond (grupo de agregación)",
    'add_interface' => 'Añadir interfaz',
    "delete_interface" => "🗑️ Eliminar interfaz",
    "ok" => "Aceptar",
    "cancel" => "Cancelar",
    'edit' => '✏️ Editar',
    'save' => '💾 Guardar',
    'save_and_apply' => 'Guardar y aplicar',
    
    //aux
    'network_config_title' => 'Configuración de Interfaces de Red',
    'enter_interface_name' => 'Introduce el nombre de la interfaz',
    'unauthorized' => 'No autorizado',
    
    // Campos de configuración de interfaces
    'name'                              => 'Nombre',
    'dhcp4'                             => 'DHCPv4',
    'dhcp6'                             => 'DHCPv6',
    'addresses'                         => 'IP + CIDR',
    'gateway4'                          => 'Puerta de enlace IPv4',
    'gateway6'                          => 'Puerta de enlace IPv6',
    'mtu'                               => 'MTU',
    'macaddress'                        => 'Dirección MAC',
    'nameservers.addresses'            => 'Direcciones DNS',
    'nameservers.search'               => 'Dominios de búsqueda DNS',
    'optional'                          => 'Opcional',
    'accept-ra'                         => 'Aceptar anuncios de router',
    'wakeonlan'                         => 'Wake-on-LAN',
    'routes.to'                         => 'Destino de ruta',
    'routes.via'                        => 'Puerta de enlace de ruta',
    'routes.metric'                     => 'Métrica de ruta',
    'ipv6-privacy'                      => 'Privacidad IPv6',
    'dhcp4-overrides.use-dns'          => 'Sobrescribir DNS de DHCPv4',
    'dhcp4-overrides.use-routes'       => 'Sobrescribir rutas de DHCPv4',
    'dhcp4-overrides.send-hostname'    => 'Enviar nombre de host por DHCPv4',
    'dhcp4-overrides.hostname'         => 'Nombre de host personalizado para DHCPv4',
    'dhcp4-overrides.use-hostname'     => 'Usar nombre de host del sistema para DHCPv4',
    'dhcp6-overrides.use-dns'          => 'Sobrescribir DNS de DHCPv6',
    'dhcp6-overrides.use-routes'       => 'Sobrescribir rutas de DHCPv6',
    'match.name'                        => 'Coincidir nombre de interfaz',
    'match.macaddress'                 => 'Coincidir dirección MAC',
    'match.driver'                     => 'Coincidir controlador',
    'set-name'                          => 'Renombrar interfaz',
    'interfaces'                        => 'Interfaces',
    'parameters.mode'                  => 'Modo de enlace',
    'parameters.primary'               => 'Interfaz primaria',
    'parameters.mii-monitor-interval'  => 'Intervalo de monitorización MII',
    'parameters.up-delay'              => 'Retardo de activación',
    'parameters.down-delay'            => 'Retardo de desactivación',
    'parameters.lacp-rate'             => 'Frecuencia LACP',
    'parameters.transmit-hash-policy' => 'Política de hash de transmisión',
    'parameters.min-links'             => 'Enlaces mínimos',
    'parameters.stp'                   => 'Protocolo STP',
    'parameters.priority'              => 'Prioridad del puente',
    'parameters.forward-delay'         => 'Retardo de reenvío',
    'parameters.hello-time'            => 'Tiempo de saludo',
    'parameters.max-age'               => 'Edad máxima',
    'parameters.ageing-time'           => 'Tiempo de envejecimiento',
    'id'                                => 'ID',
    'link'                              => 'Enlace',
    'access-points.<SSID>.password'    => 'Contraseña Wi-Fi',
    'access-points.<SSID>.mode'        => 'Modo Wi-Fi',
    'access-points.<SSID>.band'        => 'Banda Wi-Fi',
    'access-points.<SSID>.channel'     => 'Canal Wi-Fi',
    'mode'                              => 'Modo de túnel',
    'local'                             => 'Dirección local',
    'remote'                            => 'Dirección remota',
    'port'                              => 'Puerto',
    'key.private'                       => 'Clave privada',
    'peers.keys.public'                => 'Clave pública del par',
    'peers.allowed-ips'                => 'IPs permitidas',
    'peers.keepalive'                  => 'Keepalive persistente',
    'peers.endpoint'                   => 'Punto de conexión del par',
    'routes.table'                     => 'Tabla de rutas',
    'routing-policy.from'              => 'Política de enrutamiento desde',
    'routing-policy.table'             => 'Tabla de política de enrutamiento',
    'mark'                              => 'Marca de paquete',



    //basura a revisar
    'interface' => 'Interfaz',
    'invalid_name' => 'El nombre no puede estar vacío',
    'name' => 'Nombre',
    'trigger' => 'Trigger',
    'method' => 'Método',
    'ip' => 'IP',
    'netmask' => 'Máscara',
    'gateway' => 'Gateway',
    'remove' => 'Eliminar',
    'ifreload_output' => 'Salida de ifreload:',
    "connection_error" => "Error de conexión.",
    "invalid_interface_name" => "Solo se pueden eliminar interfaces lógicas (br* o bond*).",
    "interface_deleted" => "Interfaz eliminada correctamente.",
    "delete_failed" => "Error al eliminar la interfaz.",
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   users.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //buttons
    "edit" => "✏️ Editar",
    "save" => "💾 Guardar",
    "delete" => "🗑️ Eliminar",
    "add_user" => "➕ Añadir Usuario",
    "cancel" => "Cancelar",

    //aux
    "user_name" => "Usuario",
    "user_pass" => "Contraseña",
    "user_role" => "Rol",
    "user_language" => "Idioma",
    "actions" => "Acciones",
    "confirm_delete" => "¿Seguro que deseas eliminar al usuario?",
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   policies.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //botones
    "add_policy" => "➕ Crear Regla",
    // campos nftables
    'family'           => 'Familia',
    'table'            => 'Tabla',
    'chain'            => 'Cadena',
    'id'               => 'ID',
    'position'         => 'Posición',
    'action'           => 'Acción',
    'enable'           => 'Habilitada',
    'name'             => 'Nombre',
    'ip.protocol'      => 'Protocolo IP',
    'ip.saddr.op'      => 'Negar IP origen',
    'ip.saddr'         => 'IP origen',
    'sport.op'         => 'Negar puerto origen',
    'sport'            => 'Puerto origen',
    'ip.daddr.op'      => 'Negar IP destino',
    'ip.daddr'         => 'IP destino',
    'dport.op'         => 'Negar puerto destino',
    'dport'            => 'Puerto destino',
    'meta.iifname'     => 'Interfaz de entrada',
    'meta.oifname'     => 'Interfaz de salida',
    'ct.state'         => 'Estado de conexión',
    'packets'          => 'Paquetes',
    'bytes'            => 'Bytes',
    'dnat.addr'        => 'IP DNAT',
    'dnat.port'        => 'Puerto DNAT',
    'snat.addr'        => 'IP SNAT',
    'masquerade'        => 'Masquerade',
    'log'              => 'Log',

    // Campos de bpfilter
    // Campos de reglas de red / firewall
    'id'                 => 'ID',
    'hook'               => 'Hook',
    'chain'              => 'Cadena',
    'position'           => 'Posición',
    'action'             => 'Acción',
    'enable'             => 'Habilitada',
    'name'               => 'Nombre',
    'interface'          => 'Interfaz',
    'l3_protocol'        => 'Protocolo de capa 3',
    'l4_protocol'        => 'Protocolo de capa 4',
    'source'             => 'Origen',
    'sport'              => 'Puerto origen',
    'destination'        => 'Destino',
    'dport'              => 'Puerto destino',
    'tcp_flags'          => 'Flags TCP',
    'ipv6_next_header'   => 'Encabezado siguiente IPv6',
    'icmp_type'          => 'Tipo ICMP',
    'icmp_code'          => 'Código ICMP',
    'icmpv6_type'        => 'Tipo ICMPv6',
    'icmpv6_code'        => 'Código ICMPv6',
    'probability'        => 'Probabilidad',

    
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   routing.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //buttons
    'reload_routes' => 'Recargar rutas del sistema en ejecución',
    //aux
    'routing_title' => 'Tabla de rutas del sistema',
    'loading_routes' => 'Cargando rutas...',
    'table' => 'Tabla',
    'ip_version' => 'Versión IP',
    'destination' => 'Destino',
    'interface' => 'Interfaz',
    'gateway' => 'Gateway',
    'metric' => 'Métrica',
    'proto' => 'Protocolo',
    'scope' => 'Scope',
    'src' => 'Origen',
    'rules_title' => 'Reglas de enrutamiento',
    'rule_from' => 'Desde',
    'rule_table' => 'Tabla',
    
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   commit.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //buttons
    'compare_commit' => 'Comparar commit',
    'apply_commit'   => 'Aplicar commit',
    'config_audit' => 'Auditoría de configuración',
    //aux

    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   monitor.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //buttons
    'search' => 'Buscar',
    //aux
    'menu_monitor' => 'Monitor de tráfico',
    'init_date' => 'Fecha inicio',
    'init_time' => 'Hora inicio',
    'end_date' => 'Fecha fin',
    'end_time' => 'Hora fin',
    'ip_addr' => 'IP origen',
    'ip_dest' => 'IP destino',
    'sport' => 'Puerto origen',
    'dport' => 'Puerto destino',
    'max_record' => 'Máx. registros',
    'L4protocol' => 'Proto',
    'time' => 'Tiempo',
    'firewall' => 'Cortafuegos',
    'Logdate' => 'Fecha',
    'Logtime' => 'Hora',
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   alias         //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //buttons
    "add_alias" => "➕ Añadir",
    //table alias
    'id' => 'ID',
    'name' => 'Nombre',
    'content' => 'Contenido',
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   url filter        //////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //buttons
    'upload_button' => 'Subir Archivo',
    //table profile
    'profile' => 'Perfil',
    'type' => 'Tipo',
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   certificates      //////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //buttons
    'download' => '⬇️ Descargar'

    


    

];
