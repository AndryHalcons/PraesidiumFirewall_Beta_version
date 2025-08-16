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

// Ejecutar el primer script Python con sudo
$script1 = '/usr/bin/python3 /var/www/html/interfaces/check_new_physical_interfaces/replace_allow-hotplug.py';
$output1 = shell_exec("sudo $script1 2>&1");

// Ejecutar el segundo script Python con sudo
$script2 = '/usr/bin/python3 /var/www/html/interfaces/check_new_physical_interfaces/check_interfacesJSON.py';
$output2 = shell_exec("sudo $script2 2>&1");

// Mostrar la salida de ambos scripts
echo "<h3>Resultado de replace_allow-hotplug.py</h3>";
echo "<pre>$output1</pre>";

echo "<h3>Resultado de check_interfacesJSON.py</h3>";
echo "<pre>$output2</pre>";
?>
