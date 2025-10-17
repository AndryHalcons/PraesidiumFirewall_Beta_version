<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$chain = trim($_GET['table'] ?? $_GET['chain'] ?? '');
$allowedChains = ['bonds', 'bridges', 'ethernets', 'wireguard', 'vlans', 'wifis', 'tunnels'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro inválido o ausente']);
    exit;
}

// Dispatcher: solo ejecuta la función
switch ($chain) {
    case 'bonds': get_bonds(); break;
    case 'bridges': get_bridges(); break;
    case 'ethernets': get_ethernets(); break;
    case 'wireguard': get_wireguard(); break;
    case 'vlans': get_vlans(); break;
    case 'wifis': get_wifis(); break;
    case 'tunnels': get_tunnels(); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}

// Funciones autónomas por tipo


function get_ethernets() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_interfaces.json'), true);
    $columns = $structure['ethernets'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/interfaces.json'), true);
    $block = $data['network']['ethernets'] ?? [];

    $result = [];
    foreach ($block as $name => $entry) {
        $entry['name'] = $name;
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $entry[$col] ?? "";
        }
        $result[] = $flat;
    }

    echo json_encode(['ethernets' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}





function get_bridges() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_interfaces.json'), true);
    $columns = $structure['bridges'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/interfaces.json'), true);
    $block = $data['network']['bridges'] ?? [];

    $result = [];
    foreach ($block as $name => $entry) {
        $entry['name'] = $name;
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $entry[$col] ?? "";
        }
        $result[] = $flat;
    }

    echo json_encode(['bridges' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


function get_vlans() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_interfaces.json'), true);
    $columns = $structure['vlans'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/interfaces.json'), true);
    $block = $data['network']['vlans'] ?? [];

    $result = [];
    foreach ($block as $name => $entry) {
        $entry['name'] = $name;
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $entry[$col] ?? "";
        }
        $result[] = $flat;
    }

    echo json_encode(['vlans' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


function get_bonds() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_interfaces.json'), true);
    $columns = $structure['bonds'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/interfaces.json'), true);
    $block = $data['network']['bonds'] ?? [];

    $result = [];
    foreach ($block as $name => $entry) {
        $entry['name'] = $name;
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $entry[$col] ?? "";
        }
        $result[] = $flat;
    }

    echo json_encode(['bonds' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


function get_wifis() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_interfaces.json'), true);
    $columns = $structure['wifis'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/interfaces.json'), true);
    $block = $data['network']['wifis'] ?? [];

    $result = [];
    foreach ($block as $name => $entry) {
        $entry['name'] = $name;
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $entry[$col] ?? "";
        }
        $result[] = $flat;
    }

    echo json_encode(['wifis' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


function get_wireguard() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_interfaces.json'), true);
    $columns = $structure['wireguard'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/interfaces.json'), true);
    $block = $data['network']['wireguard'] ?? [];

    $result = [];
    foreach ($block as $name => $entry) {
        $entry['name'] = $name;
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $entry[$col] ?? "";
        }
        $result[] = $flat;
    }

    echo json_encode(['wireguard' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


function get_tunnels() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_interfaces.json'), true);
    $columns = $structure['tunnels'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/interfaces.json'), true);
    $block = $data['network']['tunnels'] ?? [];

    $result = [];
    foreach ($block as $name => $entry) {
        $entry['name'] = $name;
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $entry[$col] ?? "";
        }
        $result[] = $flat;
    }

    echo json_encode(['tunnels' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
