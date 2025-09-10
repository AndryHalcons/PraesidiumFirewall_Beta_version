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
$currentAlias = "vlans";
// check interfaces scripts add/quit new/old physical interfaces
$script5 = '/usr/bin/python3 /var/www/backend/checks/check_interfaces/main_interfaces_check.py';
shell_exec("sudo $script5 2>&1");
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
  <h1><?= htmlspecialchars($L['sidebar_vlans']) ?></h1>
  <div id="<?= htmlspecialchars($currentAlias) ?>_table"></div>
  <script>
    renderTableInterface("<?= htmlspecialchars($currentAlias) ?>");
  </script>
</body>
</html>