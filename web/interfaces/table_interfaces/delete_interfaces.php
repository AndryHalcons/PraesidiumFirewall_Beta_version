<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

header('Content-Type: application/json');

$archivoYAML = '/var/www/config/interfaces.yml';

// Verifica que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Decodifica el cuerpo JSON
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null || !isset($input['interface'])) {
    echo json_encode(['error' => 'Datos JSON inválidos']);
    exit;
}

$interfaz = trim($input['interface']);

// Verifica si el nombre empieza por "br" o "bond"
if (preg_match('/^br[0-9a-zA-Z_-]+$/', $interfaz)) {
    $seccion = 'bridges';
} elseif (preg_match('/^bond[0-9a-zA-Z_-]+$/', $interfaz)) {
    $seccion = 'bonds';
} else {
    echo json_encode(['error' => 'Solo se pueden eliminar interfaces tipo bridge o bond']);
    exit;
}

// Carga el archivo YAML
$config = yaml_parse_file($archivoYAML);
if ($config === false) {
    echo json_encode(['error' => 'No se pudo leer el archivo YAML']);
    exit;
}

// Verifica que la sección exista
if (!isset($config['network'][$seccion])) {
    echo json_encode(['error' => "La sección '$seccion' no existe en el archivo YAML"]);
    exit;
}

// Verifica si la interfaz existe
if (!isset($config['network'][$seccion][$interfaz])) {
    echo json_encode(['error' => "La interfaz '$interfaz' no existe en '$seccion'"]);
    exit;
}

// Elimina la interfaz
unset($config['network'][$seccion][$interfaz]);

// Guarda el YAML actualizado
$yaml = yaml_emit($config);
if (file_put_contents($archivoYAML, $yaml) === false) {
    echo json_encode(['error' => 'No se pudo guardar el archivo YAML']);
    exit;
}

// Devuelve éxito
echo json_encode(['success' => true, 'mensaje' => "Interfaz '$interfaz' eliminada correctamente"]);
