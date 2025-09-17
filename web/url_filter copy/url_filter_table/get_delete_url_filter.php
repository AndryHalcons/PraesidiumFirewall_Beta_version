<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$chain = trim($input['table'] ?? '');
$allowedChains = ['url_policies', 'url_list', 'url_listen_ports'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

switch ($chain) {
    case 'url_policies':      get_url_policies_form(); break;
    case 'url_list':          get_url_list_form(); break;
    case 'url_listen_ports':  get_url_listen_ports_form(); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}

function get_url_policies_form() {
    $path = '/var/www/config/squid_policies.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['squid']['url_policies'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';

    if ($id === '') {
        echo json_encode(['error' => 'ID no proporcionado']);
        return;
    }

    $found = false;
    foreach ($json['squid']['url_policies'] as $i => $entry) {
        if (($entry['rule']['id'] ?? '') === $id) {
            unset($json['squid']['url_policies'][$i]);
            $json['squid']['url_policies'] = array_values($json['squid']['url_policies']);
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo json_encode(['error' => 'ID no encontrado']);
        return;
    }

    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    echo json_encode(['success' => true, 'deleted_id' => $id]);
}

function get_url_list_form() {
    $path = '/var/www/config/squid_policies.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['squid']['url_list'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';

    if ($id === '') {
        echo json_encode(['error' => 'ID no proporcionado']);
        return;
    }

    $found = false;
    foreach ($json['squid']['url_list'] as $i => $entry) {
        if (($entry['rule']['id'] ?? '') === $id) {
            unset($json['squid']['url_list'][$i]);
            $json['squid']['url_list'] = array_values($json['squid']['url_list']);
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo json_encode(['error' => 'ID no encontrado']);
        return;
    }

    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    echo json_encode(['success' => true, 'deleted_id' => $id]);
}

function get_url_listen_ports_form() {
    $path = '/var/www/config/squid_policies.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['squid']['url_listen_ports'])) {
        echo json_encode(['error' => 'Error al interpretar el JSON']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';

    if ($id === '') {
        echo json_encode(['error' => 'ID no proporcionado']);
        return;
    }

    $found = false;
    foreach ($json['squid']['url_listen_ports'] as $i => $entry) {
        if (($entry['rule']['id'] ?? '') === $id) {
            unset($json['squid']['url_listen_ports'][$i]);
            $json['squid']['url_listen_ports'] = array_values($json['squid']['url_listen_ports']);
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo json_encode(['error' => 'ID no encontrado']);
        return;
    }

    $saved = file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        echo json_encode(['error' => 'No se pudo guardar el archivo']);
        return;
    }

    echo json_encode(['success' => true, 'deleted_id' => $id]);
}
