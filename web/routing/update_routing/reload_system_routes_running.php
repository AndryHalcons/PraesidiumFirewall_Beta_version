<?php
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

header("Content-Type: application/json");

// Ruta absoluta al script Python
$script = escapeshellcmd("/var/www/backend/checks/check_routes/check_system_routes_running.py");

// Ejecutar con sudo (asumiendo que el visudo permite esto sin contraseña)
$output = shell_exec("sudo python3 $script 2>&1");

if ($output === null) {
    http_response_code(500);
    echo json_encode(["error" => "Error al ejecutar el script"]);
    exit;
}

echo json_encode([
    "status" => "ok",
    "output" => $output
]);
