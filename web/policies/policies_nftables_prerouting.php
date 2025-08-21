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
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($L['sidebar_nftables_prerouting']) ?></title>
</head>
<body>
  <h2><?= htmlspecialchars($L['sidebar_nftables_prerouting']) ?></h2>
  <div id="rules-output"></div>

  <script src="/policies/policies_nftables_prerouting/policies_nftables_prerouting.js"></script>
</body>
</html>
