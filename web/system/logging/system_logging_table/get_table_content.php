<?php
session_start();
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
$structurePath = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_system_logging.json';
$formsPath = '/var/www/backend/checks/system_data/default_forms/forms_system_logging.json';
$configPath = '/var/www/config/system_logging.json';
$structure = json_decode(file_get_contents($structurePath), true);
$forms = json_decode(file_get_contents($formsPath), true);
$config = json_decode(file_get_contents($configPath), true);
if (!isset($structure['system_logging'], $forms['system_logging']['_field_map']) || !is_array($config)) {
    echo json_encode(['error' => 'Configuración system_logging inválida']);
    exit;
}
$row = [];
foreach ($structure['system_logging'] as $column) {
    $map = $forms['system_logging']['_field_map'][$column] ?? null;
    if (!$map) {
        $row[$column] = '';
        continue;
    }
    $section = $map['section'];
    $key = $map['key'];
    $row[$column] = $config[$section][$key] ?? '';
}
echo json_encode(['system_logging' => [$row]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
