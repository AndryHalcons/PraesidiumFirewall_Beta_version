<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

$validHooks = ['BF_HOOK_TC_INGRESS', 'BF_HOOK_TC_EGRESS', 'BF_HOOK_XDP'];
$hook = $_POST['hook'] ?? '';

if (!in_array($hook, $validHooks)) {
    http_response_code(400);
    exit("Hook inválido.");
}

$file = "/var/www/config/rules.json";
if (!file_exists($file)) {
    http_response_code(500);
    exit("Archivo de reglas no encontrado.");
}

$json = file_get_contents($file);
$data = json_decode($json, true);

if (!isset($data[$hook]['rules']) || !is_array($data[$hook]['rules'])) {
    http_response_code(404);
    exit("Bloque '$hook' no encontrado o mal formado.");
}

// 🧠 Ordenar por 'position'
usort($data[$hook]['rules'], function ($a, $b) {
    return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
});

if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    http_response_code(500);
    exit("Error al guardar el archivo.");
}

echo "OK";
