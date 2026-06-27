<?php
require_once __DIR__ . '/../../common/security/auth.php';
require_login_json();
header('Content-Type: application/json');


$allowedTables = [
    'FORWARDING',
    'PREROUTING',
    'POSTROUTING',
    'input',
    'output'
];

$table = $_GET['table'] ?? '';

if (!in_array($table, $allowedTables)) {
    echo json_encode(['error' => 'mi mensaje de errore es este mierda de parametro']);
    exit;
}

$jsonPath = '/var/www/backend/checks/system_data/default_tables_structure/structure_tables_policies.json';

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

