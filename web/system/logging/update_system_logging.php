<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/file/json_store.php';

require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Entrada JSON inválida']);
    exit;
}

$config = $input['config'] ?? null;
if (!is_array($config)) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta config']);
    exit;
}

$allowedSizes = ['10M', '25M', '50M', '100M', '250M', '500M', '1G', '2G'];
$allowedRetention = ['1day', '3day', '7day', '14day', '30day'];
$allowedRotation = ['daily', 'weekly'];

function require_allowed_value(array $source, string $key, array $allowed, string $section) {
    $value = $source[$key] ?? null;
    if (!in_array($value, $allowed, true)) {
        throw new InvalidArgumentException("Valor inválido en {$section}.{$key}");
    }
    return $value;
}

function require_bool_value(array $source, string $key, string $section): bool {
    if (!array_key_exists($key, $source) || !is_bool($source[$key])) {
        throw new InvalidArgumentException("Booleano inválido en {$section}.{$key}");
    }
    return $source[$key];
}

function require_rotate_value(array $source, string $key, string $section): int {
    $value = $source[$key] ?? null;
    if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
        throw new InvalidArgumentException("Rotación inválida en {$section}.{$key}");
    }
    $value = (int)$value;
    if ($value < 1 || $value > 30) {
        throw new InvalidArgumentException("Rotación fuera de rango en {$section}.{$key}");
    }
    return $value;
}

try {
    $journald = $config['journald'] ?? [];
    $systemLogs = $config['system_logs'] ?? [];
    $nftablesLogs = $config['nftables_logs'] ?? [];
    $normalized = [
        'journald' => [
            'system_max_use' => require_allowed_value($journald, 'system_max_use', $allowedSizes, 'journald'),
            'system_keep_free' => require_allowed_value($journald, 'system_keep_free', $allowedSizes, 'journald'),
            'runtime_max_use' => require_allowed_value($journald, 'runtime_max_use', $allowedSizes, 'journald'),
            'max_retention_sec' => require_allowed_value($journald, 'max_retention_sec', $allowedRetention, 'journald'),
            'compress' => require_bool_value($journald, 'compress', 'journald'),
        ],
        'system_logs' => [
            'enabled' => require_bool_value($systemLogs, 'enabled', 'system_logs'),
            'rotation' => require_allowed_value($systemLogs, 'rotation', $allowedRotation, 'system_logs'),
            'rotate' => require_rotate_value($systemLogs, 'rotate', 'system_logs'),
            'maxsize' => require_allowed_value($systemLogs, 'maxsize', $allowedSizes, 'system_logs'),
            'compress' => require_bool_value($systemLogs, 'compress', 'system_logs'),
            'delaycompress' => require_bool_value($systemLogs, 'delaycompress', 'system_logs'),
        ],
        'nftables_logs' => [
            'enabled' => require_bool_value($nftablesLogs, 'enabled', 'nftables_logs'),
            'size' => require_allowed_value($nftablesLogs, 'size', $allowedSizes, 'nftables_logs'),
            'rotate' => require_rotate_value($nftablesLogs, 'rotate', 'nftables_logs'),
            'compress' => require_bool_value($nftablesLogs, 'compress', 'nftables_logs'),
            'delaycompress' => require_bool_value($nftablesLogs, 'delaycompress', 'nftables_logs'),
        ],
    ];
    json_store_write('/var/www/config/system_logging.json', $normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    chmod('/var/www/config/system_logging.json', 0664);
    echo json_encode(['status' => 'ok', 'config' => $normalized]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
