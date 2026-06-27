<?php
// Endpoint WireGuard: valida y guarda una entrada en el candidate JSON.
// WireGuard endpoint: validates and saves one entry into the candidate JSON.

// Fase 1: abrir sesión, exigir admin y validar CSRF antes de tocar candidate.
// Phase 1: open session, require admin, and validate CSRF before touching candidate.
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/file/json_store.php';
require_once __DIR__ . '/../common/wireguard_store.php';
require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');

// Fase 2: leer payload JSON enviado por el modal genérico.
// Phase 2: read the JSON payload sent by the generic modal.
$input = json_decode(file_get_contents('php://input'), true);
$rule = $input['rule'] ?? null;
if (!is_array($rule)) { wireguard_error(wireguard_t('wireguard_error_invalid_payload'), null); }

// Fase 3: cargar candidate y resolver nombre explícito o autogenerado.
// Phase 3: load candidate and resolve explicit or auto-generated name.
$config = wireguard_read_json(WIREGUARD_CONFIG_PATH);
$name = trim((string)($rule['name'] ?? ''));
if ($name === '' || $name === 'Auto') { $name = wireguard_make_name($config, 'remote_access'); }
wireguard_validate_entry_name($name, 'name');
unset($rule['name']);

// Fase 4: conservar claves privadas existentes cuando la tabla envía el marcador enmascarado.
// Phase 4: preserve existing private keys when the table sends the masked marker.
if (($rule['private_key'] ?? '') === '********' && isset($config['remote_access'][$name]['private_key'])) {
    $rule['private_key'] = $config['remote_access'][$name]['private_key'];
}

// Fase 6: validar reglas PHP antes de persistir el candidate.
// Phase 6: validate PHP rules before persisting the candidate.
$rule = wireguard_validate_rule('wireguard_remote_access', $rule, $config, $name);
$config['remote_access'][$name] = $rule;
// Fase 7: guardar candidate manteniendo secciones vacías como objetos JSON.
// Phase 7: save candidate while keeping empty sections as JSON objects.
$saved = json_store_write(WIREGUARD_CONFIG_PATH, wireguard_prepare_for_json($config), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($saved === false) { wireguard_error(wireguard_t('wireguard_error_save_permissions'), null); }
@chmod(WIREGUARD_CONFIG_PATH, 0664);
// Fase 8: responder al modal para que refresque la tabla afectada.
// Phase 8: respond to the modal so it can refresh the affected table.
echo json_encode(['success' => true, 'updated' => $name], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
