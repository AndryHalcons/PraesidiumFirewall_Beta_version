<?php
require_once __DIR__ . '/../../common/security/session.php';
praesidium_session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$table = trim($_GET['table'] ?? '');
$allowedTables = ['certificates'];

if ($table === '' || !in_array($table, $allowedTables, true)) {
    echo json_encode(['error' => 'parametro incorrecto']);
    exit;
}

// Dispatcher: solo ejecuta la función
switch ($table) {
    case 'certificates': get_certificates(); break;
    default:
        echo json_encode(['error' => 'Tabla no soportada']);
        break;
}

// Funciones autónomas por tabla

function get_certificates() {
    $path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_certificates.json';
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['certificates'])) {
        echo json_encode(['error' => 'Error al cargar o interpretar la estructura de certificates']);
        return;
    }

    echo json_encode(['certificates' => $json['certificates']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}