<?php
require_once __DIR__ . '/../../../common/security/auth.php';
/*
#############################################################################
   Endpoint de metadatos de formulario para Servicios
   Services form metadata endpoint

   Entrega forms_services.json al renderer genérico. Ahí se declaran checkbox,
   campos no editables y la desactivación de Add/Delete para respetar el modelo
   de catálogo fijo.

   It provides forms_services.json to the generic renderer. That file declares
   checkbox fields, non-editable fields and Add/Delete disabling to preserve the
   fixed-catalog model.
#############################################################################
*/
require_login_json();
header('Content-Type: application/json');


// Solo la tabla services puede solicitar este formulario.
// Only the services table may request this form metadata.
$table = $_GET['table'] ?? '';
if ($table !== 'services') {
    echo json_encode(['error' => 'Parámetro table inválido']);
    exit;
}

// Fuente declarativa del formulario usada por el patrón genérico de Praesidium.
// Declarative form source used by Praesidium's generic pattern.
$formPath = '/var/www/backend/checks/system_data/default_forms/forms_services.json';
if (!file_exists($formPath)) {
    echo json_encode(['error' => 'Archivo de configuración no encontrado']);
    exit;
}

$formData = json_decode(file_get_contents($formPath), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'JSON mal formado']);
    exit;
}

echo json_encode($formData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
