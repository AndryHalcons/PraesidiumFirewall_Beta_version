<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

// Ruta correcta al archivo YAML
$yamlPath = '/var/www/config/interfaces.yml';

// 🔧 Función para convertir ciertos campos en listas
function normalizarCamposLista(array &$config, array $camposLista) {
    foreach ($camposLista as $campo) {
        if (isset($config[$campo])) {
            // Si ya es array, no tocar
            if (is_array($config[$campo])) continue;

            // Si es string, convertir en array
            if (is_string($config[$campo])) {
                // Separar por comas, espacios o saltos de línea
                $items = preg_split('/[\s,]+/', $config[$campo]);
                $items = array_filter(array_map('trim', $items));
                $config[$campo] = array_values($items);
            }
        }
    }
}

// 🧩 Función para estructurar correctamente el campo nameservers
function formatearNameservers(array &$config) {
    if (isset($config['nameservers']) && is_string($config['nameservers'])) {
        $items = preg_split('/[\s,]+/', $config['nameservers']);
        $items = array_filter(array_map('trim', $items));
        $config['nameservers'] = [
            'addresses' => array_values($items)
        ];
    } elseif (isset($config['nameservers']['addresses']) && is_string($config['nameservers']['addresses'])) {
        $items = preg_split('/[\s,]+/', $config['nameservers']['addresses']);
        $items = array_filter(array_map('trim', $items));
        $config['nameservers']['addresses'] = array_values($items);
    }
}





// Leer el contenido actual del YAML
$contenido = file_exists($yamlPath) ? yaml_parse_file($yamlPath) : ['network' => ['version' => 2]];

// Recibir JSON desde JavaScript
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Nombre de interfaz no proporcionado']);
    exit;
}

$nombre = $input['name'];
$configNueva = $input;
unset($configNueva['name']); // Ya lo usamos como clave

// 📋 Lista de campos que deben ser listas en YAML
$camposQueDebenSerLista = [
    'interfaces',
    'addresses',
    'address',
    'search',
    'match.driver',
    'nameservers.addresses'
];

// 🧼 Normalizar campos antes de guardar
normalizarCamposLista($configNueva, $camposQueDebenSerLista);
formatearNameservers($configNueva);

// Determinar sección según el prefijo
$seccion = 'ethernets';
if (str_starts_with($nombre, 'bond')) {
    $seccion = 'bonds';
} elseif (str_starts_with($nombre, 'br')) {
    $seccion = 'bridges';
}

// Inicializar sección si no existe
if (!isset($contenido['network'][$seccion])) {
    $contenido['network'][$seccion] = [];
}

// Reemplazar configuración de la interfaz
$contenido['network'][$seccion][$nombre] = $configNueva;

// Guardar el YAML actualizado
yaml_emit_file($yamlPath, $contenido);

// Respuesta JSON para el frontend
echo json_encode([
    'success' => true,
    'interfaz' => $nombre,
    'seccion' => $seccion
]);
