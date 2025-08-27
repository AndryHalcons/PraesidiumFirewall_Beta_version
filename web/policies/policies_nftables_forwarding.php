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
  <script>
    const LANG = <?= json_encode($L) ?>;
  </script>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($L['sidebar_nftables_forwarding']) ?></title>
  <link rel="stylesheet" href="../styles.css">
</head>
<body>
  <h2><?= htmlspecialchars($L['sidebar_nftables_forwarding']) ?></h2>
  <div id="nftablesrules-forwarding"></div>

  <script src="/policies/policies_nftables_forwarding/policies_nftables_forwarding.js"></script>
</body>
</html>
