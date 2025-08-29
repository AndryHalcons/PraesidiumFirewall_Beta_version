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
?>

<h2><?= htmlspecialchars($L['sidebar_dashboard']) ?></h2>
