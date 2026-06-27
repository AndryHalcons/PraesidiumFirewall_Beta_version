<?php
require_once __DIR__ . '/../../../common/security/session.php';
praesidium_session_start();
header('Content-Type: application/json');
if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}
$table = trim($_GET['table'] ?? '');
if ($table !== 'system_logging') {
    echo json_encode(['error' => 'Parámetro inválido']);
    exit;
}
$path = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_system_logging.json';
$json = json_decode(file_get_contents($path), true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($json['system_logging'])) {
    echo json_encode(['error' => 'Error al cargar estructura de system_logging']);
    exit;
}
echo json_encode(['system_logging' => $json['system_logging']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
