<?php
// Endpoint WireGuard: valida y guarda una entrada en el candidate JSON.
// WireGuard endpoint: validates and saves one entry into the candidate JSON.

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/file/json_store.php';
require_once __DIR__ . '/../common/wireguard_store.php';
require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$rule = $input['rule'] ?? null;
if (!is_array($rule)) { wireguard_error(wireguard_t('wireguard_error_invalid_payload'), null); }

$config = wireguard_read_json(WIREGUARD_CONFIG_PATH);
$name = trim((string)($rule['name'] ?? ''));
if ($name === '' || $name === 'Auto') { $name = wireguard_make_name($config, 'remote_clients'); }
wireguard_validate_entry_name($name, 'name');
unset($rule['name']);

if (($rule['private_key'] ?? '') === '********' && isset($config['remote_clients'][$name]['private_key'])) {
    $rule['private_key'] = $config['remote_clients'][$name]['private_key'];
}

$rule = wireguard_validate_rule('wireguard_remote_clients', $rule, $config, $name);
$config['remote_clients'][$name] = $rule;
$saved = json_store_write(WIREGUARD_CONFIG_PATH, wireguard_prepare_for_json($config), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($saved === false) { wireguard_error(wireguard_t('wireguard_error_save_permissions'), null); }
@chmod(WIREGUARD_CONFIG_PATH, 0664);
echo json_encode(['success' => true, 'updated' => $name], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
