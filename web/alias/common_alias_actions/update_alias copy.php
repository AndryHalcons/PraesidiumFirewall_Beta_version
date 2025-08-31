<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

// Ruta local del archivo JSON de configuración
// Local path to the alias configuration JSON file
$path = '/var/www/config/alias.json';

//funciones de validacion de datos:
//data validation functions
require_once __DIR__ . '/validation_alias.php';
// Carga el contenido actual del archivo JSON
// Load the current content of the JSON file
function loadAliasData($path) {
    if (!file_exists($path)) return []; 
    $json = file_get_contents($path);
    return json_decode($json, true);
}

// Guarda los datos actualizados en el archivo JSON
// Save updated data to the JSON file
function saveAliasData($path, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); // Codifica con formato legible
                                                                            // Encode with readable formatting
    file_put_contents($path, $json); // Escribe el contenido en el archivo
                                     // Write content to file
}

// Actualiza una entrada del tipo alias_address
// Update an entry of type alias_address
function updateAliasAddress($data, &$aliasData) {
    validateSimply($data);
    validateIPandCIDR($data['content']);
    updateAliasAddressONgroups($data, $aliasData);
    updateAliasEntry('alias_address', $data, $aliasData);
}


// Actualiza una entrada del tipo alias_addr_group
// Update an entry of type alias_addr_group
function updateAliasAddrGroup($data, &$aliasData) {
    validateSimply($data);
    updateAliasEntry('alias_addr_group', $data, $aliasData);
}

// Actualiza una entrada del tipo alias_service
// Update an entry of type alias_service
function updateAliasService($data, &$aliasData) {
    validateSimply($data);
    validatePort($data['content']);
    updateAliasServiceONgroups($data, $aliasData);
    updateAliasEntry('alias_service', $data, $aliasData);
}


// Actualiza una entrada del tipo alias_service_group
// Update an entry of type alias_service_group
function updateAliasServiceGroup($data, &$aliasData) {
    validateSimply($data);
    updateAliasEntry('alias_service_group', $data, $aliasData);
}

// Función genérica para actualizar o insertar una entrada
// Generic function to update or insert an entry
function updateAliasEntry($key, $entry, &$aliasData) {
    if (!isset($aliasData[$key])) $aliasData[$key] = []; // Inicializa el array si no existe
                                                         // Initialize array if it doesn't exist
    $updated = false;
    foreach ($aliasData[$key] as &$item) {
        if ($item['id'] == $entry['id']) { // Busca por ID para actualizar
                                           // Search by ID to update
            $item = $entry;
            $updated = true;
            break;
        }
    }
    if (!$updated) {
        $aliasData[$key][] = $entry; // Si no se actualizó, se agrega como nuevo
                                     // If not updated, add as new entry
    }
}

// Procesa la solicitud entrante
// Process the incoming request
$requestBody = file_get_contents('php://input'); // Obtiene el cuerpo del POST
                                                 // Get POST body
$inputData = json_decode($requestBody, true);    // Decodifica el JSON recibido
                                                 // Decode received JSON

$aliasData = loadAliasData($path); // Carga los datos actuales del archivo
                                   // Load current data from file

// Determina qué tipo de alias se está actualizando
// Determine which alias type is being updated
if (isset($inputData['alias_address'])) {
    updateAliasAddress($inputData['alias_address'], $aliasData);
} elseif (isset($inputData['alias_addr_group'])) {
    updateAliasAddrGroup($inputData['alias_addr_group'], $aliasData);
} elseif (isset($inputData['alias_service'])) {
    updateAliasService($inputData['alias_service'], $aliasData);
} elseif (isset($inputData['alias_service_group'])) {
    updateAliasServiceGroup($inputData['alias_service_group'], $aliasData);
} else {
    http_response_code(400); // Código de error si no se encuentra alias válido
                             // Error code if no valid alias found
    echo json_encode(['error' => 'No se encontró un alias válido en el JSON.']);
    exit;
}

// Guarda los datos actualizados en el archivo
// Save updated data to the file
saveAliasData($path, $aliasData);

// Devuelve respuesta de éxito
// Return success response
echo json_encode(['status' => 'ok', 'message' => 'Alias actualizado correctamente.']);
?>
