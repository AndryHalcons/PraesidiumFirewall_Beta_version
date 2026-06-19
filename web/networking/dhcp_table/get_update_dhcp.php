<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
csrf_validate_or_exit();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$chain = trim($input['table'] ?? '');
$allowedChains = ['url_policies', 'dhcp'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

switch ($chain) {
    case 'url_policies':      get_url_policies_update($chain); break;
    case 'dhcp':          get_dhcp_update($chain); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}
// Funciones autónomas por tablag
function get_dhcp_update($chain) {
    $path = '/var/www/config/dhcp.json';

    // Leer archivo JSON
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']);
        return;
    }

    // Decodificar contenido
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['dhcp']) || !is_array($json['dhcp'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']);
        return;
    }

    // Leer datos enviados por POST
    $input = json_decode(file_get_contents('php://input'), true);
    $rule = $input['rule'] ?? null;

    // Validaciones y conversión
    require __DIR__ . '/validation_dhcp.php';
    $rule = check_create_id($rule, $chain);

    if (!is_array($rule) || empty($rule['id'])) {
        echo json_encode(['error' => 'Datos inválidos']);
        return;
    }

    $id = $rule['id'];
    $updated = false;

    // Buscar y actualizar por ID dentro de dhcp
    foreach ($json['dhcp'] as $i => $entry) {
        if (($entry['rule']['id'] ?? '') === $id) {
            $json['dhcp'][$i]['rule'] = $rule;
            $updated = true;
            break;
        }
    }

    // Si no se encontró el ID, añadir como nueva entrada
    if (!$updated) {
        $json['dhcp'][] = ['rule' => $rule];
    }

    // Guardar archivo actualizado
    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    // Confirmar éxito
    echo json_encode(['success' => true, 'updated' => $id]);
}


function get_url_policies_update($chain) {
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
    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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

