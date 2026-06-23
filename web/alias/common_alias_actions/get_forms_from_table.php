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

// Carga la configuración de formularios de Alias.
// Loads the Alias forms configuration.
function load_alias_forms_config() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_alias.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return null;
    }
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['error' => 'Error al interpretar los datos de alias']);
        return null;
    }
    return $json;
}

// Devuelve los nombres de objetos disponibles para poblar selectores de grupos.
// Returns available object names to populate group selectors.
function get_alias_object_names($sourceKey) {
    $path = '/var/www/config/alias.json';
    if (!file_exists($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json[$sourceKey]) || !is_array($json[$sourceKey])) {
        return [];
    }
    $names = [];
    foreach ($json[$sourceKey] as $entry) {
        if (isset($entry['name']) && is_string($entry['name'])) {
            $names[] = $entry['name'];
        }
    }
    return array_values(array_unique($names));
}
function get_alias_address_form() {
    $json = load_alias_forms_config();
    if ($json === null || !isset($json['alias_address'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de alias_address']);
        return;
    }
    echo json_encode($json['alias_address'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_alias_addr_group_form() {
    $json = load_alias_forms_config();
    if ($json === null || !isset($json['alias_addr_group'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de alias_addr_group']);
        return;
    }
    $json['alias_addr_group']['object_multiselect']['content'] = get_alias_object_names('alias_address');
    echo json_encode($json['alias_addr_group'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_alias_service_form() {
    $json = load_alias_forms_config();
    if ($json === null || !isset($json['alias_service'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de alias_service']);
        return;
    }
    echo json_encode($json['alias_service'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_alias_service_group_form() {
    $json = load_alias_forms_config();
    if ($json === null || !isset($json['alias_service_group'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de alias_service_group']);
        return;
    }
    $json['alias_service_group']['object_multiselect']['content'] = get_alias_object_names('alias_service');
    echo json_encode($json['alias_service_group'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
