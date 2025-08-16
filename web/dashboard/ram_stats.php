<?php
session_start();

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

header('Content-Type: application/json');

// Ejecutar el comando 'free -m' para obtener datos de RAM en megabytes
$output = shell_exec('free -m');

if (!$output) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudo obtener información de RAM"]);
    exit;
}

$lines = explode("\n", trim($output));
$memLine = preg_split('/\s+/', $lines[1]);

$total = (int)$memLine[1];
$used = (int)$memLine[2];
$free = (int)$memLine[3];
$cached = isset($memLine[5]) ? (int)$memLine[5] : 0;

echo json_encode([
    "total" => $total,
    "used" => $used,
    "free" => $free,
    "cached" => $cached
]);
