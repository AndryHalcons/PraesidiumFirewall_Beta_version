<?php

session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

$chain = $_POST['chain'] ?? null;
$handle = $_POST['handle'] ?? null;

$validChains = ['input','output', 'POSTROUTING', 'PREROUTING', 'FORWARDING'];

if (!in_array($chain, $validChains)) {
    exit("Cadena inválida");
}

if (!is_numeric($handle)) {
    exit("Handle inválido");
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

// 🧹 Eliminar la regla que coincida con el handle y la cadena
$originalCount = count($data['nftables']);
$data['nftables'] = array_filter($data['nftables'], function ($entry) use ($handle, $chain) {
    return !(isset($entry['rule']['handle']) && $entry['rule']['handle'] == $handle && $entry['rule']['chain'] === $chain);
});

$newCount = count($data['nftables']);

if ($originalCount === $newCount) {
    exit("No se encontró ninguna regla con handle $handle en la cadena $chain");
}

// 💾 Guardar el archivo actualizado
$newJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (file_put_contents($jsonPath, $newJson) === false) {
    exit("Error al guardar el archivo");
}

echo "Regla con handle $handle eliminada correctamente de la cadena $chain";
