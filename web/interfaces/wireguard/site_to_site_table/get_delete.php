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
if ($name === '' || !isset($config['site_to_site'][$name])) { wireguard_error('La entrada que intenta borrar no existe o ya fue eliminada.', 'name'); }
wireguard_can_delete('site_to_site', $name, $config);
unset($config['site_to_site'][$name]);
$saved = json_store_write(WIREGUARD_CONFIG_PATH, wireguard_prepare_for_json($config), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($saved === false) { wireguard_error('No se pudo guardar la configuración WireGuard después del borrado.', null); }
@chmod(WIREGUARD_CONFIG_PATH, 0664);
echo json_encode(['success' => true, 'deleted' => $name], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
