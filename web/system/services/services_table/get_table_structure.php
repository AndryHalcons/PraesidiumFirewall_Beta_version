<?php
require_once __DIR__ . '/../../../common/security/auth.php';
/*
#############################################################################
   Endpoint de estructura de tabla para Servicios
   Services table structure endpoint

   Entrega structure_table_services.json al renderer genérico. La estructura
   define qué columnas viajan al modal, por eso debe mantenerse alineada con
   get_update.php y el catálogo estable de Servicios.

   It provides structure_table_services.json to the generic renderer. The
   structure defines which columns travel to the modal, so it must stay aligned
   with get_update.php and the stable Services catalog.
#############################################################################
*/
require_login_json();
header('Content-Type: application/json');


// Acepta únicamente la tabla fija services para evitar exposición de estructuras ajenas.
// Accepts only the fixed services table to avoid exposing unrelated structures.
$table = $_GET['table'] ?? '';
if ($table !== 'services') {
    echo json_encode(['error' => 'Parámetro table inválido']);
    exit;
}

// Fuente declarativa de columnas usada por renderTableGeneric.
// Declarative column source used by renderTableGeneric.
$jsonPath = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_services.json';
if (!file_exists($jsonPath)) {
    echo json_encode(['error' => 'Archivo de estructura no encontrado']);
    exit;
}

$structures = json_decode(file_get_contents($jsonPath), true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($structures[$table])) {
    echo json_encode(['error' => 'Estructura inválida para servicios']);
    exit;
}

echo json_encode([$table => $structures[$table]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
