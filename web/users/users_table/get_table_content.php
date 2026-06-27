<?php
require_once __DIR__ . '/../../common/security/auth.php';
require_login_json();


header('Content-Type: application/json');

// Definir las tablas que se pueden consultar
// Define the allowed tables that can be queried
$allowedTables = ['table_users'];

// Obtener el nombre de la tabla desde el parámetro GET
// Get the table name from the GET parameter
$table = $_GET['table'] ?? '';

// Verificar si la tabla solicitada está permitida
// Check if the requested table is allowed
if (!in_array($table, $allowedTables, true)) {
    echo json_encode(['error' => 'Parámetro inválido']); // Invalid parameter
    exit;
}

// Ruta al archivo JSON que contiene los datos
// Path to the JSON file containing user data
$jsonPath = '/var/www/config/users.json';

// Verificar si el archivo existe
// Check if the file exists
if (!file_exists($jsonPath)) {
    echo json_encode(['error' => 'Archivo de datos no encontrado']); // Data file not found
    exit;
}

// Leer y decodificar el contenido del archivo JSON
// Read and decode the contents of the JSON file
$data = json_decode(file_get_contents($jsonPath), true);

// Verificar si hubo errores al decodificar el JSON
// Check for errors during JSON decoding
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'JSON inválido: ' . json_last_error_msg()]); // Invalid JSON
    exit;
}

// Verificar si la tabla solicitada existe en el archivo
// Check if the requested table exists in the JSON data
if (!isset($data[$table])) {
    echo json_encode(['error' => 'No existe la tabla solicitada']); // Requested table does not exist
    exit;
}

// Filtrar los datos para ocultar la contraseña real
// Filter the data to mask the real password
$filtered = array_map(function ($user) {
    // Si existe el campo 'user_pass', reemplazarlo por asteriscos
    // If the 'user_pass' field exists, replace it with asterisks
    if (isset($user['user_pass'])) {
        $user['user_pass'] = '******'; // Masked password
    }
    return $user;
}, $data[$table]);

// Devolver los datos filtrados en formato JSON
// Return the filtered data as JSON
echo json_encode([
    $table => $filtered
]);
