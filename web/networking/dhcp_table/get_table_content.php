<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$chain = trim($_GET['table'] ?? $_GET['chain'] ?? '');
$allowedChains = ['dhcp'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

switch ($chain) {
    case 'dhcp':          get_dhcp_content(); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}

function get_dhcp_content() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_dhcp.json'), true);
    $columns = $structure['dhcp'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/dhcp.json'), true);
    $block = $data['dhcp'] ?? [];

    $result = [];
    foreach ($block as $entry) {
        $rule = $entry['rule'] ?? [];
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $rule[$col] ?? "";
        }
        $result[] = $flat; // ✅ sin envoltorio "rule"
    }

    error_log(json_encode(['dhcp' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); // log para depurar

    echo json_encode(['dhcp' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


