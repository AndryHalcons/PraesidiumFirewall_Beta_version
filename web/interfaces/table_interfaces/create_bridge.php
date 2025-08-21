<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

header('Content-Type: application/json');

// Ruta al archivo YAML
$archivoYAML = '/var/www/config/interfaces.yml';

// Validación de método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos del cuerpo JSON
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null) {
    echo json_encode(['error' => 'Datos JSON inválidos']);
    exit;
}

$nombreBridge = $input['name'] ?? null;
if (!$nombreBridge || !preg_match('/^br[0-9a-zA-Z_-]+$/', $nombreBridge)) {
    echo json_encode(['error' => 'Nombre de bridge inválido']);
    exit;
}

// Función para normalizar nombres de bridges
function normalizarBridge($nombre) {
    if (preg_match('/^br(\d+)$/i', $nombre, $matches)) {
        return 'br' . intval($matches[1]); // br001 → br1
    }
    return strtolower($nombre); // br-Test → br-test
}

// Leer el archivo YAML
$config = yaml_parse_file($archivoYAML);
if ($config === false) {
    echo json_encode(['error' => 'No se pudo leer el archivo YAML']);
    exit;
}

// Asegurar estructura base
if (!isset($config['network'])) {
    $config['network'] = ['version' => 2];
}
if (!isset($config['network']['bridges'])) {
    $config['network']['bridges'] = [];
}

// Verificar si el bridge ya existe (normalizado)
$nombreNormalizado = normalizarBridge($nombreBridge);
foreach ($config['network']['bridges'] as $bridgeExistente => $_) {
    if (normalizarBridge($bridgeExistente) === $nombreNormalizado) {
        echo json_encode(['error' => "Ya existe un bridge similar: '$bridgeExistente'"]);
        exit;
    }
}

// Añadir el nuevo bridge
$config['network']['bridges'][$nombreBridge] = [
    'interfaces' => []
];

// Guardar el archivo YAML actualizado
$yaml = yaml_emit($config);
if (file_put_contents($archivoYAML, $yaml) === false) {
    echo json_encode(['error' => 'No se pudo guardar el archivo YAML']);
    exit;
}

echo json_encode(['mensaje' => "Bridge '$nombreBridge' creado correctamente"]);
