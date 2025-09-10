<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$chain = trim($_GET['table'] ?? $_GET['chain'] ?? '');
$allowedChains = ['bonds', 'bridges', 'ethernets', 'wireguard', 'vlans', 'wifis', 'tunnels'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

// Dispatcher: solo ejecuta la función
switch ($chain) {
    case 'bonds':      get_bonds_form(); break;
    case 'bridges':    get_bridges_form(); break;
    case 'ethernets':  get_ethernets_form(); break;
    case 'wireguard':  get_wireguard_form(); break;
    case 'vlans':      get_vlans_form(); break;
    case 'wifis':      get_wifis_form(); break;
    case 'tunnels':    get_tunnels_form(); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}

// Funciones autónomas por tipo
function get_ethernets_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['ethernets'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de ethernets']);
        return;
    }

    //echo json_encode(['ethernets' => $json['ethernets']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo json_encode($json['ethernets'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
function get_bonds_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['bonds'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de bonds']);
        return;
    }

    echo json_encode(['bonds' => $json['bonds']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_bridges_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['bridges'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de bridges']);
        return;
    }

    echo json_encode(['bridges' => $json['bridges']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}



function get_wireguard_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['wireguard'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de wireguard']);
        return;
    }

    echo json_encode(['wireguard' => $json['wireguard']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_vlans_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['vlans'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de vlans']);
        return;
    }

    echo json_encode(['vlans' => $json['vlans']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_wifis_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['wifis'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de wifis']);
        return;
    }

    echo json_encode(['wifis' => $json['wifis']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_tunnels_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['tunnels'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de tunnels']);
        return;
    }

    echo json_encode(['tunnels' => $json['tunnels']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
