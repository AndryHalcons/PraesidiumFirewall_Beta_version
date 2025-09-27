<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$chain = trim($_GET['table'] ?? $_GET['chain'] ?? '');
$allowedChains = ['url_policies', 'url_list', 'url_listen_ports','url_profile','url_port_profile'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

switch ($chain) {
    case 'url_policies':      get_url_policies(); break;
    case 'url_profile':          get_url_profile(); break;
    case 'url_port_profile':     get_url_url_port_profile($chain); break;
    case 'url_listen_ports':  get_url_listen_ports(); break;
    case 'url_list':  get_url_list(); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}

// Funciones autónomas por tabla

function get_url_policies() {
    $path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_squid.json';
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url_policies'])) {
        echo json_encode(['error' => 'Error al cargar o interpretar la estructura de url_policies']);
        return;
    }

    echo json_encode(['url_policies' => $json['url_policies']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

function get_url_url_port_profile() {
    $path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_squid.json';
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url_port_profile'])) {
        echo json_encode(['error' => 'Error al cargar o interpretar la estructura de url_port_profile']);
        return;
    }

    echo json_encode(['url_port_profile' => $json['url_port_profile']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_url_listen_ports() {
    $path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_squid.json';
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url_listen_ports'])) {
        echo json_encode(['error' => 'Error al cargar o interpretar la estructura de url_listen_ports']);
        return;
    }

    echo json_encode(['url_listen_ports' => $json['url_listen_ports']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}



function get_url_list() {
    $path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_squid.json';
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url_list'])) {
        echo json_encode(['error' => 'Error al cargar o interpretar la estructura de url_list']);
        return;
    }

    echo json_encode(['url_list' => $json['url_list']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

