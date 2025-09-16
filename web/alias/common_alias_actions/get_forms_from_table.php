<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$chain = trim($_GET['table'] ?? $_GET['chain'] ?? '');
$allowedChains = [
    'alias_address',
    'alias_addr_group',
    'alias_service',
    'alias_service_group'
];


if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

// Dispatcher: solo ejecuta la función
switch ($chain) {
    case 'alias_address':      get_alias_address_form(); break;
    case 'alias_addr_group':    get_alias_addr_group_form(); break;
    case 'alias_service':  get_alias_service_form(); break;
    case 'alias_service_group':  get_alias_service_group_form(); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}

// Funciones autónomas por tipo
function get_alias_address_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_alias.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['alias_address'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de ethernets']);
        return;
    }

    echo json_encode($json['alias_address'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
}

function get_alias_addr_group_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_alias.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['alias_addr_group'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de ethernets']);
        return;
    }

    echo json_encode($json['alias_addr_group'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
}

function get_alias_service_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_alias.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['alias_service'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de ethernets']);
        return;
    }

    echo json_encode($json['alias_service'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
}

function get_alias_service_group_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_alias.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['alias_service_group'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de ethernets']);
        return;
    }

    echo json_encode($json['alias_service_group'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
}