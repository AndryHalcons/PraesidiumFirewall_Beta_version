<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_admin_json();
csrf_validate_or_exit();

// Verificar si el usuario está autenticado


// Función para obtener el JSON del commit
function getCommitJson() {


    $dateStr = date('YmdHis');
    $commit = [
        'commit' => [
            'date' => $dateStr,
            'user' => $_SESSION['username']
        ]
    ];

    return json_encode($commit);
}


// Función para iniciar el commit llamando al script Python
function starting_commit() {

    $json = getCommitJson();

    // Ruta al script Python
    $pythonScript = '/var/www/backend/commits/commit_apply.py';
    $escapedJson = escapeshellarg($json);
    $command = "sudo /usr/bin/python3 $pythonScript $escapedJson";

    // Ejecutar el comando
    $output = shell_exec($command);
    error_log("DEBUG commitRaw: " . $output);
    return $output;
}

// Función para buscar el commit en commit_history.json
function getCommitDetailsByDate($dateKey) {
    $filePath = '/var/www/config/commit_history/commit_history.json';

    if (!file_exists($filePath)) {
        return ["error" => "Archivo commit_history.json no encontrado"];
    }

    $jsonContent = file_get_contents($filePath);
    $data = json_decode($jsonContent, true);

    if (!isset($data['commits'][$dateKey])) {
        return ["error" => "No se encontró el commit con fecha: $dateKey"];
    }

    return $data['commits'][$dateKey];
}

// Establecer cabecera JSON
header('Content-Type: application/json');

// Ejecutar el commit
$commitRaw = starting_commit();
$commitData = json_decode($commitRaw, true);

// Preparar respuesta con ambos JSON
$response = [
    "commit_result" => $commitData,
    "commit_details" => []
];

if (isset($commitData['date'])) {
    $response["commit_details"] = getCommitDetailsByDate($commitData['date']);
}

echo json_encode($response);
