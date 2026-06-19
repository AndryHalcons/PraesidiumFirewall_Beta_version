<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$chain = trim($_GET['table'] ?? $_GET['chain'] ?? $input['table'] ?? $input['chain'] ?? '');
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







function get_ethernets_form() {
    $path = '/var/www/config/interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['network']['ethernets'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $name = $input['name'] ?? '';

    if ($name === '' || !isset($json['network']['ethernets'][$name])) {
        echo json_encode(['error' => 'Interfaz no encontrada']);
        return;
    }

    unset($json['network']['ethernets'][$name]);

    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    echo json_encode(['success' => true, 'deleted' => $name]);
}

function get_bonds_form() {
    $path = '/var/www/config/interfaces.json';

    // Leer el archivo JSON actual
    // Read the current JSON file
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']); // Could not read the file
        return;
    }

    // Decodificar el contenido JSON
    // Decode the JSON content
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['network']['bonds'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']); // Error parsing the JSON
        return;
    }

    // Leer los datos enviados por POST
    // Read the data sent via POST
    $input = json_decode(file_get_contents('php://input'), true);
    $name = $input['name'] ?? '';

    // Validar que se haya enviado el nombre
    // Validate that the name was provided
    if ($name === '' || !isset($json['network']['bonds'][$name])) {
        echo json_encode(['error' => 'Interfaz no encontrada']); // Interface not found
        return;
    }

    // Eliminar la interfaz
    // Delete the interface
    unset($json['network']['bonds'][$name]);

    // Guardar el archivo actualizado
    // Save the updated file
    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']); // Could not save the file
        return;
    }

    // Confirmar éxito
    // Confirm success
    echo json_encode(['success' => true, 'deleted' => $name]);
}

function get_bridges_form() {
    $path = '/var/www/config/interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['network']['bridges'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $name = $input['name'] ?? '';

    if ($name === '' || !isset($json['network']['bridges'][$name])) {
        echo json_encode(['error' => 'Interfaz no encontrada']);
        return;
    }

    unset($json['network']['bridges'][$name]);

    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    echo json_encode(['success' => true, 'deleted' => $name]);
}

function get_vlans_form() {
    $path = '/var/www/config/interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['network']['vlans'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $name = $input['name'] ?? '';

    if ($name === '' || !isset($json['network']['vlans'][$name])) {
        echo json_encode(['error' => 'Interfaz no encontrada']);
        return;
    }

    unset($json['network']['vlans'][$name]);

    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    echo json_encode(['success' => true, 'deleted' => $name]);
}

function get_wireguard_form() {
    $path = '/var/www/config/interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['network']['wireguard'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $name = $input['name'] ?? '';

    if ($name === '' || !isset($json['network']['wireguard'][$name])) {
        echo json_encode(['error' => 'Interfaz no encontrada']);
        return;
    }

    unset($json['network']['wireguard'][$name]);

    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    echo json_encode(['success' => true, 'deleted' => $name]);
}

function get_wifis_form() {
    $path = '/var/www/config/interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['network']['wifis'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $name = $input['name'] ?? '';

    if ($name === '' || !isset($json['network']['wifis'][$name])) {
        echo json_encode(['error' => 'Interfaz no encontrada']);
        return;
    }

    unset($json['network']['wifis'][$name]);

    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    echo json_encode(['success' => true, 'deleted' => $name]);
}

function get_tunnels_form() {
    $path = '/var/www/config/interfaces.json';

    // Leer el archivo JSON actual
    // Read the current JSON file
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']); // Could not read the file
        return;
    }

    // Decodificar el contenido JSON
    // Decode the JSON content
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['network']['tunnels'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']); // Error parsing the JSON
        return;
    }

    // Leer los datos enviados por POST
    // Read the data sent via POST
    $input = json_decode(file_get_contents('php://input'), true);
    $name = $input['name'] ?? '';

    // Validar que se haya enviado el nombre
    // Validate that the name was provided
    if ($name === '' || !isset($json['network']['tunnels'][$name])) {
        echo json_encode(['error' => 'Interfaz no encontrada']); // Interface not found
        return;
    }

    // Eliminar la interfaz
    // Delete the interface
    unset($json['network']['tunnels'][$name]);

    // Guardar el archivo actualizado
    // Save the updated file
    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']); // Could not save the file
        return;
    }

    // Confirmar éxito
    // Confirm success
    echo json_encode(['success' => true, 'deleted' => $name]);
}
