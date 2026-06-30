<?php
require_once __DIR__ . '/../common/security/auth.php';
require_login_json();


header('Content-Type: application/json');

function leerCpuStats() {
    $lines = file('/proc/stat');
    $cores = [];

    foreach ($lines as $line) {
        if (preg_match('/^cpu[0-9]+/', $line)) {
            $parts = preg_split('/\s+/', trim($line));
            array_shift($parts);
            $idle = (int)$parts[3] + (int)$parts[4];
            $total = array_sum(array_map('intval', $parts));
            $cores[] = ['idle' => $idle, 'total' => $total];
        }
    }

    return $cores;
}

$start = leerCpuStats();
usleep(300000);
$end = leerCpuStats();
$usages = [];

foreach ($start as $i => $core) {
    if (!isset($end[$i])) {
        continue;
    }
    $idleDiff = $end[$i]['idle'] - $core['idle'];
    $totalDiff = $end[$i]['total'] - $core['total'];
    $usage = $totalDiff > 0 ? round(100 * (1 - $idleDiff / $totalDiff), 2) : 0;
    $usages[] = max(0, min(100, $usage));
}

$average = count($usages) ? round(array_sum($usages) / count($usages), 2) : 0;
echo json_encode(['cores' => $usages, 'average' => $average, 'core_count' => count($usages)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
