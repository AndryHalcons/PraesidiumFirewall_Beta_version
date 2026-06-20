<?php
// Página hija WireGuard: configuración de servidores y clientes de acceso remoto.
// WireGuard child page: remote-access server and client configuration.
session_start();
if (!isset($_SESSION['username'])) { exit(htmlspecialchars($L['unauthorized'] ?? 'unauthorized', ENT_QUOTES, 'UTF-8')); }
$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../../lang/{$language}.php";
if (!file_exists($langFile)) { $langFile = __DIR__ . "/../../lang/es.php"; }
$L = require $langFile;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head><meta charset="UTF-8"></head>
<body>
  <section class="wireguard-section-header">
    <h1><?= htmlspecialchars($L['wireguard_remote_access'] ?? 'wireguard_remote_access') ?></h1>
    <p><?= htmlspecialchars($L['wireguard_remote_access_long_desc'] ?? ($L['wireguard_remote_access_desc'] ?? 'wireguard_remote_access_desc')) ?></p>
  </section>
  <div class="wireguard-help-box">
    <?= htmlspecialchars($L['wireguard_remote_access_form_help'] ?? 'wireguard_remote_access_form_help') ?>
  </div>

  <h2><?= htmlspecialchars($L['wireguard_remote_servers'] ?? 'wireguard_remote_servers') ?></h2>
  <p class="wireguard-subsection-help"><?= htmlspecialchars($L['wireguard_remote_servers_help'] ?? 'wireguard_remote_servers_help') ?></p>
  <div id="wireguard_remote_access_table"></div>

  <h2><?= htmlspecialchars($L['wireguard_remote_clients'] ?? 'wireguard_remote_clients') ?></h2>
  <p class="wireguard-subsection-help"><?= htmlspecialchars($L['wireguard_remote_clients_help'] ?? 'wireguard_remote_clients_help') ?></p>
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
