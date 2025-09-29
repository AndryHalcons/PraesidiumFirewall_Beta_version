<?php
session_start();
header('Content-Type: application/json');

// Verificar sesión activa
// Check active session
if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Validar parámetro "table"
// Validate "table" parameter
$chain = trim($_GET['table'] ?? $_GET['chain'] ?? '');
$allowedChains = ['url_policies', 'url_list', 'url_listen_ports', 'url_profile'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

// Validar nombre de archivo
// Validate filename
$fileName = basename(trim($_GET['file'] ?? ''));
if ($fileName === '' || pathinfo($fileName, PATHINFO_EXTENSION) !== 'txt') {
    echo json_encode(['error' => 'Nombre de archivo inválido']);
    exit;
}

// Ruta completa al archivo
// Full path to the file
$filePath = "/var/www/config/squid_config/squid_folder/conf.d/domain_list/$fileName";

// Verificar existencia y lectura
// Check existence and readability
if (!file_exists($filePath) || !is_readable($filePath)) {
    echo json_encode(['error' => 'Archivo no encontrado o no accesible']);
    exit;
}

// Leer contenido
// Read content
$content = file_get_contents($filePath);
echo json_encode(['content' => $content], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
