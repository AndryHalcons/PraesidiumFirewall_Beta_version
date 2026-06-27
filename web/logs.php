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
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
  <script>
    const LANG = <?= json_encode($L) ?>;
    const USERNAME = <?= json_encode($username) ?>;
  </script>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($L['sidebar_logs']) ?></title>
</head>
<body>
  <section class="cajita-cabecera">
    <h2><?= htmlspecialchars($L['sidebar_logs']) ?></h2>
  </section>
  <div id="tabla-monitorOptions"></div>
  <div id="tabla-monitorLogs"></div>
  <script src="/monitor/logs_table/monitor.js"></script>
</body>
</html>
