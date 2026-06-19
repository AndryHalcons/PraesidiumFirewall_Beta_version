<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_admin_json();
csrf_validate_or_exit();
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

$allowedTables = [
    'BF_HOOK_XDP',
    'BF_HOOK_TC_INGRESS',
    'BF_HOOK_TC_EGRESS',
];

if (!in_array($data['table'], $allowedTables)) {
    echo json_encode(['error' => 'Tabla no permitida: ' . $data['table']]);
    exit;
}

// Incluye las funciones de validación y sanitización
// Include validation and sanitization functions
require __DIR__ . '/validation_policy.php';

// Ruta del archivo de configuración de reglas
// Path to the bpfilter rules configuration file
$jsonPath = '/var/www/config/rules_bpfilter_human_viewer.json';

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

// Verifica que el JSON sea válido y tenga la clave 'bpfilter'
// Validate that the JSON is correct and contains the 'bpfilter' key
if (json_last_error() !== JSON_ERROR_NONE || !isset($rulesJson['bpfilter'])) {
    echo json_encode(['error' => 'JSON de reglas mal formado']);
    exit;
}
//////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////// Execute Updated ////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////
// Función para validar la regla recibida
// Function to validate the received rule
function validate_bpfilter_policy(array $data, array $rule): array {
    $rule = validationFamiliy($data,$rule);
    $rule = gen_chain_name($rule);
    $rule = validation_icmp_no_ports($rule);
    $rule = Main_convert_alias_object_to_network_object($rule);
    $rule = get_id_from_policy($rule);
    validation_form_field_review($rule);
    $rule = assign_position($rule);
    return $rule;
}
// proceso de validacion
// validation process

$validated = validate_bpfilter_policy($data, $data['rule']);

//preceso de satinizacion
//sanitization process

$sanitized = saniticed_bpfilter_policy($validated);


// Validación de compatibilidad entre protocolos de BPfilter
validate_bpfilter_protocols($sanitized['rule']);


//insert o update de la regla
//insert or update of the police
$rulesJson = update_or_insert_bpf_rule($sanitized['rule'], $rulesJson);

//ordenamos las reglas por el campo posicion
//We order the rules by the position field

$rulesJson = reorderPosition(
    $rulesJson,
    $sanitized['rule']['id'],
    $sanitized['rule']['position'],
    $sanitized['rule']['hook'],
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

