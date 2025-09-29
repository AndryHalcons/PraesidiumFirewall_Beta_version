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
$currentAlias = "url_networks_list_profile";
$path_get_table_structure = "/url_filter/url_filter_table/get_table_structure_url_filter.php";
$path_get_table_content = "/url_filter/url_filter_table/get_table_content_url_filter.php";
$path_get_forms_from_table = "/url_filter/url_filter_table/get_forms_from_table_url_filter.php";
$path_get_update = "/url_filter/url_filter_table/get_update_policy_url_filter.php";
$path_get_delete = "/url_filter/url_filter_table/get_delete_url_filter.php";
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
  <script>
    const LANG = <?= json_encode($L) ?>;
    const USERNAME = <?= json_encode($username) ?>;
  </script>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="../styles.css">
</head>
<body>
  <h1><?= htmlspecialchars($L['sidebar_url_network_list_profile']) ?></h1>
  <div id="<?= htmlspecialchars($currentAlias) ?>_table"></div>
    <script>
     renderTableGeneric(
      <?= json_encode($currentAlias) ?>,
      <?= json_encode($path_get_table_structure) ?>,
      <?= json_encode($path_get_table_content) ?>,
      <?= json_encode($path_get_forms_from_table) ?>,
      <?= json_encode($path_get_update) ?>,
      <?= json_encode($path_get_delete) ?>
    );
  </script>

</body>