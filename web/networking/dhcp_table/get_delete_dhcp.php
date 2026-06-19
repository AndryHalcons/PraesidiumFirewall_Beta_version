<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/file/json_store.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$userTable = trim($input['table'] ?? '');
$idToDelete = isset($input['id']) && is_numeric($input['id']) ? (string)(int)$input['id'] : null;
if ($userTable !== 'dhcp') {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}
if ($idToDelete === null) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$jsonPath = '/var/www/config/dhcp.json';
$data = json_decode((string)@file_get_contents($jsonPath), true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['dhcp']) || !is_array($data['dhcp'])) {
    http_response_code(500);
    echo json_encode(['error' => 'JSON mal formado o tabla no encontrada']);
    exit;
}
$data['dhcp'] = array_values(array_filter($data['dhcp'], fn($entry) => (string)($entry['rule']['id'] ?? '') !== $idToDelete));
if (json_store_write($jsonPath, $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo guardar el archivo']);
    exit;
}
@chmod($jsonPath, 0664);
echo json_encode(['success' => true]);
