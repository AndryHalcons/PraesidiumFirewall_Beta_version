<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/file/json_store.php';
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

$allowedChains = ['bonds', 'bridges', 'ethernets', 'wireguard', 'vlans', 'wifis'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro inválido o ausente']);
    exit;
}

// Dispatcher: solo ejecuta la función
switch ($chain) {
    case 'bonds': get_bonds($chain); break;
    case 'bridges': get_bridges($chain); break;
    case 'ethernets': get_ethernets($chain); break;
    case 'wireguard': get_wireguard($chain); break;
    case 'vlans': get_vlans($chain); break;
    case 'wifis': get_wifis($chain); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}


//////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////// Funciones autónomas por tipo de interfaz ////////////////
//////////////////////////////  Autonomous functions by interface type ///////////////////
//////////////////////////////////////////////////////////////////////////////////////////

function get_ethernets($chain) {
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
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['network']['ethernets'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']); // Error parsing the JSON
        return;
    }

    // Leer los datos enviados por POST
    // Read the data sent via POST
    $input = json_decode(file_get_contents('php://input'), true);
    $rule = $input['rule'] ?? null;

    // Validar que la entrada tenga el campo 'name'
    // Validate that the input contains the 'name' field
    if (!is_array($rule) || empty($rule['name'])) {
        echo json_encode(['error' => 'Datos inválidos']); // Invalid data
        return;
    }

    ////////////////////////////////////////////////////////////////////
    /////////////////validate and convert alias/////////////////////////
    ////////////////////////////////////////////////////////////////////
    require __DIR__ . '/validation_interface.php';
    $rule = Main_convert_alias_object_to_network_object($rule);
    validation_form_field_review($rule, $chain);

    // Extraer el nombre de la interfaz y eliminarlo del array
    // Extract the interface name and remove it from the array
    $name = $rule['name'];
    unset($rule['name']);

    // Actualizar o añadir la interfaz en la sección 'ethernets'
    // Update or add the interface in the 'ethernets' section
    $json['network']['ethernets'][$name] = $rule;

    // Guardar el archivo actualizado
    // Save the updated file
    $saved = json_store_write($path, $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']); // Could not save the file
        return;
    }

    // Confirmar éxito
    // Confirm success
    echo json_encode(['success' => true, 'updated' => $name]);
}

function get_bridges($chain) {
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
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['network']['bridges'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']); // Error parsing the JSON
        return;
    }

    // Leer los datos enviados por POST
    // Read the data sent via POST
    $input = json_decode(file_get_contents('php://input'), true);
    $rule = $input['rule'] ?? null;


    ////////////////////////////////////////////////////////////////////
    /////////////////validate and convert alias/////////////////////////
    ////////////////////////////////////////////////////////////////////
    require __DIR__ . '/validation_interface.php';
    $rule = Main_convert_alias_object_to_network_object($rule);
    validation_form_field_review($rule, $chain);
    $rule = check_create_Name($rule, $chain);

    // Validar que la entrada tenga el campo 'name'
    // Validate that the input contains the 'name' field
    if (!is_array($rule) || empty($rule['name'])) {
        echo json_encode(['error' => 'Datos inválidos']); // Invalid data
        return;
    }


    // Extraer el nombre de la interfaz y eliminarlo del array
    // Extract the interface name and remove it from the array
    $name = $rule['name'];
    unset($rule['name']);

    // Actualizar o añadir la interfaz en la sección 'bridges'
    // Update or add the interface in the 'bridges' section
    $json['network']['bridges'][$name] = $rule;

    // Guardar el archivo actualizado
    // Save the updated file
    $saved = json_store_write($path, $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']); // Could not save the file
        return;
    }

    // Confirmar éxito
    // Confirm success
    echo json_encode(['success' => true, 'updated' => $name]);
}

function get_bonds($chain) {
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
    $rule = $input['rule'] ?? null;


    ////////////////////////////////////////////////////////////////////
    /////////////////validate and convert alias/////////////////////////
    ////////////////////////////////////////////////////////////////////
    require __DIR__ . '/validation_interface.php';
    $rule = Main_convert_alias_object_to_network_object($rule);
    validation_form_field_review($rule, $chain);
    $rule = check_create_Name($rule, $chain);

    // Validar que la entrada tenga el campo 'name'
    // Validate that the input contains the 'name' field
    if (!is_array($rule) || empty($rule['name'])) {
        echo json_encode(['error' => 'Datos inválidos']); // Invalid data
        return;
    }

    // Extraer el nombre de la interfaz y eliminarlo del array
    // Extract the interface name and remove it from the array
    $name = $rule['name'];
    unset($rule['name']);

    // Actualizar o añadir la interfaz en la sección 'bonds'
    // Update or add the interface in the 'bonds' section
    $json['network']['bonds'][$name] = $rule;

    // Guardar el archivo actualizado
    // Save the updated file
    $saved = json_store_write($path, $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']); // Could not save the file
        return;
    }

    // Confirmar éxito
    // Confirm success
    echo json_encode(['success' => true, 'updated' => $name]);
}

