<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}
$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../lang/es.php";
}
$L = require $langFile;
$currentAlias = "dhcp";
$path_get_table_structure = "/networking/dhcp_table/get_table_structure.php";
$path_get_table_content = "/networking/dhcp_table/get_table_content.php";
$path_get_forms_from_table = "/networking/dhcp_table/get_forms_from_table.php";
$path_get_update = "/networking/dhcp_table/get_update_dhcp.php";
$path_get_delete = "/networking/dhcp_table/get_delete_dhcp.php";
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
  <script>
    const LANG = <?= json_encode($L) ?>;
  </script>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="../styles.css">
</head>
<body>
  <section class="cajita-cabecera">
    <h1><?= htmlspecialchars($L['sidebar_dhcp'] ?? 'DHCP') ?></h1>
  </section>
  <p class="section-description"><?= htmlspecialchars($L['dhcp_description'] ?? 'Configure dnsmasq DHCP server scopes or relay entries.') ?></p>
  <div id="<?= htmlspecialchars($currentAlias) ?>_table"></div>
  <script>
    renderTableGeneric(
      "<?= htmlspecialchars($currentAlias) ?>",
      "<?= htmlspecialchars($path_get_table_structure) ?>",
      "<?= htmlspecialchars($path_get_table_content) ?>",
      "<?= htmlspecialchars($path_get_forms_from_table) ?>",
      "<?= htmlspecialchars($path_get_update) ?>",
      "<?= htmlspecialchars($path_get_delete) ?>"
    );
  </script>
</body>
</html>
