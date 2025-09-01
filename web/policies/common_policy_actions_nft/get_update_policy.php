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

$tableName = $data['table'];
$ruleData = $data['rule'];



// Incluye las funciones de validación y sanitización
// Include validation and sanitization functions
require __DIR__ . '/validation_policy.php';
// Función para validar la regla recibida
// Function to validate the received rule
function validate_nftables_policy(array $rule): array {
    $rule = validation_icmp_no_ports($rule);
    $rule = Main_convert_alias_object_to_network_object($rule);
    $rule = comment_convert_id_name($rule);

    return $rule;
}


// Función para convertir la regla al formato de nftables
// Function to convert the rule to nftables format
function saniticed_nftables_policy($rule) {
    // Esta función generará el bloque expr más adelante
    // This function will generate the expr block later
    return [
        "rule" => [
            "family" => $rule["family"] ?? "inet",
            "table"  => $rule["table"] ?? "filter",
            "chain"  => $rule["chain"] ?? $tableName,
            "handle" => $rule["handle"] ?? null,
            "position" => $rule["position"] ?? 1,
            "expr" => [], // Se completará en el futuro
            "comment" => $rule["comment"] ?? ""
        ]
    ];
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

// Aplica validación y sanitización a la regla
// Apply validation and sanitization to the rule
$validated = validate_nftables_policy($ruleData);
$sanitized = saniticed_nftables_policy($validated);

// Busca si ya existe una regla con el mismo handle
// Search for an existing rule with the same handle
$handle = $sanitized["rule"]["handle"];
$found = false;

foreach ($rulesJson["nftables"] as $index => $entry) {
    if (isset($entry["rule"]) && $entry["rule"]["handle"] == $handle) {
        // Si se encuentra, se actualiza la regla existente
        // If found, update the existing rule
        $rulesJson["nftables"][$index] = $sanitized;
        $found = true;
        break;
    }
}

// Si no se encuentra, se añade como nueva regla
// If not found, add it as a new rule
if (!$found) {
    $rulesJson["nftables"][] = $sanitized;
}

// Guarda el archivo actualizado
// Save the updated file
if (file_put_contents($jsonPath, json_encode($rulesJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(["success" => true, "updated" => $sanitized]);
} else {
    echo json_encode(["error" => "No se pudo guardar el archivo"]);
}



