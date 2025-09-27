<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$chain = trim($input['table'] ?? '');
$allowedChains = ['url_policies', 'url_list', 'url_listen_ports','url_profile','url_port_profile'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

switch ($chain) {
    case 'url_policies':      get_url_policies_delete(); break;
    case 'url_profile':          get_url_profile_delete($chain); break;
    case 'url_port_profile':     get_url_url_port_profile($chain); break;
    case 'url_list':  get_url_list_delete(); break;
    case 'url_listen_ports':  get_url_listen_ports_delete(); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}

function get_url_policies_delete() {
    $path = '/var/www/config/squid_config/squid_policies.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['squid']['url_policies'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';

    if ($id === '') {
        echo json_encode(['error' => 'ID no proporcionado']);
        return;
    }

    $found = false;
    foreach ($json['squid']['url_policies'] as $i => $entry) {
        if (($entry['rule']['id'] ?? '') === $id) {
            unset($json['squid']['url_policies'][$i]);
            $json['squid']['url_policies'] = array_values($json['squid']['url_policies']);
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo json_encode(['error' => 'ID no encontrado']);
        return;
    }

    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    echo json_encode(['success' => true, 'deleted_id' => $id]);
}

function get_url_profile_delete($chain) {
    require __DIR__ . '/validation_url_filter.php';
    $path = '/var/www/config/squid_config/squid_policies.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']);
        return;
    }



    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['squid']['url_profile'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';

    if ($id === '') {
        echo json_encode(['error' => 'ID no proporcionado']);
        return;
    }
    /////////////////////////////////////////////
    /////////////// validate ///////////////////
    /////////////////////////////////////////////
    validate_profile_delete($id, $chain);

    $found = false;
    foreach ($json['squid']['url_profile'] as $i => $entry) {
        if (($entry['rule']['id'] ?? '') === $id) {
            unset($json['squid']['url_profile'][$i]);
            $json['squid']['url_profile'] = array_values($json['squid']['url_profile']);
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo json_encode(['error' => 'ID no encontrado']);
        return;
    }

    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    echo json_encode(['success' => true, 'deleted_id' => $id]);
}

function get_url_listen_ports_delete() {
    $path = '/var/www/config/squid_config/squid_policies.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['squid']['url_listen_ports'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';

    if ($id === '') {
        echo json_encode(['error' => 'ID no proporcionado']);
        return;
    }

    $found = false;
    foreach ($json['squid']['url_listen_ports'] as $i => $entry) {
        if (($entry['rule']['id'] ?? '') === $id) {
            unset($json['squid']['url_listen_ports'][$i]);
            $json['squid']['url_listen_ports'] = array_values($json['squid']['url_listen_ports']);
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo json_encode(['error' => 'ID no encontrado']);
        return;
    }

    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    echo json_encode(['success' => true, 'deleted_id' => $id]);
}


function get_url_list_delete() {
    require __DIR__ . '/validation_url_filter.php';
    $input = json_decode(file_get_contents('php://input'), true);
    $filename = $input['file'] ?? '';

    // Validar que se haya proporcionado el nombre del archivo
    // Validate that the filename was provided
    if ($filename === '') {
        echo json_encode(['error' => 'Nombre de archivo no proporcionado']);
        return;
    }
    validate_url_list_delete($filename);
    // Construir la ruta completa al archivo
    // Build the full path to the file
    $filePath = '/var/www/config/squid_config/acl_domains/' . basename($filename);

    // Verificar si el archivo existe
    // Check if the file exists
    if (!file_exists($filePath)) {
        echo json_encode(['error' => 'El archivo especificado no existe']);
        return;
    }

    // Intentar borrar el archivo
    // Attempt to delete the file
    $deleted = unlink($filePath);
    if (!$deleted) {
        echo json_encode(['error' => 'No se pudo borrar el archivo']);
        return;
    }

    // Confirmar éxito
    // Confirm success
    echo json_encode(['success' => true, 'deleted_file' => $filename]);
}

