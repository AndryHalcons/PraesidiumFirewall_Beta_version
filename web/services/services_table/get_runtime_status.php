<?php
require_once __DIR__ . '/../../common/security/auth.php';
/*
#############################################################################
   Endpoint de refresco runtime de Servicios
   Services runtime refresh endpoint

   Lo usa el botón Actualizar estado. Ejecuta los mismos checkers en vivo que
   la carga inicial de tabla: systemctl o sysctl.

   Used by the Refresh status button. It runs the same live checkers as the
   initial table load: systemctl or sysctl.
#############################################################################
*/
require_login_json();
header('Content-Type: application/json');


$table = $_GET['table'] ?? '';
if ($table !== 'services') {
    echo json_encode(['success' => false, 'error' => 'Parámetro table inválido']);
    exit;
}

require_once __DIR__ . '/services_common.php';

// Calcula el estado runtime para cada entrada fija del catálogo.
// Calculates runtime status for every fixed catalog entry.
$result = [];
foreach (services_catalog() as $name => $definition) {
    $result[$name] = [
        'runtime_status' => services_runtime_status($definition)
    ];
}

echo json_encode(['success' => true, 'services' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
