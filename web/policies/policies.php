<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}
$language = $_SESSION['language'] ?? 'es';
// Subir un nivel desde /dashboard a /web para acceder a /lang
$langFile = __DIR__ . "/../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../lang/es.php";
}
$L = require $langFile;
?>
<h2><?= htmlspecialchars($L['sidebar_rules']) ?></h2>
<p>Bienvenido policies.</p>
