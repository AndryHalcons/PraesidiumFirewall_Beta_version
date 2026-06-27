<?php
require_once __DIR__ . '/../common/security/session.php';
praesidium_session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

header('Content-Type: application/json');

$info = [];
foreach (file('/proc/meminfo') as $line) {
    if (preg_match('/^([^:]+):\s+(\d+)/', $line, $matches)) {
        $info[$matches[1]] = (int)$matches[2];
    }
}

$total = (int)round(($info['MemTotal'] ?? 0) / 1024);
$available = (int)round(($info['MemAvailable'] ?? 0) / 1024);
$free = (int)round(($info['MemFree'] ?? 0) / 1024);
$cached = (int)round((($info['Cached'] ?? 0) + ($info['SReclaimable'] ?? 0)) / 1024);
$used = max(0, $total - $available);
$usedPercent = $total > 0 ? round(($used / $total) * 100, 2) : 0;

echo json_encode([
    'total' => $total,
    'used' => $used,
    'free' => $free,
    'cached' => $cached,
    'used_percent' => $usedPercent
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
