<?php
session_start();
if (!isset($_SESSION['username'])) { exit("No autorizado"); }
$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../../lang/{$language}.php";
if (!file_exists($langFile)) { $langFile = __DIR__ . "/../../lang/es.php"; }
$L = require $langFile;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head><meta charset="UTF-8">
</head>
<body>
  <h1><?= htmlspecialchars($L['wireguard_remote_access'] ?? 'WireGuard remote access') ?></h1>
  <p><?= htmlspecialchars($L['wireguard_remote_access_desc'] ?? 'Create remote-access VPN servers and clients.') ?></p>

  <h2><?= htmlspecialchars($L['wireguard_remote_servers'] ?? 'VPN servers') ?></h2>
  <div id="wireguard_remote_access_table"></div>

  <h2><?= htmlspecialchars($L['wireguard_remote_clients'] ?? 'VPN clients') ?></h2>
  <div id="wireguard_remote_clients_table"></div>

  <script>
    window.LANG = <?= json_encode($L) ?>;
    renderTableGeneric(
      "wireguard_remote_access",
      "/interfaces/wireguard/remote_access_table/get_table_structure.php",
      "/interfaces/wireguard/remote_access_table/get_table_content.php",
      "/interfaces/wireguard/remote_access_table/get_forms_from_table.php",
      "/interfaces/wireguard/remote_access_table/get_update.php",
      "/interfaces/wireguard/remote_access_table/get_delete.php"
    );
    renderTableGeneric(
      "wireguard_remote_clients",
      "/interfaces/wireguard/remote_clients_table/get_table_structure.php",
      "/interfaces/wireguard/remote_clients_table/get_table_content.php",
      "/interfaces/wireguard/remote_clients_table/get_forms_from_table.php",
      "/interfaces/wireguard/remote_clients_table/get_update.php",
      "/interfaces/wireguard/remote_clients_table/get_delete.php"
    );
  </script>
</body>
</html>
