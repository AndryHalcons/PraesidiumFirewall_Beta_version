<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

$chain = $_POST['chain'] ?? null;
$validChains = ['input', 'POSTROUTING', 'PREROUTING', 'FORWARDING'];

if (!in_array($chain, $validChains)) {
    exit("Cadena inválida");
}

$jsonPath = '/var/www/config/rules_nftables.json';

if (!file_exists($jsonPath)) {
    exit("Archivo de reglas no encontrado");
}

$jsonContent = file_get_contents($jsonPath);
$data = json_decode($jsonContent, true);

if (!is_array($data) || !isset($data['nftables'])) {
    exit("Formato JSON inválido");
}

// Separar reglas de la cadena objetivo
$otrasEntradas = [];
$reglasObjetivo = [];

foreach ($data['nftables'] as $entrada) {
    if (isset($entrada['rule']) && isset($entrada['rule']['chain']) && $entrada['rule']['chain'] === $chain) {
        $reglasObjetivo[] = $entrada;
    } else {
        $otrasEntradas[] = $entrada;
    }
}

// Ordenar reglas por campo 'position'
usort($reglasObjetivo, function ($a, $b) {
    $posA = $a['rule']['position'] ?? PHP_INT_MAX;
    $posB = $b['rule']['position'] ?? PHP_INT_MAX;
    return $posA <=> $posB;
});

// Reconstruir el array final
$data['nftables'] = array_merge($otrasEntradas, $reglasObjetivo);

// Guardar el JSON actualizado
$newJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (file_put_contents($jsonPath, $newJson) === false) {
    exit("Error al guardar el archivo reordenado");
}

echo "Reglas de la cadena '$chain' reordenadas correctamente por posición.";
