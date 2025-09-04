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
    $jsonPath = '/var/www/config/rules_nftables.json';

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

    // Extiende los valores válidos de interfaces con los del sistema extraidos del archivo de interfaces
    $interfaces = import_all_interfaces();
    if (isset($formConfig['select']['meta.iifname'])) {
        $formConfig['select']['meta.iifname'] = array_merge($formConfig['select']['meta.iifname'], $interfaces);
    }
    if (isset($formConfig['select']['meta.oifname'])) {
        $formConfig['select']['meta.oifname'] = array_merge($formConfig['select']['meta.oifname'], $interfaces);
    }

    // Validar campos tipo "select"
    if (isset($formConfig['select'])) {
        foreach ($formConfig['select'] as $key => $validValues) {
            if (isset($rule[$key])) {
                $value = $rule[$key];

                // Si viene vacío o solo espacios, lo damos por válido
                if (trim($value) === '') {
                    continue;
                }

                if (!in_array($value, $validValues, true)) {
                    echo json_encode([
                        "error" => "value in validation_form_field_review_select '{$value}' not found"
                    ]);
                    exit;
                }
            }
        }
    }

    // Validar campos tipo "checkbox"
    if (isset($formConfig['checkbox'])) {
        foreach ($formConfig['checkbox'] as $key => $options) {
            if (isset($rule[$key])) {
                $value = $rule[$key];

                // Si viene vacío o solo espacios, lo damos por válido
                if (trim($value) === '') {
                    continue;
                }

                if (!in_array($value, $options, true)) {
                    echo json_encode([
                        "error" => "alias port validation_form_field_review_checkbox '{$value}' not found"
                    ]);
                    exit;
                }
            }
        }
    }


    // Validar campos "not_editable" (excepto 'id')
    if (isset($formConfig['not_editable'])) {
        foreach ($formConfig['not_editable'] as $key => $validValues) {
            if ($key === 'id') {
                continue;
            }

            if (isset($rule[$key])) {
                $value = $rule[$key];
                if (!in_array($value, $validValues, true)) {
                    echo json_encode(["error" => "alias port validation_form_field_review_not_editable '{$value}' not found"]);
                    exit;
                }
            }
        }
    }
    
    // Si todo está bien, no se hace nada
}
//genera la entrada log compatible con nftables si es true, si es false borra "log" de la regla
//Generates the nftables-compatible log entry if true, if false deletes "log" from the rule
function log_format_nft(array $rule): array {
    if (isset($rule['log'])) {
        if ($rule['log'] === 'true') {
            $id = $rule['id'] ?? '';
            $chain = $rule['chain'] ?? '';
            $action = $rule['action'] ?? '';
            $rule['log'] = "nftables {$id} {$chain} {$action}";
        } elseif ($rule['log'] === 'false') {
            unset($rule['log']);
        }
    }
    return $rule;
}



