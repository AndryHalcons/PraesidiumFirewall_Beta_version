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

// Ejecutar el script principal
$script3 = '/usr/bin/python3 /var/www/html/interfaces/check_new_physical_interfaces/compare_ifquery_iplinkshow.py';
shell_exec("sudo $script3 2>&1");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Interfaces de red</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
    <div id="tabla-interfaces">Cargando interfaces...</div>
    <script src="/interfaces/table_interfaces/table_interfaces.js"></script>
</body>
</html>
