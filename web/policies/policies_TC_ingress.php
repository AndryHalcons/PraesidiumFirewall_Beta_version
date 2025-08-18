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
  <title><?= htmlspecialchars($L['sidebar_TC_Ingress']) ?></title>
</head>
<body>
  <h2><?= htmlspecialchars($L['sidebar_TC_Ingress']) ?></h2>
  <div id="rules-output"></div>

  <script src="/policies/policies_TC_ingress/policies_TC_ingress.js"></script>
</body>
</html>
