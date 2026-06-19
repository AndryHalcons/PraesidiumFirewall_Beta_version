<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/file/json_store.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');

// Verifica si el usuario tiene sesión activa
// Check if the user has an active session
if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']); // Not authorized
    exit;
}

// Leer el cuerpo de la solicitud (JSON enviado por el frontend)
// Read the request body (JSON sent from frontend)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validar que el JSON sea válido y contenga las claves necesarias
// Validate that the JSON is valid and contains required keys
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['table']) || !isset($data['rule'])) {
    echo json_encode(['error' => 'Entrada JSON inválida']); // Invalid JSON input
    exit;
}

// Definir las tablas permitidas para edición
// Define allowed tables for editing
$allowedTables = ['table_users'];
if (!in_array($data['table'], $allowedTables)) {
    echo json_encode(['error' => 'Tabla no permitida: ' . $data['table']]); // Table not allowed
    exit;
}

// Ruta del archivo JSON que contiene los datos
// Path to the JSON file containing user data
$jsonPath = '/var/www/config/users.json';

// Verifica que el archivo exista
// Check that the file exists
if (!file_exists($jsonPath)) {
    echo json_encode(['error' => 'Archivo de datos no encontrado']); // Data file not found
    exit;
}

// Cargar y decodificar el contenido actual del archivo
// Load and decode the current content of the file
$raw = file_get_contents($jsonPath);
$rulesJson = json_decode($raw, true);

// Verifica que el JSON esté bien formado y contenga la tabla solicitada
// Validate that the JSON is well-formed and contains the requested table
if (json_last_error() !== JSON_ERROR_NONE || !isset($rulesJson[$data['table']])) {
    echo json_encode(['error' => 'JSON mal formado o tabla no encontrada']); // Malformed JSON or missing table
    exit;
}

//////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////// validation_user ////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////
require __DIR__ . '/validation_user.php';
// Esta función recibe la regla y devuelve el JSON completo actualizado
// This function receives the rule and returns the full updated JSON
function validation_user(array $rule, array $rulesJson): array {
    $rule = check_user_id($rule);
    $rule = hash_pass($rule);
    $rulesJson = update_or_add_user($rule,$rulesJson);


    return $rulesJson; // Devuelve el JSON completo actualizado
    // Return the full updated JSON
}

//////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////// EJECUCIÓN //////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

// Ejecutar validation_user para obtener el nuevo JSON
// Run validators to get the new JSON
$updatedJson = validation_user($data['rule'], $rulesJson);

// Guardar el archivo actualizado
// Save the updated file
$saved = json_store_write($jsonPath, $updatedJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if ($saved === false) {
    echo json_encode(['error' => 'No se pudo guardar el archivo']); // Failed to save file
    exit;
}

//////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////// RESPUESTA FINAL ////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

// Enviar respuesta de éxito al frontend
// Send success response to frontend
echo json_encode(['success' => true]);
exit;
