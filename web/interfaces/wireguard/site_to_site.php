<?php
// Página hija WireGuard: configuración de túneles sede-a-sede.
// WireGuard child page: site-to-site tunnel configuration.
session_start();
if (!isset($_SESSION['username'])) { exit(htmlspecialchars($L['unauthorized'] ?? 'unauthorized', ENT_QUOTES, 'UTF-8')); }
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
    <h1><?= htmlspecialchars($L['wireguard_site_to_site'] ?? 'wireguard_site_to_site') ?></h1>
    <p><?= htmlspecialchars($L['wireguard_site_to_site_long_desc'] ?? ($L['wireguard_site_to_site_desc'] ?? 'wireguard_site_to_site_desc')) ?></p>
  </section>
  <div class="wireguard-help-box">
    <?= htmlspecialchars($L['wireguard_site_to_site_form_help'] ?? 'wireguard_site_to_site_form_help') ?>
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
