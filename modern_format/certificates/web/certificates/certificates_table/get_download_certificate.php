<?php
require_once __DIR__ . '/../../common/security/auth.php';
require_login_json();

// Verificar si el usuario tiene sesión activa
// Check if the user has an active session


// Leer el cuerpo de la solicitud JSON
// Read the JSON request body
$input = json_decode(file_get_contents('php://input'), true);

// Obtener el alias de la tabla desde GET o POST
// Get the table alias from GET or POST
$chain = trim($_GET['table'] ?? $_GET['chain'] ?? $input['table'] ?? '');

// Definir las cadenas permitidas
// Define allowed chains
$allowedChains = ['certificates'];

// Validar que la cadena sea válida
// Validate that the chain is valid
if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Parámetro inválido o ausente']);
    // Invalid or missing parameter
    exit;
}

// Validar que se recibieron los parámetros necesarios
// Validate that required parameters were received
if (!$input || empty($input['fileName']) || empty($input['name'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros faltantes o inválidos']);
    // Missing or invalid parameters
    exit;
}

// Obtener el nombre del archivo y el nombre lógico
// Get the filename and logical name
$fileName = basename($input['fileName']); // Evita rutas relativas
// Prevent relative paths
$name = basename($input['name']);         // Nombre lógico del archivo
// Logical name of the file

// Definir el directorio de certificados
// Define the certificates directory
$targetDir = "/var/www/config/certs/";
$filePath = $targetDir . $fileName;

// Verificar si el archivo existe
// Check if the file exists
if (!file_exists($filePath)) {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['error' => 'Archivo no encontrado']);
    // File not found
    exit;
}

// Servir el archivo como descarga binaria
// Serve the file as binary download
header('Content-Type: application/octet-stream');
// Set content type for binary download
header('Content-Disposition: attachment; filename="' . $fileName . '"');
// Force download with original filename
header('Content-Length: ' . filesize($filePath));
// Set content length
header('Content-Encoding: none');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
// Prevent compression interference
readfile($filePath);
// Output file content
exit;
