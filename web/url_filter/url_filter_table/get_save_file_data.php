<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']); // Not authorized
    exit;
}

// Validar parámetro "table"
// Validate "table" parameter
$chain = trim($_POST['table'] ?? $_POST['chain'] ?? '');
$allowedChains = ['url_policies', 'url_list', 'url_listen_ports', 'url_profile'];

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
$filePath = "/var/www/config/squid_config/$fileName";

// Intentar escribir el contenido
// Try to write the content
if (@file_put_contents($filePath, $content) === false) {
    echo json_encode(['error' => 'No se pudo guardar el archivo']); // Failed to save file
    exit;
}

// Éxito
// Success
echo json_encode(['message' => 'Archivo guardado correctamente']); // File saved successfully
