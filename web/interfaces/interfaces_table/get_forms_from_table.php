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
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

// Dispatcher: solo ejecuta la función
switch ($chain) {
    case 'bonds':      get_bonds_form(); break;
    case 'bridges':    get_bridges_form(); break;
    case 'ethernets':  get_ethernets_form(); break;
    case 'wireguard':  get_wireguard_form(); break;
    case 'vlans':      get_vlans_form(); break;
    case 'wifis':      get_wifis_form(); break;
    case 'tunnels':    get_tunnels_form(); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}

// Funciones autónomas por tipo
function get_ethernets_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['ethernets'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de ethernets']);
        return;
    }

    //echo json_encode(['ethernets' => $json['ethernets']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo json_encode($json['ethernets'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
}

function get_bridges_form() {
    // Ruta del archivo de configuración del formulario de bridges
    // Path to the bridges form configuration file
    $path = '/var/www/backend/checks/system_data/default_forms/forms_interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        // Error al leer el archivo de configuración
        // Error reading the configuration file
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    // Decodificar el contenido JSON del formulario
    // Decode the JSON content of the form
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['bridges'])) {
        // Error al interpretar el JSON o falta la sección 'bridges'
        // Error parsing JSON or missing 'bridges' section
        echo json_encode(['error' => 'Error al interpretar los datos de bridges']);
        return;
    }

    // Ruta del archivo con la lista de interfaces clasificadas
    // Path to the file containing the classified interface list
    $ifacePath = '/var/www/backend/checks/system_data/data_interfaces/all_interfaces_list.json';
    if (file_exists($ifacePath)) {
        // Leer el archivo de interfaces
        // Read the interface file
        $ifaceRaw = file_get_contents($ifacePath);
        $ifaceData = json_decode($ifaceRaw, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // Inicializar lista de interfaces físicas (ethernets + bonds)
            // Initialize list of physical interfaces (ethernets + bonds)
            $names = [];

            if (isset($ifaceData["ethernets"])) {
                // Añadir interfaces Ethernet
                // Add Ethernet interfaces
                $names = array_merge($names, $ifaceData["ethernets"]);
            }
            if (isset($ifaceData["bonds"])) {
                // Añadir interfaces Bond
                // Add Bond interfaces
                $names = array_merge($names, $ifaceData["bonds"]);
            }

            // Insertar las interfaces físicas en el campo select del formulario
            // Insert physical interfaces into the form's select field
            if (isset($json["bridges"]["select"]["interfaces"])) {
                $json["bridges"]["select"]["interfaces"] = array_merge(
                    $json["bridges"]["select"]["interfaces"],
                    $names
                );
            }
        }
    }

    // Devolver el formulario de bridges como JSON
    // Return the bridges form as JSON
    echo json_encode($json['bridges'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_bonds_form() {
    // Ruta del archivo de configuración del formulario de bonds
    // Path to the bonds form configuration file
    $path = '/var/www/backend/checks/system_data/default_forms/forms_interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        // Error al leer el archivo de configuración
        // Error reading the configuration file
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    // Decodificar el contenido JSON del formulario
    // Decode the JSON content of the form
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['bonds'])) {
        // Error al interpretar el JSON o falta la sección 'bonds'
        // Error parsing JSON or missing 'bonds' section
        echo json_encode(['error' => 'Error al interpretar los datos de bonds']);
        return;
    }

    // Ruta del archivo con la lista de interfaces clasificadas
    // Path to the file containing the classified interface list
    $ifacePath = '/var/www/backend/checks/system_data/data_interfaces/all_interfaces_list.json';
    if (file_exists($ifacePath)) {
        // Leer el archivo de interfaces
        // Read the interface file
        $ifaceRaw = file_get_contents($ifacePath);
        $ifaceData = json_decode($ifaceRaw, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // Inicializar lista de interfaces Ethernet
            // Initialize list of Ethernet interfaces
            $names = [];

            if (isset($ifaceData["ethernets"])) {
                // Añadir interfaces Ethernet
                // Add Ethernet interfaces
                $names = array_merge($names, $ifaceData["ethernets"]);
            }

            // Insertar las interfaces Ethernet en el campo multiselect del formulario.
            // Insert Ethernet interfaces into the form's multiselect field.
            if (isset($json["bonds"]["multiselect"]["interfaces"])) {
                $json["bonds"]["multiselect"]["interfaces"] = array_merge(
                    $json["bonds"]["multiselect"]["interfaces"],
                    $names
                );
            }
        }
    }

    // Devolver el formulario de bonds como JSON
    // Return the bonds form as JSON
    echo json_encode($json['bonds'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function get_vlans_form() {
    // Ruta del archivo de configuración del formulario de VLANs
    // Path to the VLANs form configuration file
    $path = '/var/www/backend/checks/system_data/default_forms/forms_interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        // Error al leer el archivo de configuración
        // Error reading the configuration file
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    // Decodificar el contenido JSON del formulario
    // Decode the JSON content of the form
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['vlans'])) {
        // Error al interpretar el JSON o falta la sección 'vlans'
        // Error parsing JSON or missing 'vlans' section
        echo json_encode(['error' => 'Error al interpretar los datos de vlans']);
        return;
    }

    // Ruta del archivo con la lista de interfaces clasificadas
    // Path to the file containing the classified interface list
    $ifacePath = '/var/www/backend/checks/system_data/data_interfaces/all_interfaces_list.json';
    if (file_exists($ifacePath)) {
        // Leer el archivo de interfaces
        // Read the interface file
        $ifaceRaw = file_get_contents($ifacePath);
        $ifaceData = json_decode($ifaceRaw, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // Inicializar lista de interfaces válidas para VLANs (bonds + bridges)
            // Initialize list of valid interfaces for VLANs (bonds + bridges)
            $links = [];

            if (isset($ifaceData["bonds"])) {
                // Añadir interfaces Bond
                // Add Bond interfaces
                $links = array_merge($links, $ifaceData["bonds"]);
            }
            if (isset($ifaceData["bridge"])) {
                // Añadir interfaces Bridge
                // Add Bridge interfaces
                $links = array_merge($links, $ifaceData["bridge"]);
            }

            // Insertar las interfaces en el campo select.link del formulario
            // Insert interfaces into the form's select.link field
            if (isset($json["vlans"]["select"]["link"])) {
                $json["vlans"]["select"]["link"] = array_merge(
                    $json["vlans"]["select"]["link"],
                    $links
                );
            }
        }
    }

    // Devolver el formulario de VLANs como JSON
    // Return the VLANs form as JSON
    echo json_encode($json['vlans'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


function get_wireguard_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['wireguard'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de wireguard']);
        return;
    }

    echo json_encode($json['wireguard'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

}


function get_wifis_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['wifis'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de wifis']);
        return;
    }

    echo json_encode($json['wifis'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

}

function get_tunnels_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_interfaces.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['tunnels'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de tunnels']);
        return;
    }

    echo json_encode($json['tunnels'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

}
