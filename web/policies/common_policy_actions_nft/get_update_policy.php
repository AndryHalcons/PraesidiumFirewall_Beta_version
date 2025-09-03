<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}
header('Content-Type: application/json');


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

$allowedTables = [ 'FORWARDING', 'PREROUTING', 'POSTROUTING', 'input', 'output' ];

if (!in_array($data['table'], $allowedTables)) {
    echo json_encode(['error' => 'Tabla no permitida: ' . $data['table']]);
    exit;
}

// Incluye las funciones de validación y sanitización
// Include validation and sanitization functions
require __DIR__ . '/validation_policy.php';

// Ruta del archivo de configuración de reglas
// Path to the nftables rules configuration file
$jsonPath = '/var/www/config/rules_nftables_human_viewer.json';

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
///////////////////////////// Execute Updated ////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////
// Función para validar la regla recibida
// Function to validate the received rule
function validate_nftables_policy(array $data, array $rule): array {
    $rule = validationFamiliy($data,$rule);
    $rule = validation_icmp_no_ports($rule);
    $rule = Main_convert_alias_object_to_network_object($rule);
    $rule = get_id_from_policy($rule);
    validation_form_field_review($rule);
    $rule = assign_position($rule);
    return $rule;
}
// proceso de validacion
// validation process
$validated = validate_nftables_policy($data, $data['rule']);
//preceso de satinizacion
//sanitization process
$sanitized = saniticed_nftables_policy($validated);
//insert o update de la regla
//insert or update of the police
$rulesJson = update_or_insert_nft_rule($sanitized['rule'], $rulesJson);
//ordenamos las reglas por el campo posicion
//We order the rules by the position field

$rulesJson = reorderPosition(
    $rulesJson,
    $sanitized['rule']['id'],
    $sanitized['rule']['position'],
    $sanitized['rule']['family'],
    $sanitized['rule']['table'],
    $sanitized['rule']['chain']
);

// guardar el archivo actualizado
//save updated file
$saved = file_put_contents($jsonPath, json_encode($rulesJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

if ($saved === false) {
    echo json_encode(['error' => 'No se pudo guardar el archivo']);
    exit;
}

//////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////// JSON for front /////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

// respuesta final al frontend
echo json_encode(['success' => true]);
exit;


