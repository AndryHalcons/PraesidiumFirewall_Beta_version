<?php
require_once __DIR__ . '/../../common/security/auth.php';
require_login_json();

header('Content-Type: application/json');

// Tablas permitidas
$allowedTables = [
    'alias_address',
    'alias_addr_group',
    'alias_service',
    'alias_service_group'
];


$table = $_GET['table'] ?? ''; 

if (!in_array($table, $allowedTables, true)) {
    echo json_encode(['error' => 'Parámetro inválido']);
    exit;
}


$jsonPath = '/var/www/config/alias.json';

if (!file_exists($jsonPath)) {
    echo json_encode(['error' => 'Archivo de datos no encontrado']);
    exit;
}

$data = json_decode(file_get_contents($jsonPath), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'JSON inválido: ' . json_last_error_msg()]);
    exit;
}

if (!isset($data[$table])) {
    echo json_encode(['error' => 'No existe la tabla solicitada']);
    exit;
}

// Devolver solo la parte solicitada
echo json_encode([
    $table => $data[$table]
]);
