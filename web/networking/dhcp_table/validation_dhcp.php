<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////    Import Json to to consult  ///////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////


function import_dhcp_json() {
    $jsonPath = '/var/www/config/dhcp.json';

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


function import_dhcp_forms() {
    $jsonPath = '/var/www/backend/checks/system_data/default_forms/forms_dhcp.json';

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
    $formConfig = import_dhcp_forms();
    $configJson = import_dhcp_json();

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
                    // vacío o solo espacios → válido
                    // empty or whitespace → valid
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

    // IP no válida → lanzar error y terminar  
    // Invalid IP → throw error and exit  
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



// Genera automáticamente un ID si no se proporciona, solo si se puede acceder a la lista
// Automatically generates an ID if not provided, only if the list is accessible
function check_create_id(array $rule, string $chain): array {
    // Si el campo 'id' existe, es numérico y no está vacío, no hacemos nada
    // If 'id' exists, is numeric, and not empty, do nothing
    if (isset($rule['id']) && is_numeric($rule['id']) && $rule['id'] !== '') {
        return $rule;
    }

    // Importar el JSON actual
    // Import current JSON
    $jsonData = import_dhcp_json();

    // Verificar que se haya cargado correctamente y que la lista exista
    // Check that it was loaded correctly and the list exists
    if (!is_array($jsonData) || !isset($jsonData[$chain]) || !is_array($jsonData[$chain])) {
        // No se puede generar un ID sin acceder a la lista
        // Cannot generate an ID without accessing the list
        return $rule;
    }

    // Extraer la lista correspondiente
    // Extract the corresponding list
    $list = $jsonData[$chain];

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








