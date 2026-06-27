<?php
require_once __DIR__ . '/../../common/security/session.php';
praesidium_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_admin_json();
csrf_validate_or_exit();
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
