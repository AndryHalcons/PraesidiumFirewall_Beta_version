<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}
$path = '/var/www/config/alias.json';
// Ruta local del archivo JSON de configuración
// Local path to the alias configuration JSON file


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

// Guarda los datos actualizados en el archivo JSON (tambien normaliza los arrays)
// Save updated data to the JSON file (also normalizes arrays)
function saveAliasData($path, $data) {
    foreach ($data as $sectionKey => &$section) {
        if (is_array($section)) {
            foreach ($section as &$entry) {
                if (isset($entry['content'])) {
                    $normalizado = [];

                    // Si viene como string → lo partimos por comas
                    if (is_string($entry['content'])) {
                        $normalizado = array_map('trim', explode(',', $entry['content']));
                    }
                    // Si viene como array
                    elseif (is_array($entry['content'])) {
                        foreach ($entry['content'] as $c) {
                            if (is_string($c)) {
                                // Si el string tiene comas, lo partimos
                                if (strpos($c, ',') !== false) {
                                    $partes = array_map('trim', explode(',', $c));
                                    $normalizado = array_merge($normalizado, $partes);
                                } else {
                                    $normalizado[] = trim($c);
                                }
                            }
                        }
                    }

                    // Eliminar duplicados y vacíos
                    $normalizado = array_values(array_filter(array_unique($normalizado), 'strlen'));

                    // Guardar normalizado
                    $entry['content'] = $normalizado;
                }
            }
        }
    }

    // Guardar en JSON
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    file_put_contents($path, $json);
}







// Actualiza una entrada del tipo alias_address
// Update an entry of type alias_address
function updateAliasAddress($data, &$aliasData, $path) {
    $keyJson = 'alias_address';
    $data = validateSimply($data, $path, $keyJson);
    validateIPandCIDR($data['content']);
    updateAliasAddressONgroups($data, $aliasData);
    updateAliasEntry('alias_address', $data, $aliasData);
}


// Actualiza una entrada del tipo alias_addr_group
// Update an entry of type alias_addr_group
function updateAliasAddrGroup($data, &$aliasData, $path) {
    $keyJson = 'alias_addr_group';
    $data = validateSimply($data, $path, $keyJson);
    updateAliasEntry('alias_addr_group', $data, $aliasData);
}

// Actualiza una entrada del tipo alias_service
// Update an entry of type alias_service
function updateAliasService($data, &$aliasData, $path) {
    $keyJson = 'alias_service';
    $data = validateSimply($data, $path, $keyJson);
    validatePort($data['content']);
    updateAliasServiceONgroups($data, $aliasData);
    updateAliasEntry('alias_service', $data, $aliasData);
}


// Actualiza una entrada del tipo alias_service_group
// Update an entry of type alias_service_group
function updateAliasServiceGroup($data, &$aliasData, $path) {
    $keyJson = 'alias_service_group';
    $data = validateSimply($data, $path, $keyJson);
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
    updateAliasAddress($inputData['alias_address'], $aliasData, $path);
} elseif (isset($inputData['alias_addr_group'])) {
    updateAliasAddrGroup($inputData['alias_addr_group'], $aliasData, $path);
} elseif (isset($inputData['alias_service'])) {
    updateAliasService($inputData['alias_service'], $aliasData, $path);
} elseif (isset($inputData['alias_service_group'])) {
    updateAliasServiceGroup($inputData['alias_service_group'], $aliasData, $path);
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
