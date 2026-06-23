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


//importa el archivo de formulario para validar los datos del resto de campos
//import the form file to validate the data in the remaining fields
function import_forms_interfaces_json() {
    $jsonPath = '/var/www/backend/checks/system_data/default_forms/forms_interfaces.json';

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


//importa la lista de interfaces en array 
//imports the list of interfaces into array
function import_all_interfaces(): array {
    $path = '/var/www/backend/checks/system_data/data_interfaces/all_interfaces_list.json';
    if (!file_exists($path)) return [];
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    return $data['all_interfaces'] ?? [];
}


//importa la lista de interfaces de configuracion de interfaces.json en array 
//import the list of interfaces from interfaces.json configuration into an array
function import_config_interfaces_json() {
    $jsonPath = '/var/www/config/interfaces.json';

    if (!file_exists($jsonPath)) {
        return false;
    }

    $raw = file_get_contents($jsonPath);
    $ifaceJsonData = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    return $ifaceJsonData;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////    form field review        /////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
//revisa los campos que contienen formularios
//check the fields that contain forms
function validation_form_field_review(array $rule, ?string $chain = null): void {
    $allFormConfig = import_forms_interfaces_json();
    if (!$allFormConfig) {
        echo json_encode(["error" => "No se pudo cargar la configuración del formulario interfaces"]);
        exit;
    }

    // Selecciona la configuración de la sección actual cuando se informa desde get_update_interface.
    // Select the current section configuration when get_update_interface provides it.
    $formConfig = ($chain !== null && isset($allFormConfig[$chain]) && is_array($allFormConfig[$chain]))
        ? $allFormConfig[$chain]
        : $allFormConfig;

    // Añadir interfaces del sistema a los campos que usan selección de interfaces.
    // Add system interfaces to fields that select interfaces.
    $interfaces = import_all_interfaces();
    if (isset($formConfig['select']['interfaces'])) {
        $formConfig['select']['interfaces'] = array_merge($formConfig['select']['interfaces'], $interfaces);
    }
    if (isset($formConfig['select']['link'])) {
        $formConfig['select']['link'] = array_merge($formConfig['select']['link'], $interfaces);
    }
    if (isset($formConfig['multiselect']['interfaces'])) {
        $formConfig['multiselect']['interfaces'] = array_merge($formConfig['multiselect']['interfaces'], $interfaces);
    }

    // Validar select simple.
    // Validate simple select fields.
    if (isset($formConfig['select'])) {
        foreach ($formConfig['select'] as $key => $validValues) {
            if (isset($rule[$key])) {
                $value = trim((string)$rule[$key]);
                if ($value === '') { // vacío o solo espacios -> válido
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

    // Validar multiselect CSV: cada elemento debe existir y no se permiten duplicados.
    // Validate multiselect CSV: each item must exist and duplicates are rejected.
    if (isset($formConfig['multiselect'])) {
        foreach ($formConfig['multiselect'] as $key => $validValues) {
            if (isset($rule[$key])) {
                $items = array_values(array_filter(array_map('trim', explode(',', (string)$rule[$key])), 'strlen'));
                if (count($items) !== count(array_unique($items))) {
                    echo json_encode(["error" => "duplicate value in validation_form_field_review_multiselect '{$key}'"]);
                    exit;
                }
                foreach ($items as $value) {
                    if (!in_array($value, $validValues, true)) {
                        echo json_encode(["error" => "value in validation_form_field_review_multiselect '{$value}' not found"]);
                        exit;
                    }
                }
            }
        }
    }

    // Validar checkbox.
    // Validate checkbox fields.
    if (isset($formConfig['checkbox'])) {
        foreach ($formConfig['checkbox'] as $key => $options) {
            if (isset($rule[$key])) {
                $value = trim((string)$rule[$key]);
                if ($value === '') { // vacío o solo espacios -> válido
                    $rule[$key] = "";
                    continue;
                }
                if (!in_array($value, $options, true)) {
                    echo json_encode(["error" => "alias port validation_form_field_review_checkbox '{$value}' not found"]);
                    exit;
                }
            }
        }
    }

    // Validar not_editable (excepto id).
    // Validate not_editable fields (except id).
    if (isset($formConfig['not_editable'])) {
        foreach ($formConfig['not_editable'] as $key => $validValues) {
            if ($key === 'id') continue;
            if (isset($rule[$key])) {
                $value = trim((string)$rule[$key]);
                if ($value === '') {
                    $rule[$key] = "";
                    continue;
                }
                if (!empty($validValues) && !in_array($value, $validValues, true)) {
                    echo json_encode(["error" => "alias port validation_form_field_review_not_editable '{$value}' not found"]);
                    exit;
                }
            }
        }
    }
}




////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// IPV4 & IPV6 VALIDATION SECTION ////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
function convert_alias_group_to_Network_ips_one_IP(string $value): bool {
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

    // Verifica que solo haya un elemento  
    // Ensure only one item is provided  
    if (count($items) !== 1) {
        echo json_encode(["error" => "only one IP, CIDR or alias is allowed"]);
        exit;
    }

    $item = $items[0];

    // Si es IP o CIDR válida, se acepta  
    // If it's a valid IP or CIDR, accept it  
    if (validate_ip_or_cidr($item)) {
        return true;
    }

    // Verifica si el elemento es un grupo de alias  
    // Check if the item is an alias group  
    if (isset($aliasJsonData['alias_addr_group'])) {
        foreach ($aliasJsonData['alias_addr_group'] as $group) {
            if (isset($group['name']) && $group['name'] === $item) {
                // Lanzamos error si es grupo  
                // Throw error if it's a group  
                echo json_encode(["error" => "alias group '{$item}' is not allowed"]);
                exit;
            }
        }
    }

    // Si no es grupo, lo tratamos como alias individual  
    // If it's not a group, treat it as an individual alias  
    if (!convert_alias_ip_to_ip($item)) {
        // Si no se pudo resolver, se lanza error  
        // If resolution fails, throw an error  
        echo json_encode(["error" => "alias or group '{$item}' not found or invalid"]);
        exit;
    }

    // Si todo es válido, retorna true  
    // If everything is valid, return true  
    return true;
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

    // Campos relacionados con direcciones IP individuales
    $ipFields = ['addresses', 'gateway4', 'gateway6'];

    foreach ($ipFields as $field) {
        if (isset($rule[$field])) {
            // Llama a la función de conversión de grupos IP solo para validar
            convert_alias_group_to_Network_ips_one_IP($rule[$field]);
        }
    }


    // Campos relacionados con direcciones IP multiples
    $ipFields = [
    'local',
    'nameservers.addresses',
    'peers.allowed-ips',
    'peers.endpoint',
    'remote',
    'routes.to',
    'routes.via',
    'routing-policy.from',
    'routing-policy.to'
    ];

    foreach ($ipFields as $field) {
        if (isset($rule[$field])) {
            // Llama a la función de conversión de grupos IP solo para validar
            convert_alias_group_to_Network_ips_multiple_IP($rule[$field]);
        }
    }

    // Devuelve la regla original sin modificar
    return $rule;
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// create Name & validate ID  ///////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


//Genera automáticamente un nombre para la interfaz si el campo 'name' está vacío.
//Generates an automatic interface name if the 'name' field is empty.
function check_create_Name(array $rule, string $chain): array {
    // Verifica si el campo 'name' está vacío o contiene solo espacios
    // Check if the 'name' field is empty or just whitespace
    if (!isset($rule['name']) || trim($rule['name']) === '') {

        // Carga la configuración actual de interfaces desde interfaces.json
        // Load current interface configuration from interfaces.json
        $config = import_config_interfaces_json();

        // Si no se pudo cargar o el tipo de interfaz no existe, se lanza error
        // If loading fails or the interface type doesn't exist, throw error
        if (!$config || !isset($config['network'][$chain])) {
            echo json_encode(['error' => "No se pudo cargar la configuración para '$chain'"]);
            exit;
        }

        // Mapeo de prefijos por tipo de interfaz
        // Prefix mapping by interface type
        $prefixMap = [
            'bridges'    => 'br',
            'bonds'      => 'bond',
            'ethernets'  => 'eth',
            'wireguard'  => 'wg',
            'vlans'      => 'vlan',
            'wifis'      => 'wlan',
            'tunnels'    => 'tun'
        ];

        // Verifica que el tipo de interfaz tenga un prefijo definido
        // Ensure the interface type has a defined prefix
        if (!isset($prefixMap[$chain])) {
            echo json_encode(['error' => "Tipo de interfaz desconocido: '$chain'"]);
            exit;
        }

        $prefix = $prefixMap[$chain];

        // Obtiene los nombres ya existentes en esa categoría
        // Get existing interface names in that category
        $existingNames = array_keys($config['network'][$chain]);

        // Busca el primer nombre disponible siguiendo el patrón: prefijo + número
        // Find the first available name using pattern: prefix + number
        $index = 0;
        while (in_array($prefix . $index, $existingNames)) {
            $index++;
        }

        // Asigna el nombre generado al campo 'name'
        // Assign the generated name to the 'name' field
        $rule['name'] = $prefix . $index;
    }

    // Devuelve el array actualizado
    // Return the updated array
    return $rule;
}

