<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../../lang/es.php";
}
$L = require $langFile;

// Recibir parámetro 'hook' por POST
$hook = $_POST['hook'] ?? '';

if (!$hook) {
    http_response_code(400);
    echo json_encode(["error" => "Parámetro 'hook' no proporcionado"]);
    exit;
}

// Leer archivo JSON completo
$jsonPath = "/var/www/config/rules.json";
if (!file_exists($jsonPath)) {
    http_response_code(500);
    echo json_encode(["error" => "Archivo de configuración no encontrado"]);
    exit;
}

$jsonContent = file_get_contents($jsonPath);
$data = json_decode($jsonContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(["error" => "Error al parsear el JSON"]);
    exit;
}

// Verificar si el hook existe y tiene 'rules'
if (!isset($data[$hook]['rules']) || !is_array($data[$hook]['rules'])) {
    http_response_code(404);
    echo json_encode(["error" => "No se encontraron reglas para el hook especificado"]);
    exit;
}

// Devolver solo el array de reglas
header('Content-Type: application/json');
echo json_encode($data[$hook]['rules']);
