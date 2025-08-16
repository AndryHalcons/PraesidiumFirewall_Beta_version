<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

header('Content-Type: application/json');

function leerCpuStats() {
    $lines = file('/proc/stat');
    $cores = [];

    foreach ($lines as $line) {
        if (preg_match('/^cpu[0-9]+/', $line)) {
            $parts = preg_split('/\s+/', trim($line));
            array_shift($parts); // quitar "cpuN"

            // Campos: user, nice, system, idle, iowait, irq, softirq, steal, guest, guest_nice
            $idle = $parts[3] + $parts[4]; // idle + iowait
            $total = array_sum($parts);

            $cores[] = ['idle' => $idle, 'total' => $total];
        }
    }

    return $cores;
}

// Leer dos veces para calcular diferencia
$start = leerCpuStats();
usleep(500000); // 0.5 segundos
$end = leerCpuStats();

$usages = [];

foreach ($start as $i => $core) {
    $idleDiff = $end[$i]['idle'] - $core['idle'];
    $totalDiff = $end[$i]['total'] - $core['total'];
    $usage = $totalDiff > 0 ? round(100 * (1 - $idleDiff / $totalDiff), 2) : 0;
    $usages[] = $usage;
}

echo json_encode(['cores' => $usages]);
