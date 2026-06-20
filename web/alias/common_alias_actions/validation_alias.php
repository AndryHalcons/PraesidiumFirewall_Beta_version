<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////// carga de json  /////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Importa el archivo de reglas Nftables actual para consultas
// Imports the current rules file for queries
function import_policy_nft_json() {
    $jsonPath = '/var/www/config/rules_nftables_human_viewer.json';

    if (!file_exists($jsonPath)) {
        return false;
    }

    $raw = file_get_contents($jsonPath);
    $NFTJsonData = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    return $NFTJsonData;
}


// Importa el archivo de reglas BPFILTER actual para consultas
// Imports the current rules file for queries
function import_policy_bpf_json() {
    $jsonPath = '/var/www/config/rules_bpfilter_human_viewer.json';

    if (!file_exists($jsonPath)) {
        return false;
    }

    $raw = file_get_contents($jsonPath);
    $BPFJsonData = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    return $BPFJsonData;
}


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////validate forms  /////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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

    // Validación de nombre duplicado en la misma sección
    if (file_exists($path)) {
        $jsonContent = file_get_contents($path);
        $aliasData = json_decode($jsonContent, true);

        if (isset($aliasData[$keyJson]) && is_array($aliasData[$keyJson])) {
            foreach ($aliasData[$keyJson] as $item) {
                // Si el nombre ya existe y no es el mismo ID -> error
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



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////// validate duplicate names  //////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Función para validar nombres duplicados en todas las familias de alias
// Function to validate duplicate names across all alias families
function validate_duplicate_names($data, $aliasData) {
    // Se recorta el nombre del alias que se quiere validar
    // Trim the name of the alias to be validated
    $newName = trim($data['name']);

    // Se obtiene el ID actual si existe (para excluirlo en caso de actualización)
    // Get the current ID if present (to exclude it in case of update)
    $currentId = isset($data['id']) ? intval($data['id']) : null;

    // Se recorren todas las secciones del JSON (direcciones, servicios, grupos, etc.)
    // Iterate through all sections of the JSON (addresses, services, groups, etc.)
    foreach ($aliasData as $section) {
        // Se recorren todas las entradas dentro de cada sección
        // Iterate through all entries within each section
        foreach ($section as $item) {
            // Se compara el nombre recortado con el nombre de cada entrada
            // Compare the trimmed name with each entry's name
            // Se excluye el alias actual si el ID coincide
            // Exclude the current alias if the ID matches
            if (
                isset($item['name']) &&
                trim($item['name']) === $newName &&
                isset($item['id']) &&
                intval($item['id']) !== $currentId
            ) {
                // Si hay coincidencia con otro alias, se devuelve error 409
                // If there's a match with another alias, return HTTP 409 error
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



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////// validate ip or cidr  ///////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////// validate ports  ////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////// Funciones que evitan mezclar ipv4 e ipv6 en los alias /////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function contains_mixed_ip_versions_nft(string $ipList): bool {
    $allVersions = [];

    $entries = preg_split('/[\s,]+/', $ipList, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($entries as $entry) {
        $version = detect_ip_version($entry);

        if ($version === 'IPv4' || $version === 'IPv6') {
            $allVersions[] = $version;
        }
    }

    // Si no hay IPs válidas, no hay mezcla -> se permite
    if (count($allVersions) === 0) {
        return true;
    }

    // Si hay más de un tipo de IP -> mezcla -> no se permite
    return count(array_unique($allVersions)) === 1;
}

function detect_ip_version(string $input): string {
    $ip = explode('/', $input)[0]; // Elimina la máscara si es CIDR

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return 'IPv4';
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return 'IPv6';
    }

    return 'Desconocido';
}



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////// check if alias or ips  /////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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
    $ipList = [];

    foreach ($data['content'] as $item) {
	// Si está en alias_address -> válido
        if (in_array($item, $validNames, true)) {
            continue;
        }
		// Si es una IP válida -> válido
        if (filter_var($item, FILTER_VALIDATE_IP)) {
            $ipList[] = $item;
            continue;
        }
		// Si es una red CIDR válida -> válido
        if (strpos($item, '/') !== false) {
            [$ip, $mask] = explode('/', $item, 2);
            if (filter_var($ip, FILTER_VALIDATE_IP) && ctype_digit($mask) && (int)$mask >= 0 && (int)$mask <= 128) {
                $ipList[] = $item;
                continue;
            }
        }
		// Si no cumple ninguna condición -> inválido
        $invalid[] = $item;
    }

    if (!empty($invalid)) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode([
            'error' => "Los siguientes valores no existen en '$keyJson' ni son IP/CIDR válidos: " . implode(', ', $invalid)
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    // Verificar mezcla de versiones IP
    $ipString = implode(',', $ipList);
    if (!contains_mixed_ip_versions_nft($ipString)) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['error' => 'No se permite mezclar IPv4 e IPv6 en los campos IP'])); //linea 463
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
        // Si está en alias_service -> válido
        if (in_array($item, $validNames, true)) {
            continue;
        }
        // Si es un puerto válido -> válido
        if (ctype_digit($item) && (int)$item >= 1 && (int)$item <= 65535) {
            continue;
        }
        // Si no cumple ninguna condición -> inválido
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


/* individual
function is_object_in_policy($name) {
    error_log("DEBUG: Recibido name = '$name'");

    $rulesFile = '/var/www/config/rules_nftables_human_viewer.json';
    if (!file_exists($rulesFile)) return;

    $target = trim($name);
    if ($target === '') return; // No se compara alias vacío

    $json = file_get_contents($rulesFile);
    $rulesData = json_decode($json, true);
    if (!isset($rulesData['nftables']) || !is_array($rulesData['nftables'])) return;

    $fields = [
        'sport',
        'dport',
        'dnat.port',
        'snat.port',
        'dnat.addr',
        'snat.addr',
        'ip.daddr',
        'ip.saddr'
    ];

    $matchedRules = [];

    foreach ($rulesData['nftables'] as $entry) {
        if (!isset($entry['rule']) || !is_array($entry['rule'])) continue;

        $rule = $entry['rule'];

        foreach ($fields as $field) {
            if (!isset($rule[$field]) || !is_string($rule[$field]) || trim($rule[$field]) === '') continue;

            $values = array_map('trim', explode(',', $rule[$field]));
            //error_log("DEBUG: Revisando campo '$field' con valor = '{$rule[$field]}'");

            if (in_array($target, $values, true)) {
                //error_log("DEBUG: MATCH encontrado en regla '{$rule['name']}'");

                if (isset($rule['name']) && !in_array($rule['name'], $matchedRules)) {
                    $matchedRules[] = $rule['name'];
                }
                break;
            }
        }
    }

    if (!empty($matchedRules)) {
        http_response_code(409);
        echo json_encode(['error' => 'Object is used in policies NFTables "' . implode(', ', $matchedRules) . '"']);
        exit;
    }
}
*/
/* doble
function is_object_in_policy($name) {
    // DEBUG: Recibimos el nombre del objeto a verificar
    // DEBUG: Received the object name to check
    error_log("DEBUG: Recibido name = '$name'");

    $target = trim($name);
    // Si el nombre está vacío, no hacemos nada
    // If the name is empty, we do nothing
    if ($target === '') return;

    $matchedRules = [];

    // Verificación en el archivo de reglas NFTables
    // Check in the NFTables rules file
    $nftFile = '/var/www/config/rules_nftables_human_viewer.json';
    if (file_exists($nftFile)) {
        $json = file_get_contents($nftFile);
        $rulesData = json_decode($json, true);

        // Aseguramos que el bloque 'nftables' existe y es un array
        // Ensure the 'nftables' block exists and is an array
        if (isset($rulesData['nftables']) && is_array($rulesData['nftables'])) {
            $fields = [
                'sport',
                'dport',
                'dnat.port',
                'snat.port',
                'dnat.addr',
                'snat.addr',
                'ip.daddr',
                'ip.saddr'
            ];

            foreach ($rulesData['nftables'] as $entry) {
                if (!isset($entry['rule']) || !is_array($entry['rule'])) continue;

                $rule = $entry['rule'];

                foreach ($fields as $field) {
                    // Saltamos si el campo no existe o está vacío
                    // Skip if the field doesn't exist or is empty
                    if (!isset($rule[$field]) || !is_string($rule[$field]) || trim($rule[$field]) === '') continue;

                    $values = array_map('trim', explode(',', $rule[$field]));
                    // Comprobamos si el valor objetivo está en la lista
                    // Check if the target value is in the list
                    if (in_array($target, $values, true)) {
                        if (isset($rule['name']) && !in_array($rule['name'], $matchedRules)) {
                            $matchedRules[] = $rule['name'];
                        }
                        break;
                    }
                }
            }
        }
    }

    // Verificación en el archivo de reglas BPFilter
    // Check in the BPFilter rules file
    $bpFile = '/var/www/config/rules_bpfilter_human_viewer.json';
    if (file_exists($bpFile)) {
        $json = file_get_contents($bpFile);
        $bpData = json_decode($json, true);

        // Aseguramos que el bloque 'bpfilter' existe y es un array
        // Ensure the 'bpfilter' block exists and is an array
        if (isset($bpData['bpfilter']) && is_array($bpData['bpfilter'])) {
            $fields = ['source', 'sport', 'destination', 'dport'];

            foreach ($bpData['bpfilter'] as $entry) {
                if (!isset($entry['rule']) || !is_array($entry['rule'])) continue;

                $rule = $entry['rule'];

                foreach ($fields as $field) {
                    // Saltamos si el campo no existe o está vacío
                    // Skip if the field doesn't exist or is empty
                    if (!isset($rule[$field]) || !is_string($rule[$field]) || trim($rule[$field]) === '') continue;

                    $values = array_map('trim', explode(',', $rule[$field]));
                    // Comprobamos si el valor objetivo está en la lista
                    // Check if the target value is in the list
                    if (in_array($target, $values, true)) {
                        if (isset($rule['name']) && !in_array($rule['name'], $matchedRules)) {
                            $matchedRules[] = $rule['name'];
                        }
                        break;
                    }
                }
            }
        }
    }

    //  Si se encontraron coincidencias, devolvemos error 409 con los nombres de las reglas
    //  If matches were found, return error 409 with the rule names
    if (!empty($matchedRules)) {
        http_response_code(409);
        echo json_encode(['error' => 'Object is used in policies: "' . implode(', ', $matchedRules) . '"']);
        exit;
    }
}
*/


function is_object_in_policy($name) {
    // DEBUG: Recibimos el nombre del objeto a verificar
    // DEBUG: Received the object name to check
    error_log("DEBUG: Recibido name = '$name'");

    $target = trim($name);
    // Si el nombre está vacío, no hacemos nada
    // If the name is empty, we do nothing
    if ($target === '') return;

    $matchedRules = [];

    // Verificación en el archivo de reglas NFTables
    // Check in the NFTables rules file
    $nftFile = '/var/www/config/rules_nftables_human_viewer.json';
    if (file_exists($nftFile)) {
        $json = file_get_contents($nftFile);
        $rulesData = json_decode($json, true);

        if (isset($rulesData['nftables']) && is_array($rulesData['nftables'])) {
            $fields = [
                'sport',
                'dport',
                'dnat.port',
                'snat.port',
                'dnat.addr',
                'snat.addr',
                'ip.daddr',
                'ip.saddr'
            ];

            foreach ($rulesData['nftables'] as $entry) {
                if (!isset($entry['rule']) || !is_array($entry['rule'])) continue;

                $rule = $entry['rule'];

                foreach ($fields as $field) {
                    if (!isset($rule[$field]) || !is_string($rule[$field]) || trim($rule[$field]) === '') continue;

                    $values = array_map('trim', explode(',', $rule[$field]));
                    if (in_array($target, $values, true)) {
                        if (isset($rule['name']) && !in_array($rule['name'], $matchedRules)) {
                            $matchedRules[] = $rule['name'];
                        }
                        break;
                    }
                }
            }
        }
    }

    // Verificación en el archivo de reglas BPFilter
    // Check in the BPFilter rules file
    $bpFile = '/var/www/config/rules_bpfilter_human_viewer.json';
    if (file_exists($bpFile)) {
        $json = file_get_contents($bpFile);
        $bpData = json_decode($json, true);

        if (isset($bpData['bpfilter']) && is_array($bpData['bpfilter'])) {
            $fields = ['source', 'sport', 'destination', 'dport'];

            foreach ($bpData['bpfilter'] as $entry) {
                if (!isset($entry['rule']) || !is_array($entry['rule'])) continue;

                $rule = $entry['rule'];

                foreach ($fields as $field) {
                    if (!isset($rule[$field]) || !is_string($rule[$field]) || trim($rule[$field]) === '') continue;

                    $values = array_map('trim', explode(',', $rule[$field]));
                    if (in_array($target, $values, true)) {
                        if (isset($rule['name']) && !in_array($rule['name'], $matchedRules)) {
                            $matchedRules[] = $rule['name'];
                        }
                        break;
                    }
                }
            }
        }
    }

    // Verificación en el archivo de políticas Squid
    // Check in the Squid policies file
    $squidFile = '/var/www/config/squid_config/squid_policies.json';
    if (file_exists($squidFile)) {
        $json = file_get_contents($squidFile);
        $squidData = json_decode($json, true);

        // Aseguramos que el bloque 'url_policies' existe y es un array
        // Ensure the 'url_policies' block exists and is an array
        if (isset($squidData['squid']['url_policies']) && is_array($squidData['squid']['url_policies'])) {
            foreach ($squidData['squid']['url_policies'] as $entry) {
                if (!isset($entry['rule']) || !is_array($entry['rule'])) continue;

                $rule = $entry['rule'];

                // Verificamos si el campo ip_addr_group coincide
                // Check if the ip_addr_group field matches
                if (isset($rule['ip_addr_group']) && is_string($rule['ip_addr_group'])) {
                    $values = array_map('trim', explode(',', $rule['ip_addr_group']));
                    if (in_array($target, $values, true)) {
                        // Usamos el campo 'profile' como nombre de la regla si existe
                        // Use the 'profile' field as rule name if available
                        $ruleName = isset($rule['profile']) ? $rule['profile'] : 'SquidRule';
                        if (!in_array($ruleName, $matchedRules)) {
                            $matchedRules[] = $ruleName;
                        }
                    }
                }
            }
        }
    }

    // Si se encontraron coincidencias, devolvemos error 409 con los nombres de las reglas
    // If matches were found, return error 409 with the rule names
    if (!empty($matchedRules)) {
        http_response_code(409);
        echo json_encode(['error' => 'Object is used in policies: "' . implode(', ', $matchedRules) . '"']);
        exit;
    }
}


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////// update nftables and policy alias names  ////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//funcion que comprueba previamente a los borrados si nombre y id existen para evitar manipulaciones del front
//function that checks before deletions if name and id exist to avoid front-end manipulations
function match_name_id($data, $id, $name) {
    foreach (['alias_address', 'alias_addr_group', 'alias_service', 'alias_service_group'] as $family) {
        if (!isset($data[$family]) || !is_array($data[$family])) continue;

        foreach ($data[$family] as $item) {
            if (
                isset($item['id'], $item['name']) &&
                intval($item['id']) === intval($id) &&
                trim($item['name']) === trim($name)
            ) {
                return; // Coincidencia válida encontrada
            }
        }
    }

    http_response_code(400);
    echo json_encode(['error' => "Alias con id '$id' y nombre '$name' no existe"]);
    exit;
}


//obtiene el "name" que tenia antiguamente si fuese un update, util para actualizar otros archivos con el mismo name.
//gets the "name" it had before if it was an update, useful for updating other files with the same name.
function getAliasOldNameById(array $aliasData, string $sectionKey, int $id): ?string {
    if (!isset($aliasData[$sectionKey]) || !is_array($aliasData[$sectionKey])) {
        return null;
    }

    foreach ($aliasData[$sectionKey] as $entry) {
        if (isset($entry['id']) && (int)$entry['id'] === $id) {
            return $entry['name'] ?? null;
        }
    }

    return null;
}


/*
function update_name_on_rules(string $newName, int $id, string $sectionKey): void {
    $aliasPath = '/var/www/config/alias.json';
    $nftPath = '/var/www/config/rules_nftables_human_viewer.json';
    $bpfPath = '/var/www/config/rules_bpfilter_human_viewer.json';

    $aliasData = loadAliasData($aliasPath);
    $oldName = getAliasOldNameById($aliasData, $sectionKey, $id);

    if ($oldName === null || $oldName === $newName) {
        return; // No hay cambio de nombre
    }

    // NFTables
    $nftData = import_policy_nft_json();
    if (is_array($nftData) && isset($nftData['nftables'])) {
        foreach ($nftData['nftables'] as &$entry) {
            if (!isset($entry['rule']) || !is_array($entry['rule'])) continue;

            $fields = [
                'ip.saddr', 'sport', 'ip.daddr', 'dport',
                'dnat.addr', 'snat.addr', 'snat.port', 'dnat.port'
            ];

            foreach ($fields as $field) {
                if (isset($entry['rule'][$field]) && is_string($entry['rule'][$field])) {
                    $entry['rule'][$field] = replaceAliasInField($entry['rule'][$field], $oldName, $newName);
                }
            }
        }

        // Guardar cambios en NFTables
        file_put_contents($nftPath, json_encode($nftData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    // BPFILTER
    $bpfData = import_policy_bpf_json();
    if (is_array($bpfData) && isset($bpfData['bpfilter'])) {
        foreach ($bpfData['bpfilter'] as &$entry) {
            if (!isset($entry['rule']) || !is_array($entry['rule'])) continue;

            $fields = ['source', 'sport', 'destination', 'dport'];

            foreach ($fields as $field) {
                if (isset($entry['rule'][$field]) && is_string($entry['rule'][$field])) {
                    $entry['rule'][$field] = replaceAliasInField($entry['rule'][$field], $oldName, $newName);
                }
            }
        }

        // Guardar cambios en BPFILTER
        file_put_contents($bpfPath, json_encode($bpfData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
*/


function update_name_on_rules(string $newName, int $id, string $sectionKey): void {
    $aliasPath = '/var/www/config/alias.json';
    $nftPath = '/var/www/config/rules_nftables_human_viewer.json';
    $bpfPath = '/var/www/config/rules_bpfilter_human_viewer.json';
    $squidPath = '/var/www/config/squid_config/squid_policies.json';

    $aliasData = loadAliasData($aliasPath);
    $oldName = getAliasOldNameById($aliasData, $sectionKey, $id);

    // Si no hay cambio de nombre, salimos
    // If there's no name change, exit
    if ($oldName === null || $oldName === $newName) {
        return;
    }

    //  NFTables
    //  NFTables
    $nftData = import_policy_nft_json();
    if (is_array($nftData) && isset($nftData['nftables'])) {
        foreach ($nftData['nftables'] as &$entry) {
            if (!isset($entry['rule']) || !is_array($entry['rule'])) continue;

            $fields = [
                'ip.saddr', 'sport', 'ip.daddr', 'dport',
                'dnat.addr', 'snat.addr', 'snat.port', 'dnat.port'
            ];

            foreach ($fields as $field) {
                if (isset($entry['rule'][$field]) && is_string($entry['rule'][$field])) {
                    $entry['rule'][$field] = replaceAliasInField($entry['rule'][$field], $oldName, $newName);
                }
            }
        }

        // Guardar cambios en NFTables
        // Save changes to NFTables
        file_put_contents($nftPath, json_encode($nftData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    //  BPFILTER
    //  BPFILTER
    $bpfData = import_policy_bpf_json();
    if (is_array($bpfData) && isset($bpfData['bpfilter'])) {
        foreach ($bpfData['bpfilter'] as &$entry) {
            if (!isset($entry['rule']) || !is_array($entry['rule'])) continue;

            $fields = ['source', 'sport', 'destination', 'dport'];

            foreach ($fields as $field) {
                if (isset($entry['rule'][$field]) && is_string($entry['rule'][$field])) {
                    $entry['rule'][$field] = replaceAliasInField($entry['rule'][$field], $oldName, $newName);
                }
            }
        }

        // Guardar cambios en BPFILTER
        // Save changes to BPFILTER
        file_put_contents($bpfPath, json_encode($bpfData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    //  SQUID
    //  SQUID
    if (file_exists($squidPath)) {
        $squidData = json_decode(file_get_contents($squidPath), true);

        // Verificamos que existan políticas de URL
        // Check that URL policies exist
        if (is_array($squidData) && isset($squidData['squid']['url_policies'])) {
            foreach ($squidData['squid']['url_policies'] as &$entry) {
                if (!isset($entry['rule']) || !is_array($entry['rule'])) continue;

                // Solo modificamos el campo ip_addr_group si es una cadena
                // Only modify ip_addr_group field if it's a string
                if (isset($entry['rule']['ip_addr_group']) && is_string($entry['rule']['ip_addr_group'])) {
                    $entry['rule']['ip_addr_group'] = replaceAliasInField($entry['rule']['ip_addr_group'], $oldName, $newName);
                }
            }

            // Guardar cambios en Squid
            // Save changes to Squid
            file_put_contents($squidPath, json_encode($squidData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}


function replaceAliasInField(string $fieldValue, string $oldAlias, string $newAlias): string {
    $parts = array_map('trim', explode(',', $fieldValue));
    foreach ($parts as &$part) {
        if ($part === $oldAlias) {
            $part = $newAlias;
        }
    }
    return implode(',', $parts);
}

