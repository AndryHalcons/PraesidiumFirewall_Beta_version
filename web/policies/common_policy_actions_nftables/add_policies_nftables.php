<?php

session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

$chain = $_POST['chain'] ?? null;
$validChains = ['input', 'POSTROUTING', 'PREROUTING'];

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

// 🔍 Buscar el nombre de tabla según la cadena
$tableName = ($chain === 'input') ? 'filter' : 'nat';

// 🔢 Buscar el menor handle disponible en la cadena específica
$usedHandles = [];

foreach ($data['nftables'] as &$entry) {
    if (
        isset($entry['rule']['handle']) &&
        isset($entry['rule']['chain']) &&
        $entry['rule']['chain'] === $chain &&
        is_numeric($entry['rule']['handle'])
    ) {
        $usedHandles[] = (int)$entry['rule']['handle'];
    }

    // 🔁 Reordenar posiciones: incrementar en 1 si pertenece a la misma cadena
    if (
        isset($entry['rule']['chain']) &&
        $entry['rule']['chain'] === $chain &&
        isset($entry['rule']['position']) &&
        is_numeric($entry['rule']['position'])
    ) {
        $entry['rule']['position'] += 1;
    }
}

sort($usedHandles);
$newHandle = 1;
foreach ($usedHandles as $handle) {
    if ($handle === $newHandle) {
        $newHandle++;
    } else {
        break; // Encontramos un hueco
    }
}

// 🧱 Crear regla mínima válida (any any) con posición 1
$newRule = [
    "rule" => [
        "family" => "inet",
        "table" => $tableName,
        "chain" => $chain,
        "handle" => $newHandle,
        "position" => 1,
        "expr" => [
            [
                "match" => [
                    "op" => "==",
                    "left" => [
                        "meta" => [
                            "key" => "iifname"
                        ]
                    ],
                    "right" => "any"
                ]
            ],
            [
                "match" => [
                    "op" => "==",
                    "left" => [
                        "meta" => [
                            "key" => "oifname"
                        ]
                    ],
                    "right" => "any"
                ]
            ],
            [
                "counter" => [
                    "packets" => 0,
                    "bytes" => 0
                ]
            ]
        ]
    ]
];

// ➕ Añadir la nueva regla al principio del array
array_unshift($data['nftables'], $newRule);

// 💾 Guardar el archivo actualizado
$newJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (file_put_contents($jsonPath, $newJson) === false) {
    exit("Error al guardar la regla");
}

echo "Regla añadida correctamente con handle $newHandle y posición 1";
