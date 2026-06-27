<?php
require_once __DIR__ . '/../../common/security/auth.php';
require_login_json();
header('Content-Type: application/json');


$allowedTables = [
    'alias_address',
    'alias_addr_group',
    'alias_service',
    'alias_service_group'
];

$table = $_GET['table'] ?? '';

if (!in_array($table, $allowedTables)) {
    echo json_encode(['error' => 'Parámetro inválido']);
    exit;
}

$jsonPath = '/var/www/backend/checks/system_data/default_tables_structure/structure_tables_alias.json';

if (!file_exists($jsonPath)) {
    echo json_encode(['error' => 'Archivo de estructura no encontrado']);
    exit;
}

$structures = json_decode(file_get_contents($jsonPath), true);

if (!isset($structures[$table])) {
    echo json_encode(['error' => 'Estructura no definida para esta tabla: '. $table]);
    exit;
}

echo json_encode([
    $table => $structures[$table]
]);