//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////ID and name section     /////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
// Genera un ID único buscando el primer número no usado en los comentarios
// Generates a unique ID by finding the first unused number in rule comments
function get_id_from_policy(): string {
    $data = import_policy_nft_json();
    if (!$data || !isset($data['nftables']) || !is_array($data['nftables'])) {
        return "1"; // fallback si no se puede leer el archivo
    }

    $usedIds = [];

    foreach ($data['nftables'] as $entry) {
        if (isset($entry['rule']) && isset($entry['rule']['comment'])) {
            $comment = $entry['rule']['comment'];
            if (preg_match("/id='(\d+)'/", $comment, $match)) {
                $usedIds[] = (int)$match[1];
            }
        }
    }

    // Busca el primer ID libre empezando desde 1
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
// Elimina duplicados y solapamientos en una lista de puertos y rangos
// Removes duplicates and overlaps in a list of ports and ranges
function validation_not_duplicate_ports(string $value): string {
    $items = array_map('trim', explode(',', $value)); // Divide la cadena por comas
    $allPorts = []; // Lista completa de puertos individuales

    foreach ($items as $item) {
        // Si es un rango (ej. 22-50)
        // If it's a range (e.g. 22-50)
        if (preg_match('/^(\d+)-(\d+)$/', $item, $matches)) {
            $start = (int)$matches[1];
            $end = (int)$matches[2];
            if ($start > $end) {
                [$start, $end] = [$end, $start]; // Corrige si el rango está invertido
            }
            for ($i = $start; $i <= $end; $i++) {
                $allPorts[$i] = true;
            }
        }
        // Si es un puerto individual
        // If it's a single port
        elseif (ctype_digit($item)) {
            $allPorts[(int)$item] = true;
        }
    }

    // Ordena los puertos únicos
    // Sort unique ports
    $sortedPorts = array_keys($allPorts);
    sort($sortedPorts);

    // Agrupa puertos contiguos en rangos
    // Group contiguous ports into ranges
    $result = [];
    $start = $end = null;

    foreach ($sortedPorts as $port) {
        if ($start === null) {
            $start = $end = $port;
        } elseif ($port === $end + 1) {
            $end = $port;
        } else {
            $result[] = ($start === $end) ? (string)$start : "{$start}-{$end}";
            $start = $end = $port;
        }
    }

    // Añade el último grupo
    if ($start !== null) {
        $result[] = ($start === $end) ? (string)$start : "{$start}-{$end}";
    }

    // Devuelve la lista final como cadena separada por comas
    // Return the final list as a comma-separated string
    return implode(',', $result);
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
function convert_alias_port_to_network_port(string $value): string {
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
                return $entry['content'][0] ?? '';
            }
        }
    }

    // Si no se encuentra, se detiene el script y se devuelve error
    // If not found, stop the script and return error
    echo json_encode(["error" => "alias port no encontrado en ningun sitio '{$value}' not found"]);
    exit;
}
// Convierte una lista de puertos, alias y grupos en puertos reales
// Converts a list of ports, aliases, and groups into real port numbers
function convert_alias_port_group_to_network_port(string $value): string {
    // Importa el archivo JSON con los alias definidos
    $aliasJsonData = import_alias_json();

    // Si el valor está vacío, no se procesa
    if (trim($value) === '') {
        return '';
    }

    // Si no se pudo cargar el archivo, se detiene el script
    if (!$aliasJsonData) {
        echo json_encode(["error" => "alias file not found or invalid"]);
        exit;
    }

    $finalPorts = [];
    $items = array_map('trim', explode(',', $value));

    foreach ($items as $item) {
        if ($item === '') {
            continue; // Ignora elementos vacíos individuales
        }

        if (ctype_digit($item) || preg_match('/^\d+-\d+$/', $item)) {
            validation_ports_range($item);
            $finalPorts[] = $item;
            continue;
        }

        $foundGroup = false;

        if (isset($aliasJsonData['alias_service_group'])) {
            foreach ($aliasJsonData['alias_service_group'] as $group) {
                if (isset($group['name']) && $group['name'] === $item) {
                    foreach ($group['content'] as $entry) {
                        if (ctype_digit($entry) || preg_match('/^\d+-\d+$/', $entry)) {
                            validation_ports_range($entry);
                            $finalPorts[] = $entry;
                        } else {
                            $resolved = convert_alias_port_to_network_port($entry);
                            validation_ports_range($resolved);
                            $finalPorts[] = $resolved;
                        }
                    }
                    $foundGroup = true;
                    break;
                }
            }
        }

        if (!$foundGroup) {
            $resolved = convert_alias_port_to_network_port($item);
            validation_ports_range($resolved);
            $finalPorts[] = $resolved;
        }
    }

    $cleaned = validation_not_duplicate_ports(implode(',', $finalPorts));
    return $cleaned;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// IPV4 & IPV6 VALIDATION SECTION ////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Verifica si una IP objetivo está contenida dentro de una red CIDR, compatible con IPv4 e IPv6.
// Checks whether a target IP is contained within a CIDR network, supporting both IPv4 and IPv6.
function cidr_contains(string $cidr, string $target): bool {
    // Extrae la IP base y la máscara del CIDR
    // Extracts the base IP and mask from the CIDR
    [$base, $mask] = explode('/', $cidr);
    [$check, $checkMask] = explode('/', $target);

    // Verifica si la IP base es IPv4
    // Checks if the base IP is IPv4
    if (filter_var($base, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        // Convierte las IPs a formato numérico largo
        // Converts IPs to long integer format
        $baseLong = ip2long($base);
        $checkLong = ip2long($check);

        // Calcula la máscara de red en formato numérico
        // Calculates the network mask in numeric format
        $netmask = ~((1 << (32 - (int)$mask)) - 1);

        // Compara las IPs aplicando la máscara
        // Compares the IPs after applying the mask
        return ($baseLong & $netmask) === ($checkLong & $netmask);
    }

    // Verifica si la IP base es IPv6
    // Checks if the base IP is IPv6
    if (filter_var($base, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // Convierte las IPs a formato binario
        // Converts IPs to binary format
        $baseBin = inet_pton($base);
        $checkBin = inet_pton($check);

        // Calcula cuántos bytes y bits se deben comparar
        // Calculates how many bytes and bits to compare
        $bytes = (int)$mask >> 3;
        $bits = (int)$mask % 8;

        // Compara los bytes completos
        // Compares the full bytes
        if (substr($baseBin, 0, $bytes) !== substr($checkBin, 0, $bytes)) {
            return false;
        }

        // Si no hay bits restantes, la IP está contenida
        // If no remaining bits, the IP is contained
        if ($bits === 0) {
            return true;
        }

        // Compara los bits restantes del siguiente byte
        // Compares the remaining bits of the next byte
        $maskByte = 0xFF << (8 - $bits);
        return (ord($baseBin[$bytes]) & $maskByte) === (ord($checkBin[$bytes]) & $maskByte);
    }

    // Si la IP no es válida, retorna falso
    // If the IP is not valid, returns false
    return false;
}
// Normaliza una lista de IPs y redes CIDR, valida su formato, elimina duplicados,
// ordena por máscara ascendente y filtra redes contenidas para retornar solo las más específicas.
// Normalizes a list of IPs and CIDR networks, validates format, removes duplicates,
// sorts by ascending mask, and filters out contained networks to return only the most specific ones.

function validation_ip_networks(string $value): string {
    // DEBUG: valor original recibido
    error_log("DEBUG validation_ip_networks: valor recibido = >{$value}<");

    // Divide la cadena por comas, elimina espacios y filtra vacíos
    $items = array_filter(array_map('trim', explode(',', $value)), fn($v) => $v !== '');
    //error_log("DEBUG items después de explode/trim/filter: " . json_encode($items));

    $normalized = [];

    foreach ($items as $idx => $item) {
        error_log("DEBUG iteración {$idx}: item = >{$item}<");

        // IP sin CIDR → se normaliza como /32 (IPv4) o /128 (IPv6)
        if (filter_var($item, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            //error_log("DEBUG {$item} detectada como IPv4");
            $normalized[] = "{$item}/32";
        } elseif (filter_var($item, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            //error_log("DEBUG {$item} detectada como IPv6");
            $normalized[] = "{$item}/128";
        }
        // IP con CIDR → se valida y se agrega si es válida
        elseif (preg_match('/^(.+)\/(\d{1,3})$/', $item, $matches)) {
            $ip = $matches[1];
            $cidr = (int)$matches[2];
            //error_log("DEBUG {$item} detectada como CIDR: IP={$ip}, máscara={$cidr}");

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $cidr >= 0 && $cidr <= 32) {
                $normalized[] = "{$ip}/{$cidr}";
            } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && $cidr >= 0 && $cidr <= 128) {
                $normalized[] = "{$ip}/{$cidr}";
            } else {
                //error_log("ERROR CIDR inválido: {$item}");
                echo json_encode(["error" => "invalid CIDR '{$item}'"]);
                exit;
            }
        }
        // Formato inválido → se muestra error y se detiene
        else {
            //error_log("ERROR formato inválido: >{$item}<");
            echo json_encode(["error" => "invalid IP format '{$item}'"]);
            exit;
        }
    }

    // Elimina duplicados exactos
    $normalized = array_unique($normalized);
    //error_log("DEBUG normalizados únicos: " . json_encode($normalized));

    // Ordena por máscara ascendente (más amplias primero)
    usort($normalized, function ($a, $b) {
        [$ipA, $maskA] = explode('/', $a);
        [$ipB, $maskB] = explode('/', $b);
        return (int)$maskA <=> (int)$maskB;
    });
    //error_log("DEBUG ordenados por máscara: " . json_encode($normalized));

    $final = [];

    foreach ($normalized as $candidate) {
        $contained = false;
        foreach ($final as $existing) {
            if (cidr_contains($existing, $candidate)) {
                //error_log("DEBUG {$candidate} está contenido en {$existing}, se omite");
                $contained = true;
                break;
            }
        }
        if (!$contained) {
            $final[] = $candidate;
        }
    }

    //error_log("DEBUG resultado final: " . json_encode($final));

    return implode(',', $final);
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
function convert_alias_ip_to_ip(string $value): string {
    $aliasJsonData = import_alias_json();
     // Si el valor está vacío o solo contiene espacios, lo ignoramos
    if (trim($value) === '') {
        error_log("DEBUG convert_alias_ip_to_ip: valor vacío, se ignora");
        return '';
    }

    // Verifica que se haya cargado correctamente el JSON
    // Check that the JSON was loaded successfully
    if (!$aliasJsonData) {
        echo json_encode(["error" => "alias file not found or invalid"]);
        exit;
    }
        // DEBUG: mostrar el valor recibido
    error_log("DEBUG convert_alias_ip_to_ip: valor recibido = >{$value}<");

    // DEBUG: listar todos los alias disponibles en alias_address
    if (isset($aliasJsonData['alias_address'])) {
        foreach ($aliasJsonData['alias_address'] as $entry) {
            error_log("DEBUG alias en JSON: >{$entry['name']}<");
        }
    }

    // Busca el alias en alias_address
    // Search for the alias in alias_address
    if (isset($aliasJsonData['alias_address'])) {
        foreach ($aliasJsonData['alias_address'] as $entry) {
            if (isset($entry['name']) && $entry['name'] === $value) {
                return $entry['content'][0] ?? '';
            }
        }
    }

    // Si no se encuentra, se detiene el script y se devuelve error
    // If not found, stop the script and return error
    echo json_encode(["error" => "alias IP '{$value}' not found"]);
    exit;
}
// Convierte IPs, alias y grupos de alias en una lista normalizada de redes IP únicas.
// Converts IPs, aliases, and alias groups into a normalized list of unique network addresses.
function convert_alias_group_to_Network_ips(string $value): string {
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
    $resolvedIps = [];

    foreach ($items as $item) {
        // Ignorar valores vacíos o solo espacios
        if (trim($item) === '') {
            continue;
        }
        // Si es IP o CIDR válida, se conserva
        // If it's a valid IP or CIDR, keep it as-is
        if (validate_ip_or_cidr($item)) {
            $resolvedIps[] = $item;
            continue;
        }

        $foundGroup = false;

        // Verifica si el elemento es un grupo de alias
        // Check if the item is an alias group
        if (isset($aliasJsonData['alias_addr_group'])) {
            foreach ($aliasJsonData['alias_addr_group'] as $group) {
                if (isset($group['name']) && $group['name'] === $item) {
                    // Recorre cada alias dentro del grupo
                    // Iterate over each alias inside the group
                    foreach ($group['content'] as $aliasName) {
                        $ip = convert_alias_ip_to_ip($aliasName);
                        if ($ip !== '') {
                            $resolvedIps[] = $ip;
                        }
                    }
                    $foundGroup = true;
                    break;
                }
            }
        }

        // Si no es grupo, lo tratamos como alias individual
        // If it's not a group, treat it as an individual alias
        if (!$foundGroup) {
            $ip = convert_alias_ip_to_ip($item);
            if ($ip !== '') {
                $resolvedIps[] = $ip;
                continue;
            }

            // Si no se pudo resolver, se lanza error
            // If resolution fails, throw an error
            echo json_encode(["error" => "alias or group '{$item}' not found or invalid"]);
            exit;
        }
    }

    // Normaliza y elimina duplicados antes de devolver
    // Normalize and remove duplicates before returning
    return validation_ip_networks(implode(',', $resolvedIps));
}
// Convierte alias en objetos de red reales usando funciones auxiliares
// Converts aliases into real network objects using helper functions
function Main_convert_alias_object_to_network_object(array $rule): array {
    // Campos relacionados con puertos
    // Port-related fields
    $portFields = ['sport', 'dport', 'dnat.port'];

    foreach ($portFields as $field) {
        if (isset($rule[$field])) {
            // Llama a la función de conversión de puertos
            // Call the port conversion function
            $rule[$field] = convert_alias_port_group_to_network_port($rule[$field]);
        }
    }

    // Campos relacionados con direcciones IP
    // IP-related fields
    $ipFields = ['ip.daddr', 'ip.saddr', 'dnat.addr', 'snat.addr'];

    foreach ($ipFields as $field) {
        if (isset($rule[$field])) {
            // Llama a la función de conversión de grupos IP
            // Call the IP group conversion function
            $rule[$field] = convert_alias_group_to_Network_ips($rule[$field]);
        }
    }

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
            "family"   => $rule["family"],
            "table"    => $rule["table"],
            "chain"    => $rule["chain"],
            "position" => $rule["position"],
            "id" => $rule["id"],
            "name" => $rule["name"],
            "expr"     => build_expr($rule, $rule["comment"]), //pasamos comment para personalizar los logs
            "comment"  => $rule["comment"],
        ]
    ];
    // 🧾 Registrar el resultado en los logs
}


//genera la estructura de expr en nftables
//generate the structure of expr in nftables
function build_expr(array $rule, string $comment): array {
    $expr = [];
    
    // Limpieza de /32 o /128 en snat/dnat justo antes de insertar, incompatible con nftables
    //Cleaning up /32 or /128 in snat/dnat just before inserting, incompatible with nftables
    foreach (["snat.addr", "dnat.addr"] as $field) {
        if (!empty($rule[$field])) {
            $rule[$field] = preg_replace('/\/(32|128)$/', '', $rule[$field]);
        }
    }
    // Protocolo IP
    //añadida compatibilidad con tcp-udp
    if (!empty($rule["ip.protocol"])) {
        $protocols = array_map('trim', explode(',', $rule["ip.protocol"]));

        if (count($protocols) === 1) {
            // Solo un protocolo (ej. "tcp")
            $expr[] = [
                "match" => [
                    "op" => "==",
                    "left" => [
                        "payload" => [
                            "protocol" => "ip",
                            "field" => "protocol"
                        ]
                    ],
                    "right" => $protocols[0]
                ]
            ];
        } else {
            // Varios protocolos (ej. "tcp, udp")
            $expr[] = [
                "match" => [
                    "op" => "==",
                    "left" => [
                        "payload" => [
                            "protocol" => "ip",
                            "field" => "protocol"
                        ]
                    ],
                    "right" => [
                        "set" => $protocols
                    ]
                ]
            ];
        }
    }
    // Dirección de origen
    if (!empty($rule["ip.saddr"])) {
        $set = array_map(function ($cidr) {
            [$addr, $len] = explode("/", trim($cidr));
            return ["prefix" => ["addr" => $addr, "len" => (int)$len]];
        }, explode(",", $rule["ip.saddr"]));

        $expr[] = [
            "match" => [
                "op" => $rule["ip.saddr.op"] ?? "==",
                "left" => [
                    "payload" => [
                        "protocol" => "ip",
                        "field" => "saddr"
                    ]
                ],
                "right" => ["set" => $set]
            ]
        ];
    }
    // Dirección de destino
    if (!empty($rule["ip.daddr"])) {
        $set = array_map(function ($cidr) {
            [$addr, $len] = explode("/", trim($cidr));
            return ["prefix" => ["addr" => $addr, "len" => (int)$len]];
        }, explode(",", $rule["ip.daddr"]));

        $expr[] = [
            "match" => [
                "op" => $rule["ip.daddr.op"] ?? "==",
                "left" => [
                    "payload" => [
                        "protocol" => "ip",
                        "field" => "daddr"
                    ]
                ],
                "right" => ["set" => $set]
            ]
        ];
    }
    //comptaiblidad con tcp udp juntos
    // support for tcp udp together
    //sport
    if (!empty($rule["sport"])) {
        $ports = array_map('trim', explode(',', $rule["sport"]));
        $items = [];

        foreach ($ports as $p) {
            if (preg_match('/^(\d+)-(\d+)$/', $p, $m)) {
                // Rango → objeto con clave "range"
                $items[] = ["range" => [(int)$m[1], (int)$m[2]]];
            } elseif (ctype_digit($p)) {
                // Puerto único
                $items[] = (int)$p;
            }
        }

        // Determinar si es un único puerto, un único rango o una combinación
        $right = count($items) === 1 ? $items[0] : ["set" => $items];

        // Validación compuesta para decidir si usar "th"
        $proto_raw = trim($rule["ip.protocol"]);
        $is_tcp_udp = $proto_raw === 'tcp, udp';
        $has_snat = !empty(trim($rule["snat.addr"] ?? ''));
        $has_dnat = !empty(trim($rule["dnat.addr"] ?? ''));
        $has_both_ports = !empty(trim($rule["sport"] ?? '')) && !empty(trim($rule["dport"] ?? ''));

        // Usar 'th' solo si protocolo es 'tcp, udp' y hay snat o dnat
        $proto = ($is_tcp_udp && ($has_snat || $has_dnat || $has_both_ports)) ? 'th' : $proto_raw;

        $expr[] = [
            "match" => [
                "op" => $rule["sport.op"] ?? "==",
                "left" => [
                    "payload" => [
                        "protocol" => $proto,
                        "field" => "sport"
                    ]
                ],
                "right" => $right
            ]
        ];
    }
    //compatibilidad con tcp udp juntos
    // support for tcp udp together
    //dport
    if (!empty($rule["dport"])) {
        $ports = array_map('trim', explode(',', $rule["dport"]));
        $items = [];

        foreach ($ports as $p) {
            if (preg_match('/^(\d+)-(\d+)$/', $p, $m)) {
                // Rango → objeto con clave "range"
                $items[] = ["range" => [(int)$m[1], (int)$m[2]]];
            } elseif (ctype_digit($p)) {
                // Puerto único
                $items[] = (int)$p;
            }
        }

        // Determinar si es un único puerto, un único rango o una combinación
        $right = count($items) === 1 ? $items[0] : ["set" => $items];

        // Validación compuesta para decidir si usar "th"
        $proto_raw = trim($rule["ip.protocol"]);
        $is_tcp_udp = $proto_raw === 'tcp, udp';
        $has_snat = !empty(trim($rule["snat.addr"] ?? ''));
        $has_dnat = !empty(trim($rule["dnat.addr"] ?? ''));
        $has_both_ports = !empty(trim($rule["sport"] ?? '')) && !empty(trim($rule["dport"] ?? ''));
        // Usar 'th' solo si protocolo es 'tcp, udp' y hay snat o dnat
        $proto = ($is_tcp_udp && ($has_snat || $has_dnat || $has_both_ports )) ? 'th' : $proto_raw;

        $expr[] = [
            "match" => [
                "op" => $rule["dport.op"] ?? "==",
                "left" => [
                    "payload" => [
                        "protocol" => $proto,
                        "field" => "dport"
                    ]
                ],
                "right" => $right
            ]
        ];
    }

    // Interfaces in
    if (!empty($rule["meta.iifname"])) {
        $expr[] = [
            "match" => [
                "op" => "==",
                "left" => ["meta" => ["key" => "iifname"]],
                "right" => $rule["meta.iifname"]
            ]
        ];
    }
    // Interfaces out
    if (!empty($rule["meta.oifname"])) {
        $expr[] = [
            "match" => [
                "op" => "==",
                "left" => ["meta" => ["key" => "oifname"]],
                "right" => $rule["meta.oifname"]
            ]
        ];
    }

    // Estado de conexión
    if (!empty($rule["ct.state"])) {
        $states = array_map("trim", explode(",", $rule["ct.state"]));
        $expr[] = [
            "match" => [
                "op" => "==",
                "left" => ["ct" => ["key" => "state"]],
                "right" => ["set" => $states]
            ]
        ];
    }

    // Counter
    if (isset($rule["packets"]) || isset($rule["bytes"])) {
        $expr[] = [
            "counter" => [
                "packets" => (int)($rule["packets"] ?? 0),
                "bytes" => (int)($rule["bytes"] ?? 0)
            ]
        ];
    }

    // Log
    if (!empty($rule["log"])) {
        $expr[] = [
            "log" => [
                "prefix" => $rule["log"] . " ", // ← espacio añadido
                "flags" => "all",
                "level" => "info"
            ]
        ];
    }

    /*
    if (!empty($rule["log"])) {
        $expr[] = [
            "log" => [
                "prefix" => $rule["log"],
                "flags" => "all",
                "level" => "info"
            ]
        ];
    }
    */
     // SNAT
    if (!empty($rule["snat.addr"])) {
        $snat = ["addr" => $rule["snat.addr"]];
        if (!empty($rule["snat.port"])) {
            $snat["port"] = (int)$rule["snat.port"];
        }
        $expr[] = ["snat" => $snat];
    }
    // DNAT
    if (!empty($rule["dnat.addr"])) {
        $dnat = ["addr" => $rule["dnat.addr"]];
        if (!empty($rule["dnat.port"])) {
            $dnat["port"] = (int)$rule["dnat.port"];
        }
        $expr[] = ["dnat" => $dnat];
    }
    
    //dnat añadida comptabilidad con tcp udp
    // Acción final
    if (!empty($rule["action"])) {
        $expr[] = [$rule["action"] => null];
    }
    return $expr;
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// write and order policy   ///////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Reasigna la posición de una regla según su familia, tabla y cadena
// Reassigns the position of a rule based on its family, table, and chain
function reassign_position(array $rule): array {
    // Carga el JSON que contiene todas las reglas actuales
    // Loads the JSON containing all current rules
    $jsonData = import_policy_nft_json();

    // Si no se puede cargar o no contiene reglas, se devuelve la regla tal cual
    // If loading fails or there are no rules, return the rule as-is
    if (!$jsonData || !isset($jsonData["nftables"])) {
        return $rule;
    }

    // Extrae los valores clave para identificar el grupo de reglas
    // Extracts key values to identify the rule group
    $family = $rule["family"];
    $table = $rule["table"];
    $chain = $rule["chain"];

    // Verifica si la posición viene definida o está vacía
    // Checks whether the position is defined or empty
    $incomingPosition = isset($rule["position"]) && $rule["position"] !== "" ? (int)$rule["position"] : null;

    // Si no viene posición, se asigna la posición 1
    // If no position is provided, assign position 1
    if ($incomingPosition === null) {
        $rule["position"] = 1;
        $incomingPosition = 1;

        // Desplaza hacia adelante (+1) todas las reglas que coincidan en familia, tabla y cadena
        // Shift forward (+1) all rules that match family, table, and chain
        foreach ($jsonData["nftables"] as &$entry) {
            if (isset($entry["rule"])) {
                $r = &$entry["rule"];

                if (
                    isset($r["family"], $r["table"], $r["chain"], $r["position"]) &&
                    $r["family"] === $family &&
                    $r["table"] === $table &&
                    $r["chain"] === $chain
                ) {
                    $r["position"] = (int)$r["position"] + 1;
                }
            }
        }
    } else {
        // Si ya viene una posición, se respeta
        // If a position is already provided, it is respected

        // Desplaza hacia adelante (+1) todas las reglas con posición igual o superior
        // Shift forward (+1) all rules with equal or higher position
        foreach ($jsonData["nftables"] as &$entry) {
            if (isset($entry["rule"])) {
                $r = &$entry["rule"];

                if (
                    isset($r["family"], $r["table"], $r["chain"], $r["position"]) &&
                    $r["family"] === $family &&
                    $r["table"] === $table &&
                    $r["chain"] === $chain &&
                    (int)$r["position"] >= $incomingPosition
                ) {
                    $r["position"] = (int)$r["position"] + 1;
                }
            }
        }
    }

    // Devuelve la regla con la posición ajustada
    // Returns the rule with the adjusted position
    return $rule;
}

/*
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
*/
function update_or_insert_nft_rule(array $rule, array $rulesJson): array {
    $id = isset($rule['id']) ? (int)$rule['id'] : null;
    if (!$id) return $rulesJson;

    foreach ($rulesJson['nftables'] as $index => $entry) {
        if (!isset($entry['rule'])) continue;
        $existingId = isset($entry['rule']['id']) ? (int)$entry['rule']['id'] : null;

        if ($existingId === $id) {
            $rulesJson['nftables'][$index]['rule'] = $rule;
            // Reasigna el retorno de reorder_policies
            $rulesJson = reorder_policies($rulesJson);
            return $rulesJson;
        }
    }

    // Inserta como nueva
    $rulesJson['nftables'][] = ['rule' => $rule];
    $rulesJson = reorder_policies($rulesJson);
    return $rulesJson;
}

function reorder_policies(array $rulesJson): array {
    // Extraer solo las reglas
    $rules = [];
    foreach ($rulesJson['nftables'] as $entry) {
        if (isset($entry['rule'])) {
            $rules[] = $entry;
        }
    }
    // Ordenar solo las reglas por position
    usort($rules, function ($a, $b) {
        $pa = isset($a['rule']['position']) ? (int)$a['rule']['position'] : PHP_INT_MAX;
        $pb = isset($b['rule']['position']) ? (int)$b['rule']['position'] : PHP_INT_MAX;
        return $pa <=> $pb;
    });
    // Reconstruir el array original, reemplazando solo las reglas
    $ruleIndex = 0;
    foreach ($rulesJson['nftables'] as $i => $entry) {
        if (isset($entry['rule'])) {
            $rulesJson['nftables'][$i] = $rules[$ruleIndex++];
        }
    }

    return $rulesJson;
}

































