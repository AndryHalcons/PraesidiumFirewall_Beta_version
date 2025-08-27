<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../lang/es.php";
}
$L = require $langFile;

// check interfaces scripts add/quit new/old physical interfaces
$script5 = '/usr/bin/python3 /var/www/backend/checks/check_interfaces/main_interfaces_check.py';
shell_exec("sudo $script5 2>&1");



?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Interfaces de red</title>
    <link rel="stylesheet" href="/styles.css">
    <script>const lang = <?php echo json_encode($L); ?>;</script>

</head>
<body>
    <div id="tabla-interfaces">Cargando interfaces...</div>
    <div id="tabla-bonds"></div>
    <div id="tabla-bridges"></div>
    <script src="/interfaces/table_interfaces/table_interfaces.js"></script>
</body>
</html>
