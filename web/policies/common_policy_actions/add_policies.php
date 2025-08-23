<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../../lang/es.php";
}
$L = require $langFile;

// Solo si se reciben exactamente los dos parámetros
$validHooks = ['BF_HOOK_TC_INGRESS', 'BF_HOOK_TC_EGRESS', 'BF_HOOK_XDP'];

foreach ($validHooks as $hookKey) {
    if (isset($_POST[$hookKey]) && array_key_exists('new', $_POST)) {
        $hook = $_POST[$hookKey];
        add_rule($hook);
        exit;
    }
}


function obtenerNuevoId($hook) {
    $validHooks = ['BF_HOOK_TC_INGRESS', 'BF_HOOK_TC_EGRESS', 'BF_HOOK_XDP'];
    if (!in_array($hook, $validHooks)) {
        return null;
    }

    $jsonPath = "/var/www/config/rules.json";
    if (!file_exists($jsonPath)) {
        return null;
    }

    $jsonData = json_decode(file_get_contents($jsonPath), true);
    if (!isset($jsonData[$hook]['rules']) || !is_array($jsonData[$hook]['rules'])) {
        return null;
    }

    $existingIds = [];
    foreach ($jsonData[$hook]['rules'] as $rule) {
        if (isset($rule['id']) && is_int($rule['id'])) {
            $existingIds[] = $rule['id'];
        }
    }

    $newId = 1;
    while (in_array($newId, $existingIds)) {
        $newId++;
    }

    return $newId;
}

function obtenerNuevaPosicion($hook) {
    $validHooks = ['BF_HOOK_TC_INGRESS', 'BF_HOOK_TC_EGRESS', 'BF_HOOK_XDP'];
    if (!in_array($hook, $validHooks)) {
        return null;
    }

    $jsonPath = "/var/www/config/rules.json";
    if (!file_exists($jsonPath)) {
        return null;
    }

    $jsonData = json_decode(file_get_contents($jsonPath), true);
    if (!isset($jsonData[$hook]['rules']) || !is_array($jsonData[$hook]['rules'])) {
        return null;
    }

    $existingPositions = [];
    foreach ($jsonData[$hook]['rules'] as $rule) {
        if (isset($rule['position']) && is_int($rule['position'])) {
            $existingPositions[] = $rule['position'];
        }
    }

    $newPosition = 1;
    while (in_array($newPosition, $existingPositions)) {
        $newPosition++;
    }

    return $newPosition;
}

function add_rule($hook) { // ← función renombrada aquí
    $file = "/var/www/config/rules.json";

    if (!file_exists($file)) {
        http_response_code(500);
        echo "Archivo de reglas no encontrado.";
        return;
    }

    $json = file_get_contents($file);
    $data = json_decode($json, true);

    if (!isset($data[$hook])) {
        http_response_code(400);
        echo "Bloque '$hook' no encontrado en el archivo.";
        return;
    }

    $rules = &$data[$hook]['rules'];
    $newId = obtenerNuevoId($hook);
    $newPosition = obtenerNuevaPosicion($hook);

    $newRule = [
        "id" => $newId,
        "position" => $newPosition
    ];

    $rules[] = $newRule;

    if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        http_response_code(500);
        echo "Error al guardar la nueva regla.";
        return;
    }

    echo "Regla añadida correctamente al bloque '$hook'.";
}