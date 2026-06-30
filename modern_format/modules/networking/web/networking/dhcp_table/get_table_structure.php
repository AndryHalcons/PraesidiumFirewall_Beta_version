<?php
require_once __DIR__ . '/../../common/security/auth.php';
require_login_json();
header('Content-Type: application/json');


$table = trim($_GET['table'] ?? $_GET['chain'] ?? '');
if ($table !== 'dhcp') {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

$path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_dhcp.json';
$json = json_decode((string)@file_get_contents($path), true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($json['dhcp']) || !is_array($json['dhcp'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar o interpretar la estructura de DHCP']);
    exit;
}

echo json_encode(['dhcp' => $json['dhcp']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
