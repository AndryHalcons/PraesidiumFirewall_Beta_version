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
$currentAlias = "alias_service";
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
  <script>
    const LANG = <?= json_encode($L) ?>;
    const USERNAME = <?= json_encode($username) ?>;
    const aliasName = <?= json_encode("alias_service") ?>;
  </script>
  <meta charset="UTF-8">
</head>
<body>
  <h1><?= htmlspecialchars($L['sidebar_services_objects']) ?></h1>
  <div id="<?= htmlspecialchars($currentAlias) ?>_table"></div>
  <script>
    renderTableFromAlias("<?= htmlspecialchars($currentAlias) ?>");
  </script>
</body>
</html>