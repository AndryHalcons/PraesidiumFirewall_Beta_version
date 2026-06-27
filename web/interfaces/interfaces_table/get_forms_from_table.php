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
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}


// Carga nombres de alias de dirección para campos de Interfaces que aceptan IP/CIDR o alias individual.
// Loads address alias names for Interfaces fields that accept an IP/CIDR or individual alias.
function get_interface_address_alias_options(): array {
    $aliasPath = '/var/www/config/alias.json';
    if (!file_exists($aliasPath)) {
        return [];
    }

    $raw = file_get_contents($aliasPath);
    if ($raw === false) {
        return [];
    }

    $aliasData = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($aliasData)) {
        return [];
    }

    $names = [];
    foreach (($aliasData['alias_address'] ?? []) as $entry) {
        if (isset($entry['name']) && is_string($entry['name']) && trim($entry['name']) !== '') {
            $names[] = trim($entry['name']);
        }
    }

    return array_values(array_unique($names));
}

// Rellena object_multiselect con alias_address sin ofrecer grupos en Interfaces.
// Populates object_multiselect with alias_address without offering groups in Interfaces.
function populate_interface_object_multiselect_options(array &$formConfig): void {
    if (!isset($formConfig['object_multiselect']) || !is_array($formConfig['object_multiselect'])) {
        return;
    }

    $aliasNames = get_interface_address_alias_options();
    foreach ($formConfig['object_multiselect'] as $field => $existingOptions) {
        $baseOptions = is_array($existingOptions) ? $existingOptions : [];
        $formConfig['object_multiselect'][$field] = array_values(array_unique(array_merge($baseOptions, $aliasNames)));
    }
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
    populate_interface_object_multiselect_options($json['ethernets']);
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
    populate_interface_object_multiselect_options($json['bridges']);
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
    populate_interface_object_multiselect_options($json['bonds']);
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

    // Ruta del archivo candidate de interfaces gestionado por Praesidium.
    // Path to the Praesidium-managed candidate interfaces file.
    $ifacePath = '/var/www/config/interfaces.json';
    if (file_exists($ifacePath)) {
        // Leer candidate, no runtime: VLAN debe poder enlazar con bonds creados en el mismo commit.
        // Read candidate, not runtime: VLAN must link to bonds created in the same commit.
        $ifaceRaw = file_get_contents($ifacePath);
        $ifaceData = json_decode($ifaceRaw, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($ifaceData["network"])) {
            // Inicializar links válidos para VLANs desde la intención candidate (ethernets + bonds + bridges).
            // Initialize valid VLAN links from candidate intent (ethernets + bonds + bridges).
            $links = [];

            foreach (["ethernets", "bonds", "bridges"] as $section) {
                if (isset($ifaceData["network"][$section]) && is_array($ifaceData["network"][$section])) {
                    $links = array_merge($links, array_keys($ifaceData["network"][$section]));
                }
            }

            // Insertar links deduplicados en select.link preservando la opción vacía inicial.
            // Insert deduplicated links into select.link while preserving the initial empty option.
            if (isset($json["vlans"]["select"]["link"])) {
                $json["vlans"]["select"]["link"] = array_values(array_unique(array_merge(
                    $json["vlans"]["select"]["link"],
                    $links
                )));
            }
        }
    }

    // Devolver el formulario de VLANs como JSON
    // Return the VLANs form as JSON
    populate_interface_object_multiselect_options($json['vlans']);
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

    populate_interface_object_multiselect_options($json['wireguard']);
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

    populate_interface_object_multiselect_options($json['wifis']);
    echo json_encode($json['wifis'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

}

