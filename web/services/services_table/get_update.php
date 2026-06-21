<?php
/*
#############################################################################
   Endpoint de actualización candidate para Servicios
   Services candidate update endpoint

   Solo persiste desired_enabled en el candidate JSON. No arranca, para ni
   aplica servicios; esa responsabilidad pertenece al flujo de commit/apply.

   It only persists desired_enabled in the candidate JSON. It does not start,
   stop or apply services; that belongs to the commit/apply flow.
#############################################################################
*/
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/file/json_store.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');

require_once __DIR__ . '/services_common.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['table']) || $data['table'] !== 'services' || !isset($data['rule']) || !is_array($data['rule'])) {
    echo json_encode(['success' => false, 'error' => services_t('services_error_invalid_json', 'Invalid JSON input')]);
    exit;
}

$rule = $data['rule'];
// renderTableGeneric envía solo las columnas visibles de la estructura.
// renderTableGeneric sends only the visible columns from the structure.
// Servicios usa display_name como identificador visible estable cuando service_name no viaja en el modal.
// Services uses display_name as the stable visible identifier when service_name is not sent by the modal.
$serviceName = trim((string)($rule['service_name'] ?? $rule['display_name'] ?? ''));
$desired = trim((string)($rule['desired_enabled'] ?? ''));
$catalog = services_catalog();

// Resolver display_name amigable a service_name estable cuando el modal genérico
// no envía la clave interna como columna visible.
// Resolve friendly display_name to stable service_name when the generic modal
// does not send the internal key as a visible column.
if (!isset($catalog[$serviceName])) {
    foreach ($catalog as $catalogName => $definition) {
        if ((string)($definition['display_name'] ?? '') === $serviceName) {
            $serviceName = $catalogName;
            break;
        }
    }
}

// Valida que la fila pertenezca al catálogo fijo de Servicios.
// Validates that the row belongs to the fixed Services catalog.
if (!isset($catalog[$serviceName])) {
    echo json_encode(['success' => false, 'error' => services_t('services_error_service_not_allowed', 'Service is not allowed')]);
    exit;
}

// Impide cambios en entradas de monitorización marcadas como no configurables.
// Prevents changes on monitor-only entries marked as non-configurable.
if ($catalog[$serviceName]['configurable'] !== 'true') {
    echo json_encode(['success' => false, 'error' => services_t('services_error_not_configurable', 'This service is not configurable from the interface')]);
    exit;
}

// Solo se aceptan valores booleanos serializados como strings del patrón actual.
// Only boolean values serialized as current-pattern strings are accepted.
if (!in_array($desired, ['true', 'false'], true)) {
    echo json_encode(['success' => false, 'error' => services_t('services_error_invalid_desired', 'Invalid desired_enabled value')]);
    exit;
}

$candidate = services_load_candidate();
$candidate['services'][$serviceName]['desired_enabled'] = $desired;

$saved = json_store_write(services_candidate_path(), $candidate, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($saved === false) {
    echo json_encode(['success' => false, 'error' => services_t('services_error_save_failed', 'Could not save services.json')]);
    exit;
}

// Mantiene permisos coherentes con los JSON candidate de Praesidium.
// Keeps permissions consistent with Praesidium candidate JSON files.
@chmod(services_candidate_path(), 0664);
@chgrp(services_candidate_path(), 'www-data');

echo json_encode(['success' => true]);
