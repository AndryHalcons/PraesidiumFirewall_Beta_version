<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

// Cargar archivo de idioma según la sesión
$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../../lang/es.php";
}
$L = require $langFile;

// Recibir datos JSON
$input = json_decode(file_get_contents("php://input"), true);
$interfaceName = $input['interface'] ?? '';

// Validar nombre de interfaz lógica: debe empezar por br o bond
if (!preg_match('/^(br|bond)/', $interfaceName)) {
    echo json_encode([
        "success" => false,
        "error" => $L["invalid_interface_name"]
    ]);
    exit;
}

// Ruta del archivo de configuración
$configPath = "/var/www/config/interfaces.json";

// Verificar existencia del archivo
if (!file_exists($configPath)) {
    echo json_encode([
        "success" => false,
        "error" => $L["connection_error"]
    ]);
    exit;
}

// Leer y decodificar el archivo
$jsonData = json_decode(file_get_contents($configPath), true);

// Verificar estructura
if (!isset($jsonData["interfaces"]) || !is_array($jsonData["interfaces"])) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid configuration format."
    ]);
    exit;
}

// Filtrar interfaces, eliminando la que coincide y reindexar
$originalCount = count($jsonData["interfaces"]);
$jsonData["interfaces"] = array_values(array_filter($jsonData["interfaces"], function ($iface) use ($interfaceName) {
    return $iface["name"] !== $interfaceName;
}));

// Verificar si se eliminó algo
if (count($jsonData["interfaces"]) === $originalCount) {
    echo json_encode([
        "success" => false,
        "error" => "Interface not found."
    ]);
    exit;
}

// Guardar el archivo actualizado
if (file_put_contents($configPath, json_encode($jsonData, JSON_PRETTY_PRINT)) === false) {
    echo json_encode([
        "success" => false,
        "error" => "Failed to write configuration."
    ]);
    exit;
}

// Éxito
echo json_encode([
    "success" => true
]);
