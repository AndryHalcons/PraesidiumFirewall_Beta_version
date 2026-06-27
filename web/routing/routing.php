<?php
require_once __DIR__ . '/../common/security/session.php';
praesidium_session_start();
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
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
    <section class="cajita-cabecera">
        <h2><?= htmlspecialchars($L['routing_title'] ?? 'routing_title') ?></h2>
    </section>

    <section id="routing-table-container">
        <div id="routing-table">
            <p><?= htmlspecialchars($L['loading_routes'] ?? 'Cargando rutas...') ?></p>
        </div>
    </section>

    <script>
        const LANG = <?= json_encode($L, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="/routing/routing_table.php/routing_table.js"></script>
</body>
</html>
