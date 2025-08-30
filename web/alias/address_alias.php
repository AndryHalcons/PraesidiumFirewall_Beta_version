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
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
  <script>
    const LANG = <?= json_encode($L) ?>;
    const USERNAME = <?= json_encode($username) ?>;
  </script>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($L['sidebar_address_alias']) ?></title>
</head>
<body>
  <h1><?= htmlspecialchars($L['sidebar_address_alias']) ?></h1>

  <div id="alias_address-table"></div>

  <script src="/alias/address_alias/address_alias.js"></script>
  <script>
    renderTableFromAlias("alias_address");
  </script>
</body>
</html>