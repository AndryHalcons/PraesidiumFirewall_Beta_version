<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

// Función para obtener el JSON del commit
function getCommitJson() {
    ob_start();
    include __DIR__ . '/../commit_common_actions/get_user.php';
    return ob_get_clean();
}

// Función para iniciar el commit llamando al script Python
function starting_commit() {
    $json = getCommitJson();

    // Ejecutar primero el script PHP de conversión de política a lenguaje backend
    //require_once '/var/www/html/commits/check_commit/commit_common_actions/nft/convert_update_policy_to_backend.php';
    //convert_update_policy_to_backend();

    // Ruta al script Python
    $pythonScript = '/var/www/backend/commits/commit_apply.py';
    $escapedJson = escapeshellarg($json);
    $command = "sudo /usr/bin/python3 $pythonScript $escapedJson";

    // Ejecutar el comando
    $output = shell_exec($command);

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
