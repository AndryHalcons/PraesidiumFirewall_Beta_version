<?php
require_once __DIR__ . '/../../../common/security/auth.php';
require_login_json();
header('Content-Type: application/json');

$table = trim($_GET['table'] ?? '');
if ($table !== 'system_logging') {
    echo json_encode(['error' => 'Parámetro inválido']);
    exit;
}
$path = '/var/www/backend/checks/system_data/default_forms/forms_system_logging.json';
$json = json_decode(file_get_contents($path), true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($json['system_logging'])) {
    echo json_encode(['error' => 'Error al cargar formulario de system_logging']);
    exit;
}
echo json_encode($json['system_logging'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
