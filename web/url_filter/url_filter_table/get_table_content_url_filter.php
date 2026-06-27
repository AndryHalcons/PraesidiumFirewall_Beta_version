<?php
require_once __DIR__ . '/../../common/security/session.php';
praesidium_session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$chain = trim($_GET['table'] ?? $_GET['chain'] ?? '');
$allowedChains = ['url_policies', 'url_list', 'url_listen_ports','url_profile','url_port_profile','url_network_list','url_networks_list_profile'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

switch ($chain) {
    case 'url_policies':      get_url_policies_form(); break;
    case 'url_profile':          get_url_profile_form(); break;
    case 'url_port_profile':     get_url_url_port_profile_form($chain); break;
    case 'url_listen_ports':  get_url_listen_ports_form(); break;
    case 'url_list':  get_url_list_form(); break;
    case 'url_network_list':  get_url_network_list_form(); break;
    case 'url_networks_list_profile':  get_url_networks_list_profile(); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}

function get_url_policies_form() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_squid.json'), true);
    $columns = $structure['url_policies'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/squid_config/squid_policies.json'), true);
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

function get_url_profile_form() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_squid.json'), true);
    $columns = $structure['url_profile'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/squid_config/squid_policies.json'), true);
    $block = $data['squid']['url_profile'] ?? [];

    $result = [];
    foreach ($block as $entry) {
        $rule = $entry['rule'] ?? [];
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $rule[$col] ?? "";
        }
        $result[] = $flat;
    }

    echo json_encode(['url_profile' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_url_networks_list_profile() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_squid.json'), true);
    $columns = $structure['url_networks_list_profile'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/squid_config/squid_policies.json'), true);
    $block = $data['squid']['url_networks_list_profile'] ?? [];

    $result = [];
    foreach ($block as $entry) {
        $rule = $entry['rule'] ?? [];
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $rule[$col] ?? "";
        }
        $result[] = $flat;
    }

    echo json_encode(['url_networks_list_profile' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}



function get_url_url_port_profile_form() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_squid.json'), true);
    $columns = $structure['url_port_profile'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/squid_config/squid_policies.json'), true);
    $block = $data['squid']['url_port_profile'] ?? [];

    $result = [];
    foreach ($block as $entry) {
        $rule = $entry['rule'] ?? [];
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $rule[$col] ?? "";
        }
        $result[] = $flat;
    }

    echo json_encode(['url_port_profile' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_url_listen_ports_form() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_squid.json'), true);
    $columns = $structure['url_listen_ports'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/squid_config/squid_policies.json'), true);
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

function get_url_list_form() {
    // Leer la estructura de columnas
    // Read the column structure
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_squid.json'), true);
    $columns = $structure['url_list'] ?? [];

    // Directorio de los archivos .txt
    // Directory containing the .txt files
    $dir = '/var/www/config/squid_config/squid_folder/conf.d/domain_list/';

    // Inicializar array de resultados
    // Initialize result array
    $result = [];

    // Escanear el directorio
    // Scan the directory
    foreach (scandir($dir) as $file) {
        if (is_file("$dir/$file") && pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
            $flat = [];

            // Rellenar las columnas según la estructura
            // Fill columns according to the structure
            foreach ($columns as $col) {
                $flat[$col] = ($col === 'file') ? $file : "";
            }

            $result[] = $flat;
        }
    }

    // Devolver el JSON con el formato correcto
    // Return the JSON with the correct format
    echo json_encode(['url_list' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


function get_url_network_list_form() {
    // Leer la estructura de columnas
    // Read the column structure
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_squid.json'), true);
    $columns = $structure['url_network_list'] ?? [];

    // Directorio de los archivos .txt
    // Directory containing the .txt files
    $dir = '/var/www/config/squid_config/squid_folder/conf.d/ip_list/';

    // Inicializar array de resultados
    // Initialize result array
    $result = [];

    // Escanear el directorio
    // Scan the directory
    foreach (scandir($dir) as $file) {
        if (is_file("$dir/$file") && pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
            $flat = [];

            // Rellenar las columnas según la estructura
            // Fill columns according to the structure
            foreach ($columns as $col) {
                $flat[$col] = ($col === 'file') ? $file : "";
            }

            $result[] = $flat;
        }
    }

    // Devolver el JSON con el formato correcto
    // Return the JSON with the correct format
    echo json_encode(['url_network_list' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}