function get_vlans($chain) {
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
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['network']['vlans'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']); // Error parsing the JSON
        return;
    }

    // Leer los datos enviados por POST
    // Read the data sent via POST
    $input = json_decode(file_get_contents('php://input'), true);
    $rule = $input['rule'] ?? null;


    ////////////////////////////////////////////////////////////////////
    /////////////////validate and convert alias/////////////////////////
    ////////////////////////////////////////////////////////////////////
    require __DIR__ . '/validation_interface.php';
    $rule = Main_convert_alias_object_to_network_object($rule);
    validation_form_field_review($rule, $chain);
    $rule = check_create_Name($rule, $chain);

    // Validar que la entrada tenga el campo 'name'
    // Validate that the input contains the 'name' field
    if (!is_array($rule) || empty($rule['name'])) {
        echo json_encode(['error' => 'Datos inválidos']); // Invalid data
        return;
    }

    // Extraer el nombre de la interfaz y eliminarlo del array
    // Extract the interface name and remove it from the array
    $name = $rule['name'];
    unset($rule['name']);

    // Actualizar o añadir la interfaz en la sección 'vlans'
    // Update or add the interface in the 'vlans' section
    $json['network']['vlans'][$name] = $rule;

    // Guardar el archivo actualizado
    // Save the updated file
    $saved = json_store_write($path, $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']); // Could not save the file
        return;
    }

    // Confirmar éxito
    // Confirm success
    echo json_encode(['success' => true, 'updated' => $name]);
}

function get_wireguard($chain) {
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
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['network']['wireguard'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']); // Error parsing the JSON
        return;
    }

    // Leer los datos enviados por POST
    // Read the data sent via POST
    $input = json_decode(file_get_contents('php://input'), true);
    $rule = $input['rule'] ?? null;



    ////////////////////////////////////////////////////////////////////
    /////////////////validate and convert alias/////////////////////////
    ////////////////////////////////////////////////////////////////////
    require __DIR__ . '/validation_interface.php';
    $rule = Main_convert_alias_object_to_network_object($rule);
    validation_form_field_review($rule, $chain);
    $rule = check_create_Name($rule, $chain);

    // Validar que la entrada tenga el campo 'name'
    // Validate that the input contains the 'name' field
    if (!is_array($rule) || empty($rule['name'])) {
        echo json_encode(['error' => 'Datos inválidos']); // Invalid data
        return;
    }




    // Extraer el nombre de la interfaz y eliminarlo del array
    // Extract the interface name and remove it from the array
    $name = $rule['name'];
    unset($rule['name']);

    // Actualizar o añadir la interfaz en la sección 'wireguard'
    // Update or add the interface in the 'wireguard' section
    $json['network']['wireguard'][$name] = $rule;

    // Guardar el archivo actualizado
    // Save the updated file
    $saved = json_store_write($path, $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']); // Could not save the file
        return;
    }

    // Confirmar éxito
    // Confirm success
    echo json_encode(['success' => true, 'updated' => $name]);
}

function get_wifis($chain) {
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
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['network']['wifis'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']); // Error parsing the JSON
        return;
    }

    // Leer los datos enviados por POST
    // Read the data sent via POST
    $input = json_decode(file_get_contents('php://input'), true);
    $rule = $input['rule'] ?? null;


    ////////////////////////////////////////////////////////////////////
    /////////////////validate and convert alias/////////////////////////
    ////////////////////////////////////////////////////////////////////
    require __DIR__ . '/validation_interface.php';
    $rule = Main_convert_alias_object_to_network_object($rule);
    validation_form_field_review($rule, $chain);
    // Genera nombre automático para Wi-Fi cuando la WebGUI envía "Nombre Auto" sin campo name.
    // Generate an automatic Wi-Fi name when the WebGUI sends "Auto Name" without a name field.
    $rule = check_create_Name($rule, $chain);

    // Validar que la entrada tenga el campo 'name'
    // Validate that the input contains the 'name' field
    if (!is_array($rule) || empty($rule['name'])) {
        echo json_encode(['error' => 'Datos inválidos']); // Invalid data
        return;
    }

    // Extraer el nombre de la interfaz y eliminarlo del array
    // Extract the interface name and remove it from the array
    $name = $rule['name'];
    unset($rule['name']);

    // Actualizar o añadir la interfaz en la sección 'wifis'
    // Update or add the interface in the 'wifis' section
    $json['network']['wifis'][$name] = $rule;

    // Guardar el archivo actualizado
    // Save the updated file
    $saved = json_store_write($path, $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']); // Could not save the file
        return;
    }

    // Confirmar éxito
    // Confirm success
    echo json_encode(['success' => true, 'updated' => $name]);
}

