<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/file/json_store.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_admin_json();
csrf_validate_or_exit();
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']); 
    exit;
}

header('Content-Type: application/json');

// Leer y decodificar el cuerpo JSON
// Read and decode the JSON body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validar formato JSON
// Validate JSON format
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Entrada JSON inválida']); // Invalid JSON input
    exit;
}

// Validar tabla permitida
// Validate allowed table
$allowedTables = [ 'FORWARDING', 'PREROUTING', 'POSTROUTING', 'input', 'output' ];
if (!isset($data['table']) || !in_array($data['table'], $allowedTables)) {
    echo json_encode(['error' => 'Tabla no permitida']); // Table not allowed
    exit;
}

// Validar que el ID sea un entero positivo
// Validate that ID is a positive integer
function is_valid_integer_id($value): bool {
    return is_int($value) || (is_string($value) && ctype_digit($value));
}

if (!isset($data['id']) || !is_valid_integer_id($data['id'])) {
    echo json_encode(['error' => 'ID inválido']); // Invalid ID
    exit;
}

// Ruta del archivo de reglas
// Path to the rules file
$jsonPath = '/var/www/config/rules_nftables_human_viewer.json';

// Verificar que el archivo existe
// Check that the file exists
if (!file_exists($jsonPath)) {
    echo json_encode(['error' => 'Archivo de reglas no encontrado']); // Rules file not found
    exit;
}

// Cargar y decodificar el JSON
// Load and decode the JSON
$raw = file_get_contents($jsonPath);
$rulesJson = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($rulesJson['nftables'])) {
    echo json_encode(['error' => 'JSON de reglas mal formado']); // Malformed rules JSON
    exit;
}

// Eliminar la regla con el ID proporcionado
// Remove the rule with the provided ID
$idToDelete = (string)$data['id'];
$originalCount = count($rulesJson['nftables']);

$rulesJson['nftables'] = array_values(array_filter($rulesJson['nftables'], function ($entry) use ($idToDelete) {
    return isset($entry['rule']['id']) && (string)$entry['rule']['id'] !== $idToDelete;
}));

// Verificar si se eliminó algo
// Check if something was deleted
if (count($rulesJson['nftables']) === $originalCount) {
    echo json_encode(['error' => 'No se encontró ninguna regla con ese ID']); // No rule found with that ID
    exit;
}

// Guardar el archivo actualizado
// Save the updated file
$saved = json_store_write($jsonPath, $rulesJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($saved === false) {
    echo json_encode(['error' => 'No se pudo guardar el archivo']); // Failed to save file
    exit;
}

// Todo correcto
// All good
echo json_encode(['success' => true]);
exit;
