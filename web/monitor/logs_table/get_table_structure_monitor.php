<?php
require_once __DIR__ . '/../../common/security/auth.php';
require_login_json();
// Verificar si el usuario está autenticado

header('Content-Type: application/json');
// Ruta al archivo JSON
$jsonPath = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_monitor.json';

// Verificar si el archivo existe
if (!file_exists($jsonPath)) {
    echo json_encode(['error' => 'Archivo JSON no encontrado']);
    exit;
}

// Leer el contenido del archivo
$jsonContent = file_get_contents($jsonPath);

// Verificar si se pudo leer correctamente
if ($jsonContent === false) {
    echo json_encode(['error' => 'Error al leer el archivo JSON']);
    exit;
}

// Decodificar el JSON para validar su estructura
$data = json_decode($jsonContent, true);

// Verificar si el JSON es válido
if ($data === null) {
    echo json_encode(['error' => 'JSON mal formado']);
    exit;
}

// Devolver el contenido al frontend
echo json_encode($data);
