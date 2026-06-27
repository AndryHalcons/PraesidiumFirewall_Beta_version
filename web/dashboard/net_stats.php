<?php
require_once __DIR__ . '/../common/security/auth.php';
require_login_json();


header('Content-Type: application/json');

$interfaces = [];
$lines = file('/proc/net/dev');
foreach ($lines as $line) {
    if (strpos($line, ':') === false) {
        continue;
    }
    [$name, $data] = array_map('trim', explode(':', $line, 2));
    if ($name === 'lo') {
        continue;
    }
    $parts = preg_split('/\s+/', $data);
    if (count($parts) < 16) {
        continue;
    }
    $interfaces[] = [
        'name' => $name,
        'rx_bytes' => (int)$parts[0],
        'tx_bytes' => (int)$parts[8]
    ];
}

echo json_encode(['timestamp' => microtime(true), 'interfaces' => $interfaces], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
