<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

// 🧩 Paso 1: recibir y decodificar el JSON
$input = json_decode(file_get_contents("php://input"), true);
$hook = $input["hook"] ?? null;
$updatedRule = $input["rule"] ?? null;

if (!$hook || !$updatedRule || !isset($updatedRule["id"])) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan parámetros"]);
    exit;
}

// 🧩 Paso 2: cargar el archivo JSON
$jsonPath = "/var/www/config/rules.json";
if (!file_exists($jsonPath)) {
    http_response_code(500);
    echo json_encode(["error" => "Archivo de reglas no encontrado"]);
    exit;
}

$data = json_decode(file_get_contents($jsonPath), true);
if (!isset($data[$hook]["rules"])) {
    http_response_code(404);
    echo json_encode(["error" => "Hook no encontrado"]);
    exit;
}

// 🧩 Paso 3: buscar y reemplazar la regla por ID
$found = false;
foreach ($data[$hook]["rules"] as &$rule) {
    if (isset($rule["id"]) && $rule["id"] == $updatedRule["id"]) {
        $rule = $updatedRule;
        $found = true;
        break;
    }
}

if (!$found) {
    http_response_code(404);
    echo json_encode(["error" => "Regla con ID {$updatedRule['id']} no encontrada"]);
    exit;
}

// 🧩 Paso 4: guardar el archivo actualizado
file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// 🧩 Paso 5: responder
echo json_encode([
    "status" => "OK",
    "message" => "Regla actualizada correctamente",
    "hook" => $hook,
    "id" => $updatedRule["id"]
]);
