<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_login_json();
header('Content-Type: application/json');

$path = '/var/www/config/system_logging.json';
$default = [
    'journald' => [
        'system_max_use' => '100M',
        'system_keep_free' => '1G',
        'runtime_max_use' => '50M',
        'max_retention_sec' => '7day',
        'compress' => true,
    ],
    'system_logs' => [
        'enabled' => true,
        'rotation' => 'daily',
        'rotate' => 7,
        'maxsize' => '100M',
        'compress' => true,
        'delaycompress' => true,
    ],
    'nftables_logs' => [
        'enabled' => true,
        'size' => '50M',
        'rotate' => 7,
        'compress' => true,
        'delaycompress' => true,
    ],
];

if (!file_exists($path)) {
    echo json_encode(['config' => $default]);
    exit;
}

$content = file_get_contents($path);
$data = json_decode($content, true);
if (!is_array($data)) {
    http_response_code(500);
    echo json_encode(['error' => 'system_logging.json inválido']);
    exit;
}

echo json_encode(['config' => array_replace_recursive($default, $data)]);
