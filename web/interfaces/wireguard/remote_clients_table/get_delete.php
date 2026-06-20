<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/file/json_store.php';
require_once __DIR__ . '/../common/wireguard_store.php';
require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$name = trim((string)($input['name'] ?? ''));
$config = wireguard_read_json(WIREGUARD_CONFIG_PATH);
if ($name === '' || !isset($config['remote_clients'][$name])) { echo json_encode(['error' => 'Entrada no encontrada']); exit; }
unset($config['remote_clients'][$name]);
$saved = json_store_write(WIREGUARD_CONFIG_PATH, wireguard_prepare_for_json($config), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($saved === false) { echo json_encode(['error' => 'No se pudo guardar wireguard.json']); exit; }
@chmod(WIREGUARD_CONFIG_PATH, 0664);
echo json_encode(['success' => true, 'deleted' => $name], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
