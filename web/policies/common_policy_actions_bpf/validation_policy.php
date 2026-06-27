<?php
require_once __DIR__ . '/../../common/security/session.php';
praesidium_session_start();
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
function import_policy_bpf_json() {
    $jsonPath = '/var/www/config/rules_bpfilter_human_viewer.json';

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
function import_forms_bpf_json() {
    $jsonPath = '/var/www/backend/checks/system_data/default_forms/forms_policies_bpf.json';

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
    $path = '/var/www/backend/checks/system_data/data_interfaces/physical_interfaces_list.json';
    if (!file_exists($path)) return [];

    $raw = file_get_contents($path);
    $data = json_decode($raw, true);

    if (!isset($data['physical_interfaces']) || !is_array($data['physical_interfaces'])) {
        return [];
    }

    // Extraer solo los nombres
    // only extract names
    $names = [];
    foreach ($data['physical_interfaces'] as $iface) {
        if (isset($iface['name']) && is_string($iface['name'])) {
            $names[] = $iface['name'];
        }
    }

    return $names;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////    family bpfilter field  & Chain name     //////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////

//valida que sea un hook valido
//validates that it is a valid hook
function validationFamiliy($data, $rule)
    {
        switch (strtoupper($data['table'])) {
            case 'BF_HOOK_XDP':
                $rule['hook'] = 'BF_HOOK_XDP';
                break;
            case 'BF_HOOK_TC_INGRESS':
                $rule['hook'] = 'BF_HOOK_TC_INGRESS';
                break;
            case 'BF_HOOK_TC_EGRESS':
                $rule['hook'] = 'BF_HOOK_TC_EGRESS';
                break;
        }

        return $rule;
}


// Genera el nombre de la cadena según su interfaz y hook; también comprueba que 'hook' e 'interface' no vengan vacíos
// Generates the chain name based on its interface and hook; also checks that 'hook' and 'interface' are not empty

function gen_chain_name(array $rule): array {
    if (empty($rule['interface'])) {
        echo json_encode(['error' => "El campo 'interface' es obligatorio en bpfilter"]);
        exit;
    }

    if (empty($rule['hook'])) {
        echo json_encode(['error' => "El campo 'hook' es obligatorio en bpfilter"]);
        exit;
    }

    $rule['chain'] = "{$rule['interface']}_" . strtolower($rule['hook']);

    return $rule;
}





//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////    form field review        /////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
//revisa los campos que contienen formularios
//check the fields that contain forms

function validation_form_field_review(array $rule): void {
    $formConfig = import_forms_bpf_json();
    if (!$formConfig) {
        echo json_encode(["error" => "No se pudo cargar la configuración del formulario interfaces"]);
        exit;
    }

    // Añadir interfaces del sistema
    $interfaces = import_all_interfaces();
    if (isset($formConfig['select']['interface'])) {
        $formConfig['select']['interface'] = array_merge($formConfig['select']['interface'], $interfaces);
    }

    // Validar select
    if (isset($formConfig['select'])) {
        foreach ($formConfig['select'] as $key => $validValues) {
            if (isset($rule[$key])) {
                $value = trim((string)$rule[$key]);
                if ($value === '') {
                    $rule[$key] = "";
                    continue;
                }
                if (!in_array($value, $validValues, true)) {
                    echo json_encode(["error" => "validation_form_field_review_select '{$value}' not found"]);
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

    // Validar not_editable (excepto id)
    if (isset($formConfig['not_editable'])) {
        foreach ($formConfig['not_editable'] as $key => $validValues) {
            if ($key === 'id' || $key === 'chain') continue;
            if (isset($rule[$key])) {
                $value = $rule[$key];
                if (!in_array($value, $validValues, true)) {
                    echo json_encode(["error" => "validation_form_field_review_not_editable '{$value}' not found"]);
                    exit;
                }
            }
        }
    }
}




//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////ID section  //////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
// Comprueba que tiene un ID correcto, si no tiene le asigna uno
// Check that it has a correct ID, if it doesn't, assign one.
// Verifica y asigna un ID válido a la regla
function get_id_from_policy(array $rule): array {
    // Verifica si el campo 'id' existe
    if (isset($rule['id'])) {
        $idCandidate = $rule['id'];

        // Si es string, eliminar espacios
        if (is_string($idCandidate)) {
            $idCandidate = trim($idCandidate);
        }

        // Si está vacío, nulo o solo espacios -> generar nuevo ID
        if ($idCandidate === '' || $idCandidate === null) {
            $rule['id'] = get_id(); // asigna nuevo ID
            return $rule;
        }

        // Si es entero positivo o string numérico positivo -> lo aceptamos
        if ((is_int($idCandidate) && $idCandidate > 0) ||
            (is_string($idCandidate) && ctype_digit($idCandidate) && (int)$idCandidate > 0)) {
            $rule['id'] = (string)(int)$idCandidate; // normaliza a string
            return $rule;
        }

        // Si está mal formado o es negativo -> generar nuevo ID
        $rule['id'] = get_id();
        return $rule;
    }

    // Si no existe el campo 'id' -> generar nuevo ID
    $rule['id'] = get_id();
    return $rule;
}

//devuelve el proximo id disponbile
//returns the next available id
// Genera un ID único que no esté en uso en el archivo de reglas
// Generates a unique ID not currently used in the rules file
function get_id(): string {
    // Carga el JSON de reglas
    // Load the rules JSON
    $data = import_policy_bpf_json();

    // Si el archivo no existe o está mal formado, se detiene el script
    // If the file doesn't exist or is malformed, stop the script
    if (!$data || !isset($data['bpfilter']) || !is_array($data['bpfilter'])) {
        echo json_encode(['error' => 'Imposible obtener ID']);
        exit;
    }

    // Comenzamos con el ID 1
    // Start with ID 1
    $id = 1;

    // Bucle para encontrar un ID libre
    // Loop to find a free ID
    while (true) {
        $found = false;

        // Recorremos todas las reglas existentes
        // Iterate through all existing rules
        foreach ($data['bpfilter'] as $entry) {
            // Comparamos el ID actual con los existentes
            // Compare current ID with existing ones
            if (isset($entry['rule']['id']) && (string)(int)$entry['rule']['id'] === (string)$id) {
                $found = true;
                break; // Si se encuentra, salimos del foreach
                       // If found, break out of the foreach
            }
        }

        // Si no se encontró el ID, lo devolvemos
        // If the ID wasn't found, return it
        if (!$found) {
            return (string)$id;
        }

        // Si el ID está en uso, incrementamos y volvemos a intentar
        // If the ID is in use, increment and try again
        $id++;
    }
}



//convierte el campo name y el campo id en partes del campo comment de bpfilter
//si no hay id por que la regla por ejemplo es nueva, se llama a get_id_from_policy() que devuelve un id unico
//makes the name field and id field parts of the bpfilter comment field
//if there is no id because the rule is new, for example, get_id_from_policy() is called which returns a unique id

//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////PORTS VALIDATION SECTION/////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
//elimina puertos de los campos puerto si el protocolo de la regla es icmp
//Remove ports from the port fields if the rule protocol is icmp
function validation_icmp_no_ports(array $rule): array {
    $protocol = strtolower($rule['ip.protocol'] ?? '');

    if ($protocol === 'ICMP' || $protocol === 'ICMPv6') {
        $fieldsToClear = [
            'sport',
            'dport'
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
    if (trim($value) === '') {
        return true;
    }
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

function convert_alias_ip_to_ip(string $value): bool {
    // ignorar vacios
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
    $portFields = ['sport', 'dport'];

    foreach ($portFields as $field) {
        if (isset($rule[$field])) {
            // Llama a la función de conversión de puertos solo para validar
            convert_alias_port_group_to_network_port($rule[$field]);
        }
    }

    // Campos relacionados con direcciones IP
    $ipFields = ['source', 'destination'];

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
        return $rule;
    }
    $posCandidate = is_string($rule["position"]) ? trim($rule["position"]) : $rule["position"];
    // Si no es un entero positivo -> asigna 1 por defecto
    // If not a positive integer -> assign default 1
    if (!is_int($posCandidate) && (!is_string($posCandidate) || !ctype_digit($posCandidate))) {
        $rule["position"] = 1;
        return $rule;
    }

    // Normaliza a entero
    // Normalize to integer
    $rule["position"] = (int)$posCandidate;
    return $rule;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// validate  bp filter protocols  /////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function validate_bpfilter_protocols(array $rule): bool {
    $l3 = $rule['l3_protocol'] ?? '';
    $l4 = $rule['l4_protocol'] ?? '';
    $ip6 = $rule['ipv6_next_header'] ?? '';
    $tcpFlags = $rule['tcp_flags'] ?? '';
    $icmpType = $rule['icmp_type'] ?? '';
    $icmpCode = $rule['icmp_code'] ?? '';
    $icmpv6Type = $rule['icmpv6_type'] ?? '';
    $icmpv6Code = $rule['icmpv6_code'] ?? '';
    $source = $rule['source'] ?? '';
    $destination = $rule['destination'] ?? '';
    if (!contains_mixed_ip_versions($source, $destination)) {
        echo json_encode(['error' => "No se permite mezclar IPv4 e IPv6 entre source y destination"]);
        exit;
    }

    // l3 nunca vacio, no se permite por formulario, pero por si acaso contra modificaciones de front
    if ($l3 === '') {
        echo json_encode(['error' => "El campo l3_protocol es obligatorio"]);
        exit;
    }
    // L3: protocolos no compatibles con campos adicionales
    if (in_array($l3, ['MPLS', 'IPX', 'ARP'])) {
        if ($l4 !== '' || $ip6 !== '' || $tcpFlags !== '' || $icmpType !== '' || $icmpCode !== '' || $icmpv6Type !== '' || $icmpv6Code !== '') {
            echo json_encode(['error' => "L3 protocol '{$l3}' no permite campos adicionales"]);
            exit;
        }
    }

    // L3: IPv4 no debe tener campos ICMPv6 ni ipv6_next_header
    if ($l3 === 'IPv4') {
        if ($ip6 !== '') {
            echo json_encode(['error' => "ipv6_next_header no es compatible con IPv4"]);
            exit;
        }
        if ($icmpv6Type !== '' || $icmpv6Code !== '') {
            echo json_encode(['error' => "icmpv6_type/code no son válidos con IPv4"]);
            exit;
        }
    }

    // L3: IPv6 no debe tener campos ICMP
    if ($l3 === 'IPv6') {
        if ($icmpType !== '' || $icmpCode !== '' ) {
            echo json_encode(['error' => "icmp_type/code no son válidos con IPv6"]);
            exit;
        }
    }
        // ipv6_next_header solo valido con IPv6
    if ($l3 !== 'IPv6' && $ip6 !== '') {
        echo json_encode(['error' => "ipv6_next_header solo es válido con IPv6"]);
        exit;
    }


    // L4: TCP permite tcp_flags, pero no ICMP ni ICMPv6
    if ($l4 === 'TCP') {
        if ($icmpType !== '' || $icmpCode !== '' || $icmpv6Type !== '' || $icmpv6Code !== '') {
            echo json_encode(['error' => "TCP no debe tener campos ICMP ni ICMPv6"]);
            exit;
        }
    }

    // L4: UDP no permite ICMP, ICMPv6 ni tcp_flags
    if ($l4 === 'UDP') {
        if ($icmpType !== '' || $icmpCode !== '' || $icmpv6Type !== '' || $icmpv6Code !== '') {
            echo json_encode(['error' => "UDP no debe tener campos ICMP ni ICMPv6"]);
            exit;
        }
        if ($tcpFlags !== '') {
            echo json_encode(['error' => "tcp_flags no es válido con UDP"]);
            exit;
        }
    }

    // L4: ICMP no permite ICMPv6 ni tcp_flags
    if ($l4 === 'ICMP') {
        if ($icmpv6Type !== '' || $icmpv6Code !== '') {
            echo json_encode(['error' => "ICMP no debe tener campos ICMPv6"]);
            exit;
        }
        if ($tcpFlags !== '') {
            echo json_encode(['error' => "tcp_flags no es válido con ICMP"]);
            exit;
        }
    }

    // L4: ICMPv6 no permite ICMP ni tcp_flags
    if ($l4 === 'ICMPv6') {
        if ($icmpType !== '' || $icmpCode !== '') {
            echo json_encode(['error' => "ICMPv6 no debe tener campos ICMP"]);
            exit;
        }
        if ($tcpFlags !== '') {
            echo json_encode(['error' => "tcp_flags no es válido con ICMPv6"]);
            exit;
        }
    }

    // tcp_flags solo válido con TCP
    if ($tcpFlags !== '' && $l4 !== 'TCP') {
        echo json_encode(['error' => "tcp_flags solo es válido con TCP"]);
        exit;
    }
    //compatibilidad campo next header
    if ($ip6 !== '') {
        // Si ipv6_next_header indica TCP, no debe haber ICMP ni ICMPv6
        if ($ip6 === 'TCP') {
            if ($icmpType !== '' || $icmpCode !== '' || $icmpv6Type !== '' || $icmpv6Code !== '') {
                echo json_encode(['error' => "ipv6_next_header = TCP no debe tener campos ICMP ni ICMPv6"]);
                exit;
            }
        }

        // Si ipv6_next_header indica UDP, no debe haber ICMP ni ICMPv6 ni tcp_flags
        if ($ip6 === 'UDP') {
            if ($icmpType !== '' || $icmpCode !== '' || $icmpv6Type !== '' || $icmpv6Code !== '' || $tcpFlags !== '') {
                echo json_encode(['error' => "ipv6_next_header = UDP no debe tener ICMP, ICMPv6 ni tcp_flags"]);
                exit;
            }
        }

        // Si ipv6_next_header indica ICMP, no debe haber ICMPv6 ni tcp_flags
        if ($ip6 === 'ICMP') {
            if ($icmpv6Type !== '' || $icmpv6Code !== '' || $tcpFlags !== '') {
                echo json_encode(['error' => "ipv6_next_header = ICMP no debe tener campos ICMPv6 ni tcp_flags"]);
                exit;
            }
        }

        // Si ipv6_next_header indica ICMPv6, no debe haber ICMP ni tcp_flags
        if ($ip6 === 'ICMPv6') {
            if ($icmpType !== '' || $icmpCode !== '' || $tcpFlags !== '') {
                echo json_encode(['error' => "ipv6_next_header = ICMPv6 no debe tener campos ICMP ni tcp_flags"]);
                exit;
            }
        }

        // Si ipv6_next_header indica Hop-by-Hop, Routing, Fragment, AH, ESP, Destination -> no debe haber ningún campo adicional
        if (in_array($ip6, ['Hop-by-Hop', 'Routing', 'Fragment', 'AH', 'ESP', 'Destination'])) {
            if ($l4 !== '' || $tcpFlags !== '' || $icmpType !== '' || $icmpCode !== '' || $icmpv6Type !== '' || $icmpv6Code !== '') {
                echo json_encode(['error' => "ipv6_next_header = '{$ip6}' no permite campos adicionales"]);
                exit;
            }
        }
    }


    return true;
}


// Verificación de mezcla de IPv4 e IPv6 en source y destination
function contains_mixed_ip_versions(string $source, string $destination): bool {
    $allVersions = [];

    // Combina source y destination en una sola lista
    $combined = array_merge(
        preg_split('/[\s,]+/', $source, -1, PREG_SPLIT_NO_EMPTY),
        preg_split('/[\s,]+/', $destination, -1, PREG_SPLIT_NO_EMPTY)
    );

    foreach ($combined as $entry) {
        $version = detect_ip_version($entry);

        // Ignorar vacíos, alias y entradas desconocidas
        if ($version === 'IPv4' || $version === 'IPv6') {
            $allVersions[] = $version;
        }
    }

    // Si no hay IPs válidas, no hay mezcla
    if (count($allVersions) === 0) {
        return true;
    }

    // Si hay más de un tipo de IP, hay mezcla
    return count(array_unique($allVersions)) === 1;
}


function detect_ip_version(string $input): string {
    // Elimina la máscara si es una red (ej. 192.168.0.0/24)
    $ip = explode('/', $input)[0];

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return 'IPv4';
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return 'IPv6';
    }

    return 'Desconocido';
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// Saniticed to bpfilter json format  ///////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Función para convertir la regla al formato de bpfilter
// Function to convert the rule to bpfilter format
// Genera la estructura base de una regla bpfilter
// Generates the base structure of an bpfilter rule
function saniticed_bpfilter_policy(array $rule): array {
    return [
        "rule" => [
            "id"               => $rule["id"]               ?? "",
            "hook"             => $rule["hook"]             ?? "",
            "chain"            => $rule["chain"]            ?? "",
            "position"         => $rule["position"]         ?? "",
            "action"           => $rule["action"]           ?? "",
            "enable"           => $rule["enable"]           ?? "",
            "name"             => $rule["name"]             ?? "",
            "interface"        => $rule["interface"]        ?? "",
            "l3_protocol"      => $rule["l3_protocol"]      ?? "",
            "l4_protocol"      => $rule["l4_protocol"]      ?? "",
            "source"           => $rule["source"]           ?? "",
            "sport"            => $rule["sport"]            ?? "",
            "destination"      => $rule["destination"]      ?? "",
            "dport"            => $rule["dport"]            ?? "",
            "tcp_flags"        => $rule["tcp_flags"]        ?? "",
            "ipv6_next_header" => $rule["ipv6_next_header"] ?? "",
            "icmp_type"        => $rule["icmp_type"]        ?? "",
            "icmp_code"        => $rule["icmp_code"]        ?? "",
            "icmpv6_type"      => $rule["icmpv6_type"]      ?? "",
            "icmpv6_code"      => $rule["icmpv6_code"]      ?? "",
            "probability"      => $rule["probability"]      ?? ""
        ]
    ];
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// write and order policy   ///////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Reasigna la posición de una regla según su familia
// Reassigns the position of a rule based on its family
function reassign_position(array $rule): array {
    $jsonData = import_policy_bpf_json();

    // Si no hay datos válidos, devolvemos la regla tal cual
    // If no valid data, return the rule unchanged
    if (!$jsonData || !isset($jsonData["bpfilter"]) || !is_array($jsonData["bpfilter"])) {
        return $rule;
    }

    // Normalizamos los campos hook y chain
    // Normalize hook and chain fields
    $hook = trim((string)($rule["hook"] ?? ""));
    $chain = trim((string)($rule["chain"] ?? ""));

    // Definimos la familia: hook + chain si chain tiene valor, solo hook si está vacío
    // Define the family: hook + chain if chain has value, only hook if empty
    $family = $chain !== "" ? "{$hook}_{$chain}" : $hook;

    // Normalizamos la posición entrante
    // Normalize incoming position
    $incomingPosition = isset($rule["position"]) && $rule["position"] !== ""
        ? (int)$rule["position"]
        : null;

    if ($incomingPosition === null) {
        // Si no hay posición, asignamos la primera (1)
        // If no position, assign first (1)
        $rule["position"] = 1;
        $incomingPosition = 1;

        // Recorremos las reglas existentes y aumentamos posición si pertenecen a la misma familia
        // Iterate existing rules and increment position if they belong to the same family
        foreach ($jsonData["bpfilter"] as &$entry) {
            $entryHook = trim((string)($entry["rule"]["hook"] ?? ""));
            $entryChain = trim((string)($entry["rule"]["chain"] ?? ""));
            $entryFamily = $entryChain !== "" ? "{$entryHook}_{$entryChain}" : $entryHook;

            if (isset($entry["rule"]["position"]) && $entryFamily === $family) {
                $entry["rule"]["position"] = (int)$entry["rule"]["position"] + 1;
            }
        }
    } else {
        // Si ya hay posición, ajustamos las reglas con posición igual o mayor en la misma familia
        // If position exists, adjust rules with equal or higher position in the same family
        foreach ($jsonData["bpfilter"] as &$entry) {
            $entryHook = trim((string)($entry["rule"]["hook"] ?? ""));
            $entryChain = trim((string)($entry["rule"]["chain"] ?? ""));
            $entryFamily = $entryChain !== "" ? "{$entryHook}_{$entryChain}" : $entryHook;

            if (
                isset($entry["rule"]["position"]) &&
                $entryFamily === $family &&
                (int)$entry["rule"]["position"] >= $incomingPosition
            ) {
                $entry["rule"]["position"] = (int)$entry["rule"]["position"] + 1;
            }
        }
    }

    // Devolvemos la regla actualizada
    // Return the updated rule
    return $rule;
}


function update_or_insert_bpf_rule(array $rule, array $rulesJson): array {
    // Normaliza el ID de la nueva regla
    $id = isset($rule['id']) ? (int)$rule['id'] : null;

    if (!$id) return $rulesJson;

    foreach ($rulesJson['bpfilter'] as $index => $entry) {
        if (!isset($entry['rule'])) continue;

        $existing = $entry['rule'];

        // Normaliza el ID de la regla existente
        $existingId = isset($existing['id']) ? (int)$existing['id'] : null;

        // Compara los IDs como enteros
        if ($existingId === $id) {
            $rulesJson['bpfilter'][$index]['rule'] = $rule;
            return $rulesJson;
        }
    }

    // Si no se encontró coincidencia, se inserta como nueva
    $rulesJson['bpfilter'][] = ['rule' => $rule];
    return $rulesJson;
}

function reorderPosition($rulesJson, $id, $position, $hook, $chain) {
    $targetPos = (int)$position;

    // Normalizar la familia objetivo
    // Normalize target family
    $chain = trim((string)$chain);
    $targetFamily = $chain !== "" ? "{$hook}_{$chain}" : $hook;

    $block = [];
    $others = [];

    // Separar reglas del bloque afectado y las demás
    // Separate affected block rules and others
    foreach ($rulesJson['bpfilter'] as $entry) {
        $rule = $entry['rule'];
        $rule['position'] = (int)$rule['position'];

        $entryChain = trim((string)($rule['chain'] ?? ""));
        $entryHook = $rule['hook'] ?? "";
        $entryFamily = $entryChain !== "" ? "{$entryHook}_{$entryChain}" : $entryHook;

        if ($entryFamily === $targetFamily) {
            $block[] = $entry;
        } else {
            $others[] = $entry;
        }
    }

    // Buscar la regla objetivo por ID
    // Find target rule by ID
    $targetIndex = null;
    foreach ($block as $i => $entry) {
        if ((string)$entry['rule']['id'] === (string)$id) {
            $targetIndex = $i;
            break;
        }
    }

    if ($targetIndex === null) {
        return $rulesJson; 
        // No se encontró la regla
        // Rule not found
    }

    // Aplicar lógica de desplazamiento
    // Apply position shifting logic
    foreach ($block as $i => &$entry) {
        if ($i === $targetIndex) {
            $entry['rule']['position'] = $targetPos;
            continue;
        }

        $pos = $entry['rule']['position'];
        if ($pos >= $targetPos) {
            $entry['rule']['position'] = $pos + 1;
        }
    }

    // Reordenar y renumerar secuencialmente
    // Reorder and renumber sequentially
    usort($block, fn($a, $b) => $a['rule']['position'] <=> $b['rule']['position']);
    $pos = 1;
    foreach ($block as &$entry) {
        $entry['rule']['position'] = $pos++;
    }

    // Reconstruir el JSON con orden físico correcto
    // Rebuild JSON with correct physical order
    $rulesJson['bpfilter'] = array_merge($others, $block);
    return $rulesJson;
}












