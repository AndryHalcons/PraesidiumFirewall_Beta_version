<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

function del_policy($hook, $id) {
    $validHooks = ['BF_HOOK_TC_INGRESS', 'BF_HOOK_TC_EGRESS', 'BF_HOOK_XDP'];
    if (!in_array($hook, $validHooks) || !is_numeric($id)) {
        http_response_code(400);
        echo "Parámetros inválidos.";
        return;
    }

    $file = "/var/www/config/rules.json";
    if (!file_exists($file)) {
        http_response_code(500);
        echo "Archivo de reglas no encontrado.";
        return;
    }

    $json = file_get_contents($file);
    $data = json_decode($json, true);

    if (!isset($data[$hook]['rules']) || !is_array($data[$hook]['rules'])) {
        http_response_code(404);
        echo "Bloque '$hook' no encontrado o mal formado.";
        return;
    }

    $rules = &$data[$hook]['rules'];
    $index = array_search($id, array_column($rules, 'id'));

    if ($index === false) {
        http_response_code(404);
        echo "Regla con ID $id no encontrada en '$hook'.";
        return;
    }

    array_splice($rules, $index, 1);

    if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        http_response_code(500);
        echo "Error al guardar el archivo.";
        return;
    }

    echo "OK";
}

// 📨 Entrada JSON
$input = json_decode(file_get_contents("php://input"), true);
$hook = $input['hook'] ?? '';
$id = $input['id'] ?? null;

del_policy($hook, $id);
