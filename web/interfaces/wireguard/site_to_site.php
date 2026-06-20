<?php
session_start();
if (!isset($_SESSION['username'])) { exit("No autorizado"); }
$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../../lang/{$language}.php";
if (!file_exists($langFile)) { $langFile = __DIR__ . "/../../lang/es.php"; }
$L = require $langFile;
$currentAlias = "wireguard_site_to_site";
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head><meta charset="UTF-8"></head>
<body>
  <section class="wireguard-section-header">
    <h1><?= htmlspecialchars($L['wireguard_site_to_site'] ?? 'WireGuard site to site') ?></h1>
    <p><?= htmlspecialchars($L['wireguard_site_to_site_long_desc'] ?? ($L['wireguard_site_to_site_desc'] ?? 'Create point-to-point VPNs between two offices or networks.')) ?></p>
  </section>
  <div class="wireguard-help-box">
    <?= htmlspecialchars($L['wireguard_site_to_site_form_help'] ?? 'Complete the tunnel IPs, local/remote networks, endpoint and keys. Active entries require all critical fields.') ?>
  </div>
  <div id="<?= htmlspecialchars($currentAlias) ?>_table"></div>
  <script>
    window.LANG = <?= json_encode($L) ?>;
    renderTableGeneric(
      "<?= htmlspecialchars($currentAlias) ?>",
      "/interfaces/wireguard/site_to_site_table/get_table_structure.php",
      "/interfaces/wireguard/site_to_site_table/get_table_content.php",
      "/interfaces/wireguard/site_to_site_table/get_forms_from_table.php",
      "/interfaces/wireguard/site_to_site_table/get_update.php",
      "/interfaces/wireguard/site_to_site_table/get_delete.php"
    );
  </script>
</body>
</html>
