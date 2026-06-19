<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}
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
    window.LANG = <?= json_encode($L) ?>;
  </script>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="/styles.css">
</head>
<body>
  <h1><?= htmlspecialchars($L['sidebar_settings'] ?? 'Configuración') ?></h1>
  <section class="settings-card">
    <h2><?= htmlspecialchars($L['system_logging_title'] ?? 'System logs') ?></h2>
    <p><?= htmlspecialchars($L['system_logging_description'] ?? '') ?></p>
    <div id="system-logging-status" class="settings-status"></div>
    <form id="system-logging-form" class="settings-form"></form>
  </section>
  <script src="/system/logging/system_logging.js"></script>
</body>
</html>
