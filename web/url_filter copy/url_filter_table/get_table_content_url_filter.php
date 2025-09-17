<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$chain = trim($_GET['table'] ?? $_GET['chain'] ?? '');
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
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_squid.json'), true);
    $columns = $structure['url_policies'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/squid_policies.json'), true);
    $block = $data['squid']['url_policies'] ?? [];

    $result = [];
    foreach ($block as $entry) {
        $rule = $entry['rule'] ?? [];
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $rule[$col] ?? "";
        }
        $result[] = $flat;
    }

    echo json_encode(['url_policies' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_url_list_form() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_squid.json'), true);
    $columns = $structure['url_list'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/squid_policies.json'), true);
    $block = $data['squid']['url_list'] ?? [];

    $result = [];
    foreach ($block as $entry) {
        $rule = $entry['rule'] ?? [];
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $rule[$col] ?? "";
        }
        $result[] = $flat;
    }

    echo json_encode(['url_list' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_url_listen_ports_form() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_squid.json'), true);
    $columns = $structure['url_listen_ports'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/squid_policies.json'), true);
    $block = $data['squid']['url_listen_ports'] ?? [];

    $result = [];
    foreach ($block as $entry) {
        $rule = $entry['rule'] ?? [];
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $rule[$col] ?? "";
        }
        $result[] = $flat;
    }

    echo json_encode(['url_listen_ports' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
