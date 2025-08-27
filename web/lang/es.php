<?php
return [
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
    'menu_interfaces'       => 'Interfaces',
    'menu_monitor'          => 'Monitor',
    'menu_users'            => 'Usuarios',
    'menu_logout'           => 'Cerrar sesión',
    'menu_commit'           => 'Commit',
    // Sidebar
    'sidebar_dashboard'     => 'Panel',
    'sidebar_XDP_policies'  => 'Reglas XDP',
    'sidebar_TC_Egress'     => 'Reglas TC Egress',
    'sidebar_TC_Ingress'    => 'Reglas TC Ingress',
    'sidebar_nftables_prerouting'    => 'Reglas Prerouting',
    'sidebar_nftables_forwarding'    => 'Reglas Forwarding',
    'sidebar_nftables_postrouting'   => 'Reglas Postrouting',
    'sidebar_nftables_input'   =>  'Reglas input Management',
    'sidebar_nftables_output'   => 'Reglas output Management',
    'sidebar_logs'          => 'Registros',
    'sidebar_settings'      => 'Configuración',
    'sidebar_Routing'      => 'Enrutamiento',
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
    'network_config_title' => 'Configuración de Interfaces de Red',
    'add_interface' => 'Añadir interfaz',
    'name' => 'Nombre',
    'trigger' => 'Trigger',
    'method' => 'Método',
    'ip' => 'IP',
    'netmask' => 'Máscara',
    'gateway' => 'Gateway',
    'remove' => 'Eliminar',
    'save_and_apply' => 'Guardar y aplicar',
    'ifreload_output' => 'Salida de ifreload:',
    'unauthorized' => 'No autorizado',
    'edit' => '✏️ Editar',
    'save' => '💾 Guardar',
    'interface' => 'Interfaz',
    "create_bridge" => "➕ Crear Bridge",
    "create_bond" => "➕ Crear Bond (grupo de agregación)",
    "delete_interface" => "🗑️ Eliminar interfaz",
    'enter_interface_name' => 'Introduce el nombre de la interfaz',
    'invalid_name' => 'El nombre no puede estar vacío',
    "ok" => "Aceptar",
    "cancel" => "Cancelar",
    "connection_error" => "Error de conexión.",
    "invalid_interface_name" => "Solo se pueden eliminar interfaces lógicas (br* o bond*).",
    "interface_deleted" => "Interfaz eliminada correctamente.",
    "delete_failed" => "Error al eliminar la interfaz.",
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   users.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    "username" => "Usuario",
    "password" => "Contraseña",
    "role" => "Rol",
    "language" => "Idioma",
    "actions" => "Acciones",
    "edit" => "✏️ Editar",
    "save" => "💾 Guardar",
    "delete" => "🗑️ Eliminar",
    "add_user" => "➕ Añadir Usuario",
    "cancel" => "Cancelar",
    "confirm_delete" => "¿Seguro que deseas eliminar al usuario?",
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   policies.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    // Encabezados de tabla
    "actions" => "Acciones",
    "id" => "ID",
    "position" => "Posición",
    "name" => "Nombre",
    "description" => "Descripción",
    "action" => "Acción",
    "enabled" => "¿Habilitada?",
    // Campos de match
    "iface" => "Interfaz",
    "l3_proto" => "Protocolo L3",
    "l4_proto" => "Protocolo L4",
    "ip4_saddr" => "IPv4 Origen",
    "ip4_daddr" => "IPv4 Destino",
    "ip4_snet" => "Red IPv4 Origen",
    "ip4_dnet" => "Red IPv4 Destino",
    "ip4_proto" => "Protocolo IPv4",
    "ip6_saddr" => "IPv6 Origen",
    "ip6_daddr" => "IPv6 Destino",
    "ip6_snet" => "Red IPv6 Origen",
    "ip6_dnet" => "Red IPv6 Destino",
    "ip6_nexthdr" => "Encabezado siguiente IPv6",
    "tcp_sport" => "Puerto TCP Origen",
    "tcp_dport" => "Puerto TCP Destino",
    "tcp_flags" => "Flags TCP",
    "udp_sport" => "Puerto UDP Origen",
    "udp_dport" => "Puerto UDP Destino",
    "icmp_type" => "Tipo ICMP",
    "icmp_code" => "Código ICMP",
    "icmpv6_type" => "Tipo ICMPv6",
    "icmpv6_code" => "Código ICMPv6",
    "probability" => "Probabilidad",
    "add_policy" => "➕ Crear Regla",
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   routing.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    'routing_title' => 'Tabla de rutas del sistema',
    'loading_routes' => 'Cargando rutas...',
    'table' => 'Tabla',
    'ip_version' => 'Versión IP',
    'action' => 'Acción',
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
    'reload_routes' => 'Recargar rutas del sistema en ejecución',
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   commit.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    'compare_commit' => 'Comparar commit',
    'apply_commit'   => 'Aplicar commit',
    'config_audit' => 'Auditoría de configuración',
];
