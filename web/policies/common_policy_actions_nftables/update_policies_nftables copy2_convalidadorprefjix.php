<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

header('Content-Type: application/json');

// Leer el cuerpo de la petición
$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);

// Validar que se recibió correctamente el objeto 'rule'
$nuevaRegla = $input['rule'] ?? null;

if (!$nuevaRegla || !isset($nuevaRegla['table']) || !isset($nuevaRegla['chain']) || !isset($nuevaRegla['handle'])) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Faltan los campos 'table', 'chain' o 'handle' en el objeto 'rule'."
    ]);
    exit;
}

// Validar la regla con el script de validación
require_once __DIR__ . '/validation_policies_nftables.php';

try {
    $nuevaRegla = validarReglaNftables($nuevaRegla);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "La validación de la regla falló.",
        "details" => $e->getMessage()
    ]);
    exit;
}


foreach ($nuevaRegla['expr'] as $k => &$expresion) {
    if (isset($expresion['log'])) {
        $prefix = trim($expresion['log']['prefix'] ?? '');

        if ($prefix === 'enabled') {
            $handle = $nuevaRegla['handle'];
            $chain = strtolower($nuevaRegla['chain']);
            $expresion['log']['prefix'] = "nftables {$handle} {$chain}";
            $expresion['log']['flags'] = 'all';
            $expresion['log']['level'] = 'info';

            // Eliminar group explícitamente si existe
            if (isset($expresion['log']['group'])) {
                unset($expresion['log']['group']);
            }
        }

        // Si prefix está vacío, eliminar todo el bloque log
        if ($prefix === '') {
            unset($nuevaRegla['expr'][$k]);
        }
    }
}
unset($expresion);
// 🔧 Reindexar para evitar claves numéricas en JSON
$nuevaRegla['expr'] = array_values($nuevaRegla['expr']);








// Cargar el archivo JSON existente
$archivo = "/var/www/config/rules_nftables.json";
$contenido = file_exists($archivo) ? file_get_contents($archivo) : '';
$datos = $contenido ? json_decode($contenido, true) : ["nftables" => []];

// Buscar y reemplazar la regla
$actualizado = false;
foreach ($datos["nftables"] as $i => $entrada) {
    if (isset($entrada["rule"])) {
        $regla = $entrada["rule"];
        if (
            $regla["table"] === $nuevaRegla["table"] &&
            $regla["chain"] === $nuevaRegla["chain"] &&
            $regla["handle"] == $nuevaRegla["handle"]
        ) {
            $datos["nftables"][$i]["rule"] = $nuevaRegla;
            $actualizado = true;
            break;
        }
    }
}

if (!$actualizado) {
    http_response_code(404);
    echo json_encode([
        "status" => "error",
        "message" => "No se encontró ninguna regla que coincida con table, chain y handle."
    ]);
    exit;
}

// Guardar el archivo actualizado
file_put_contents($archivo, json_encode($datos, JSON_PRETTY_PRINT));

echo json_encode([
    "status" => "success",
    "message" => "Regla actualizada correctamente.",
    "table" => $nuevaRegla["table"],
    "chain" => $nuevaRegla["chain"],
    "handle" => $nuevaRegla["handle"]
]);
