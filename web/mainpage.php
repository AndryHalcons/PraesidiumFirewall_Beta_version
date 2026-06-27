<?php
require_once __DIR__ . '/common/security/auth.php';
require_login_page();
require_once __DIR__ . '/common/security/csrf.php';


$username = $_SESSION['username'];
$role     = $_SESSION['role'];
$language = $_SESSION['language'] ?? 'es';

$langFile = __DIR__ . "/lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/lang/es.php";
}
$L = require $langFile;
$csrfToken = csrf_get_token();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($L['title']) ?></title>
    <link rel="stylesheet" href="styles.css">
    <script src="javascript.js"></script>
    <script src="/my_js/generic_table.js"></script>
    <script src="/libraries/chart.umd.js"></script>
    <script src="/my_js/url_file_list.js"></script>
    <script src="/my_js/certs_table.js"></script>
</head>
<body>

    <div class="header-top">
        <div class="header-left">
            <h1><?= htmlspecialchars($L['title']) ?></h1>
            <h2><?= htmlspecialchars($L['welcome']) ?>, <?= htmlspecialchars($username) ?>!</h2>
            <div class="user-info">
                <p><?= htmlspecialchars($L['role']) ?>: <?= htmlspecialchars($role) ?></p>
                <p><?= htmlspecialchars($L['language']) ?>: <?= htmlspecialchars($language) ?></p>
            </div>
        </div>
    </div>

    <div class="top-menu">
        <a href="#" data-page="home.php"><?= htmlspecialchars($L['menu_home']) ?></a>
        <a href="#" data-page="monitor/monitor.php"><?= htmlspecialchars($L['menu_monitor']) ?></a>
        <a href="#" data-page="users/users.php"><?= htmlspecialchars($L['menu_users']) ?></a>
        <a href="#" data-page="commits/commit.php"><?= htmlspecialchars($L['menu_commit']) ?></a>
        <a href="logout.php"><?= htmlspecialchars($L['menu_logout']) ?></a>
    </div>


    <div class="sidebar">
      <details open>
        <summary><?= htmlspecialchars($L['sidebar_dashboard']) ?></summary>
        <a href="#" data-page="dashboard/dashboard.php"><?= htmlspecialchars($L['sidebar_dashboard']) ?></a>
      </details>
      <details>
        <summary><?= htmlspecialchars($L['sidebar_interfaces']) ?></summary>
        <a href="#" data-page="interfaces/ethernets.php"><?= htmlspecialchars($L['sidebar_ethernets']) ?></a>
        <a href="#" data-page="interfaces/bonds.php"><?= htmlspecialchars($L['sidebar_bonds']) ?></a>
        <a href="#" data-page="interfaces/bridges.php"><?= htmlspecialchars($L['sidebar_bridges']) ?></a>
        <a href="#" data-page="interfaces/vlans.php"><?= htmlspecialchars($L['sidebar_vlans']) ?></a>
        <a href="#" data-page="interfaces/wireguard/site_to_site.php"> <?= htmlspecialchars($L['wireguard_site_to_site']) ?></a>
        <a href="#" data-page="interfaces/wireguard/remote_access.php"> <?= htmlspecialchars($L['wireguard_remote_access']) ?></a>
        <a href="#" data-page="interfaces/wifis.php"><?= htmlspecialchars($L['sidebar_wifis']) ?></a>
      </details>
      <details>
        <summary>BPfilter</summary>
        <a href="#" data-page="policies/policies_xdp.php"><?= htmlspecialchars($L['sidebar_XDP_policies']) ?></a>
        <a href="#" data-page="policies/policies_TC_ingress.php"><?= htmlspecialchars($L['sidebar_TC_Ingress']) ?></a>
        <a href="#" data-page="policies/policies_TC_egress.php"><?= htmlspecialchars($L['sidebar_TC_Egress']) ?></a>
      </details>
      <details>
        <summary>Nftables</summary>
        <a href="#" data-page="policies/policies_nftables_forwarding.php"><?= htmlspecialchars($L['sidebar_nftables_forwarding']) ?></a>
        <a href="#" data-page="policies/policies_nftables_prerouting.php"><?= htmlspecialchars($L['sidebar_nftables_prerouting']) ?></a>
        <a href="#" data-page="policies/policies_nftables_postrouting.php"><?= htmlspecialchars($L['sidebar_nftables_postrouting']) ?></a>
        <a href="#" data-page="policies/policies_nftables_input.php"><?= htmlspecialchars($L['sidebar_nftables_input']) ?></a>
        <a href="#" data-page="policies/policies_nftables_output.php"><?= htmlspecialchars($L['sidebar_nftables_output']) ?></a>
      </details>
      <details>
        <summary><?= htmlspecialchars($L['sidebar_url_filtering']) ?></summary>
        <a href="#" data-page="/url_filter/url_policies.php"><?= htmlspecialchars($L['sidebar_url_policies']) ?></a>
        <a href="#" data-page="/url_filter/url_profile.php"><?= htmlspecialchars($L['sidebar_url_profile']) ?></a>
        <a href="#" data-page="/url_filter/url_port_profile.php"><?= htmlspecialchars($L['sidebar_url_port_profile']) ?></a>
        <a href="#" data-page="/url_filter/url_networks_list_profile.php"><?= htmlspecialchars($L['sidebar_url_network_list_profile']) ?></a>
        <a href="#" data-page="/url_filter/url_list.php"><?= htmlspecialchars($L['sidebar_url_list']) ?></a>
        <a href="#" data-page="/url_filter/url_network_list.php"><?= htmlspecialchars($L['sidebar_url_network_list']) ?></a>
        <a href="#" data-page="/url_filter/url_listen_ports.php"><?= htmlspecialchars($L['sidebar_url_listen_ports']) ?></a>
      </details>
      <details>
        <summary><?= htmlspecialchars($L['sidebar_AliasObjects']) ?></summary>
        <a href="#" data-page="/alias/address_alias.php"><?= htmlspecialchars($L['sidebar_address_alias']) ?></a>
        <a href="#" data-page="/alias/address_alias_group.php"><?= htmlspecialchars($L['sidebar_address_group_alias']) ?></a>
        <a href="#" data-page="/alias/service_alias.php"><?= htmlspecialchars($L['sidebar_services_objects']) ?></a>
        <a href="#" data-page="/alias/service_alias_group.php"><?= htmlspecialchars($L['sidebar_services_group_objects']) ?></a>
      </details>
      <details>
        <summary><?= htmlspecialchars($L['sidebar_networking']) ?></summary>
        <a href="#" data-page="/networking/dhcp_config.php"><?= htmlspecialchars($L['sidebar_dhcp']) ?></a>
        <a href="#" data-page="routing/routing.php"><?= htmlspecialchars($L['sidebar_Routing']) ?></a>
      </details>
      <details>
        <summary><?= htmlspecialchars($L['sidebar_certificates']) ?></summary>
        <a href="#" data-page="/certificates/certificates.php"><?= htmlspecialchars($L['sidebar_certificates']) ?></a>
      </details>
      <details>
        <summary><?= htmlspecialchars($L['sidebar_system']) ?></summary>
        <a href="#" data-page="logs.php"><?= htmlspecialchars($L['sidebar_logs']) ?></a>
        <a href="#" data-page="/system/logging/system_logging.php"><?= htmlspecialchars($L['sidebar_settings']) ?></a>
        <a href="#" data-page="/system/services/services.php"><?= htmlspecialchars($L['sidebar_services']) ?></a>
      </details>
    </div>


    <div class="main-content" id="main-content">
        <p><?= htmlspecialchars($L['main_content']) ?></p>
    </div>
    
</body>
</html>
