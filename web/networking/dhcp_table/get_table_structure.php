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
    case 'dhcp':      get_dhcp_structure(); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}

// Funciones autónomas por tabla

function get_dhcp_structure() {
    $path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_dhcp.json';
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['dhcp'])) {
        echo json_encode(['error' => 'Error al cargar o interpretar la estructura de dhcp']);
        return;
    }

    echo json_encode(['dhcp' => $json['dhcp']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_url_profile() {
    $path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_squid.json';
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url_profile'])) {
        echo json_encode(['error' => 'Error al cargar o interpretar la estructura de url_profile']);
        return;
    }

    echo json_encode(['url_profile' => $json['url_profile']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

