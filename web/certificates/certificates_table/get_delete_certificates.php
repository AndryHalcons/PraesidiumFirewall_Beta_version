<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

//$table = trim($_GET['table'] ?? '');
$input = json_decode(file_get_contents('php://input'), true);
$table = trim($input['table'] ?? '');
$allowedTables = ['certificates'];



if ($table === '' || !in_array($table, $allowedTables, true)) {
    echo json_encode(['error' => 'parametro incorrecto']);
    exit;
}

// Dispatcher: solo ejecuta la función
switch ($table) {
    case 'certificates': get_delete_certificates(); break;
    default:
        echo json_encode(['error' => 'Tabla no soportada']);
        break;
}



function get_delete_certificates(): void {
    $targetDir = "/var/www/config/certs/";

    // Leer el cuerpo de la solicitud JSON
    // Read the JSON request body
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar que se recibió correctamente
    // Validate that it was received correctly
    if (!$input || empty($input['fileName'])) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Parámetros faltantes o inválidos" // Missing or invalid parameters
        ]);
        exit;
    }

    // Obtener el nombre del archivo
    // Get the filename
    $fileName = basename($input['fileName']); // Evita rutas relativas
    $filePath = $targetDir . $fileName;

    // Verificar si el archivo existe
    // Check if the file exists
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Archivo no encontrado" // File not found
        ]);
        exit;
    }

    // Intentar borrar el archivo
    // Try to delete the file
    if (!unlink($filePath)) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "No se pudo eliminar el archivo" // Could not delete the file
        ]);
        exit;
    }

    // Confirmar eliminación
    // Confirm deletion
    echo json_encode([
        "success" => true,
        "message" => "Archivo eliminado correctamente", // File successfully deleted
        "file" => $fileName
    ]);

    exit;
}
