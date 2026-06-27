<?php
require_once __DIR__ . '/../../common/security/session.php';
praesidium_session_start();
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

$chain = $_GET['table'] ?? $_GET['chain'] ?? '';
$chain = is_string($chain) ? trim($chain) : '';

if ($chain === '') {
    echo json_encode(['error' => 'Parámetro requerido: "table" o "chain"']);
    exit;
}

$allowedChains = ['FORWARDING', 'PREROUTING', 'POSTROUTING', 'input', 'output'];
if (!in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'get_table_content: Parámetro inválido']);
    exit;
}

$structurePath = '/var/www/backend/checks/system_data/default_tables_structure/structure_tables_policies.json';
if (!file_exists($structurePath)) {
    echo json_encode(['error' => 'Archivo de estructura no encontrado']);
    exit;
}

$structureRaw = file_get_contents($structurePath);
$structureData = json_decode($structureRaw, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($structureData[$chain])) {
    echo json_encode(['error' => 'Estructura inválida o no definida para la cadena']);
    exit;
}

$columns = $structureData[$chain];

$jsonPath = '/var/www/config/rules_nftables_human_viewer.json';
if (!file_exists($jsonPath)) {
    echo json_encode(['error' => 'Archivo de datos no encontrado']);
    exit;
}

$raw = file_get_contents($jsonPath);
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['nftables']) || !is_array($data['nftables'])) {
    echo json_encode(['error' => 'Formato de datos no válido']);
    exit;
}

/**
 * Devuelve solo los campos de la regla que están en $columns
 */
function satinize_rule(array $rule, array $columns): array {
    $flat = [];
    foreach ($columns as $col) {
        $flat[$col] = $rule[$col] ?? "";
    }
    return $flat;
}

$sanitized = [];
foreach ($data['nftables'] as $item) {
    if (isset($item['rule']) && $item['rule']['chain'] === $chain) {
        $flat = satinize_rule($item['rule'], $columns);
        $sanitized[] = $flat;
    }
}

echo json_encode([$chain => $sanitized], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
