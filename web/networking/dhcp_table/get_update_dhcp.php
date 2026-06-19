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
$chain = trim($input['table'] ?? '');
$allowedChains = ['url_policies', 'dhcp'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

switch ($chain) {
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
    $saved = json_store_write($path, $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    // Confirmar éxito
    echo json_encode(['success' => true, 'updated' => $id]);
}


