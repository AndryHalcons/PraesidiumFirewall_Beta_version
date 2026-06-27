<?php
require_once __DIR__ . '/../../../common/security/session.php';
// Endpoint WireGuard: valida dependencias y borra una entrada del candidate JSON.
// WireGuard endpoint: validates dependencies and deletes one entry from the candidate JSON.

// Fase 1: abrir sesión, exigir admin y validar CSRF antes de borrar.
// Phase 1: open session, require admin, and validate CSRF before deleting.
praesidium_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/file/json_store.php';
require_once __DIR__ . '/../common/wireguard_store.php';
require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');

// Fase 2: leer el nombre que renderTableGeneric envía para borrar.
// Phase 2: read the name sent by renderTableGeneric for deletion.
$input = json_decode(file_get_contents('php://input'), true);
$name = trim((string)($input['name'] ?? ''));
// Fase 3: cargar candidate para comprobar existencia y dependencias.
// Phase 3: load candidate to check existence and dependencies.
$config = wireguard_read_json(WIREGUARD_CONFIG_PATH);
if ($name === '' || !isset($config['site_to_site'][$name])) { wireguard_error(wireguard_t('wireguard_error_delete_missing'), 'name'); }
// Fase 4: bloquear borrados peligrosos, por ejemplo servidor con clientes.
// Phase 4: block dangerous deletes, for example a server with clients.
wireguard_can_delete('site_to_site', $name, $config);
// Fase 5: retirar la entrada del candidate y persistir el JSON normalizado.
// Phase 5: remove the entry from candidate and persist normalized JSON.
unset($config['site_to_site'][$name]);
$saved = json_store_write(WIREGUARD_CONFIG_PATH, wireguard_prepare_for_json($config), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($saved === false) { wireguard_error(wireguard_t('wireguard_error_save_after_delete'), null); }
@chmod(WIREGUARD_CONFIG_PATH, 0664);
// Fase 6: confirmar borrado para que la tabla se recargue.
// Phase 6: confirm deletion so the table can reload.
echo json_encode(['success' => true, 'deleted' => $name], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
