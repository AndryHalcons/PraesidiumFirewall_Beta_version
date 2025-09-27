<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$chain = trim($_GET['table'] ?? $_GET['chain'] ?? '');
$allowedChains = ['url_policies', 'url_list', 'url_listen_ports','url_profile','url_port_profile'];

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
    // Ruta del archivo de configuración del formulario
    // Path to the form configuration file
    $formPath = '/var/www/backend/checks/system_data/default_forms/forms_squid.json';

    // Leer el contenido del archivo
    // Read the file content
    $formRaw = file_get_contents($formPath);
    if ($formRaw === false) {
        // Error al leer el archivo
        // Error reading the file
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']); // Failed to read config file
        return;
    }

    // Decodificar el JSON del formulario
    // Decode the form JSON
    $formJson = json_decode($formRaw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($formJson['url_policies'])) {
        // Error al interpretar el JSON o falta la sección esperada
        // Error parsing JSON or expected section missing
        echo json_encode(['error' => 'Error al interpretar los datos de ethernets']); // Failed to parse JSON or missing section
        return;
    }

    // Obtener los datos de alias usando la función existente
    // Get alias data using the existing function
    $aliasJson = import_alias_json();
    if ($aliasJson === false || !isset($aliasJson['squid']['url_profile'])) {
        // Error al obtener los perfiles desde el archivo de alias
        // Error retrieving profiles from alias file
        echo json_encode(['error' => 'No se pudieron obtener los perfiles de alias']); // Failed to retrieve alias profiles
        return;
    }

    // Extraer los nombres de perfil desde la sección url_profile
    // Extract profile names from the url_profile section
    $profileNames = ['']; // Añadir opción vacía al inicio / Add empty option at the beginning
    foreach ($aliasJson['squid']['url_profile'] as $profile) {
        if (isset($profile['rule']['name'])) {
            $profileNames[] = $profile['rule']['name'];
        }
    }

    // Insertar los nombres en el campo "profile" del formulario
    // Insert the names into the "profile" field of the form
    $formJson['url_policies']['select']['profile'] = $profileNames;

    // Devolver el JSON actualizado al frontend
    // Return the updated JSON to the frontend
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
    $configDir = '/var/www/config/squid_config/acl_domains/';
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




// Funciones autónomas por tipo
function get_url_listen_ports_form() {
    $path = '/var/www/backend/checks/system_data/default_forms/forms_squid.json';
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo de configuración']);
        return;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url_listen_ports'])) {
        echo json_encode(['error' => 'Error al interpretar los datos de url_listen_ports']);
        return;
    }

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