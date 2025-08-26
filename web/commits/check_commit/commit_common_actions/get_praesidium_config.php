<?php
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$mode = isset($_POST['mode']) ? $_POST['mode'] : 'candidate';

switch ($mode) {
    case 'running':
        $basePath = '/var/www/config_running/';
        break;
    case 'historic':
        $basePath = '/var/www/config/commit_history/';
        break;
    case 'candidate':
    default:
        $basePath = '/var/www/config/';
        break;
}

$filenames = [
    "interfaces.yml",
    "routes.json",
    "rules_nftables.json",
    "rules.json",
    "users.json"
];

$config = [];

// Convertir interfaces.yml a JSON usando el módulo nativo yaml
$interfacesPath = $basePath . $filenames[0];
if (file_exists($interfacesPath)) {
    $yamlContent = file_get_contents($interfacesPath);
    $parsedYaml = yaml_parse($yamlContent);
    $config['interfaces'] = is_array($parsedYaml) ? $parsedYaml : ["error" => "Error al parsear YAML"];
} else {
    $config['interfaces'] = ["error" => "Archivo interfaces.yml no encontrado"];
}

// Cargar y decodificar los demás archivos JSON
for ($i = 1; $i < count($filenames); $i++) {
    $path = $basePath . $filenames[$i];
    $key = pathinfo($filenames[$i], PATHINFO_FILENAME); // nombre sin extensión

    if (file_exists($path)) {
        $jsonContent = file_get_contents($path);
        $decoded = json_decode($jsonContent, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Si es un array plano, lo dejamos como está
            $config[$key] = $decoded;
        } else {
            $config[$key] = ["error" => "JSON mal formado", "raw" => $jsonContent];
        }
    } else {
        $config[$key] = ["error" => "Archivo no encontrado"];
    }
}

// Devolver JSON legible y bonito
header('Content-Type: application/json');
echo json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
