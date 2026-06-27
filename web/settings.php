<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}
$username = $_SESSION['username'];
$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/lang/es.php";
}
$L = require $langFile;
$currentAlias = "system_logging";
$path_get_table_structure = "/system/logging/system_logging_table/get_table_structure.php";
$path_get_table_content = "/system/logging/system_logging_table/get_table_content.php";
$path_get_forms_from_table = "/system/logging/system_logging_table/get_forms_from_table.php";
$path_get_update = "/system/logging/system_logging_table/get_update_system_logging.php";
$path_get_delete = "/system/logging/system_logging_table/get_delete_system_logging.php";
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
  <script>
    window.LANG = <?= json_encode($L) ?>;
    window.USERNAME = <?= json_encode($username) ?>;
  </script>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="/styles.css">
</head>
<body>
  <section class="cajita-cabecera">
    <h1><?= htmlspecialchars($L['sidebar_settings']) ?></h1>
  </section>
  <p><?= htmlspecialchars($L['system_logging_description'] ?? '') ?></p>
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
