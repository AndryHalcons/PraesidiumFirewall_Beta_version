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
// check interfaces scripts add/quit new/old physical interfaces
$script5 = '/usr/bin/python3 /var/www/backend/checks/check_interfaces/main_interfaces_check.py';
shell_exec("sudo $script5 2>&1");
?>

<h2><?= htmlspecialchars($L['sidebar_dashboard']) ?></h2>
