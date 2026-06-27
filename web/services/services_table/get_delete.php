<?php
require_once __DIR__ . '/../../common/security/session.php';
/*
#############################################################################
   Endpoint de borrado deshabilitado para Servicios
   Delete-disabled endpoint for Services

   La sección Servicios es un catálogo fijo, no un CRUD libre. Este endpoint
   existe solo para satisfacer la firma de renderTableGeneric mientras el JSON
   de formularios mantiene disable_delete=true.

   The Services section is a fixed catalog, not free-form CRUD. This endpoint
   exists only to satisfy renderTableGeneric's signature while the forms JSON
   keeps disable_delete=true.
#############################################################################
*/
praesidium_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');

require_once __DIR__ . '/services_common.php';

// Rechaza siempre cualquier intento de borrado, incluso si alguien llama directo al endpoint.
// Always rejects delete attempts, even if somebody calls the endpoint directly.
echo json_encode(['success' => false, 'error' => services_t('services_error_delete_disabled', 'The services table does not allow deleting rows')]);
