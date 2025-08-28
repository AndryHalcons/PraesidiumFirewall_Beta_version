<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

header('Content-Type: application/json');

// Obtener fecha y hora del sistema por separado
$fecha = date('Y-m-d');
$hora = date('H:i:s');

// Devolver solo date y time en el JSON
echo json_encode([
    "date" => $fecha,
    "time" => $hora
]);
