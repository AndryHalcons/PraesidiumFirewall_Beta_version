<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$chain = trim($_GET['table'] ?? $_GET['chain'] ?? '');
$allowedChains = ['bonds', 'bridges', 'ethernets', 'wireguard', 'vlans', 'wifis'];

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
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}

// Funciones autónomas por tipo


// Expone rutas heredadas guardadas bajo "routes" como columnas planas routes.to/routes.via.
// Expose legacy routes stored under "routes" as flat routes.to/routes.via columns.
function normalize_legacy_routes_for_table(array $entry): array {
    if ((!empty($entry['routes.to']) && !empty($entry['routes.via'])) || empty($entry['routes'])) {
        return $entry;
    }

    $routes = $entry['routes'];

    // Algunos candidates antiguos guardan routes como string tipo "{'to':'default','via':'1.2.3.4'}".
    // Some old candidates store routes as a string like "{'to':'default','via':'1.2.3.4'}".
    if (is_string($routes)) {
        $candidate = json_decode($routes, true);
        if (!is_array($candidate)) {
            $candidate = json_decode(str_replace("'", '"', $routes), true);
        }
        $routes = is_array($candidate) ? $candidate : null;
    }

    if (!is_array($routes)) {
        return $entry;
    }

    // Acepta tanto {to,via} como [{to,via}, ...]; la WebGUI actual solo muestra una ruta plana.
    // Accept both {to,via} and [{to,via}, ...]; the current WebGUI only displays one flat route.
    $route = null;
    if (isset($routes['to']) || isset($routes['via'])) {
        $route = $routes;
    } else {
        foreach ($routes as $item) {
            if (is_array($item) && (isset($item['to']) || isset($item['via']))) {
                $route = $item;
                break;
            }
        }
    }

    if (is_array($route)) {
        if (empty($entry['routes.to']) && isset($route['to'])) {
            $entry['routes.to'] = $route['to'];
        }
        if (empty($entry['routes.via']) && isset($route['via'])) {
            $entry['routes.via'] = $route['via'];
        }
        if (empty($entry['routes.metric']) && isset($route['metric'])) {
            $entry['routes.metric'] = $route['metric'];
        }
    }

    return $entry;
}


function get_ethernets() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_interfaces.json'), true);
    $columns = $structure['ethernets'] ?? [];

    $data = @json_decode(@file_get_contents('/var/www/config/interfaces.json'), true);
    $block = $data['network']['ethernets'] ?? [];

    $result = [];
    foreach ($block as $name => $entry) {
        $entry['name'] = $name;
        $entry = normalize_legacy_routes_for_table($entry);
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
        $entry = normalize_legacy_routes_for_table($entry);
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
        $entry = normalize_legacy_routes_for_table($entry);
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
        $entry = normalize_legacy_routes_for_table($entry);
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
        $entry = normalize_legacy_routes_for_table($entry);
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
        $entry = normalize_legacy_routes_for_table($entry);
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $entry[$col] ?? "";
        }
        $result[] = $flat;
    }

    echo json_encode(['wireguard' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


