<?php
require_once __DIR__ . '/../common/security/auth.php';
require_login_page();

// Carga el idioma activo de la sesión para mantener la página dentro del patrón Praesidium.
// Loads the active session language to keep the page within the Praesidium pattern.
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
  <title><?= htmlspecialchars($L['menu_monitor_sessions']) ?></title>
</head>
<body>
  <section class="cajita-cabecera">
    <h2><?= htmlspecialchars($L['menu_monitor_sessions']) ?></h2>
    <p><?= htmlspecialchars($L['monitor_sessions_description']) ?></p>
  </section>
</body>
</html>
