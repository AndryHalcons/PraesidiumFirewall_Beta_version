<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['username'])) {
    http_response_code(401); // Código de estado HTTP para "No autorizado"
    exit(json_encode(["error" => "No autorizado"]));
}

// Generar fecha en formato YYYYMMDDHHMMSS
$dateStr = date('YmdHis');

// Construir el objeto commit
$commit = [
    'commit' => [
        'date' => $dateStr,
        'user' => $_SESSION['username']
    ]
];

// Establecer cabecera de tipo JSON
header('Content-Type: application/json');

// Devolver el JSON
echo json_encode($commit);
