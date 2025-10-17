<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$chain = trim($_GET['table'] ?? $_GET['chain'] ?? '');
$allowedChains = ['dhcp'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

// Dispatcher: solo ejecuta la función
switch ($chain) {
    case 'dhcp':    get_dhcp_form(); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}



//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////    function for type //////// ///////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
function get_dhcp_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_dhcp.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['dhcp'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de dhcp']);
        return;
    }

    // Cargar interfaces desde el archivo correspondiente
    $ifacePath = '/var/www/backend/checks/system_data/data_interfaces/all_interfaces_list.json';
    if (file_exists($ifacePath)) {
        $ifaceRaw = file_get_contents($ifacePath);
        $ifaceData = json_decode($ifaceRaw, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($ifaceData["all_interfaces"])) {
            $interfaces = $ifaceData["all_interfaces"];

            // Añadir interfaces al campo "interface" sin eliminar las existentes
            $json['dhcp']['select']['interface'] = array_merge(
                $json['dhcp']['select']['interface'] ?? [],
                $interfaces
            );
        }
    }

    echo json_encode($json['dhcp'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
