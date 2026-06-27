<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}
$username = $_SESSION['username'];
$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../lang/es.php";
}
$L = require $langFile;
$currentAlias = "alias_addr_group";
$path_get_table_structure = "/alias/common_alias_actions/get_table_structure.php";
$path_get_table_content = "/alias/common_alias_actions/get_table_content.php";
$path_get_forms_from_table = "/alias/common_alias_actions/get_forms_from_table.php";
$path_get_update = "/alias/common_alias_actions/update_alias.php";
$path_get_delete = "/alias/common_alias_actions/delete_alias.php";
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
  <script>
    const LANG = <?= json_encode($L) ?>;
    const USERNAME = <?= json_encode($username) ?>;
  </script>
  <meta charset="UTF-8">
</head>
<body>
  <section class="cajita-cabecera">
    <h1><?= htmlspecialchars($L['sidebar_address_group_alias']) ?></h1>
  </section>
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