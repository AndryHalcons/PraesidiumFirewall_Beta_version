<?php
return [
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   mainpage.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    'title'                 => 'Praesidium Firewall',
    'welcome'               => 'Welcome',
    'role'                  => 'Role',
    'language'              => 'Language',
    // Top menu
    'menu_home'             => 'Home',
    'menu_interfaces'       => 'Interfaces',
    'menu_monitor'          => 'Monitor',
    'menu_users'            => 'Users',
    'menu_logout'           => 'Log out',
    'menu_commit'           => 'Commit',
    // Sidebar
    'sidebar_dashboard'     => 'Dashboard',
    'sidebar_interfaces'    => 'Interfaces',
    'sidebar_ethernets'     => 'Ethernets',
    'sidebar_bridges'       => 'Bridges',
    'sidebar_vlans'         => 'VLANs',
    'sidebar_bonds'         => 'Bonds',
    'sidebar_tunnels'       => 'Tunnels',
    'sidebar_wireguard'     => 'WireGuard',
    'sidebar_wifis'         => 'Wi-Fi',
    'sidebar_rules'         => 'Policies',
    'sidebar_XDP_policies'  => 'Policies XDP',
    'sidebar_TC_Egress'     => 'Policies TC Egress',
    'sidebar_TC_Ingress'    => 'Policies TC Ingress',
    'sidebar_nftables_prerouting'    => 'Policies Prerouting',
    'sidebar_nftables_forwarding'    => 'Policies Forwarding',
    'sidebar_nftables_postrouting'   => 'Policies Postrouting',
    'sidebar_nftables_input'   =>  'Policies input Management',
    'sidebar_nftables_output'   => 'Policies output Management',
    'sidebar_system'   => 'System',
    'sidebar_logs'          => 'Logs',
    'sidebar_settings'      => 'Settings',
    'sidebar_Routing'      => 'Routing',
    'sidebar_services'      => 'Services',
    'sidebar_AliasObjects'      => 'Objects Aliases',
    'sidebar_address_alias'           => 'Address Aliases',
    'sidebar_address_group_alias'     => 'Address Groups',
    'sidebar_services_objects'        => 'Service Aliases',
    'sidebar_services_group_objects'  => 'Service Groups',

    // Content
    'main_content'          => 'Content here...',
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   Dashboard.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    'cpu_total' => 'CPU Total',
    'cpu_average' => 'CPU Average',
    'ram_total' => 'Total',
    'ram_used' => 'Used',
    'ram_free' => 'Free',
    'ram_cached' => 'Cached',
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   Interfaces.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    'network_config_title' => 'Network Interface Configuration',
    'add_interface' => 'Add Interface',
    'name' => 'Name',
    'trigger' => 'Trigger',
    'method' => 'Method',
    'ip' => 'IP Address',
    'netmask' => 'Netmask',
    'gateway' => 'Gateway',
    'remove' => 'Remove',
    'save_and_apply' => '💾 Save and Apply',
    'ifreload_output' => 'ifreload Output:',
    'unauthorized' => 'Unauthorized',
    'edit' => "✏️ Edit",
    'save' => "💾 Save",
    'interface' => 'Interface',
    "create_bridge" => "➕ Create Bridge",
    "create_bond" => "➕ Create Bond (aggregation group)",
    "delete_interface" => "🗑️ Delete interface",
    'enter_interface_name' => 'Enter the interface name',
    'invalid_name' => 'The name cannot be empty',
    "ok" => "OK",
    "cancel" => "Cancel",
    "connection_error" => "Connection error.",
    "invalid_interface_name" => "Only logical interfaces (br* or bond*) can be deleted.",
    "interface_deleted" => "Interface successfully deleted.",
    "delete_failed" => "Failed to delete interface.",
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   users.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    "username" => "Username",
    "password" => "Password",
    "role" => "Role",
    "language" => "Language",
    "actions" => "Actions",
    "edit" => "✏️ Edit",
    "save" => "💾 Save",
    "delete" => "🗑️ Delete",
    "add_user" => "➕ Add User",
    "cancel" => "Cancel",
    "confirm_delete" => "Are you sure you want to delete the user",
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   policies.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //button
    "add_policy" => "➕ Add Policy",
    // nftables fields
    'family'           => 'Family',
    'table'            => 'Table',
    'chain'            => 'Chain',
    'id'               => 'ID',
    'position'         => 'Position',
    'action'           => 'Action',
    'enable'           => 'Enabled',
    'name'             => 'Name',
    'ip.protocol'      => 'IP Protocol',
    'ip.saddr.op'      => 'Negate Source IP',
    'ip.saddr'         => 'Source IP',
    'sport.op'         => 'Negate Source Port',
    'sport'            => 'Source Port',
    'ip.daddr.op'      => 'Negate Destination IP',
    'ip.daddr'         => 'Destination IP',
    'dport.op'         => 'Negate Destination Port',
    'dport'            => 'Destination Port',
    'meta.iifname'     => 'Input Interface',
    'meta.oifname'     => 'Output Interface',
    'ct.state'         => 'Connection State',
    'packets'          => 'Packets',
    'bytes'            => 'Bytes',
    'dnat.addr'        => 'DNAT IP',
    'dnat.port'        => 'DNAT port',
    'snat.addr'        => 'SNAT IP',
    'masquerade'        => 'Masquerade',
    'log'              => 'Log',
    

    // bpfilter fields
    // Network / Firewall rule fields
    'id'                 => 'ID',
    'hook'               => 'Hook',
    'chain'              => 'Chain',
    'position'           => 'Position',
    'action'             => 'Action',
    'enable'             => 'Enabled',
    'name'               => 'Name',
    'interface'          => 'Interface',
    'l3_protocol'        => 'Layer 3 Protocol',
    'l4_protocol'        => 'Layer 4 Protocol',
    'source'             => 'Source',
    'sport'              => 'Source Port',
    'destination'        => 'Destination',
    'dport'              => 'Destination Port',
    'tcp_flags'          => 'TCP Flags',
    'ipv6_next_header'   => 'IPv6 Next Header',
    'icmp_type'          => 'ICMP Type',
    'icmp_code'          => 'ICMP Code',
    'icmpv6_type'        => 'ICMPv6 Type',
    'icmpv6_code'        => 'ICMPv6 Code',
    'probability'        => 'Probability',

    
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   routing.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    'routing_title' => 'System Routing Table',
    'loading_routes' => 'Loading routes...',
    'table' => 'Table',
    'ip_version' => 'IP Version',
    'action' => 'Action',
    'destination' => 'Destination',
    'interface' => 'Interface',
    'gateway' => 'Gateway',
    'metric' => 'Metric',
    'proto' => 'Protocol',
    'scope' => 'Scope',
    'src' => 'Source',
    'rules_title' => 'Routing Rules',
    'rule_from' => 'From',
    'rule_table' => 'Table',
    'reload_routes' => 'Reload system routes running',
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   commit.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    'compare_commit' => 'Compare commit',
    'apply_commit'   => 'Apply commit',
    'config_audit' => 'Config Audit',
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   monitor.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    'menu_monitor' => 'Traffic Monitor',
    'search' => 'Search',
    'init_date' => 'Start Date',
    'init_time' => 'Start Time',
    'end_date' => 'End Date',
    'end_time' => 'End Time',
    'ip_addr' => 'Source IP',
    'ip_dest' => 'Destination IP',
    'sport' => 'Source Port',
    'dport' => 'Destination Port',
    'max_record' => 'Max Records',
    'L4protocol' => 'Proto',
    'time' => 'Time',
    'action' => 'Action',
    'firewall' => 'Firewall',
    'Logdate' => 'Log Date',
    'Logtime' => 'Log Time',
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   alias         //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    'id' => 'ID',
    'name' => 'Name',
    'content' => 'Content',
    "add_alias" => "➕ Add"
];
