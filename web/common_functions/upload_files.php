<?php
require_once __DIR__ . "/validation_uploads.php";

session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => "No autorizado"
    ]);
    exit;
}

header('Content-Type: application/json'); // Asegurar respuesta JSON
$alias = $_POST['alias'] ?? null;
$file = $_FILES['domain_file']['tmp_name'] ?? null;

if (!$alias || !$file || !is_uploaded_file($file)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Datos inválidos"
    ]);
    exit;
}

switch ($alias) {
    case 'url_list':
        parse_files_squid($file); // La función se encarga de devolver el JSON
        break;
    default:
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Alias no reconocido"
        ]);
        exit;
}


// Función para obtener la fecha y hora actual en un solo string
// Function to get current date and time in a single string
function getDateTimeSuffix(): string {
    // Obtener fecha y hora combinadas en formato seguro para nombres de archivo
    // Get combined date and time in a safe format for filenames
    return date('Y-m-d_H-i-s');
}

// Función para validar y guardar el archivo subido
// Function to validate and save the uploaded file
function parse_files_squid(string $tmpFile): void {
    $targetDir = "/var/www/config/squid_config/acl_domains/";

    // Validar que el archivo tenga extensión .txt
    // Validate that the file has .txt extension
    $originalName = basename($_FILES['domain_file']['name']);
    if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'txt') {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "El archivo debe tener extensión .txt" // File must have .txt extension
        ]);
        exit;
    }

    // Validar que el archivo no esté vacío
    // Validate that the file is not empty
    if (filesize($tmpFile) === 0) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "El archivo está vacío" // The file is empty
        ]);
        exit;
    }

    // Leer líneas y validar formato
    // Read lines and validate format
    $lines = file($tmpFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $i => $line) {
        $line = trim($line);

        // Validar que no tenga protocolo (http:// o https://)
        // Validate that it does not contain protocol (http:// or https://)
        if (preg_match('/^https?:\/\//i', $line)) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Línea " . ($i + 1) . ": no debe contener protocolo" // Line must not contain protocol
            ]);
            exit;
        }

        // Validar que no tenga rutas (por ejemplo /index.html)
        // Validate that it does not contain paths (e.g. /index.html)
        if (strpos($line, '/') !== false) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Línea " . ($i + 1) . ": no debe contener rutas" // Line must not contain paths
            ]);
            exit;
        }

        // Validar formato de dominio (con o sin subdominio)
        // Validate domain format (with or without subdomain)
        if (!preg_match('/^(\*\.|)[a-z0-9.-]+\.[a-z]{2,}$/i', $line)) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Línea " . ($i + 1) . ": formato de dominio inválido" // Invalid domain format
            ]);
            exit;
        }
    }

    // Generar sufijo de fecha y hora
    // Generate date and time suffix
    $suffix = getDateTimeSuffix();

    // Separar nombre base y extensión
    // Split base name and extension
    $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);

    // Combinar nombre base, sufijo y extensión
    // Combine base name, suffix and extension
    $filename = $nameWithoutExt . '_' . $suffix . '.' . $extension;

    // Ruta completa de destino
    // Full destination path
    $targetPath = $targetDir . $filename;

    // Crear el directorio si no existe
    // Create the directory if it doesn't exist
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Guardar el archivo en el destino final
    // Save the file to the final destination
    if (!move_uploaded_file($tmpFile, $targetPath)) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Error al guardar el archivo" // Error saving the file
        ]);
        exit;
    }

    // Devolver nombre final del archivo
    // Return final filename
    echo json_encode([
        "status" => "ok",
        "message" => "Archivo procesado correctamente", // File processed successfully
        "filename" => $filename
    ]);
    exit;
}
