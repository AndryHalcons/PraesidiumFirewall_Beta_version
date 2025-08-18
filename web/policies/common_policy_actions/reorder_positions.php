<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

// 🧩 Paso 0: recibir el hook
$hook = $_POST['hook'] ?? null;

if (!$hook) {
    http_response_code(400);
    echo json_encode(["error" => "Missing hook parameter"]);
    exit;
}

// 🧩 Paso 1: cargar el JSON desde la ruta real
$jsonPath = "/var/www/config/rules.json";
if (!file_exists($jsonPath)) {
    http_response_code(500);
    echo json_encode(["error" => "Rules file not found"]);
    exit;
}

$jsonData = json_decode(file_get_contents($jsonPath), true);
if (!isset($jsonData[$hook])) {
    http_response_code(404);
    echo json_encode(["error" => "Hook not found"]);
    exit;
}

// 🧩 Paso 2: reindexar las posiciones
$rules = $jsonData[$hook]["rules"] ?? [];

foreach ($rules as $index => &$rule) {
    $rule["position"] = $index + 1;
}

// 🧩 Paso 3: guardar el JSON actualizado
$jsonData[$hook]["rules"] = $rules;
file_put_contents($jsonPath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// 🧩 Paso 4: responder
echo json_encode([
    "status" => "ok",
    "hook" => $hook,
    "total_rules" => count($rules),
    "message" => "Positions reordered successfully"
]);
