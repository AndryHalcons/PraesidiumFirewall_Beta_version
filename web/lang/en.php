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
    // Sidebar
    'sidebar_dashboard'     => 'Dashboard',
    'sidebar_rules'         => 'Policies',
    'sidebar_XDP_policies'  => 'Policies_XDP',
    'sidebar_TC_Egress'     => 'Policies_TC_Egress',
    'sidebar_TC_Ingress'    => 'Policies_TC_Ingress',
    'sidebar_nftables_prerouting'    => 'Policies_Prerouting',
    'sidebar_nftables_postrouting'   => 'Policies_Postrouting',
    'sidebar_logs'          => 'Logs',
    'sidebar_settings'      => 'Settings',
    'sidebar_Routing'      => 'Routing',
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
    // Table headers
    "actions" => "Actions",
    "id" => "ID",
    "position" => "Position",
    "name" => "Name",
    "description" => "Description",
    "action" => "Action",
    "enabled" => "Enabled?",

    // Match fields
    "iface" => "Interface",
    "l3_proto" => "L3 Protocol",
    "l4_proto" => "L4 Protocol",
    "ip4_saddr" => "IPv4 Source",
    "ip4_daddr" => "IPv4 Destination",
    "ip4_snet" => "IPv4 Source Network",
    "ip4_dnet" => "IPv4 Destination Network",
    "ip4_proto" => "IPv4 Protocol",
    "ip6_saddr" => "IPv6 Source",
    "ip6_daddr" => "IPv6 Destination",
    "ip6_snet" => "IPv6 Source Network",
    "ip6_dnet" => "IPv6 Destination Network",
    "ip6_nexthdr" => "IPv6 Next Header",
    "tcp_sport" => "TCP Source Port",
    "tcp_dport" => "TCP Destination Port",
    "tcp_flags" => "TCP Flags",
    "udp_sport" => "UDP Source Port",
    "udp_dport" => "UDP Destination Port",
    "icmp_type" => "ICMP Type",
    "icmp_code" => "ICMP Code",
    "icmpv6_type" => "ICMPv6 Type",
    "icmpv6_code" => "ICMPv6 Code",
    "probability" => "Probability",
    "add_policy" => "➕ Add Policy",
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
];
