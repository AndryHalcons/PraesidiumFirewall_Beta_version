<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');

// Verifica si el usuario está autenticado
// Check if the user is authenticated
if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']); // Not authorized
    exit;
}

// Leer el cuerpo de la solicitud (JSON enviado por fetch)
// Read the request body (JSON sent via fetch)
$input = json_decode(file_get_contents('php://input'), true);

// Validar que se recibió el parámetro 'table' y el 'id'
// Validate that 'table' and 'id' were received
$userTable = isset($input['table']) ? trim($input['table']) : '';
$idToDelete = isset($input['id']) && is_numeric($input['id']) ? (string)(int)$input['id'] : null;

$allowedTables = ['dhcp'];
if (!in_array($userTable, $allowedTables, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']); // Invalid "table" parameter
    exit;
}
if ($idToDelete === null) {
    echo json_encode(['error' => 'ID inválido']); // Invalid ID
    exit;
}

// Ruta del archivo JSON
// Path to the JSON file
$jsonPath = '/var/www/config/dhcp.json';

// Verifica que el archivo exista
// Check that the file exists
if (!file_exists($jsonPath)) {
    echo json_encode(['error' => 'Archivo de datos no encontrado']); // Data file not found
    exit;
}

// Cargar el contenido actual del archivo
// Load current content from file
$data = json_decode(file_get_contents($jsonPath), true);

// Verificar que el JSON esté bien formado y contenga la tabla
// Validate JSON structure and presence of the table
if (json_last_error() !== JSON_ERROR_NONE || !isset($data[$userTable]) || !is_array($data[$userTable])) {
    echo json_encode(['error' => 'JSON mal formado o tabla no encontrada']); // Malformed JSON or missing table
    exit;
}

// Filtrar las reglas excluyendo la que tiene el ID a eliminar
// Filter rules excluding the one with the matching ID
$data[$userTable] = array_values(array_filter($data[$userTable], function ($entry) use ($idToDelete) {
    return isset($entry['rule']['id']) && (string)$entry['rule']['id'] !== $idToDelete;
}));

// Guardar el JSON actualizado
// Save the updated JSON
$saved = file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
if ($saved === false) {
    echo json_encode(['error' => 'No se pudo guardar el archivo']); // Failed to save file
    exit;
}

// Respuesta de éxito
// Success response
echo json_encode(['success' => true]);
exit;
