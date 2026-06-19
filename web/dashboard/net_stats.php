<?php
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

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
