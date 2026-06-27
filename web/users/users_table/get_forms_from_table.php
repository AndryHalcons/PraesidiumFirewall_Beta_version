<?php
require_once __DIR__ . '/../../common/security/session.php';
praesidium_session_start();

// Verifica si el usuario está autenticado  
// Check if the user is authenticated  
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);  
    // Not authorized  
    exit;
}

// Obtiene el parámetro 'table' desde la URL  
// Get the 'table' parameter from the URL  
$userTable = $_GET['table'] ?? '';
$userTable = is_string($userTable) ? trim($userTable) : '';

// Valida que el nombre de la tabla esté permitido  
// Validate that the table name is allowed  
$allowedTables = ['table_users'];
if (!in_array($userTable, $allowedTables, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);  
    // Invalid "table" parameter  
    exit;
}

// Carga el archivo JSON con la estructura de formularios  
// Load the JSON file with the form structure  
$formPath = '/var/www/backend/checks/system_data/default_forms/forms_table_users.json';
if (!file_exists($formPath)) {
    echo json_encode(['error' => 'Archivo de configuración no encontrado']);  
    // Configuration file not found  
    exit;
}

$formRaw = file_get_contents($formPath);
$formData = json_decode($formRaw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'JSON mal formado']);  
    // Malformed JSON  
    exit;
}

// Devuelve el JSON final al frontend  
// Return the final JSON to the frontend  
echo json_encode($formData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
