<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

// Ruta local del archivo JSON de configuración
// Local path to the alias configuration JSON file
$path = '/var/www/config/alias.json';

//validaciones de datos
// data validations
//funciones de validacion de datos:
//data validation functions

//valida que los datos recibiods son validos
function validateSimply($data) {
    if (!isset($data['id'], $data['name'], $data['content'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Required fields are missing']);
        exit;
    }
    if (!is_numeric($data['id']) || !is_string($data['name']) || !is_string($data['content'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid Data']);
        exit;
    }
}

//ipv4, ipv6, ipv4 con cidr, ipv6 con cidr,(comprobar que tanto ip como cidr son validos) (comprobar que tanto ip como cidr son validos)
//ipv4, ipv6, ipv4 con cidr, ipv6 con cidr (verify that both ip and cidr are valid) (verify that both ip and cidr are valid)
function validateIPandCIDR($content) {
    // Verifica si el contenido no tiene una barra, lo que indica que no es una notación CIDR
    // Checks if the content doesn't contain a slash, meaning it's not CIDR notation
    if (strpos($content, '/') === false) {
        // Valida si la IP es válida (puede ser IPv4 o IPv6)
        // Validates if the IP is valid (can be IPv4 or IPv6)
        if (!filter_var($content, FILTER_VALIDATE_IP)) {
            // Si no es válida, responde con error 400 y mensaje en JSON
            // If it's not valid, respond with HTTP 400 and a JSON error message
            http_response_code(400);
            echo json_encode(['error' => 'Invalid IP']);
            exit;
        }
        // Si la IP es válida y no es CIDR, termina la función
        // If the IP is valid and not CIDR, exit the function
        return;
    }
    // Divide el contenido en IP y máscara usando la barra como separador
    // Split the content into IP and mask using the slash as separator
    [$ip, $mask] = explode('/', $content, 2);
    // Valida si la IP base es válida
    // Validate if the base IP is valid
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid CIDR']);
        exit;
    }
    // Determina el valor máximo permitido para la máscara: 128 si es IPv6, 32 si es IPv4
    // Determine the maximum allowed value for the mask: 128 for IPv6, 32 for IPv4
    $maxMask = strpos($ip, ':') !== false ? 128 : 32;

    // Verifica que la máscara sea numérica y esté dentro del rango válido
    // Check that the mask is numeric and within the valid range
    if (!is_numeric($mask) || $mask < 0 || $mask > $maxMask) {
        http_response_code(400);
        echo json_encode(['error' => 'Máscara CIDR inválida']);
        exit;
    }
}


// Actualiza el nombre referenciado en alias_addr_group si ha cambiado el name del alias_address
// Updates referenced name in alias_addr_group if it has changed
function updateAliasAddressONgroups($data, &$aliasData) {
    $id = intval($data['id']);
    $newName = $data['name'];

    // Buscar el nombre anterior del alias_address por ID
    $oldName = null;
    foreach ($aliasData['alias_address'] as $item) {
        if (intval($item['id']) === $id) {
            $oldName = $item['name'];
            break;
        }
    }

    // Si no se encuentra el alias, no hay nada que actualizar
    if (!$oldName || $oldName === $newName) return;

    // Recorrer los grupos y actualizar el nombre si está referenciado
    foreach ($aliasData['alias_addr_group'] as &$group) {
        foreach ($group['content'] as &$entryName) {
            if ($entryName === $oldName) {
                $entryName = $newName;
            }
        }
    }
}

// Actualiza el nombre referenciado en alias_service_group si ha cambiado
// Updates referenced name in alias_service_group if it has changed
function updateAliasServiceONgroups($data, &$aliasData) {
    $id = intval($data['id']);
    $newName = $data['name'];

    // Buscar el nombre anterior del alias_service por ID
    $oldName = null;
    foreach ($aliasData['alias_service'] as $item) {
        if (intval($item['id']) === $id) {
            $oldName = $item['name'];
            break;
        }
    }

    // Si no se encuentra el alias o el nombre no ha cambiado, no hay nada que hacer
    if (!$oldName || $oldName === $newName) return;

    // Recorrer los grupos y actualizar el nombre si está referenciado
    foreach ($aliasData['alias_service_group'] as &$group) {
        foreach ($group['content'] as &$entryName) {
            if ($entryName === $oldName) {
                $entryName = $newName;
            }
        }
    }
}



function validatePort($port) {
    // Verifica que sea numérico y esté en el rango válido de puertos
    //Verify that it is numeric and in the valid range of ports
    if (!is_numeric($port) || $port < 1 || $port > 65535) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid port number']);
        exit;
    }
}

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
