<?php
header('Content-Type: application/json');

$yamlFile = '/var/www/config/interfaces.yml';

if (!file_exists($yamlFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'Archivo no encontrado']);
    exit;
}

// Verifica que la extensión YAML esté disponible //verify php extension installed
if (!function_exists('yaml_parse_file')) {
    http_response_code(500);
    echo json_encode(['error' => 'La extensión YAML no está habilitada en PHP']);
    exit;
}

$data = yaml_parse_file($yamlFile);

if ($data === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al parsear YAML']);
    exit;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
