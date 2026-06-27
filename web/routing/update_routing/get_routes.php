<?php
require_once __DIR__ . '/../../common/security/auth.php';
require_login_json();


$routesFile = "/var/www/config/routes.json";

if (!file_exists($routesFile)) {
    http_response_code(404);
    echo json_encode(["error" => "Archivo de rutas no encontrado"]);
    exit;
}

header("Content-Type: application/json");
echo file_get_contents($routesFile);
