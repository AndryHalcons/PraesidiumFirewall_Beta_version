<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';

/*
################################################################################
######################### SYSTEM LOGGING FORMS ENDPOINT #########################
################################################################################
Español: devuelve tipos de campo y opciones desde default_forms para que la UI
no tenga hardcodeada la definición de campos.
English: returns field types and options from default_forms so the UI does not
hardcode field definitions.
################################################################################
*/

require_login_json();
header('Content-Type: application/json');

$path = '/var/www/backend/checks/system_data/default_forms/forms_system_logging.json';
if (!file_exists($path)) {
    http_response_code(404);
    echo json_encode(['error' => 'forms_system_logging.json not found']);
    exit;
}

$content = file_get_contents($path);
$data = json_decode($content, true);
if (!is_array($data)) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid system logging forms JSON']);
    exit;
}

echo json_encode($data);
