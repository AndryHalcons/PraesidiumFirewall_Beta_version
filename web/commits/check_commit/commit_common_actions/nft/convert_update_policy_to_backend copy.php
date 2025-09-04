<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}
header('Content-Type: application/json');

// Verifica que el usuario tenga sesión activa
// Check that the user has an active session
if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

/*
only if front send
// Lee el cuerpo de la solicitud y decodifica el JSON
// Read the request body and decode the JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verifica que el JSON sea válido y contenga los campos necesarios
// Validate that the JSON is correct and contains required fields
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['table']) || !isset($data['rule'])) {
    echo json_encode(['error' => 'Entrada JSON inválida']);
    exit;
}
*/
// Incluye las funciones de validación y sanitización
// Include validation and sanitization functions
require __DIR__ . '/convert_policys_validation_to_nft.php';

// Función para validar la regla recibida
// Function to validate the received rule
function validate_nftables_policy(array $rule): array {
    $rule = validation_icmp_no_ports($rule);
    $rule = Main_convert_alias_object_to_network_object($rule);
    $rule = comment_convert_id_name($rule);
    validation_form_field_review($rule);
    $rule = assign_position($rule);
    $rule = log_format_nft($rule);
    return $rule;
}

// Ruta del archivo de configuración de reglas
// Path to the nftables rules configuration file
$jsonPath = '/var/www/config/rules_nftables.json';
// Verifica que el archivo exista
// Check that the file exists
if (!file_exists($jsonPath)) {
    echo json_encode(['error' => 'Archivo de reglas no encontrado']);
    exit;
}
// Carga y decodifica el contenido del archivo
// Load and decode the file content
$raw = file_get_contents($jsonPath);
$rulesJson = json_decode($raw, true);

// Verifica que el JSON sea válido y tenga la clave 'nftables'
// Validate that the JSON is correct and contains the 'nftables' key
if (json_last_error() !== JSON_ERROR_NONE || !isset($rulesJson['nftables'])) {
    echo json_encode(['error' => 'JSON de reglas mal formado']);
    exit;
}

//////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////Archivo para el backend/////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

//  leer el archivo human_viewer y procesar todas las reglas
//read the human_viewer file and process all rules
$humanPath = '/var/www/config/rules_nftables_human_viewer.json';
if (!file_exists($humanPath)) {
    echo json_encode(['error' => 'Archivo human_viewer no encontrado']);
    exit;
}

$humanRaw = file_get_contents($humanPath);
$humanJson = json_decode($humanRaw, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($humanJson['nftables'])) {
    echo json_encode(['error' => 'JSON human_viewer mal formado']);
    exit;
}

foreach ($humanJson['nftables'] as $entry) {
    if (!isset($entry['rule']) || !is_array($entry['rule'])) {
        continue;
    }
    $validated = validate_nftables_policy($entry['rule']);
    $sanitized = saniticed_nftables_policy($validated);
    $rulesJson = update_or_insert_nft_rule($sanitized['rule'], $rulesJson);
}

// guardar el archivo actualizado
$saved = file_put_contents(
    $jsonPath,
    json_encode($rulesJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

if ($saved === false) {
    echo json_encode(['error' => 'No se pudo guardar el archivo']);
    exit;
}

//////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////Archivo para el front///////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////
// respuesta final al frontend
echo json_encode(['success' => true]);
exit;
