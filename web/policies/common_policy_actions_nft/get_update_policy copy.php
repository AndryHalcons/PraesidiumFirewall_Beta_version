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

// Incluye las funciones de validación y sanitización
// Include validation and sanitization functions
require __DIR__ . '/validation_policy.php';
// Función para validar la regla recibida
// Function to validate the received rule
function validate_nftables_policy(array $rule): array {
    $rule = validation_icmp_no_ports($rule);
    $rule = Main_convert_alias_object_to_network_object($rule);
    $rule = comment_convert_id_name($rule);
    validation_form_field_review($rule);
    $rule = assign_position($rule);
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


// proceso de validacion
// validation process
$validated = validate_nftables_policy($data['rule']);
//preceso de satinizacion
//sanitization process
$sanitized = saniticed_nftables_policy($validated, $data['table']);
//insert o update de la regla
//insert or update of the police
update_or_insert_nft_rule($sanitized['rule'], $rulesJson, $jsonPath);














