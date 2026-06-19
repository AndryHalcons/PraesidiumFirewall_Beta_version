<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';

/*
################################################################################
######################## SYSTEM LOGGING STRUCTURE ENDPOINT ######################
################################################################################
Español: devuelve la estructura declarativa de la tabla/formulario de logs del
sistema desde default_tables_structure.
English: returns the declarative system logging table/form structure from
 default_tables_structure.
################################################################################
*/

require_login_json();
header('Content-Type: application/json');

$path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_system_logging.json';
if (!file_exists($path)) {
    http_response_code(404);
    echo json_encode(['error' => 'structure_table_system_logging.json not found']);
    exit;
}

$content = file_get_contents($path);
$data = json_decode($content, true);
if (!is_array($data)) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid system logging table structure JSON']);
    exit;
}

echo json_encode($data);
