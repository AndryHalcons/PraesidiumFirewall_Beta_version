<?php
session_start();
header('Content-Type: application/json');

// Verificar sesión activa  
// Check active session  
if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']); // Not authorized
    exit;
}

// Validar parámetro "table"  
// Validate "table" parameter  
$chain = trim($_GET['table'] ?? $_GET['chain'] ?? '');
$allowedChains = ['url_policies', 'url_list', 'url_listen_ports', 'url_profile', 'url_network_list'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']); // Invalid "table" parameter
    exit;
}

// Validar nombre de archivo  
// Validate filename  
$fileName = basename(trim($_GET['file'] ?? ''));
if ($fileName === '' || pathinfo($fileName, PATHINFO_EXTENSION) !== 'txt') {
    echo json_encode(['error' => 'Nombre de archivo inválido']); // Invalid filename
    exit;
}

// Ejecutar función correspondiente  
// Execute corresponding function  
switch ($chain) {
    case 'url_list': get_file_url_list($fileName); break;
    case 'url_network_list': get_file_url_network_list($fileName); break;
    // Aquí puedes añadir más casos como get_file_url_profile(), etc.  
    // You can add more cases like get_file_url_profile(), etc.
    default:
        echo json_encode(['error' => 'Cadena no soportada']); // Unsupported chain
        break;
}


// Función para leer archivos de dominios  
// Function to read domain list files  
function get_file_url_list(string $fileName): void {
    $filePath = "/var/www/config/squid_config/squid_folder/conf.d/domain_list/$fileName";

    // Verificar existencia y lectura  
    // Check existence and readability  
    if (!file_exists($filePath) || !is_readable($filePath)) {
        echo json_encode(['error' => 'Archivo no encontrado o no accesible']); // File not found or unreadable
        exit;
    }

    // Leer contenido  
    // Read content  
    $content = file_get_contents($filePath);
    echo json_encode(['content' => $content], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


// Función para leer archivos de IP/red  
// Function to read IP/network list files  
function get_file_url_network_list(string $fileName): void {
    $filePath = "/var/www/config/squid_config/squid_folder/conf.d/ip_list/$fileName";

    // Verificar existencia y lectura  
    // Check existence and readability  
    if (!file_exists($filePath) || !is_readable($filePath)) {
        echo json_encode(['error' => 'Archivo no encontrado o no accesible']); // File not found or unreadable
        exit;
    }

    // Leer contenido  
    // Read content  
    $content = file_get_contents($filePath);
    echo json_encode(['content' => $content], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
