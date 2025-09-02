<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
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

// Importa el archivo de reglas actual para consultas
// Imports the current rules file for queries
function import_policy_nft_json() {
    $jsonPath = '/var/www/config/rules_nftables_human_viewer.json';

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
function import_forms_nft_json() {
    $jsonPath = '/var/www/backend/checks/system_data/default_forms/forms_policies_nft.json';

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

//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////    form field review        /////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
//revisa los campos que contienen formularios
//check the fields that contain forms
function validation_form_field_review(array $rule): void {
    $formConfig = import_forms_nft_json();
    if (!$formConfig) {
        echo json_encode(["error" => "No se pudo cargar la configuración del formulario interfaces"]);
        exit;
    }

    // Añadir interfaces del sistema
    $interfaces = import_all_interfaces();
    if (isset($formConfig['select']['meta.iifname'])) {
        $formConfig['select']['meta.iifname'] = array_merge($formConfig['select']['meta.iifname'], $interfaces);
    }
    if (isset($formConfig['select']['meta.oifname'])) {
        $formConfig['select']['meta.oifname'] = array_merge($formConfig['select']['meta.oifname'], $interfaces);
    }

    // Validar select
    if (isset($formConfig['select'])) {
        foreach ($formConfig['select'] as $key => $validValues) {
            if (isset($rule[$key])) {
                $value = trim((string)$rule[$key]);
                if ($value === '') { // vacío o solo espacios → válido
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

    // Validar checkbox
    if (isset($formConfig['checkbox'])) {
        foreach ($formConfig['checkbox'] as $key => $options) {
            if (isset($rule[$key])) {
                $value = trim((string)$rule[$key]);
                if ($value === '') { // vacío o solo espacios → válido
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

    // Validar not_editable (excepto id)
    if (isset($formConfig['not_editable'])) {
        foreach ($formConfig['not_editable'] as $key => $validValues) {
            if ($key === 'id') continue;
            if (isset($rule[$key])) {
                $value = $rule[$key];
                if (!in_array($value, $validValues, true)) {
                    echo json_encode(["error" => "alias port validation_form_field_review_not_editable '{$value}' not found"]);
                    exit;
                }
            }
        }
    }
}





//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////ID and name section     /////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
// Genera un ID único buscando el primer número no usado en los comentarios
// Generates a unique ID by finding the first unused number in rule comments
function get_id_from_policy(): string {
    $data = import_policy_nft_json();
    if (!$data || !isset($data['nftables']) || !is_array($data['nftables'])) {
        return "1"; // fallback
    }

    $usedIds = [];

    foreach ($data['nftables'] as $entry) {
        if (isset($entry['rule']['id']) && $entry['rule']['id'] !== '') {
            $usedIds[] = (int)$entry['rule']['id']; // normalizamos a entero
        }
    }

    $id = 1;
    while (in_array($id, $usedIds, true)) {
        $id++;
    }

    return (string)$id;
}

//convierte el campo name y el campo id en partes del campo comment de nftables
//si no hay id por que la regla por ejemplo es nueva, se llama a get_id_from_policy() que devuelve un id unico
//makes the name field and id field parts of the nftables comment field
//if there is no id because the rule is new, for example, get_id_from_policy() is called which returns a unique id
function comment_convert_id_name(array $rule): array {
    // Si no hay id, se genera automáticamente
    // If 'id' is missing, generate it automatically
    $id = isset($rule['id']) && trim($rule['id']) !== '' ? trim($rule['id']) : get_id_from_policy();

    // El name puede estar vacío, pero debe incluirse
    // 'name' can be empty, but must be included
    $name = isset($rule['name']) ? trim($rule['name']) : '';

    // Construye el campo comment con ambas claves
    // Builds the 'comment' field with both keys
    $rule['comment'] = "id='{$id}',name='{$name}'";

    return $rule;
}
//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////PORTS VALIDATION SECTION/////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
//elimina puertos de los campos puerto si el protocolo de la regla es icmp
//Remove ports from the port fields if the rule protocol is icmp
function validation_icmp_no_ports(array $rule): array {
    $protocol = strtolower($rule['ip.protocol'] ?? '');

    if ($protocol === 'icmp' || $protocol === 'icmpv6') {
        $fieldsToClear = [
            'sport.op',
            'sport',
            'dport.op',
            'dport',
            'dnat.port'
        ];

        foreach ($fieldsToClear as $field) {
            if (array_key_exists($field, $rule)) {
                $rule[$field] = '';
            }
        }
    }

    return $rule;
}

// Valida que los puertos o rangos estén dentro del rango permitido
// Validates that ports or ranges are within the allowed range
function validation_ports_range(string $value): void {
    $items = array_map('trim', explode(',', $value)); // Divide la cadena por comas
    $minPort = 0;
    $maxPort = 65535;

    foreach ($items as $item) {
        // Si es un puerto individual
        // If it's a single port
        if (ctype_digit($item)) {
            $port = (int)$item;
            if ($port < $minPort || $port > $maxPort) {
                echo json_encode(["error" => "port '{$port}' out of range"]);
                exit;
            }
            continue;
        }

        // Si es un rango de puertos (ej. 1000-2000)
        // If it's a port range (e.g. 1000-2000)
        if (preg_match('/^(\d+)-(\d+)$/', $item, $matches)) {
            $start = (int)$matches[1];
            $end = (int)$matches[2];

            if ($start < $minPort || $start > $maxPort || $end < $minPort || $end > $maxPort) {
                echo json_encode(["error" => "port range '{$item}' out of range"]);
                exit;
            }
            continue;
        }
    }
}
// Convierte un alias de puerto en su valor numérico real
// Converts a port alias into its actual numeric value
function convert_alias_port_to_network_port(string $value): bool {
    $aliasJsonData = import_alias_json();

    // Verifica que se haya cargado correctamente el JSON  
    // Check that the JSON was loaded successfully  
    if (!$aliasJsonData) {
        echo json_encode(["error" => "alias file not found or invalid"]);
        exit;
    }

    // Busca el alias en alias_service  
    // Search for the alias in alias_service  
    if (isset($aliasJsonData['alias_service'])) {
        foreach ($aliasJsonData['alias_service'] as $entry) {
            if (isset($entry['name']) && $entry['name'] === $value) {
                return true;
            }
        }
    }

    // Si no se encuentra, retorna false  
    // If not found, return false  
    return false;
}

// Convierte una lista de puertos, alias y grupos en puertos reales
// Converts a list of ports, aliases, and groups into real port numbers
function convert_alias_port_group_to_network_port(string $value): bool {
    // Importa el archivo JSON con los alias definidos  
    // Import the JSON file with defined aliases  
    $aliasJsonData = import_alias_json();

    // Si el valor está vacío, no se procesa  
    // If the value is empty, do not process  
    if (trim($value) === '') {
        return true;
    }

    // Si no se pudo cargar el archivo, se detiene el script  
    // If the file couldn't be loaded, stop the script  
    if (!$aliasJsonData) {
        echo json_encode(["error" => "alias file not found or invalid"]);
        exit;
    }

    $items = array_map('trim', explode(',', $value));

    foreach ($items as $item) {
        // Ignora elementos vacíos individuales  
        // Ignore individual empty elements  
        if ($item === '') {
            continue;
        }

        // Si es un puerto o rango válido, se valida  
        // If it's a valid port or range, validate it  
        if (ctype_digit($item) || preg_match('/^\d+-\d+$/', $item)) {
            validation_ports_range($item);
            continue;
        }

        $foundGroup = false;

        // Verifica si el elemento es un grupo de alias de servicio  
        // Check if the item is a service alias group  
        if (isset($aliasJsonData['alias_service_group'])) {
            foreach ($aliasJsonData['alias_service_group'] as $group) {
                if (isset($group['name']) && $group['name'] === $item) {
                    $foundGroup = true;

                    // Verifica cada entrada dentro del grupo  
                    // Verify each entry inside the group  
                    foreach ($group['content'] as $entry) {
                        if (ctype_digit($entry) || preg_match('/^\d+-\d+$/', $entry)) {
                            validation_ports_range($entry);
                        } else {
                            if (!convert_alias_port_to_network_port($entry)) {
                                // Si el alias no es válido, lanza error  
                                // If the alias is invalid, throw error  
                                echo json_encode(["error" => "alias port '{$entry}' in group '{$item}' is invalid"]);
                                exit;
                            }
                        }
                    }

                    break;
                }
            }
        }

        // Si no es grupo, lo tratamos como alias individual  
        // If it's not a group, treat it as an individual alias  
        if (!$foundGroup) {
            if (!convert_alias_port_to_network_port($item)) {
                // Si no se pudo resolver, se lanza error  
                // If resolution fails, throw an error  
                echo json_encode(["error" => "alias port or group '{$item}' not found or invalid"]);
                exit;
            }
        }
    }

    // Si todo es válido, retorna true  
    // If everything is valid, return true  
    return true;
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
/*
function convert_alias_ip_to_ip(string $value): bool {
    $aliasJsonData = import_alias_json();

    // Verifica que se haya cargado correctamente el JSON  
    // Check that the JSON was loaded successfully  
    if (!$aliasJsonData) {
        echo json_encode(["error" => "alias file not found or invalid"]);
        exit;
    }

    // Busca el alias en alias_address  
    // Search for the alias in alias_address  
    if (isset($aliasJsonData['alias_address'])) {
        foreach ($aliasJsonData['alias_address'] as $entry) {
            if (isset($entry['name']) && $entry['name'] === $value) {
                return true;
            }
        }
    }

    // Si no se encuentra, retorna false  
    // If not found, return false  
    return false;
}
*/
function convert_alias_ip_to_ip(string $value): bool {
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
function convert_alias_group_to_Network_ips(string $value): bool {
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
    // Campos relacionados con puertos
    $portFields = ['sport', 'dport', 'dnat.port'];

    foreach ($portFields as $field) {
        if (isset($rule[$field])) {
            // Llama a la función de conversión de puertos solo para validar
            convert_alias_port_group_to_network_port($rule[$field]);
        }
    }

    // Campos relacionados con direcciones IP
    $ipFields = ['ip.daddr', 'ip.saddr', 'dnat.addr', 'snat.addr'];

    foreach ($ipFields as $field) {
        if (isset($rule[$field])) {
            // Llama a la función de conversión de grupos IP solo para validar
            convert_alias_group_to_Network_ips($rule[$field]);
        }
    }

    // Devuelve la regla original sin modificar
    return $rule;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// Assign position if empty /////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Asigna la posición 1 si no viene definida o está vacía
// Assigns position 1 if not defined or empty
function assign_position(array $rule): array {
    // Verifica si el campo 'position' está ausente o vacío
    // Checks if the 'position' field is missing or empty
    if (!isset($rule["position"]) || trim((string)$rule["position"]) === "") {
        // Asigna la posición 1 por defecto
        // Assigns default position 1
        $rule["position"] = 1;
    }

    // Devuelve la regla modificada
    // Returns the modified rule
    return $rule;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// Saniticed to nftables json format  ///////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Función para convertir la regla al formato de nftables
// Function to convert the rule to nftables format
// Genera la estructura base de una regla nftables
// Generates the base structure of an nftables rule
function saniticed_nftables_policy(array $rule): array {
    return [
        "rule" => [
            "family"        => $rule["family"]        ?? "",
            "table"         => $rule["table"]         ?? "",
            "chain"         => $rule["chain"]         ?? "",
            "id"            => $rule["id"]            ?? "",
            "position"      => $rule["position"]      ?? "",
            "action"        => $rule["action"]        ?? "",
            "enable"        => $rule["enable"]        ?? "",
            "name"          => $rule["name"]          ?? "",
            "ip.protocol"   => $rule["ip.protocol"]   ?? "",
            "ip.saddr.op"   => $rule["ip.saddr.op"]   ?? "",
            "ip.saddr"      => $rule["ip.saddr"]      ?? "",
            "sport.op"      => $rule["sport.op"]      ?? "",
            "sport"         => $rule["sport"]         ?? "",
            "ip.daddr.op"   => $rule["ip.daddr.op"]   ?? "",
            "ip.daddr"      => $rule["ip.daddr"]      ?? "",
            "dport.op"      => $rule["dport.op"]      ?? "",
            "dport"         => $rule["dport"]         ?? "",
            "meta.iifname"  => $rule["meta.iifname"]  ?? "",
            "meta.oifname"  => $rule["meta.oifname"]  ?? "",
            "ct.state"      => $rule["ct.state"]      ?? "",
            "packets"       => $rule["packets"]       ?? "",
            "bytes"         => $rule["bytes"]         ?? "",
            "log"           => $rule["log"]           ?? "",
            "snat.addr"     => $rule["snat.addr"]     ?? "",
            "snat.port"     => $rule["snat.port"]     ?? "",
            "dnat.addr"     => $rule["dnat.addr"]     ?? "",
            "dnat.port"     => $rule["dnat.port"]     ?? ""
        ]
    ];
}




////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// write and order policy   ///////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Reasigna la posición de una regla según su familia, tabla y cadena
// Reassigns the position of a rule based on its family, table, and chain
function reassign_position(array $rule): array {
    $jsonData = import_policy_nft_json();

    if (!$jsonData || !isset($jsonData["nftables"]) || !is_array($jsonData["nftables"])) {
        return $rule;
    }

    $family = $rule["family"] ?? "";
    $table  = $rule["table"]  ?? "";
    $chain  = $rule["chain"]  ?? "";

    // Normalizamos la posición entrante
    $incomingPosition = isset($rule["position"]) && $rule["position"] !== ""
        ? (int)$rule["position"]
        : null;

    if ($incomingPosition === null) {
        $rule["position"] = 1;
        $incomingPosition = 1;

        foreach ($jsonData["nftables"] as &$entry) {
            if (isset($entry["rule"]["family"], $entry["rule"]["table"], $entry["rule"]["chain"], $entry["rule"]["position"])) {
                if (
                    $entry["rule"]["family"] === $family &&
                    $entry["rule"]["table"]  === $table &&
                    $entry["rule"]["chain"]  === $chain
                ) {
                    $entry["rule"]["position"] = (int)$entry["rule"]["position"] + 1;
                }
            }
        }
    } else {
        foreach ($jsonData["nftables"] as &$entry) {
            if (isset($entry["rule"]["family"], $entry["rule"]["table"], $entry["rule"]["chain"], $entry["rule"]["position"])) {
                if (
                    $entry["rule"]["family"] === $family &&
                    $entry["rule"]["table"]  === $table &&
                    $entry["rule"]["chain"]  === $chain &&
                    (int)$entry["rule"]["position"] >= $incomingPosition
                ) {
                    $entry["rule"]["position"] = (int)$entry["rule"]["position"] + 1;
                }
            }
        }
    }

    return $rule;
}



function update_or_insert_nft_rule(array $rule, array $rulesJson): array {
    // Normaliza el ID de la nueva regla
    $id = isset($rule['id']) ? (int)$rule['id'] : null;

    if (!$id) return $rulesJson;

    foreach ($rulesJson['nftables'] as $index => $entry) {
        if (!isset($entry['rule'])) continue;

        $existing = $entry['rule'];

        // Normaliza el ID de la regla existente
        $existingId = isset($existing['id']) ? (int)$existing['id'] : null;

        // Compara los IDs como enteros
        if ($existingId === $id) {
            $rulesJson['nftables'][$index]['rule'] = $rule;
            return $rulesJson;
        }
    }

    // Si no se encontró coincidencia, se inserta como nueva
    $rulesJson['nftables'][] = ['rule' => $rule];
    return $rulesJson;
}





























