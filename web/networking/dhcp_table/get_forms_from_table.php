<?php
require_once __DIR__ . '/../../common/security/session.php';
praesidium_session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$table = trim($_GET['table'] ?? $_GET['chain'] ?? '');
if ($table !== 'dhcp') {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

$path = '/var/www/backend/checks/system_data/default_forms/forms_dhcp.json';
$json = json_decode((string)@file_get_contents($path), true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($json['dhcp']) || !is_array($json['dhcp'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al interpretar los datos de DHCP']);
    exit;
}

$interfaces = [];
$ifacePath = '/var/www/backend/checks/system_data/data_interfaces/all_interfaces_list.json';
if (file_exists($ifacePath)) {
    $ifaceData = json_decode((string)file_get_contents($ifacePath), true);
    if (json_last_error() === JSON_ERROR_NONE && isset($ifaceData['all_interfaces']) && is_array($ifaceData['all_interfaces'])) {
        $interfaces = array_merge($interfaces, array_map('strval', $ifaceData['all_interfaces']));
    }
}

// Fallback vivo: DHCP debe adaptarse al hardware/interfaz actual aunque aún no se haya regenerado data_interfaces.
// Live fallback: DHCP must adapt to current hardware/interfaces even if data_interfaces has not been regenerated yet.
foreach (glob('/sys/class/net/*') ?: [] as $ifaceDir) {
    $name = basename($ifaceDir);
    if ($name !== 'lo' && is_dir($ifaceDir) && file_exists($ifaceDir . '/ifindex')) {
        $interfaces[] = $name;
    }
}
$interfaces = array_values(array_unique(array_filter($interfaces, fn($v) => $v !== '')));
sort($interfaces, SORT_NATURAL);
$json['dhcp']['select']['interface'] = array_values(array_unique(array_merge($json['dhcp']['select']['interface'] ?? [''], $interfaces)));

echo json_encode($json['dhcp'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
