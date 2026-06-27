<?php
require_once __DIR__ . '/../../common/security/session.php';
praesidium_session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////    Import Json to to consult  ///////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
// Importa el archivo de alias y lo devuelve como array
// Imports the alias file and returns it as an array
function import_alias_json() {
    $jsonPath = '/var/www/config/alias.json';

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

function import_squid_config_json() {
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


function import_squid_forms() {
    $jsonPath = '/var/www/backend/checks/system_data/default_forms/forms_squid.json';

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
////////////////////////////////////    form field review        /////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
//revisa los campos que contienen formularios
//check the fields that contain forms

function validation_form_field_review_policy(array $rule): void {
    $formConfig = import_squid_forms();
    $configJson = import_squid_config_json();

    if (!$formConfig || !$configJson) {
        // Error al cargar configuración
        // Error loading configuration
        echo json_encode(["error" => "No se pudo cargar la configuración del formulario o del sistema"]);
        exit;
    }

    // Validar campos tipo select
    // Validate select fields
    if (isset($formConfig['select'])) {
        foreach ($formConfig['select'] as $key => $validValues) {

            // Añadir dinámicamente perfiles de red e URL si aplica
            // Dynamically add network and URL profiles if applicable
            if ($key === 'ip_addr_group') {
                foreach ($configJson['squid']['url_networks_list_profile'] ?? [] as $entry) {
                    if (isset($entry['rule']['name'])) {
                        $validValues[] = $entry['rule']['name'];
                    }
                }
            }

            if ($key === 'profile') {
                foreach ($configJson['squid']['url_profile'] ?? [] as $entry) {
                    if (isset($entry['rule']['name'])) {
                        $validValues[] = $entry['rule']['name'];
                    }
                }
            }

            if (isset($rule[$key])) {
                $value = trim((string)$rule[$key]);
                if ($value === '') {
                    // vacío o solo espacios -> válido
                    // empty or whitespace -> valid
                    $rule[$key] = "";
                    continue;
                }
                if (!in_array($value, $validValues, true)) {
                    echo json_encode(["error" => "value in validation_form_field_review_select '{$value}' not found"]);
                    exit;
                }
            }
        }
    }

    // Validar campos tipo checkbox
    // Validate checkbox fields
    if (isset($formConfig['checkbox'])) {
        foreach ($formConfig['checkbox'] as $key => $options) {
            if (isset($rule[$key])) {
                $value = trim((string)$rule[$key]);
                if ($value === '') {
                    $rule[$key] = "";
                    continue;
                }
                if (!in_array($value, $options, true)) {
                    echo json_encode(["error" => "validation_form_field_review_checkbox '{$value}' not found"]);
                    exit;
                }
            }
        }
    }

    // Validar campos no editables (excepto 'id')
    // Validate not_editable fields (except 'id')
    if (isset($formConfig['not_editable'])) {
        foreach ($formConfig['not_editable'] as $key => $validValues) {
            if ($key === 'id') continue;
            if (isset($rule[$key])) {
                $value = $rule[$key];
                if (!in_array($value, $validValues, true)) {
                    echo json_encode(["error" => "ID validation_form_field_review_not_editable '{$value}' not found"]);
                    exit;
                }
            }
        }
    }
}





//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////    Validation policy        /////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
// Validar campos de una regla URL
// Validate fields of a URL rule
// Validar campos de una regla URL
// Validate fields of a URL rule
function validation_url_policies(array $rule): void {
    $ipGroup = $rule['ip_addr_group'] ?? '';
    $profile = $rule['profile'] ?? '';
    $action = $rule['action'] ?? '';

    // Si ip_addr_group no está vacío y no es "all", entonces profile no puede estar vacío
    // If ip_addr_group is not empty and not "all", then profile must not be empty
    if ($ipGroup !== '' && strtolower($ipGroup) !== 'all' && $profile === '') {
        echo json_encode(['error' => 'El campo "profile" no puede estar vacío si ip_addr_group no es "all"']);
        exit;
    }

    // Si ip_addr_group es "all", entonces profile debe estar vacío
    // If ip_addr_group is "all", then profile must be empty
    if (strtolower($ipGroup) === 'all' && $profile !== '') {
        echo json_encode(['error' => 'El campo "profile" debe estar vacío si ip_addr_group es "all"']);
        exit;
    }
    // El campo action debe ser "allow" o "deny"
    // The action field must be "allow" or "deny"
    if (!in_array(strtolower($rule['action'] ?? ''), ['allow', 'deny'])) {
        echo json_encode(['error' => 'El campo "action" debe contener "allow" o "deny"']);
        exit;
    }
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// IPV4 & IPV6 VALIDATION SECTION ////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//valida si es una ip individual sin CIDR
//validates if it is an individual IP without CIDR
function validate_is_ip_no_cidr(string $value): bool {
    // Si el valor está vacío o es null, lanzar error por seguridad  
    // If the value is empty or null, throw error for security reasons  
    if ($value === '' || $value === null) {
        echo json_encode(['error' => 'IP no puede estar vacía por seguridad, ya que también escucharías en la WAN']);
        exit;
    }

    // Validar si el valor es una IP sin CIDR  
    // Validate if the value is an IP without CIDR  
    if (filter_var($value, FILTER_VALIDATE_IP)) {
        return true;
    }

    // IP no válida -> lanzar error y terminar
    // Invalid IP -> throw error and exit
    echo json_encode(['error' => 'No se ha introducido una IP válida']);
    exit;
}




// Valida que las IPs o CIDRs tengan formato correcto
// Validates that IPs or CIDRs have correct format
function validate_ip_or_cidr(string $value): bool {
    $items = array_map('trim', explode(',', $value)); // Divide la cadena por comas

    foreach ($items as $item) {
        // Si es una IP válida (sin CIDR)
        if (filter_var($item, FILTER_VALIDATE_IP)) {
            continue;
        }

        // Si es una IP con CIDR
        if (preg_match('/^(.+)\/(\d{1,3})$/', $item, $matches)) {
            $ip = $matches[1];
            $cidr = (int)$matches[2];

            // Verifica que la IP base sea válida
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                return false;
            }

            // Verifica que el CIDR esté dentro del rango permitido
            if (
                (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $cidr >= 0 && $cidr <= 32) ||
                (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && $cidr >= 0 && $cidr <= 128)
            ) {
                continue;
            }

            return false;
        }

        // No es IP ni CIDR válido
        return false;
    }

    return true;
}
// Devuelve la primera IP o CIDR asociada a un alias definido en alias_address.
// Returns the first IP or CIDR linked to a named alias in alias_address.

function convert_alias_ip_to_ip(string $value): bool {
    if (trim($value) === '') {
        return true; 
    }
    $aliasJsonData = import_alias_json();

    if (!$aliasJsonData) {
        echo json_encode(["error" => "alias file not found or invalid"]);
        exit;
    }

    // DEBUG: ver exactamente qué valor llega y qué hay en el JSON
    /*
    error_log("Valor recibido: >" . $value . "<");
    foreach ($aliasJsonData['alias_address'] as $entry) {
        error_log("Comparando con: >" . $entry['name'] . "<");
    }
    */
    if (isset($aliasJsonData['alias_address'])) {
        foreach ($aliasJsonData['alias_address'] as $entry) {
            if (isset($entry['name']) && $entry['name'] === $value) {
                return true;
            }
        }
    }

    return false;
}


// Convierte IPs, alias y grupos de alias en una lista normalizada de redes IP únicas.
// Converts IPs, aliases, and alias groups into a normalized list of unique network addresses.
function convert_alias_group_to_Network_ips_multiple_IP(string $value): bool {
    $aliasJsonData = import_alias_json();

    // Verifica que se haya cargado correctamente el JSON  
    // Check that the JSON was loaded successfully  
    if (!$aliasJsonData) {
        echo json_encode(["error" => "alias file not found or invalid"]);
        exit;
    }

    // Divide la cadena por comas y elimina espacios  
    // Split the input string by commas and trim whitespace  
    $items = array_map('trim', explode(',', $value));

    foreach ($items as $item) {
        // Si es IP o CIDR válida, se acepta  
        // If it's a valid IP or CIDR, accept it  
        if (validate_ip_or_cidr($item)) {
            continue;
        }

        $foundGroup = false;

        // Verifica si el elemento es un grupo de alias  
        // Check if the item is an alias group  
        if (isset($aliasJsonData['alias_addr_group'])) {
            foreach ($aliasJsonData['alias_addr_group'] as $group) {
                if (isset($group['name']) && $group['name'] === $item) {
                    $foundGroup = true;

                    // Verifica cada alias dentro del grupo  
                    // Verify each alias inside the group  
                    foreach ($group['content'] as $aliasName) {
                        if (!convert_alias_ip_to_ip($aliasName)) {
                            // Si algún alias no es válido, lanza error  
                            // If any alias is invalid, throw error  
                            echo json_encode(["error" => "alias '{$aliasName}' in group '{$item}' is invalid"]);
                            exit;
                        }
                    }

                    break;
                }
            }
        }

        // Si no es grupo, lo tratamos como alias individual  
        // If it's not a group, treat it as an individual alias  
        if (!$foundGroup) {
            if (!convert_alias_ip_to_ip($item)) {
                // Si no se pudo resolver, se lanza error  
                // If resolution fails, throw an error  
                echo json_encode(["error" => "alias or group '{$item}' not found or invalid"]);
                exit;
            }
        }
    }

    // Si todo es válido, retorna true  
    // If everything is valid, return true  
    return true;
}

// checkea alias en objetos de red reales usando funciones auxiliares
// check aliases into real network objects using helper functions
function Main_convert_alias_object_to_network_object(array $rule): array {
    $ipFields = ['ip_addr_group'];
    foreach ($ipFields as $field) {
        if (isset($rule[$field])) {
            $value = trim($rule[$field]);
            // Si el valor es "all", lo damos por válido sin convertir
            // If the value is "all", we accept it as valid without conversion
            if (strtolower($value) === 'all') {
                continue;
            }
            // Llama a la función de conversión de grupos IP
            // Call the IP group conversion function
            convert_alias_group_to_Network_ips_multiple_IP($value);
        }
    }
    // Devuelve la regla original sin modificar
    // Return the original rule unchanged
    return $rule;
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// PORTS  VALIDATION SECTION ////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Valida que sea un puerto único válido
// Validates that it's a valid single port
function validatePort($port) {
    // Elimina espacios y convierte a entero
    $port = (int)trim($port);

    // Verifica que sea un número válido dentro del rango permitido
    if (!is_numeric($port) || $port < 1 || $port > 65535) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid port number']);
        exit;
    }
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// create Name & validate ID  ///////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


//Genera automáticamente un id
//Generates an automatic id
function check_create_id(array $rule, string $chain): array {
    // Si el campo 'id' existe y es numérico y no está vacío, no hacemos nada
    // If 'id' exists, is numeric, and not empty, do nothing
    if (isset($rule['id']) && is_numeric($rule['id']) && $rule['id'] !== '') {
        return $rule;
    }

    // Importar el JSON actual
    // Import current JSON
    $jsonData = import_squid_config_json();

    // Verificar que se haya cargado correctamente
    // Check that it was loaded correctly
    if (!is_array($jsonData) || !isset($jsonData['squid'][$chain])) {
        // Si no se puede acceder a la lista, asignamos ID 1 por defecto
        // If list is inaccessible, assign ID 1 by default
        $rule['id'] = '1';
        return $rule;
    }

    // Extraer la lista correspondiente
    // Extract the corresponding list
    $list = $jsonData['squid'][$chain];

    // Crear un conjunto de IDs existentes
    // Create a set of existing IDs
    $usedIds = [];
    foreach ($list as $entry) {
        $entryId = $entry['rule']['id'] ?? null;
        if (is_numeric($entryId)) {
            $usedIds[(int)$entryId] = true;
        }
    }

    // Buscar el primer ID libre empezando desde 1
    // Find the first free ID starting from 1
    $newId = 1;
    while (isset($usedIds[$newId])) {
        $newId++;
    }

    // Asignar el nuevo ID
    // Assign the new ID
    $rule['id'] = (string)$newId;

    // Devolver el rule actualizado
    // Return the updated rule
    return $rule;
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// Reassing position  //////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Reasignar posición en url_policies
// Reassign position in url_policies
function reassign_position(array $json, array $rule): array {
    // Extraer bloque url_policies
    // Extract url_policies block
    $block = $json['squid']['url_policies'] ?? [];
    $id = $rule['id'] ?? null;

    // Detectar posición original
    // Detect original position
    $originalPos = null;
    foreach ($block as $entry) {
        if (($entry['rule']['id'] ?? '') === $id) {
            $originalPos = isset($entry['rule']['position']) ? (int)$entry['rule']['position'] : null;
            break;
        }
    }

    // Obtener nueva posición
    // Get new position
    $targetPos = isset($rule['position']) && is_numeric($rule['position']) ? (int)$rule['position'] : null;

    // Si no hay posición, asignar la más baja disponible
    // If no position, assign the lowest available
    if ($targetPos === null) {
        $used = [];
        foreach ($block as $entry) {
            $pos = $entry['rule']['position'] ?? null;
            if (is_numeric($pos)) {
                $used[] = (int)$pos;
            }
        }
        $targetPos = 1;
        while (in_array($targetPos, $used)) {
            $targetPos++;
        }
        $rule['position'] = $targetPos;
    }

    // Separar reglas y excluir la actual
    // Separate rules and exclude the current one
    $others = [];
    foreach ($block as $entry) {
        if (($entry['rule']['id'] ?? '') !== $id) {
            $others[] = $entry;
        }
    }

    // Desplazar reglas según dirección del movimiento
    // Shift rules based on movement direction
    foreach ($others as &$entry) {
        $pos = isset($entry['rule']['position']) ? (int)$entry['rule']['position'] : null;
        if ($pos === null) continue;

        if ($originalPos !== null && $targetPos < $originalPos) {
            // Movimiento hacia arriba
            // Moving up
            if ($pos >= $targetPos && $pos < $originalPos) {
                $entry['rule']['position'] = $pos + 1;
            }
        } elseif ($originalPos !== null && $targetPos > $originalPos) {
            // Movimiento hacia abajo
            // Moving down
            if ($pos <= $targetPos && $pos > $originalPos) {
                $entry['rule']['position'] = $pos - 1;
            }
        } elseif ($originalPos === null) {
            // Nueva regla, no existía antes
            // New rule, didn't exist before
            if ($pos >= $targetPos) {
                $entry['rule']['position'] = $pos + 1;
            }
        }
    }

    // Insertar la regla dominante
    // Insert the dominant rule
    $others[] = ['rule' => $rule];

    // Reordenar por posición
    // Reorder by position
    usort($others, fn($a, $b) => ((int)$a['rule']['position']) <=> ((int)$b['rule']['position']));

    // Renumerar secuencialmente sin huecos
    // Renumber sequentially without gaps
    $pos = 1;
    foreach ($others as &$entry) {
        $entry['rule']['position'] = $pos++;
    }

    // Actualizar el JSON
    // Update the JSON
    $json['squid']['url_policies'] = $others;
    return $json;
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// validation deletes  //////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function validate_profile_delete($id, $chain) {
    $config = import_squid_config_json();
    if ($config === false || !isset($config['squid'][$chain])) {
        // Error al cargar la configuración o el chain no existe
        // Error loading configuration or chain does not exist
        echo json_encode(['error' => 'No se pudo cargar la configuración o el chain es inválido']);
        exit;
    }

    // Buscar el nombre del perfil por ID
    // Find the profile name by its ID
    $profileName = null;
    foreach ($config['squid'][$chain] as $entry) {
        if (($entry['rule']['id'] ?? '') === $id) {
            $profileName = $entry['rule']['name'] ?? null;
            break;
        }
    }

    if ($profileName === null) {
        // No se encontró el perfil con ese ID
        // Profile with that ID was not found
        echo json_encode(['error' => 'No se encontró el perfil con ese ID']);
        exit;
    }

    // Verificar si el perfil está en uso en url_policies (profile o ip_addr_group)
    // Check if the profile is used in url_policies (either in 'profile' or 'ip_addr_group')
    $policies = $config['squid']['url_policies'] ?? [];
    $usedInRules = [];

    foreach ($policies as $policy) {
        $rule = $policy['rule'] ?? [];
        if (($rule['profile'] ?? '') === $profileName || ($rule['ip_addr_group'] ?? '') === $profileName) {
            $usedInRules[] = $rule['id'] ?? 'desconocido'; // unknown
        }
    }

    if (!empty($usedInRules)) {
        // El perfil está en uso, no se puede borrar
        // Profile is in use, cannot be deleted
        echo json_encode([
            'error' => 'El perfil no se puede borrar porque está en uso en las siguientes Policy ID: ' . implode(', ', $usedInRules)
        ]);
        exit;
    }

    // Si no está en uso, la función termina sin error
    // If not in use, the function exits silently
}



function validate_url_list_delete($file) {
    $config = import_squid_config_json();
    
    // Verificar que se pudo cargar la configuración
    // Check that configuration was successfully loaded
    if ($config === false || !isset($config['squid']['url_profile'])) {
        echo json_encode(['error' => 'No se pudo cargar la configuración o falta url_profile']);
        exit;
    }

    // Recorrer los perfiles para ver si alguno usa el archivo
    // Loop through profiles to check if any use the file
    $profiles = $config['squid']['url_profile'];
    $usedByProfiles = [];

    foreach ($profiles as $entry) {
        if (($entry['rule']['file'] ?? '') === $file) {
            $usedByProfiles[] = $entry['rule']['name'] ?? 'desconocido'; // unknown
        }
    }

    // Si el archivo está en uso, devolver error
    // If the file is in use, return error
    if (!empty($usedByProfiles)) {
        echo json_encode([
            'error' => 'El archivo no se puede borrar porque está en uso en los siguientes perfiles: ' . implode(', ', $usedByProfiles)
        ]);
        exit;
    }

    // Si no está en uso, la función termina sin error
    // If not in use, function exits silently
}


function validate_url_network_list($file) {
    $config = import_squid_config_json();
    
    // Verificar que se pudo cargar la configuración
    // Check that configuration was successfully loaded
    if ($config === false || !isset($config['squid']['url_networks_list_profile'])) {
        echo json_encode(['error' => 'No se pudo cargar la configuración o falta url_networks_list_profile']);
        exit;
    }

    // Recorrer los perfiles para ver si alguno usa el archivo
    // Loop through profiles to check if any use the file
    $profiles = $config['squid']['url_networks_list_profile'];
    $usedByProfiles = [];

    foreach ($profiles as $entry) {
        if (($entry['rule']['file'] ?? '') === $file) {
            $usedByProfiles[] = $entry['rule']['name'] ?? 'desconocido'; // unknown
        }
    }

    // Si el archivo está en uso, devolver error
    // If the file is in use, return error
    if (!empty($usedByProfiles)) {
        echo json_encode([
            'error' => 'El archivo no se puede borrar porque está en uso en los siguientes perfiles: ' . implode(', ', $usedByProfiles)
        ]);
        exit;
    }

    // Si no está en uso, la función termina sin error
    // If not in use, function exits silently
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// create ip acl txt   //////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//generamos los archivos txt de acl ip por alias, solo genera un txt vacio con el nombre del alias, las ips se añaden el commit backend
//para evitar introducir objetos modificados, ya fueron verificados que son correctos previamente en Main_convert_alias_object_to_network_object
//we generate the acl ip txt files by alias, it only generates an empty txt with the alias name, the ips are added in the commit backend
//to avoid introducing modified objects, They have already been verified to be correct in Main_convert_alias_object_to_network_object
function check_and_create_acl_ip() {
    $config = import_squid_config_json();

    // Verificar que se pudo cargar la configuración
    // Check that configuration was successfully loaded
    if ($config === false || !isset($config['squid']['url_policies'])) {
        echo json_encode(['error' => 'No se pudo cargar la configuración o falta url_policies']);
        exit;
    }

    // Extraer todos los grupos IP únicos
    // Extract all unique IP groups
    $ipGroups = [];
    foreach ($config['squid']['url_policies'] as $entry) {
        $group = $entry['rule']['ip_addr_group'] ?? '';
        if ($group !== '' && strtolower($group) !== 'all') {
            $ipGroups[$group] = true;
        }
    }

    // Directorio de salida
    // Output directory
    $aclDir = '/var/www/config/squid_config/acl_ips';

    // Crear el directorio si no existe
    // Create directory if it doesn't exist
    if (!is_dir($aclDir)) {
        mkdir($aclDir, 0775, true);
    }

    // Eliminar todos los archivos existentes en el directorio
    // Delete all existing files in the directory
    $existingFiles = glob($aclDir . '/*.txt');
    foreach ($existingFiles as $file) {
        unlink($file);
    }

    // Crear archivos vacíos para cada grupo IP
    // Create empty files for each IP group
    foreach (array_keys($ipGroups) as $groupName) {
        $filePath = $aclDir . '/' . $groupName . '.txt';
        file_put_contents($filePath, '');
    }
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// rename profiles     //////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function rename_not_permit(array $json, array $rule, string $chain): array {
    $path = '/var/www/config/squid_config/squid_policies.json';

    // Leer el archivo original desde disco
    // Read original file from disk
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo json_encode(['error' => 'No se pudo leer el archivo original para comparar']);
        exit;
    }

    $original = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($original['squid'][$chain])) {
        echo json_encode(['error' => 'Error al interpretar el JSON original']);
        exit;
    }

    // Extraer ID y nuevo nombre
    // Extract ID and new name
    $id = $rule['id'] ?? null;
    $newName = $rule['name'] ?? null;
    if ($id === null || $newName === null) {
        echo json_encode(['error' => 'Falta el campo "id" o "name" en el perfil']);
        exit;
    }

    // Buscar el nombre antiguo en el archivo original
    // Find old name in original file
    $oldName = null;
    foreach ($original['squid'][$chain] as $entry) {
        if (($entry['rule']['id'] ?? '') === $id) {
            $oldName = $entry['rule']['name'] ?? null;
            break;
        }
    }

    if ($oldName === null) {
        echo json_encode(['error' => 'No se encontró el perfil con ese ID en el archivo original']);
        exit;
    }

    // Si el nombre ha cambiado, no permitimos renombrado
    // If the name has changed, renaming is not allowed
    if ($oldName !== $newName) {
        error_log("Renombrado no permitido: {$oldName} -> {$newName}");
        echo json_encode(['error' => 'No se permite cambiar el nombre del perfil', 'original_name' => $oldName, 'new_name' => $newName]);
        exit;
    }

    // Si no hay renombrado, devolver el JSON sin cambios
    // If no renaming, return JSON unchanged
    return $json;
}




