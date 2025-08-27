<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}
// Ruta al archivo JSON generado por el script Python
$jsonPath = '/var/www/backend/checks/system_data/default_forms/forms_policies_bpfilter.json';

// Establece el tipo de contenido como JSON
header('Content-Type: application/json');

// Verifica si el archivo existe y es legible
if (file_exists($jsonPath) && is_readable($jsonPath)) {
    // Lee el contenido del archivo y lo imprime
    readfile($jsonPath);
} else {
    // Devuelve un error si el archivo no está disponible
    http_response_code(404);
    echo json_encode([
        "error" => "Error archive exist?",
        "path" => $jsonPath
    ]);
}
?>