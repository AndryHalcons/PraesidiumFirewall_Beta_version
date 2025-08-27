<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

$chain = $_POST['chain'] ?? null;
$validChains = ['input', 'POSTROUTING', 'PREROUTING', 'FORWARDING'];

if (!$chain || !in_array($chain, $validChains)) {
    http_response_code(400);
    echo json_encode(["error" => "Cadena inválida o faltante"]);
    exit;
}

$jsonPath = "/var/www/config/rules_nftables.json";
if (!file_exists($jsonPath)) {
    http_response_code(500);
    echo json_encode(["error" => "Archivo de reglas no encontrado"]);
    exit;
}

$jsonData = json_decode(file_get_contents($jsonPath), true);
if (!isset($jsonData["nftables"]) || !is_array($jsonData["nftables"])) {
    http_response_code(500);
    echo json_encode(["error" => "Formato JSON inválido"]);
    exit;
}

// 🔁 Reasignar posiciones secuenciales a TODAS las reglas de la cadena
$position = 1;
foreach ($jsonData["nftables"] as &$entry) {
    if (
        isset($entry["rule"]) &&
        isset($entry["rule"]["chain"]) &&
        $entry["rule"]["chain"] === $chain
    ) {
        $entry["rule"]["position"] = $position++;
    }
}

// 💾 Guardar el JSON actualizado
if (file_put_contents($jsonPath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    http_response_code(500);
    echo json_encode(["error" => "Error al guardar el archivo"]);
    exit;
}

// ✅ Respuesta
echo json_encode([
    "status" => "ok",
    "chain" => $chain,
    "total_rules" => $position - 1,
    "message" => "Todas las posiciones actualizadas correctamente"
]);
