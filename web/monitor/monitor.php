<?php
require_once __DIR__ . '/../common/security/session.php';
praesidium_session_start();
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
    // Publica datos del monitor en window para que la página parcial pueda recargarse.
    // Publishes monitor data on window so the partial page can be loaded repeatedly.
    window.PraesidiumMonitor = window.PraesidiumMonitor || {};
    window.PraesidiumMonitor.LANG = <?= json_encode($L) ?>;
    window.PraesidiumMonitor.USERNAME = <?= json_encode($username) ?>;
  </script>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($L['menu_monitor']) ?></title>
</head>
<body>
  <section class="cajita-cabecera">
    <h2><?= htmlspecialchars($L['menu_monitor']) ?></h2>
  </section>
  <div id="tabla-monitorOptions"></div>
  <div id="tabla-monitorLogs"></div>
  <script src="/monitor/logs_table/monitor.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/monitor/logs_table/monitor.js') ?>"></script>
</body>
</html>
