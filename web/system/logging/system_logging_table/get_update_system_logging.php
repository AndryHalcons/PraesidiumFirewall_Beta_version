<?php
require_once __DIR__ . '/../../../common/security/session.php';
praesidium_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/file/json_store.php';
require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || ($input['table'] ?? '') !== 'system_logging' || !isset($input['rule']) || !is_array($input['rule'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload inválido']);
    exit;
}

$formsPath = '/var/www/backend/checks/system_data/default_forms/forms_system_logging.json';
$formsRoot = json_decode(file_get_contents($formsPath), true);
$forms = $formsRoot['system_logging'] ?? null;
$fieldMap = $forms['_field_map'] ?? [];
if (!is_array($forms) || !is_array($fieldMap)) {
    http_response_code(500);
    echo json_encode(['error' => 'Formulario system_logging inválido']);
    exit;
}

$currentPath = '/var/www/config/system_logging.json';
$config = json_decode(file_get_contents($currentPath), true);
if (!is_array($config)) {
    $config = [];
}

$rule = $input['rule'];
foreach ($fieldMap as $column => $map) {
    if (!array_key_exists($column, $rule)) {
        continue;
    }
    $section = $map['section'] ?? '';
    $key = $map['key'] ?? '';
    if ($section === '' || $key === '') {
        continue;
    }
    $value = $rule[$column];

    if (isset($forms['select'][$column])) {
        if (!in_array($value, $forms['select'][$column], true)) {
            http_response_code(400);
            echo json_encode(['error' => "Valor inválido para $column"]);
            exit;
        }
    } elseif (isset($forms['checkbox'][$column])) {
        $checked = $forms['checkbox'][$column]['checked'];
        $unchecked = $forms['checkbox'][$column]['unchecked'];
        if ($value !== $checked && $value !== $unchecked) {
            http_response_code(400);
            echo json_encode(['error' => "Valor booleano inválido para $column"]);
            exit;
        }
    } elseif (isset($forms['number'][$column])) {
        if (!is_numeric($value)) {
            http_response_code(400);
            echo json_encode(['error' => "Número inválido para $column"]);
            exit;
        }
        $value = (int)$value;
        $min = (int)$forms['number'][$column]['min'];
        $max = (int)$forms['number'][$column]['max'];
        if ($value < $min || $value > $max) {
            http_response_code(400);
            echo json_encode(['error' => "Número fuera de rango para $column"]);
            exit;
        }
    }

    if (!isset($config[$section]) || !is_array($config[$section])) {
        $config[$section] = [];
    }
    $config[$section][$key] = $value;
}

try {
    json_store_write($currentPath, $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    chmod($currentPath, 0664);
    echo json_encode(['success' => true, 'system_logging' => [$rule]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
