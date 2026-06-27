<?php
require_once __DIR__ . '/../../../common/security/auth.php';
require_login_json();

// Verificar si el usuario está autenticado


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
