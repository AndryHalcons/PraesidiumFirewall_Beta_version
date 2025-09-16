<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$chain = trim($_GET['table'] ?? $_GET['chain'] ?? '');
$allowedChains = ['url_policies', 'url_list', 'url_listen_ports'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

// Dispatcher: solo ejecuta la función
switch ($chain) {
    case 'url_policies':      get_url_policies_form(); break;
    case 'url_list':    get_url_list_form(); break;
    case 'url_listen_ports':  get_url_listen_ports_form(); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}

// Funciones autónomas por tipo
function get_url_policies_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_squid.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url_policies'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de ethernets']);
        return;
    }

    echo json_encode($json['url_policies'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
}

// Funciones autónomas por tipo
function get_url_list_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_squid.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url_list'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de ethernets']);
        return;
    }

    echo json_encode($json['url_list'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
}

// Funciones autónomas por tipo
function get_url_listen_ports_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_squid.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url_listen_ports'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de ethernets']);
        return;
    }

    echo json_encode($json['url_listen_ports'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
}