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

$fileData = $_FILES['domain_file'] ?? null;

if (!$alias || !$fileData || $fileData['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Archivo no recibido correctamente"
    ]);
    exit;
}

switch ($alias) {
    case 'url_list': upload_files_squid($fileData); break;
    case 'url_network_list': upload_files_squid_url_network_list($fileData); break;
    case 'certificates': upload_certificates($fileData); break;
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


function upload_files_squid_url_network_list(array $fileData): void {
    $targetDir = "/var/www/config/squid_config/squid_folder/conf.d/ip_list/";

    // Extraer nombre y ruta temporal del archivo
    // Extract original name and temporary path
    $tmpFile = $fileData['tmp_name'];
    $originalName = basename($fileData['name']);

    // Validar que el archivo tenga extensión .txt
    // Validate that the file has .txt extension
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

        // Validar formato de IP o red CIDR
        // Validate IP or CIDR network format
        if (
            !filter_var($line, FILTER_VALIDATE_IP) &&
            !preg_match('/^([0-9a-fA-F:.]+)\/(\d{1,3})$/', $line, $matches)
        ) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Línea " . ($i + 1) . ": formato de IP o red inválido" // Invalid IP or network format
            ]);
            exit;
        } else {
            // Si es CIDR, validar máscara
            // If CIDR, validate mask
            if (!empty($matches)) {
                $base = $matches[1];
                $mask = (int)$matches[2];

                if (
                    (filter_var($base, FILTER_FLAG_IPV4 | FILTER_VALIDATE_IP) && ($mask < 0 || $mask > 32)) ||
                    (filter_var($base, FILTER_FLAG_IPV6 | FILTER_VALIDATE_IP) && ($mask < 0 || $mask > 128))
                ) {
                    http_response_code(400);
                    echo json_encode([
                        "status" => "error",
                        "message" => "Línea " . ($i + 1) . ": máscara inválida en red CIDR" // Invalid CIDR mask
                    ]);
                    exit;
                }
            }
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



// Función para validar y guardar el archivo subido
// Function to validate and save the uploaded file
function upload_files_squid(array $fileData): void {
    $targetDir = "/var/www/config/squid_config/squid_folder/conf.d/domain_list/";

    // Extraer nombre y ruta temporal del archivo
    // Extract original name and temporary path
    $tmpFile = $fileData['tmp_name'];
    $originalName = basename($fileData['name']);

    // Validar que el archivo tenga extensión .txt
    // Validate that the file has .txt extension
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
    $suffix = date('Y-m-d_H-i-s');

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



function upload_certificates(array $fileData): void {
    $targetDir = "/var/www/config/certs/";

    // Extraer nombre y ruta temporal del archivo
    // Extract original name and temporary path
    $tmpFile = $fileData['tmp_name'];
    $originalName = basename($fileData['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    // Lista de extensiones válidas para certificados
    // List of valid certificate extensions
    $validExtensions = ['pem', 'key', 'crt', 'csr', 'srl', 'p12', 'pfx', 'der', 'cer', 'pkcs12'];

    // Validar extensión
    // Validate extension
    if (!in_array($extension, $validExtensions)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Extensión de archivo no válida para certificados" // Invalid certificate file extension
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

    // Generar sufijo de fecha y hora
    // Generate date and time suffix
    $suffix = getDateTimeSuffix();

    // Separar nombre base y extensión
    // Split base name and extension
    $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);

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
        "message" => "Certificado subido correctamente", // Certificate uploaded successfully
        "filename" => $filename
    ]);
    exit;
}

