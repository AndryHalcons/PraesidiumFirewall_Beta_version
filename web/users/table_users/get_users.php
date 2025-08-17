<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../../lang/es.php";
}
$L = require $langFile;
header('Content-Type: application/json');

$jsonPath = '/var/www/config/users.json';

if (!file_exists($jsonPath)) {
    echo json_encode([]);
    exit;
}

$data = file_get_contents($jsonPath);
echo $data;
