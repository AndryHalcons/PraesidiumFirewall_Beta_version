<?php
require_once __DIR__ . '/../../common/security/session.php';
/*
#############################################################################
   Endpoint de contenido de tabla para Servicios
   Services table content endpoint

   Devuelve filas ya enriquecidas con el estado runtime actual. No lee el
   runtime desde JSON; lo consulta en vivo mediante services_build_rows().

   Returns rows already enriched with the current runtime state. It does not
   read runtime from JSON; it checks it live through services_build_rows().
#############################################################################
*/
praesidium_session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Solo la tabla services puede consumir este endpoint genérico.
// Only the services table may consume this generic endpoint.
$table = $_GET['table'] ?? '';
if ($table !== 'services') {
    echo json_encode(['error' => 'Parámetro table inválido']);
    exit;
}

require_once __DIR__ . '/services_common.php';

echo json_encode(['services' => services_build_rows()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
