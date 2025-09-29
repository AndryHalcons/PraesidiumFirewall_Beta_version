<?php
session_start();
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

// Dispatcher: solo ejecuta la función
switch ($chain) {
    case 'url_policies':      get_url_policies_form(); break;
    case 'url_profile':    get_url_profile_form(); break;
    case 'url_port_profile':     get_url_port_profile_form($chain); break;
    case 'url_listen_ports':  get_url_listen_ports_form(); break;
    case 'url_list':  get_url_list(); break;
    case 'url_network_list':  get_url_network_list(); break;
    case 'url_networks_list_profile':  get_url_networks_list_profile(); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////    Import Json to to consult  ///////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////

function import_alias_json() {
    $jsonPath = '/var/www/config/squid_config/squid_policies.json';

    if (!file_exists($jsonPath)) {
        return false;
    }

    $raw = file_get_contents($jsonPath);
    $aliasJsonData = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    return $aliasJsonData;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////    function for type //////// ///////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////



// Funciones autónomas por tipo
// Standalone functions by type


function get_url_policies_form() {
    $formPath = '/var/www/backend/checks/system_data/default_forms/forms_squid.json';

    $formRaw = file_get_contents($formPath);
    if ($formRaw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $formJson = json_decode($formRaw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($formJson['url_policies'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de ethernets']);
        return;
    }

    $aliasJson = import_alias_json();
    if ($aliasJson === false) {
        echo json_encode(['error' => 'No se pudieron obtener los perfiles de alias']);
        return;
    }

    // Extraer los nombres de perfil de ip profile
    $profileIPs = [''];
    if (isset($aliasJson['squid']['url_networks_list_profile'])) {
        foreach ($aliasJson['squid']['url_networks_list_profile'] as $profile_ip) {
            if (isset($profile_ip['rule']['name'])) {
                $profileIPs[] = $profile_ip['rule']['name'];
            }
        }
    }
    $formJson['url_policies']['select']['ip_addr_group'] = $profileIPs;
    // Extraer los nombres de perfil desde url_profile
    $profileNames = [''];
    if (isset($aliasJson['squid']['url_profile'])) {
        foreach ($aliasJson['squid']['url_profile'] as $profile) {
            if (isset($profile['rule']['name'])) {
                $profileNames[] = $profile['rule']['name'];
            }
        }
    }

    // Añadir también los nombres de url_port_profile al mismo array
    if (isset($aliasJson['squid']['url_port_profile'])) {
        foreach ($aliasJson['squid']['url_port_profile'] as $profile) {
            if (isset($profile['rule']['name'])) {
                $profileNames[] = $profile['rule']['name'];
            }
        }
    }



    // Insertar todo en el mismo select
    $formJson['url_policies']['select']['profile'] = $profileNames;

    echo json_encode($formJson['url_policies'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}





// Funciones autónomas por tipo
// Standalone functions by type
function get_url_profile_form() {
    // Ruta del archivo de configuración del formulario
    // Path to the form configuration file
    $path = '/var/www/backend/checks/system_data/default_forms/forms_squid.json';

    // Leer el contenido del archivo
    // Read the file content
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']); // Failed to read config file
        return;
    }

    // Decodificar el JSON
    // Decode JSON
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url_profile'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de url_profile']); // Error parsing url_profile data
        return;
    }

    // Obtener archivos .txt desde el directorio de configuración
    // Get .txt files from squid_config directory
    $configDir = '/var/www/config/squid_config/squid_folder/conf.d/domain_list/';
    $txtFiles = [];

    foreach (scandir($configDir) as $file) {
        if (is_file($configDir . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
            $txtFiles[] = $file;
        }
    }

    // Insertar los nombres de archivo en el campo "file"
    // Insert file names into the "file" field
    $json['url_profile']['select']['file'] = $txtFiles;

    // Devolver el JSON actualizado
    // Return the updated JSON
    echo json_encode($json['url_profile'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


function get_url_networks_list_profile() {
    // Ruta del archivo de configuración del formulario
    // Path to the form configuration file
    $path = '/var/www/backend/checks/system_data/default_forms/forms_squid.json';

    // Leer el contenido del archivo
    // Read the file content
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']); // Failed to read config file
        return;
    }

    // Decodificar el JSON
    // Decode JSON
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url_networks_list_profile'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de url_profile']); // Error parsing url_profile data
        return;
    }

    // Obtener archivos .txt desde el directorio de configuración
    // Get .txt files from squid_config directory
    $configDir = '/var/www/config/squid_config/squid_folder/conf.d/ip_list/';
    $txtFiles = [];

    foreach (scandir($configDir) as $file) {
        if (is_file($configDir . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
            $txtFiles[] = $file;
        }
    }

    // Insertar los nombres de archivo en el campo "file"
    // Insert file names into the "file" field
    $json['url_networks_list_profile']['select']['file'] = $txtFiles;

    // Devolver el JSON actualizado
    // Return the updated JSON
    echo json_encode($json['url_networks_list_profile'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}



// Funciones autónomas por tipo
// Standalone functions by type
function get_url_port_profile_form() {
    // Ruta del archivo de configuración del formulario
    // Path to the form configuration file
    $path = '/var/www/backend/checks/system_data/default_forms/forms_squid.json';

    // Leer el contenido del archivo
    // Read the file content
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']); // Failed to read config file
        return;
    }

    // Decodificar el JSON
    // Decode JSON
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url_port_profile'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de url_port_profile']); // Error parsing url_port_profile data
        return;
    }

    // Devolver el JSON actualizado
    // Return the updated JSON
    echo json_encode($json['url_port_profile'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}





function get_url_listen_ports_form() {
    // Ruta del formulario base  
    // Path to the base form  
    $path = '/var/www/backend/checks/system_data/default_forms/forms_squid.json';

    // Ruta del archivo de certificados  
    // Path to the certificates config file  
    $certPath = '/var/www/config/certs/certificates_config.json';

    // Leer el formulario  
    // Read the form  
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    // Decodificar el JSON del formulario  
    // Decode form JSON  
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url_listen_ports'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de url_listen_ports']);
        return;
    }

    // Leer el archivo de certificados  
    // Read the certificates file  
    $certRaw = file_get_contents($certPath);
    if ($certRaw !== false) {
        $certJson = json_decode($certRaw, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($certJson['certificates'])) {
            // Extensiones válidas para certificados  
            // Valid certificate extensions  
            $validCertExtensions = ['.pem', '.crt', '.cer', '.der', '.p7b', '.pfx'];

            // Añadir certificados válidos al campo select['cert']  
            // Add valid certificates to select['cert']  
            foreach ($certJson['certificates'] as $item) {
                if (isset($item['file_name'])) {
                    $file = $item['file_name'];

                    foreach ($validCertExtensions as $ext) {
                        if (str_ends_with($file, $ext) && !in_array($file, $json['url_listen_ports']['select']['cert'])) {
                            $json['url_listen_ports']['select']['cert'][] = $file;
                            break;
                        }
                    }

                    // Añadir claves .key al campo select['key']  
                    // Add .key files to select['key']  
                    if (str_ends_with($file, '.key') && !in_array($file, $json['url_listen_ports']['select']['key'])) {
                        $json['url_listen_ports']['select']['key'][] = $file;
                    }
                }
            }
        }
    }

    // Devolver el formulario actualizado  
    // Return the updated form  
    echo json_encode($json['url_listen_ports'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Funciones autónomas por tipo
function get_url_list() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_squid.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url_list'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de url_list']);
        return;
    }

    echo json_encode($json['url_list'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
}


// Funciones autónomas por tipo
function get_url_network_list() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_squid.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url_network_list'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de url_network_list']);
        return;
    }

    echo json_encode($json['url_network_list'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
}