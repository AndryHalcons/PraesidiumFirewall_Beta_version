<?php
session_start();

// Verificar si el usuario ha iniciado sesión
// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

// Cargar archivo de idioma según la sesión
// Load language file based on session
$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../../lang/es.php";
}
$L = require $langFile;

// Ruta del archivo JSON de interfaces
// Path to the interfaces JSON file
$jsonFile = '/var/www/config/interfaces.json';

// Leer el cuerpo de la petición (JSON enviado por JavaScript)
// Read the request body (JSON sent by JavaScript)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validar que se recibió un JSON válido con el campo "name"
// Validate that a valid JSON with "name" field was received
if ($data === null || !isset($data['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido o falta el campo "name" / Invalid JSON or missing "name" field']);
    exit;
}

// Limpiar campos vacíos del array "options"
// Remove empty fields from "options" array
if (isset($data['options']) && is_array($data['options'])) {
    $data['options'] = array_filter($data['options'], function($valor) {
        return $valor !== null && $valor !== '';
    });
}

// Leer el contenido actual del archivo JSON
// Read the current content of the JSON file
$interfaces = [];
if (file_exists($jsonFile)) {
    $contenido = file_get_contents($jsonFile);
    $decoded = json_decode($contenido, true);
    if (isset($decoded['interfaces']) && is_array($decoded['interfaces'])) {
        $interfaces = $decoded['interfaces'];
    }
}

// Buscar si ya existe una interfaz con ese nombre
// Check if an interface with the same name already exists
$nombre = $data['name'];
$actualizado = false;

foreach ($interfaces as $i => $iface) {
    if (isset($iface['name']) && $iface['name'] === $nombre) {
        $interfaces[$i] = $data; // Sustituir la interfaz existente / Replace existing interface
        $actualizado = true;
        break;
    }
}

if (!$actualizado) {
    $interfaces[] = $data; // Añadir nueva interfaz / Add new interface
}

// Guardar el contenido actualizado en el archivo JSON
// Save the updated content to the JSON file
file_put_contents($jsonFile, json_encode(['interfaces' => $interfaces], JSON_PRETTY_PRINT));

// Enviar respuesta al frontend
// Send response to frontend
echo json_encode([
    'status' => 'ok',
    'actualizado' => $actualizado,
    'mensaje' => $actualizado ? 'Interfaz actualizada / Interface updated' : 'Interfaz añadida / Interface added'
]);
?>
