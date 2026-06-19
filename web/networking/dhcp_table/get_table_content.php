<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$table = trim($_GET['table'] ?? $_GET['chain'] ?? '');
if ($table !== 'dhcp') {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

$structure = json_decode((string)@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_dhcp.json'), true);
$columns = $structure['dhcp'] ?? [];
$data = json_decode((string)@file_get_contents('/var/www/config/dhcp.json'), true);
$block = $data['dhcp'] ?? [];

if (!is_array($columns) || !is_array($block)) {
    http_response_code(500);
    echo json_encode(['error' => 'JSON DHCP mal formado']);
    exit;
}

$result = [];
foreach ($block as $entry) {
    $rule = $entry['rule'] ?? [];
    if (!is_array($rule)) {
        continue;
    }
    $flat = [];
    foreach ($columns as $col) {
        $flat[$col] = isset($rule[$col]) ? (string)$rule[$col] : '';
    }
    $result[] = $flat;
}

echo json_encode(['dhcp' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
