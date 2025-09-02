<?php
header('Content-Type: application/json');

$jsonFile = '/var/www/config/interfaces.json';

if (!file_exists($jsonFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'Archivo no encontrado']);
    exit;
}

$jsonContent = file_get_contents($jsonFile);
$data = json_decode($jsonContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al decodificar JSON']);
    exit;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
