<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}




//valida que los datos recibiods son validos asigna id si es nuevo, y comprueba que no haya nombre repetidos
//validates that the data received is valid assigns id if it is new, and checks that there are no duplicate names

function validateSimply($data, $path, $keyJson) {
    // Verifica que los campos requeridos estén presentes
    if (!isset($data['name'], $data['content'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Required fields are missing']);
        exit;
    }

    // Validación separada del campo 'id'
    if (!is_numeric($data['id'])) {
        $data['id'] = getNextID($data, $path, $keyJson);
    }

    // Validación de los otros campos
    if (!is_string($data['name']) || !is_string($data['content'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid Data']);
        exit;
    }

    // Validación de longitud del nombre
    if (strlen($data['name']) >= 30) {
        http_response_code(400);
        echo json_encode(['error' => 'Name Max 30 characters']);
        exit;
    }

    // 🔹 Validación de nombre duplicado en la misma sección
    if (file_exists($path)) {
        $jsonContent = file_get_contents($path);
        $aliasData = json_decode($jsonContent, true);

        if (isset($aliasData[$keyJson]) && is_array($aliasData[$keyJson])) {
            foreach ($aliasData[$keyJson] as $item) {
                // Si el nombre ya existe y no es el mismo ID → error
                if (
                    isset($item['name']) &&
                    $item['name'] === $data['name'] &&
                    (string)$item['id'] !== (string)$data['id']
                ) {
                    http_response_code(409);
                    echo json_encode(['error' => 'Alias name already exists']);
                    exit;
                }
            }
        }
    }

    return $data;
}

function validate_duplicate_names($data, $aliasData) {
    // Se recorta el nombre del alias que se quiere validar
    // Trim the name of the alias to be validated
    $newName = trim($data['name']);

    // Se recorren todas las secciones del JSON (direcciones, servicios, grupos, etc.)
    // Iterate through all sections of the JSON (addresses, services, groups, etc.)
    foreach ($aliasData as $section) {
        // Se recorren todas las entradas dentro de cada sección
        // Iterate through all entries within each section
        foreach ($section as $item) {
            // Se compara el nombre recortado con el nombre de cada entrada
            // Compare the trimmed name with each entry's name
            if (trim($item['name']) === $newName) {
                // Si hay coincidencia, se devuelve error 409 por nombre duplicado
                // If there's a match, return HTTP 409 error due to duplicate name
                http_response_code(409);
                echo json_encode(['error' => 'Alias name must be unique across all families']);
                exit;
            }
        }
    }
}





//si viene con id erroneo le generamos uno nuevo, util para crear nuevas entradas o verificar updates
//If it comes with an incorrect ID, we generate a new one, useful for creating new entries or checking for updates
function getNextID($data, $path, $keyJson) {
    // Verifica que el archivo exista
    if (!file_exists($path)) {
        http_response_code(500);
        echo json_encode(['error' => 'Data file not found']);
        exit;
    }

    // Lee y decodifica el JSON
    $jsonContent = file_get_contents($path);
    $aliasData = json_decode($jsonContent, true);

    if (!is_array($aliasData)) {
        http_response_code(500);
        echo json_encode(['error' => 'Data file is not valid JSON']);
        exit;
    }

    // Verifica que la clave exista y sea un array
    if (!isset($aliasData[$keyJson]) || !is_array($aliasData[$keyJson])) {
        http_response_code(500);
        echo json_encode(['error' => "Alias section '$keyJson' is not a valid array"]);
        exit;
    }

    $aliasList = $aliasData[$keyJson];

    // Recolecta todos los IDs existentes, normalizados como enteros
    $existingIDs = [];
    foreach ($aliasList as $entry) {
        if (isset($entry['id']) && is_numeric($entry['id'])) {
            $existingIDs[] = (int)$entry['id'];
        }
    }

    // Busca el primer ID libre empezando desde 1
    $newID = 1;
    while (in_array($newID, $existingIDs)) {
        $newID++;
    }

    return $newID;
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


//valida que son puertos y rangos de puertos validos
//validates that they are valid ports and port ranges
function validatePort($port) {
    // Verifica si es un rango (contiene guion)
    // Check if it's a range (contains a dash)
    if (strpos($port, '-') !== false) {
        // Divide el rango en dos partes
        // Split the range into two parts
        list($start, $end) = explode('-', $port, 2);

        // Convierte ambos extremos en enteros
        // Convert both ends to integers
        $start = (int)trim($start);
        $end = (int)trim($end);

        // Verifica que ambos extremos estén en el rango válido y que el inicio sea menor o igual al final
        // Check that both ends are within valid port range and start is less than or equal to end
        if ($start < 1 || $start > 65535 || $end < 1 || $end > 65535 || $start > $end) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid port range']);
            exit;
        }
    } else {
        // Verifica que sea un puerto único válido
        // Check that it's a valid single port
        if (!is_numeric($port) || $port < 1 || $port > 65535) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid port number']);
            exit;
        }
    }
}


// Verifica si el name de alias_address está en el content de alias_addr_group
// Checks if alias_address name is inside alias_addr_group content
function isAliasAddressNameInGroupContent($data, $id) {
    // Buscar el name del alias_address por su ID
    $aliasName = null;
    foreach ($data['alias_address'] as $item) {
        if ((int)$item['id'] === (int)$id) {
            $aliasName = $item['name'];
            break;
        }
    }

    if (!$aliasName) {
        http_response_code(404);
        echo json_encode(['error' => 'Alias no encontrado']);
        exit;
    }

    // Recorrer todos los grupos y guardar todos los que contengan el alias
    $gruposCoincidentes = [];
    foreach ($data['alias_addr_group'] as $group) {
        if (in_array($aliasName, $group['content'], true)) {
            $gruposCoincidentes[] = $group['name'];
        }
    }

    // Si hay coincidencias, devolver todos los nombres
    if (!empty($gruposCoincidentes)) {
        http_response_code(409);
        echo json_encode([
            'error'  => 'El alias está siendo usado en los siguientes grupos: ' 
                        . implode(', ', $gruposCoincidentes) 
                        . ' y no puede eliminarse',
            'grupos' => $gruposCoincidentes
        ]);
        exit;
    }

    // Si no hay conflicto, continuar
    return;
}





// Verifica si el name de alias_service está en el content de alias_service_group
// Checks if alias_service name is inside alias_service_group content
function isAliasServiceNameInGroupContent($data, $id) {
    // Buscar el name del alias_service por su ID
    $aliasName = null;
    foreach ($data['alias_service'] as $item) {
        if ((int)$item['id'] === (int)$id) {
            $aliasName = $item['name'];
            break;
        }
    }

    // Si no se encuentra el alias, devolver error
    if (!$aliasName) {
        http_response_code(404);
        echo json_encode(['error' => 'Alias de servicio no encontrado']);
        exit;
    }

    // Recorrer todos los grupos y acumular coincidencias
    $gruposCoincidentes = [];
    foreach ($data['alias_service_group'] as $group) {
        if (in_array($aliasName, $group['content'], true)) {
            $gruposCoincidentes[] = $group['name'];
        }
    }

    // Si hay coincidencias, devolver todos los nombres
    if (!empty($gruposCoincidentes)) {
        http_response_code(409);
        echo json_encode([
            'error'  => 'El alias de servicio está siendo usado en los siguientes grupos: ' 
                        . implode(', ', $gruposCoincidentes) 
                        . ' y no puede eliminarse',
            'grupos' => $gruposCoincidentes
        ]);
        exit;
    }

    // Si no hay conflicto, continuar
    return;
}






function isAliasAddressNameIP_ORserviceAlias($data, $path) {
    $keyJson = 'alias_address';

    // --- Normalizar $data['content'] ---
    if (isset($data['content'])) {
        $normalizado = [];

        if (is_string($data['content'])) {
            $normalizado = array_map('trim', explode(',', $data['content']));
        } elseif (is_array($data['content'])) {
            foreach ($data['content'] as $c) {
                if (is_string($c)) {
                    if (strpos($c, ',') !== false) {
                        $partes = array_map('trim', explode(',', $c));
                        $normalizado = array_merge($normalizado, $partes);
                    } else {
                        $normalizado[] = trim($c);
                    }
                }
            }
        }

        $data['content'] = array_values(array_filter(array_unique($normalizado), 'strlen'));
    } else {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['error' => "El campo 'content' no existe"]));
    }
    // --- Fin normalización ---

    // Cargar JSON
    if (!file_exists($path)) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['error' => 'No se encontró el archivo de datos']));
    }

    $aliasData = json_decode(file_get_contents($path), true);

    if (!isset($aliasData[$keyJson]) || !is_array($aliasData[$keyJson])) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['error' => "No se encontró la sección '$keyJson' o no es válida"]));
    }

    // Lista de nombres válidos
    $validNames = array_column($aliasData[$keyJson], 'name');

    // Buscar inválidos (permitiendo IPs y CIDR)
    $invalid = [];
    foreach ($data['content'] as $item) {
        // Si está en alias_address → válido
        if (in_array($item, $validNames, true)) {
            continue;
        }
        // Si es una IP válida → válido
        if (filter_var($item, FILTER_VALIDATE_IP)) {
            continue;
        }
        // Si es una red CIDR válida → válido
        if (strpos($item, '/') !== false) {
            [$ip, $mask] = explode('/', $item, 2);
            if (filter_var($ip, FILTER_VALIDATE_IP) && ctype_digit($mask) && (int)$mask >= 0 && (int)$mask <= 128) {
                continue;
            }
        }
        // Si no cumple ninguna condición → inválido
        $invalid[] = $item;
    }

    if (!empty($invalid)) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode([
            'error' => "Los siguientes valores no existen en '$keyJson' ni son IP/CIDR válidos: " . implode(', ', $invalid)
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    return true;
}


function isAliasServiceNamePort_ORserviceAlias($data, $path) {
    $keyJson = 'alias_service';

    // --- Normalizar $data['content'] directamente aquí ---
    if (isset($data['content'])) {
        $normalizado = [];

        if (is_string($data['content'])) {
            $normalizado = array_map('trim', explode(',', $data['content']));
        } elseif (is_array($data['content'])) {
            foreach ($data['content'] as $c) {
                if (is_string($c)) {
                    if (strpos($c, ',') !== false) {
                        $partes = array_map('trim', explode(',', $c));
                        $normalizado = array_merge($normalizado, $partes);
                    } else {
                        $normalizado[] = trim($c);
                    }
                }
            }
        }

        $data['content'] = array_values(array_filter(array_unique($normalizado), 'strlen'));
    } else {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['error' => "El campo 'content' no existe"]));
    }
    // --- Fin normalización ---

    // Cargar JSON
    if (!file_exists($path)) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['error' => 'No se encontró el archivo de datos']));
    }

    $aliasData = json_decode(file_get_contents($path), true);

    if (!isset($aliasData[$keyJson]) || !is_array($aliasData[$keyJson])) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['error' => "No se encontró la sección '$keyJson' o no es válida"]));
    }

    // Lista de nombres válidos
    $validNames = array_column($aliasData[$keyJson], 'name');

    // Buscar inválidos (permitiendo puertos válidos)
    $invalid = [];
    foreach ($data['content'] as $item) {
        // Si está en alias_service → válido
        if (in_array($item, $validNames, true)) {
            continue;
        }
        // Si es un puerto válido → válido
        if (ctype_digit($item) && (int)$item >= 1 && (int)$item <= 65535) {
            continue;
        }
        // Si no cumple ninguna condición → inválido
        $invalid[] = $item;
    }

    if (!empty($invalid)) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode([
            'error' => "Los siguientes valores no existen en '$keyJson': " . implode(', ', $invalid)
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    return true;
}



