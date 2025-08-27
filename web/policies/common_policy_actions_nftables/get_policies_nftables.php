<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

$chain = $_POST['chain'] ?? null;
$validChains = ['input', 'POSTROUTING', 'PREROUTING', 'FORWARDING'];

if (!in_array($chain, $validChains)) {
    exit("no rules");
}

$jsonPath = '/var/www/config/rules_nftables.json';
if (!file_exists($jsonPath)) {
    exit("no rules");
}

$jsonData = file_get_contents($jsonPath);
$data = json_decode($jsonData, true);

if (!isset($data['nftables']) || !is_array($data['nftables'])) {
    exit("no rules");
}

$rules = [];
foreach ($data['nftables'] as $entry) {
    if (isset($entry['rule']) && isset($entry['rule']['chain']) && $entry['rule']['chain'] === $chain) {
        $rules[] = $entry['rule'];
    }
}

if (empty($rules)) {
    exit("no rules");
}

header('Content-Type: application/json');
echo json_encode($rules, JSON_PRETTY_PRINT);
