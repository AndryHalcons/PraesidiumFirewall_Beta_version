<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'];
$role     = $_SESSION['role'];
$language = $_SESSION['language'] ?? 'es';

$langFile = __DIR__ . "/lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/lang/es.php";
}
$L = require $langFile;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($L['title']) ?></title>
    <link rel="stylesheet" href="styles.css">
    <script src="/libraries/chart.umd.js"></script>
    <script src="/alias/common_alias_actions/alias_table.js"></script>
    <script src="/policies/common_policy_actions_nft/nft_table.js"></script>
    <script src="/policies/common_policy_actions_bpf/bpf_table.js"></script>

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
        <a href="#" data-page="interfaces/interfaces.php"><?= htmlspecialchars($L['menu_interfaces']) ?></a>
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
        <summary><?= htmlspecialchars($L['sidebar_AliasObjects']) ?></summary>
        <a href="#" data-page="/alias/address_alias.php"><?= htmlspecialchars($L['sidebar_address_alias']) ?></a>
        <a href="#" data-page="/alias/address_alias_group.php"><?= htmlspecialchars($L['sidebar_address_group_alias']) ?></a>
        <a href="#" data-page="/alias/service_alias.php"><?= htmlspecialchars($L['sidebar_services_objects']) ?></a>
        <a href="#" data-page="/alias/service_alias_group.php"><?= htmlspecialchars($L['sidebar_services_group_objects']) ?></a>
      </details>

      <details>
        <summary><?= htmlspecialchars($L['sidebar_system']) ?></summary>
        <a href="#" data-page="routing/routing.php"><?= htmlspecialchars($L['sidebar_Routing']) ?></a>
        <a href="#" data-page="logs.php"><?= htmlspecialchars($L['sidebar_logs']) ?></a>
        <a href="#" data-page="settings.php"><?= htmlspecialchars($L['sidebar_settings']) ?></a>
      </details>
    </div>


    <div class="main-content" id="main-content">
        <p><?= htmlspecialchars($L['main_content']) ?></p>
    </div>

    <!-- JavaScript externo -->
    <script src="javascript.js"></script>
</body>
</html>
