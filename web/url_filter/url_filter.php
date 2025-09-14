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
$currentAlias = "url_filter";
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
  <h1><?= htmlspecialchars($L['sidebar_url_filtering']) ?></h1>
  <div id="<?= htmlspecialchars($currentAlias) ?>_table"></div>
  <script>
    renderTableURLfilter("<?= htmlspecialchars($currentAlias) ?>");
  </script>
</body>
</html>