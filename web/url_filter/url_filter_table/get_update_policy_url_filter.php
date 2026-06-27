<?php
require_once __DIR__ . '/../../common/security/session.php';
praesidium_session_start();
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
$chain = trim($input['table'] ?? '');
$allowedChains = ['url_policies', 'url_list', 'url_listen_ports','url_profile','url_port_profile','url_network_list','url_networks_list_profile'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

switch ($chain) {
    case 'url_policies':      get_url_policies($chain); break;
    case 'url_profile':          get_url_profile($chain); break;
    case 'url_port_profile':     get_url_port_profile($chain); break;
    case 'url_listen_ports':  get_url_listen_ports($chain); break;
    case 'url_list':  get_url_list($chain); break;
    case 'url_network_list':  get_url_network_list($chain); break;
    case 'url_networks_list_profile':  get_url_networks_list_profile($chain); break;
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
    //validation_form_field_review($rule);
    $rule = check_create_id($rule, $chain);
    // Verificar que exista el campo id
    // Check that the id field exists
    if (!is_array($rule) || empty($rule['id'])) {
        echo json_encode(['error' => 'Datos inválidos']);
        return;
    }
    validation_url_policies($rule);
    validation_form_field_review_policy($rule);
    //reasignamos o asignamos posicion
    //reassing or assign position
    $json = reassign_position($json,$rule);


    //////////////////////////////
    //////////write /////////
    //////////////////////////////
    // Guardar archivo actualizado
    // Save updated file
    $saved = json_store_write($path, $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }
    //generamos los archivos txt de acl ip por alias, solo genera un txt vacio con el nombre del alias, las ips se añaden el commit backend
    //para evitar introducir objetos modificados, ya fueron verificados que son correctos previamente en Main_convert_alias_object_to_network_object
    //we generate the acl ip txt files by alias, it only generates an empty txt with the alias name, the ips are added in the commit backend
    //to avoid introducing modified objects, They have already been verified to be correct in Main_convert_alias_object_to_network_object
    check_and_create_acl_ip();
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
    rename_not_permit($json, $rule, $chain);
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
    $saved = json_store_write($path, $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    // Confirmar éxito
    // Confirm success
    echo json_encode(['success' => true, 'updated' => $id]);
}

function get_url_networks_list_profile($chain) {
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
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['squid']['url_networks_list_profile'])) {
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
    rename_not_permit($json, $rule, $chain);


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
    foreach ($json['squid']['url_networks_list_profile'] as $i => $entry) {
        if (($entry['rule']['id'] ?? '') === $id) {
            $json['squid']['url_networks_list_profile'][$i]['rule'] = $rule;
            $updated = true;
            break;
        }
    }
    
    // Si no se encontró el ID, añadir como nueva entrada
    // If ID was not found, add as new entry
    if (!$updated) {
        $json['squid']['url_networks_list_profile'][] = ['rule' => $rule];
    }


    // Guardar archivo actualizado
    // Save updated file
    $saved = json_store_write($path, $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    // Confirmar éxito
    // Confirm success
    echo json_encode(['success' => true, 'updated' => $id]);
}



function get_url_port_profile($chain) {
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
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['squid']['url_port_profile'])) {
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
    rename_not_permit($json, $rule, $chain);
    validatePort($rule['Port']);
    

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
    foreach ($json['squid']['url_port_profile'] as $i => $entry) {
        if (($entry['rule']['id'] ?? '') === $id) {
            $json['squid']['url_port_profile'][$i]['rule'] = $rule;
            $updated = true;
            break;
        }
    }
    
    // Si no se encontró el ID, añadir como nueva entrada
    // If ID was not found, add as new entry
    if (!$updated) {
        $json['squid']['url_port_profile'][] = ['rule' => $rule];
    }

    
    // Guardar archivo actualizado
    // Save updated file
    $saved = json_store_write($path, $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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
    validate_is_ip_no_cidr($rule['iface_ip']);
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
    $saved = json_store_write($path, $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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

function get_url_network_list($chain) {
    echo json_encode(['success' => true, 'updated' => $id]);
}