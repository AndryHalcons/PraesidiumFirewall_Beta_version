<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$table = trim($_GET['table'] ?? '');
$allowedTables = ['bonds', 'bridges', 'ethernets', 'wireguard', 'vlans', 'wifis', 'tunnels'];

if ($table === '' || !in_array($table, $allowedTables, true)) {
    echo json_encode(['error' => 'mi mensaje de errore es este mierda de parametro']);
    exit;
}

// Dispatcher: solo ejecuta la función
switch ($table) {
    case 'bonds': get_bonds(); break;
    case 'bridges': get_bridges(); break;
    case 'ethernets': get_ethernets(); break;
    case 'wireguard': get_wireguard(); break;
    case 'vlans': get_vlans(); break;
    case 'wifis': get_wifis(); break;
    case 'tunnels': get_tunnels(); break;
    default:
        echo json_encode(['error' => 'Tabla no soportada']);
        break;
}

// Funciones autónomas por tabla

function get_bonds() {
    $path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_interfaces.json';
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['bonds'])) {
        echo json_encode(['error' => 'Error al cargar o interpretar la estructura de bonds']);
        return;
    }

    echo json_encode(['bonds' => $json['bonds']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_bridges() {
    $path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_interfaces.json';
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['bridges'])) {
        echo json_encode(['error' => 'Error al cargar o interpretar la estructura de bridges']);
        return;
    }

    echo json_encode(['bridges' => $json['bridges']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_ethernets() {
    $path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_interfaces.json';
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['ethernets'])) {
        echo json_encode(['error' => 'Error al cargar o interpretar la estructura de ethernets']);
        return;
    }

    echo json_encode(['ethernets' => $json['ethernets']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_wireguard() {
    $path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_interfaces.json';
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['wireguard'])) {
        echo json_encode(['error' => 'Error al cargar o interpretar la estructura de wireguard']);
        return;
    }

    echo json_encode(['wireguard' => $json['wireguard']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_vlans() {
    $path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_interfaces.json';
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['vlans'])) {
        echo json_encode(['error' => 'Error al cargar o interpretar la estructura de vlans']);
        return;
    }

    echo json_encode(['vlans' => $json['vlans']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_wifis() {
    $path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_interfaces.json';
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['wifis'])) {
        echo json_encode(['error' => 'Error al cargar o interpretar la estructura de wifis']);
        return;
    }

    echo json_encode(['wifis' => $json['wifis']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_tunnels() {
    $path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_interfaces.json';
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['tunnels'])) {
        echo json_encode(['error' => 'Error al cargar o interpretar la estructura de tunnels']);
        return;
    }

    echo json_encode(['tunnels' => $json['tunnels']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
