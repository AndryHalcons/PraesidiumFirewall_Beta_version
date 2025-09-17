<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$chain = trim($input['table'] ?? '');
$allowedChains = ['url_policies', 'url_list', 'url_listen_ports','url_profile'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

switch ($chain) {
    case 'url_policies':      get_url_policies($chain); break;
    case 'url_profile':          get_url_profile($chain); break;
    case 'url_listen_ports':  get_url_listen_ports($chain); break;
    case 'url_list':  get_url_list(); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}



// Funciones autónomas por tabla
function get_url_policies($chain) {
    $path = '/var/www/config/squid_config/squid_policies.json';

    // Leer archivo JSON
    // Read JSON file
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']);
        return;
    }

    // Decodificar contenido
    // Decode content
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['squid']['url_policies'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']);
        return;
    }

    // Leer datos enviados por POST
    // Read POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $rule = $input['rule'] ?? null;
    //////////////////////////////
    //////////validate /////////
    //////////////////////////////

    // Validaciones y conversión
    // Validations and conversion
    require __DIR__ . '/validation_url_filter.php';
    $rule = Main_convert_alias_object_to_network_object($rule);
    //validation_form_field_review($rule);
    $rule = check_create_id($rule, $chain);
    // Verificar que exista el campo id
    // Check that the id field exists
    if (!is_array($rule) || empty($rule['id'])) {
        echo json_encode(['error' => 'Datos inválidos']);
        return;
    }
    validation_url_policies($rule);
    //reasignamos o asignamos posicion
    //reassing or assign position
    $json = reassign_position($json,$rule);


    //////////////////////////////
    //////////write /////////
    //////////////////////////////


    // Guardar archivo actualizado
    // Save updated file
    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    // Confirmar éxito
    // Confirm success
    echo json_encode(['success' => true, 'updated' => $id]);
}


function get_url_profile($chain) {
    $path = '/var/www/config/squid_config/squid_policies.json';

    // Leer archivo JSON
    // Read JSON file
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']);
        return;
    }

    // Decodificar contenido
    // Decode content
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['squid']['url_profile'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']);
        return;
    }

    // Leer datos enviados por POST
    // Read POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $rule = $input['rule'] ?? null;

    // Validaciones y conversión
    // Validations and conversion
    require __DIR__ . '/validation_url_filter.php';
    //validation_form_field_review($rule);
    $rule = check_create_id($rule, $chain);

    // Verificar que exista el campo id
    // Check that the id field exists
    if (!is_array($rule) || empty($rule['id'])) {
        echo json_encode(['error' => 'Datos inválidos']);
        return;
    }

    $id = $rule['id'];
    $updated = false;

    // Buscar y actualizar por ID
    // Search and update by ID
    foreach ($json['squid']['url_profile'] as $i => $entry) {
        if (($entry['rule']['id'] ?? '') === $id) {
            $json['squid']['url_profile'][$i]['rule'] = $rule;
            $updated = true;
            break;
        }
    }
    
    // Si no se encontró el ID, añadir como nueva entrada
    // If ID was not found, add as new entry
    if (!$updated) {
        $json['squid']['url_profile'][] = ['rule' => $rule];
    }


    // Guardar archivo actualizado
    // Save updated file
    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    // Confirmar éxito
    // Confirm success
    echo json_encode(['success' => true, 'updated' => $id]);
}

function get_url_listen_ports($chain) {
    $path = '/var/www/config/squid_config/squid_policies.json';

    // Leer archivo JSON
    // Read JSON file
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']);
        return;
    }

    // Decodificar contenido
    // Decode content
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['squid']['url_listen_ports'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']);
        return;
    }

    // Leer datos enviados por POST
    // Read POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $rule = $input['rule'] ?? null;

    // Validaciones y conversión
    // Validations and conversion
    require __DIR__ . '/validation_url_filter.php';
    $rule = Main_convert_alias_object_to_network_object($rule);
    //validation_form_field_review($rule);
    $rule = check_create_id($rule, $chain);

    // Verificar que exista el campo id
    // Check that the id field exists
    if (!is_array($rule) || empty($rule['id'])) {
        echo json_encode(['error' => 'Datos inválidos']);
        return;
    }

    $id = $rule['id'];
    $updated = false;

    // Buscar y actualizar por ID
    // Search and update by ID
    foreach ($json['squid']['url_listen_ports'] as $i => $entry) {
        if (($entry['rule']['id'] ?? '') === $id) {
            $json['squid']['url_listen_ports'][$i]['rule'] = $rule;
            $updated = true;
            break;
        }
    }
    
    // Si no se encontró el ID, añadir como nueva entrada
    // If ID was not found, add as new entry
    if (!$updated) {
        $json['squid']['url_listen_ports'][] = ['rule' => $rule];
    }


    // Guardar archivo actualizado
    // Save updated file
    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    // Confirmar éxito
    // Confirm success
    echo json_encode(['success' => true, 'updated' => $id]);
}




function get_url_list($chain) {
    echo json_encode(['success' => true, 'updated' => $id]);
}