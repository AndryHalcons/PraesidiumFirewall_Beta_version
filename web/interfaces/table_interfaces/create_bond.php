<?php
// Inicia la sesión para verificar si el usuario está autenticado
session_start();

// Si no hay sesión iniciada, redirige al login
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

// Define que la respuesta será en formato JSON
header('Content-Type: application/json');

// Ruta al archivo YAML que se va a modificar
$archivoYAML = '/var/www/config/interfaces.yml';

// Verifica que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtiene el cuerpo de la solicitud y lo decodifica desde JSON
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null) {
    echo json_encode(['error' => 'Datos JSON inválidos']);
    exit;
}

// Extrae el nombre del bond desde el JSON recibido
$nombreBond = $input['name'] ?? null;

// Valida que el nombre del bond sea correcto (debe comenzar con "bond")
if (!$nombreBond || !preg_match('/^bond[0-9a-zA-Z_-]+$/', $nombreBond)) {
    echo json_encode(['error' => 'Nombre de bond inválido']);
    exit;
}

// Función para normalizar nombres de bonds (por ejemplo, bond01 → bond1)
function normalizarBond($nombre) {
    if (preg_match('/^bond(\d+)$/i', $nombre, $matches)) {
        return 'bond' . intval($matches[1]);
    }
    return strtolower($nombre);
}

// Intenta leer el archivo YAML
$config = yaml_parse_file($archivoYAML);
if ($config === false) {
    echo json_encode(['error' => 'No se pudo leer el archivo YAML']);
    exit;
}

// Asegura que la estructura base del YAML esté presente
if (!isset($config['network'])) {
    $config['network'] = ['version' => 2];
}
if (!isset($config['network']['bonds'])) {
    $config['network']['bonds'] = [];
}

// Verifica si ya existe un bond con nombre similar (normalizado)
$nombreNormalizado = normalizarBond($nombreBond);
foreach ($config['network']['bonds'] as $bondExistente => $_) {
    if (normalizarBond($bondExistente) === $nombreNormalizado) {
        echo json_encode(['error' => "Ya existe un bond similar: '$bondExistente'"]);
        exit;
    }
}

// Crea el nuevo bond con estructura vacía
$config['network']['bonds'][$nombreBond] = [
    'interfaces' => [],
    'parameters' => []
];

// Convierte el array PHP a YAML y lo guarda en el archivo
$yaml = yaml_emit($config);
if (file_put_contents($archivoYAML, $yaml) === false) {
    echo json_encode(['error' => 'No se pudo guardar el archivo YAML']);
    exit;
}

// Devuelve una respuesta de éxito
echo json_encode(['mensaje' => "Bond '$nombreBond' creado correctamente"]);
