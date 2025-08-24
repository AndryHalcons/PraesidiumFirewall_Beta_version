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

    // Ruta al script Python
    $pythonScript = '/var/www/backend/commits/commit_apply.py';

    // Ejecutar el script y pasarle el JSON como argumento
    // IMPORTANTE: Escapar el JSON para evitar problemas con comillas
    $escapedJson = escapeshellarg($json);
    $command = "sudo /usr/bin/python3 $pythonScript $escapedJson";

    // Ejecutar el comando
    $output = shell_exec($command);

    return $output;
}

// Establecer cabecera JSON
header('Content-Type: application/json');

// Ejecutar el commit y mostrar la respuesta del script Python
echo starting_commit();
