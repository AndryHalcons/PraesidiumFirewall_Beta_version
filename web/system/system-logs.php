<?php
require_once __DIR__ . '/../common/security/auth.php';
/*
#############################################################################
   Parcial de Sistema -> Registros
   System -> Logs partial

   Mantiene la entrada histórica de Registros dentro de Sistema y carga el visor
   de logs existente sin mover ni duplicar la lógica real del Monitor.

   It keeps the historical Logs entry under System and loads the existing log
   viewer without moving or duplicating the real Monitor logic.
#############################################################################
*/
require_login_page();

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
  <title><?= htmlspecialchars($L['sidebar_logs']) ?></title>
</head>
<body>
  <section class="cajita-cabecera">
    <h2><?= htmlspecialchars($L['sidebar_logs']) ?></h2>
  </section>
  <div id="tabla-monitorOptions"></div>
  <div id="tabla-monitorLogs"></div>
  <script src="/monitor/logs_table/monitor.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/monitor/logs_table/monitor.js') ?>"></script>
</body>
</html>
