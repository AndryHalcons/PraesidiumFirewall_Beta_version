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
    'sidebar_services'      => 'Services',
    'sidebar_AliasObjects'      => 'Objects Aliases',
    'sidebar_address_alias'           => 'Address Aliases',
    'sidebar_address_group_alias'     => 'Address Groups',
    'sidebar_services_objects'        => 'Service Aliases',
    'sidebar_services_group_objects'  => 'Service Groups',
    'sidebar_url_filtering'  => 'URL filter',
    'sidebar_url_list'  => 'URL List',
    'sidebar_url_network_list'  => 'Network List',
    'sidebar_url_network_list_profile'  => 'Network List Profile',
    'sidebar_url_policies'  => 'URL Policies',
    'sidebar_url_listen_ports'  => 'Listen ports',
    'sidebar_url_profile'  => 'URL List Profile',
    'sidebar_url_port_profile'  => 'Safe Ports Profile',
    'sidebar_certificates'  => 'Certificates',
    'sidebar_networking'  => 'Networking',
    'sidebar_dhcp'  => 'DHCP',
    'sidebar_Routing'      => 'Routing',

    // Content
    'main_content'          => 'Content here...',
    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   Dashboard.php  //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    'dashboard_subtitle' => 'Real-time system status: CPU, memory and traffic per interface.',
    'dashboard_cpu_per_core' => 'CPU usage per core',
    'dashboard_ram_usage' => 'RAM usage',
    'dashboard_bandwidth_by_interface' => 'Bandwidth per interface',
    'dashboard_refresh_interval' => 'Refresh every 5s',
    'dashboard_interface' => 'Interface',
    'dashboard_rx_rate' => 'Inbound',
    'dashboard_tx_rate' => 'Outbound',
    'dashboard_rx_total' => 'Total received',
    'dashboard_tx_total' => 'Total sent',
    'dashboard_loading' => 'Loading metrics...',
    'dashboard_updated' => 'Updated',
    'dashboard_error' => 'Error loading metrics',
    'dashboard_no_interfaces' => 'No interfaces available',
    'dashboard_core_label' => 'Core',
    'dashboard_cpu_percent_label' => 'CPU %',
    'dashboard_ram_used_label' => 'Used',
    'dashboard_ram_free_label' => 'Free',
    'dashboard_ram_cached_label' => 'Cached',
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
    'mode'                              => 'Mode',
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
    //table profile
    'profile' => 'Profile',
    'type' => 'Type',
    //table polices
    'negate_ip_addr' => 'Negate IP',
    'ip_addr_group' => 'IPs',
    'negate_profile' => 'Negate Profile',
    'options' => 'Options',


    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   certificates      //////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    //buttons
    'download' => '⬇️ Download',

    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
    ////////////////////   system logging settings   //////////////////////
    ///////////////////////////////////////////////////////////////////////
    'system_logging_title' => 'System logs',
    'system_logging_description' => 'Configure journald, classic Ubuntu logs and nftables log limits. Changes are saved as candidate and applied with Commit.',
    'system_logging_group_journald' => 'systemd journal',
    'system_logging_group_system_logs' => 'Classic Ubuntu logs',
    'system_logging_group_nftables_logs' => 'Praesidium nftables logs',
    'system_logging_journald_system_max_use' => 'Journal: persistent maximum size',
    'system_logging_journald_system_keep_free' => 'Journal: protected free space',
    'system_logging_journald_runtime_max_use' => 'Journal: runtime maximum size',
    'system_logging_journald_max_retention_sec' => 'Journal: maximum retention',
    'system_logging_journald_compress' => 'Journal: compress',
    'system_logging_system_logs_enabled' => 'Ubuntu logs: apply rotation',
    'system_logging_system_logs_rotation' => 'Ubuntu logs: frequency',
    'system_logging_system_logs_rotate' => 'Ubuntu logs: rotations',
    'system_logging_system_logs_maxsize' => 'Ubuntu logs: maximum size',
    'system_logging_system_logs_compress' => 'Ubuntu logs: compress',
    'system_logging_system_logs_delaycompress' => 'Ubuntu logs: delay compression',
    'system_logging_nftables_logs_enabled' => 'nftables logs: enable dedicated file',
    'system_logging_nftables_logs_size' => 'nftables logs: maximum size',
    'system_logging_nftables_logs_rotate' => 'nftables logs: rotations',
    'system_logging_nftables_logs_compress' => 'nftables logs: compress',
    'system_logging_nftables_logs_delaycompress' => 'nftables logs: delay compression',
    'system_logging_save_candidate' => 'Save candidate',
    'system_logging_loaded' => 'Candidate configuration loaded.',
    'system_logging_saved' => 'Configuration saved as candidate. Apply Commit to move it to running.',
    'system_logging_load_error' => 'Error loading configuration',
    'system_logging_save_error' => 'Error saving configuration',

    'dhcp_description' => 'Configure dnsmasq DHCP server scopes or relay entries.',
    'range_start' => 'Range start',
    'range_end' => 'Range end',
    'lease_time' => 'Lease time',
    'dns_primary' => 'Primary DNS',
    'dns_secondary' => 'Secondary DNS',
    'ntp_server' => 'NTP server',
    'relay_local_ip' => 'Relay local IP',
    'relay_dest_server' => 'Relay destination server',
    // WireGuard VPN scenarios / Escenarios VPN WireGuard
    'wireguard_site_to_site' => 'WireGuard site to site',
    'wireguard_site_to_site_desc' => 'Create point-to-point tunnels between two offices or networks.',
    'wireguard_remote_access' => 'WireGuard remote access',
    'wireguard_remote_access_desc' => 'Create remote-access VPN servers and clients.',
    'wireguard_overview_description' => 'Choose the WireGuard scenario you want to configure. Each option uses separate forms to reduce mistakes and keep the configuration clear.',
    'wireguard_remote_servers' => 'VPN servers',
    'wireguard_remote_clients' => 'VPN clients',
    'open' => 'Open',
    'enabled' => 'Enabled',
    'interface' => 'Interface',
    'local_tunnel_ip' => 'Local tunnel IP',
    'remote_tunnel_ip' => 'Remote tunnel IP',
    'local_networks' => 'Local networks',
    'remote_networks' => 'Remote networks',
    'listen_port' => 'Listen port',
    'remote_endpoint' => 'Remote endpoint',
    'private_key' => 'Private key',
    'remote_public_key' => 'Remote public key',
    'keepalive' => 'Keepalive',
    'server_vpn_ip' => 'VPN server IP',
    'vpn_network' => 'VPN network',
    'internal_networks' => 'Internal networks',
    'dns' => 'DNS',
    'vpn' => 'VPN',
    'client_vpn_ip' => 'Client VPN IP',
    'client_public_key' => 'Client public key',
    'allowed_ips' => 'Allowed IPs',

    'wireguard_site_to_site_long_desc' => 'Use this section to join two remote networks with a WireGuard tunnel between sites. Validation enforces coherent tunnel IPs, non-overlapping networks and a valid remote endpoint.',
    'wireguard_remote_access_long_desc' => 'Use this section to let remote users securely access internal networks through WireGuard servers and associated clients.',
    'wireguard_site_to_site_form_help' => 'Complete interface, tunnel IPs, local/remote networks, endpoint, port and keys. If the entry is enabled, critical fields are mandatory.',
    'wireguard_remote_access_form_help' => 'Create a VPN server first. Then add clients associated with that server. Duplicate client IPs and public keys are not allowed.',
    'wireguard_remote_servers_help' => 'Define the server interface, VPN network, accessible internal networks, listen port and private key.',
    'wireguard_remote_clients_help' => 'Associate each client with an existing server. The client IP must belong to the selected server VPN network.',
    'wireguard_site_to_site_hint_1' => 'Join two locations with a dedicated tunnel.',
    'wireguard_site_to_site_hint_2' => 'Validate tunnel IPs, remote networks, endpoint and keys.',
    'wireguard_remote_access_hint_1' => 'Create the remote-access VPN server first.',
    'wireguard_remote_access_hint_2' => 'Then add clients associated with the server.',
    'wireguard_kicker' => 'VPN',
    'wireguard_load_error' => 'Content could not be loaded.',
    'wireguard_error_invalid_payload' => 'No valid data was received to save.',
    'wireguard_error_delete_missing' => 'The entry you are trying to delete does not exist or was already removed.',
    'wireguard_error_save_after_delete' => 'WireGuard configuration could not be saved after deletion.',
    'wireguard_error_save_permissions' => 'WireGuard configuration could not be saved. Check wireguard.json permissions.',
    'wireguard_error_required' => 'Field "{field}" is required to save an enabled entry.',
    'wireguard_error_bool' => 'Field "{field}" can only be enabled or disabled.',
    'wireguard_error_name_format' => 'Field "{field}" can only use letters, numbers, hyphens, dots and underscores; maximum 64 characters.',
    'wireguard_error_interface_format' => 'Interface must be a valid Linux name up to 15 characters, for example wg0 or wg-office.',
    'wireguard_error_cidr_format' => 'Field "{field}" must use CIDR format, for example 192.168.20.0/24. Separate multiple values with commas.',
    'wireguard_error_invalid_ip_in_field' => 'IP "{ip}" is not valid in field "{field}".',
    'wireguard_error_ipv4_prefix' => 'The IPv4 prefix for "{field}" must be between /0 and /32.',
    'wireguard_error_ipv6_prefix' => 'The IPv6 prefix for "{field}" must be between /0 and /128.',
    'wireguard_error_port_range' => 'Listen port must be a number between 1 and 65535.',
    'wireguard_error_int_range' => 'Field "{field}" must be a number between {min} and {max}.',
    'wireguard_error_key_format' => 'Field "{field}" does not look like a valid WireGuard key. It must be a 44-character base64 key ending in =.',
    'wireguard_error_endpoint_ipv6' => 'IPv6 endpoint must use [IPv6]:port format, for example [2001:db8::1]:51820.',
    'wireguard_error_endpoint_host' => 'Endpoint host can only be a domain, IPv4, or bracketed IPv6 address.',
    'wireguard_error_endpoint_format' => 'Remote endpoint must use host:port format, for example vpn.site.example:51820.',
    'wireguard_error_single_tunnel_ip' => 'Site-to-site VPN must have one local tunnel IP and one remote tunnel IP.',
    'wireguard_error_tunnel_same_family' => 'Local and remote tunnel IPs must both be IPv4 or both be IPv6.',
    'wireguard_error_tunnel_same_network' => 'Local and remote tunnel IPs must belong to the same tunnel network, for example 10.10.10.1/30 and 10.10.10.2/30.',
    'wireguard_error_network_overlap' => 'Networks in "{left}" and "{right}" must not overlap.',
    'wireguard_error_duplicate_interface' => 'Interface "{interface}" is already used by another WireGuard VPN.',
    'wireguard_error_duplicate_port' => 'Port {port} is already used by another WireGuard VPN.',
    'wireguard_error_duplicate_client_ip' => 'The VPN client IP is already assigned to another client.',
    'wireguard_error_duplicate_client_key' => 'The client public key is already used by another client.',
    'wireguard_error_missing_client_vpn' => 'The VPN selected for the client does not exist. Create the remote-access server first.',
    'wireguard_error_single_client_ip' => 'Each client must have a single VPN IP in CIDR format, for example 10.20.0.2/32.',
    'wireguard_error_client_ip_outside_vpn' => 'The client IP must belong to the selected server VPN network.',
    'wireguard_error_single_server_ip' => 'The remote-access server must have a single VPN IP.',
    'wireguard_error_server_ip_outside_vpn' => 'The VPN server IP must belong to the configured VPN network.',
    'wireguard_error_unknown_field' => 'Field "{field}" does not belong to this WireGuard form.',
    'wireguard_error_field_too_long' => 'Field "{field}" is too long.',
    'wireguard_error_delete_server_has_clients' => 'This VPN cannot be deleted because it still has associated clients. Delete or reassign the clients first.',
];
