<?php
return [


    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   Generic      ///////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //buttons
    'add'                 => '➕ Add',
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
    'sidebar_url_filtering'  => 'URL filter',
    'sidebar_url_list'  => 'URL List',
    'sidebar_url_policies'  => 'URL Policies',
    'sidebar_url_listen_ports'  => 'URL Listen ports',
    'sidebar_url_profile'  => 'URL Profile',
    'sidebar_certificates'  => 'Certificates',

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
    //buttons
    "create_bridge" => "➕ Create Bridge",
    "create_bond" => "➕ Create Bond (aggregation group)",
    'add_interface' => 'Add Interface',
    "delete_interface" => "🗑️ Delete interface",
    "ok" => "OK",
    "cancel" => "Cancel",
    'edit' => "✏️ Edit",
    'save' => "💾 Save",
    'save_and_apply' => '💾 Save and Apply',

    //aux
    'network_config_title' => 'Network Interface Configuration',
    'unauthorized' => 'Unauthorized',
    'enter_interface_name' => 'Enter the interface name',

    // Network configuration fields
    'name'                              => 'Name',
    'dhcp4'                             => 'DHCPv4',
    'dhcp6'                             => 'DHCPv6',
    'addresses'                         => 'IP + CIDR',
    'gateway4'                          => 'IPv4 Gateway',
    'gateway6'                          => 'IPv6 Gateway',
    'mtu'                               => 'MTU',
    'macaddress'                        => 'MAC Address',
    'nameservers.addresses'            => 'DNS Addresses',
    'nameservers.search'               => 'DNS Search Domains',
    'optional'                          => 'Optional',
    'accept-ra'                         => 'Accept Router Advertisements',
    'wakeonlan'                         => 'Wake-on-LAN',
    'routes.to'                         => 'Route Destination',
    'routes.via'                        => 'Route Gateway',
    'routes.metric'                     => 'Route Metric',
    'ipv6-privacy'                      => 'IPv6 Privacy Extensions',
    'dhcp4-overrides.use-dns'          => 'Override DHCPv4 DNS',
    'dhcp4-overrides.use-routes'       => 'Override DHCPv4 Routes',
    'dhcp4-overrides.send-hostname'    => 'Send Hostname via DHCPv4',
    'dhcp4-overrides.hostname'         => 'Custom DHCPv4 Hostname',
    'dhcp4-overrides.use-hostname'     => 'Use System Hostname for DHCPv4',
    'dhcp6-overrides.use-dns'          => 'Override DHCPv6 DNS',
    'dhcp6-overrides.use-routes'       => 'Override DHCPv6 Routes',
    'match.name'                        => 'Match Interface Name',
    'match.macaddress'                 => 'Match MAC Address',
    'match.driver'                     => 'Match Driver',
    'set-name'                          => 'Rename Interface',
    'interfaces'                        => 'Interfaces',
    'parameters.mode'                  => 'Bonding Mode',
    'parameters.primary'               => 'Primary Interface',
    'parameters.mii-monitor-interval'  => 'MII Monitor Interval',
    'parameters.up-delay'              => 'Up Delay',
    'parameters.down-delay'            => 'Down Delay',
    'parameters.lacp-rate'             => 'LACP Rate',
    'parameters.transmit-hash-policy' => 'Transmit Hash Policy',
    'parameters.min-links'             => 'Minimum Links',
    'parameters.stp'                   => 'Spanning Tree Protocol',
    'parameters.priority'              => 'Bridge Priority',
    'parameters.forward-delay'         => 'Forward Delay',
    'parameters.hello-time'            => 'Hello Time',
    'parameters.max-age'               => 'Max Age',
    'parameters.ageing-time'           => 'Ageing Time',
    'id'                                => 'ID',
    'link'                              => 'Link',
    'access-points.<SSID>.password'    => 'Wi-Fi Password',
    'access-points.<SSID>.mode'        => 'Wi-Fi Mode',
    'access-points.<SSID>.band'        => 'Wi-Fi Band',
    'access-points.<SSID>.channel'     => 'Wi-Fi Channel',
    'mode'                              => 'Tunnel Mode',
    'local'                             => 'Local Address',
    'remote'                            => 'Remote Address',
    'port'                              => 'Port',
    'key.private'                       => 'Private Key',
    'peers.keys.public'                => 'Peer Public Key',
    'peers.allowed-ips'                => 'Allowed IPs',
    'peers.keepalive'                  => 'Persistent Keepalive',
    'peers.endpoint'                   => 'Peer Endpoint',
    'routes.table'                     => 'Routing Table',
    'routing-policy.from'              => 'Routing Policy Source',
    'routing-policy.table'             => 'Routing Policy Table',
    'mark'                              => 'Packet Mark',



    //basura a revisar
    'name' => 'Name',
    'trigger' => 'Trigger',
    'method' => 'Method',
    'ip' => 'IP Address',
    'netmask' => 'Netmask',
    'gateway' => 'Gateway',
    'remove' => 'Remove',
    'ifreload_output' => 'ifreload Output:',
    'interface' => 'Interface',
    'invalid_name' => 'The name cannot be empty',
    "connection_error" => "Connection error.",
    "invalid_interface_name" => "Only logical interfaces (br* or bond*) can be deleted.",
    "interface_deleted" => "Interface successfully deleted.",
    "delete_failed" => "Failed to delete interface.",

    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   users.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //butons
    "edit" => "✏️ Edit",
    "save" => "💾 Save",
    "delete" => "🗑️ Delete",
    "add_user" => "➕ Add User",
    "cancel" => "Cancel",

    //aux
    "user_name" => "Username",
    "user_pass" => "Password",
    "user_role" => "Role",
    "user_language" => "Language",
    "actions" => "Actions",
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
    //buttons
    'reload_routes' => 'Reload system routes running',
    //aux
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
    
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   commit.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //butons
    'compare_commit' => 'Compare commit',
    'apply_commit'   => 'Apply commit',
    'config_audit' => 'Config Audit',
    //aux

    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   monitor.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //butons
    'search' => 'Search',

    //table log
    'menu_monitor' => 'Traffic Monitor',
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
    //buttons
    "add_alias" => "➕ Add",
    //table alias
    'id' => 'ID',
    'name' => 'Name',
    'content' => 'Content',
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   url filter        //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //buttons
    'upload_button' => 'Upload file',
    'profile' => 'Profile',
    'type' => 'Type'
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   certificates      //////////////////////////////
    ///////////////////////////////////////////////////////////////////////


    
];
