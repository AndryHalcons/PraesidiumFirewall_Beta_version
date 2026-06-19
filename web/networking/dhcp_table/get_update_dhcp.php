<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/file/json_store.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || trim($input['table'] ?? '') !== 'dhcp') {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

$path = '/var/www/config/dhcp.json';
require __DIR__ . '/validation_dhcp.php';

try {
    $json = dhcp_import_config();
    $rule = $input['rule'] ?? null;
    if (!is_array($rule)) {
        dhcp_fail('Datos inválidos');
    }
    $rule = validate_dhcp_rule($rule, $json['dhcp']);
    $updated = false;
    foreach ($json['dhcp'] as $i => $entry) {
        if (($entry['rule']['id'] ?? '') === $rule['id']) {
            $json['dhcp'][$i]['rule'] = $rule;
            $updated = true;
            break;
        }
    }
    if (!$updated) {
        $json['dhcp'][] = ['rule' => $rule];
    }
    if (json_store_write($path, $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) === false) {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        exit;
    }
    @chmod($path, 0664);
    echo json_encode(['success' => true, 'updated' => $rule['id']]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
