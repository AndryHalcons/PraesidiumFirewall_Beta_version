<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');

// Verificar sesión activa  
// Check active session  
if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']); // Not authorized
    exit;
}

// Validar parámetro "table"  
// Validate "table" parameter  
$chain = trim($_POST['table'] ?? $_POST['chain'] ?? '');
$allowedChains = ['url_policies', 'url_list', 'url_listen_ports', 'url_profile', 'url_network_list'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']); // Invalid "table" parameter
    exit;
}

// Validar nombre de archivo  
// Validate filename  
$fileName = basename(trim($_POST['file'] ?? ''));
if ($fileName === '' || pathinfo($fileName, PATHINFO_EXTENSION) !== 'txt') {
    echo json_encode(['error' => 'Nombre de archivo inválido']); // Invalid filename
    exit;
}

// Validar contenido  
// Validate content  
$content = $_POST['content'] ?? '';
if (!is_string($content)) {
    echo json_encode(['error' => 'Contenido inválido']); // Invalid content
    exit;
}

// Ejecutar función correspondiente  
// Execute corresponding function  
switch ($chain) {
    case 'url_list': save_file_url_list($fileName, $content); break;
    case 'url_network_list':  save_file_url_network_list($fileName, $content); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']); // Unsupported chain
        break;
}


// Función para guardar archivos de dominios  
// Function to save domain list files  
function save_file_url_list(string $fileName, string $content): void {
    // Validar que cada línea sea un dominio limpio  
    // Validate that each line is a clean domain  
    $lines = explode("\n", $content);
    foreach ($lines as $line) {
        $domain = trim($line);
        if ($domain === '') continue; // Saltar líneas vacías / Skip empty lines

        // Validar formato de dominio (ej. google.com, sub.domain.net)  
        // Validate domain format (e.g. google.com, sub.domain.net)  
        if (!preg_match('/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/', $domain)) {
            echo json_encode(['error' => "Dominio inválido: $domain"]); // Invalid domain
            exit;
        }
    }

    // Ruta completa al archivo  
    // Full path to the file  
    $filePath = "/var/www/config/squid_config/squid_folder/conf.d/domain_list/$fileName";

    // Intentar escribir el contenido  
    // Try to write the content  
    if (@file_put_contents($filePath, $content) === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']); // Failed to save file
        exit;
    }

    // Éxito  
    // Success  
    echo json_encode(['message' => 'Archivo guardado correctamente']); // File saved successfully
}


function save_file_url_network_list(string $fileName, string $content): void {
    // Validar que cada línea sea una IP o red válida  
    // Validate that each line is a valid IP or network  
    $lines = explode("\n", $content);
    foreach ($lines as $line) {
        $ip = trim($line);
        if ($ip === '') continue; // Saltar líneas vacías / Skip empty lines

        // Validar IP sin CIDR  
        // Validate IP without CIDR  
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            continue;
        }

        // Validar IP con CIDR  
        // Validate IP with CIDR  
        if (preg_match('/^([0-9a-fA-F:.]+)\/(\d{1,3})$/', $ip, $matches)) {
            $base = $matches[1];
            $mask = (int)$matches[2];

            // IPv4 con máscara válida  
            // Valid IPv4 with mask  
            if (filter_var($base, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $mask >= 0 && $mask <= 32) {
                continue;
            }

            // IPv6 con máscara válida  
            // Valid IPv6 with mask  
            if (filter_var($base, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && $mask >= 0 && $mask <= 128) {
                continue;
            }
        }

        // Si no pasa ninguna validación  
        // If it fails all validations  
        echo json_encode(['error' => "IP o red inválida: $ip"]); // Invalid IP or network
        exit;
    }

    // Ruta completa al archivo  
    // Full path to the file  
    $filePath = "/var/www/config/squid_config/squid_folder/conf.d/ip_list/$fileName";

    // Intentar escribir el contenido  
    // Try to write the content  
    if (@file_put_contents($filePath, $content) === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']); // Failed to save file
        exit;
    }

    // Éxito  
    // Success  
    echo json_encode(['message' => 'Archivo guardado correctamente']); // File saved successfully
}
