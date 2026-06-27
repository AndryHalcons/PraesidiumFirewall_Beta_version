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
////////////////////////////////////    family nftables field       //////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////

function validationFamiliy($data, $rule)
    {
        switch (strtoupper($data['table'])) {
            case 'FORWARDING':
                $rule['family'] = 'inet';
                $rule['table'] = 'filter';
                $rule['chain'] = 'FORWARDING';
                break;
            case 'PREROUTING':
                $rule['family'] = 'inet';
                $rule['table'] = 'nat';
                $rule['chain'] = 'PREROUTING';
                break;
            case 'POSTROUTING':
                $rule['family'] = 'inet';
                $rule['table'] = 'nat';
                $rule['chain'] = 'POSTROUTING';
                break;
            case 'INPUT':
                $rule['family'] = 'inet';
                $rule['table'] = 'filter';
                $rule['chain'] = 'input';
                break;
            case 'OUTPUT':
                $rule['family'] = 'inet';
                $rule['table'] = 'filter';
                $rule['chain'] = 'output';
                break;
        }

        return $rule;
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

    // Validar checkbox
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
    $data = import_policy_nft_json();

    // Si el archivo no existe o está mal formado, se detiene el script
    // If the file doesn't exist or is malformed, stop the script
    if (!$data || !isset($data['nftables']) || !is_array($data['nftables'])) {
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
        foreach ($data['nftables'] as $entry) {
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



//convierte el campo name y el campo id en partes del campo comment de nftables
//si no hay id por que la regla por ejemplo es nueva, se llama a get_id_from_policy() que devuelve un id unico
//makes the name field and id field parts of the nftables comment field
//if there is no id because the rule is new, for example, get_id_from_policy() is called which returns a unique id
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// validate NFT syntax protocols  /////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function validate_nft_rule_protocols(array $rule): void {
    $ipProtocol = strtolower(trim((string)($rule['ip.protocol'] ?? '')));
    $table = trim((string)($rule['table'] ?? ''));
    $sport = trim((string)($rule['sport'] ?? ''));
    $sportOp = trim((string)($rule['sport.op'] ?? ''));
    $dport = trim((string)($rule['dport'] ?? ''));
    $dportOp = trim((string)($rule['dport.op'] ?? ''));
    $snatAddr = trim((string)($rule['snat.addr'] ?? ''));
    $dnatAddr = trim((string)($rule['dnat.addr'] ?? ''));
    $dnatPort = trim((string)($rule['dnat.port'] ?? ''));
    $redirect = trim((string)($rule['redirect'] ?? ''));
    $ipSaddr = trim((string)($rule['ip.saddr'] ?? ''));
    $ipSaddrOp = trim((string)($rule['ip.saddr.op'] ?? ''));
    $ipDaddr = trim((string)($rule['ip.daddr'] ?? ''));
    $ipDaddrOp = trim((string)($rule['ip.daddr.op'] ?? ''));
    $ctState = trim((string)($rule['ct.state'] ?? ''));

    // 1. sport vacío -> sport.op debe estar vacío
    if ($sport === '' && $sportOp !== '') {
        echo json_encode(['error' => "Si sport está vacío, sport.op también debe estarlo"]);
        exit;
    }

    // 2. dport vacío -> dport.op debe estar vacío
    if ($dport === '' && $dportOp !== '') {
        echo json_encode(['error' => "Si dport está vacío, dport.op también debe estarlo"]);
        exit;
    }

    // 3. snat.addr y dnat.addr no pueden tener valor al mismo tiempo
    if ($snatAddr !== '' && $dnatAddr !== '') {
        echo json_encode(['error' => "snat.addr y dnat.addr no pueden tener valor al mismo tiempo"]);
        exit;
    }

    // 4. ip.saddr vacío -> ip.saddr.op también debe estarlo
    if ($ipSaddr === '' && $ipSaddrOp !== '') {
        echo json_encode(['error' => "Si ip.saddr está vacío, ip.saddr.op también debe estarlo"]);
        exit;
    }

    // 5. ip.daddr vacío -> ip.daddr.op también debe estarlo
    if ($ipDaddr === '' && $ipDaddrOp !== '') {
        echo json_encode(['error' => "Si ip.daddr está vacío, ip.daddr.op también debe estarlo"]);
        exit;
    }

    // 6. ip.protocol contiene UDP -> ct.state debe estar vacío
    if (str_contains($ipProtocol, 'udp') && $ctState !== '') {
        echo json_encode(['error' => "No se permite ct.state si ip.protocol contiene UDP"]);
        exit;
    }

    // 7. ip.protocol = "tcp, udp" -> ct.state debe estar vacío
    if ($ipProtocol === 'tcp, udp' && $ctState !== '') {
        echo json_encode(['error' => "No se permite ct.state si ip.protocol es 'tcp, udp'"]);
        exit;
    }

    // 8. ip.protocol = icmp -> no debe tener dnat.port ni sport/dport o redirect
    if ($ipProtocol === 'icmp') {
        if ($dnatPort !== '' || $sport !== '' || $dport !== '' || $redirect !== '') {
            echo json_encode(['error' => "icmp no debe tener dnat.port ni sport/dport"]);
            exit;
        }
    }

    // 9. ip.protocol = icmpv6 -> igual que icmp
    if ($ipProtocol === 'icmpv6') {
        if ($dnatPort !== '' || $sport !== '' || $dport !== '' || $redirect !== '') {
            echo json_encode(['error' => "icmpv6 no debe tener dnat.port ni sport/dport"]);
            exit;
        }
    }
    // 13. Si ip.protocol contiene "icmp" -> campos de puertos deben estar vacíos
    if (str_contains($ipProtocol, 'icmp') && !str_contains($ipProtocol, 'icmpv6')) {
        if ($sport !== '' || $sportOp !== '' || $dport !== '' || $dportOp !== '' || $dnatPort !== '' || $redirect !== '') {
            echo json_encode(['error' => "ip.protocol = 'icmp' no permite campos de puertos"]);
            exit;
        }
    }

    // 14. Si ip.protocol contiene "icmpv6" -> campos de puertos deben estar vacíos
    if (str_contains($ipProtocol, 'icmpv6')) {
        if ($sport !== '' || $sportOp !== '' || $dport !== '' || $dportOp !== '' || $dnatPort !== '' || $redirect !== '') {
            echo json_encode(['error' => "ip.protocol = 'icmpv6' no permite campos de puertos"]);
            exit;
        }
    }
    // 15. no se permite mezclar ipv4 e ipv6 en la misma regla
    if (!contains_mixed_ip_versions_nft($ipSaddr, $ipDaddr, $snatAddr, $dnatAddr)) {
        echo json_encode(['error' => "No se permite mezclar IPv4 e IPv6 en los campos IP"]);
        exit;
    }

    // 16. Si table = nat -> al menos uno de snat.addr o dnat.addr o masquerade o redirect debe tener valor dnat.port
    if ($table === 'nat' && ($snatAddr === '' && $dnatAddr === '' && strtolower($rule['masquerade'] ?? '') !== 'true') && $dnatPort === ''  && $redirect === '') {
    echo json_encode(['error' => "Si table es 'nat', al menos snat.addr o dnat.addr o masquerade o redirect debe tener valor"]);
    exit;
    }

    // 17. Si redirect tiene valor, dnat.addr y dnat.port deben estar vacíos
    if ($redirect !== '' && ($dnatAddr !== '' || $dnatPort !== '')) {
        echo json_encode(['error' => "Si se usa redirect, no se permite definir dnat.addr ni dnat.port"]);
        exit;
    }

    // 18. Si masquerade tiene valor, snat.addr no debe estar definido
    if (strtolower($rule['masquerade'] ?? '') === 'true' && $snatAddr !== '') {
        echo json_encode(['error' => "Si se usa masquerade, no se permite definir snat.addr"]);
        exit;
    }


}



// Verificación de mezcla de IPv4 e IPv6 en source y destination
function contains_mixed_ip_versions_nft(string $ipSaddr = '', string $ipDaddr = '', string $snatAddr = '', string $dnatAddr = ''): bool {
    $allVersions = [];

    $fields = [$ipSaddr, $ipDaddr, $snatAddr, $dnatAddr];

    foreach ($fields as $field) {
        if (!is_string($field) || trim($field) === '') {
            continue;
        }

        $entries = preg_split('/[\s,]+/', $field, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($entries as $entry) {
            $version = detect_ip_version($entry);

            if ($version === 'IPv4' || $version === 'IPv6') {
                $allVersions[] = $version;
            }
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
//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////PORTS VALIDATION SECTION/////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
//elimina puertos de los campos puerto si el protocolo de la regla es icmp, tambien borra los puertos sportOP si sport esta vacio y dportOP si dport está vacio
//Remove ports from the port fields if the rule protocol is icmp, also delete the sportOP ports if sport is empty and dportOP if dport is empty
function validation_icmp_no_ports(array $rule): array {
    $protocol = strtolower($rule['ip.protocol'] ?? '');

    // Si el protocolo es ICMP o ICMPv6, limpiamos todos los campos de puertos
    if ($protocol === 'icmp' || $protocol === 'icmpv6') {
        $fieldsToClear = [
            'sport.op',
            'sport',
            'dport.op',
            'dport',
            'dnat.port',
            'redirect'
        ];

        foreach ($fieldsToClear as $field) {
            if (array_key_exists($field, $rule)) {
                $rule[$field] = '';
            }
        }
    }

    // Si sport está vacío, también vaciamos sport.op
    if (empty($rule['sport'])) {
        $rule['sport.op'] = '';
    }

    // Si dport está vacío, también vaciamos dport.op
    if (empty($rule['dport'])) {
        $rule['dport.op'] = '';
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
    // si masquerade está activado borramos el campo snat antes de procesar
     // If masquerade is enabled, clear snat.addr to avoid conflict with dynamic NAT
    if (isset($rule['masquerade']) && strtolower($rule['masquerade']) === 'true') {
        $rule['snat.addr'] = '';
    }
    // Campos relacionados con puertos
    $portFields = ['sport', 'dport', 'dnat.port', 'redirect'];

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
    // si ip origen esta vacio su operador tambien debe estarlo
     if (empty($rule['ip.saddr'])) {
        $rule['ip.saddr.op'] = '';
    }
    // si ip destino sta vacio su operador tambien debe estarlo
    if (empty($rule['ip.daddr'])) {
        $rule['ip.daddr.op'] = '';
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
            "masquerade"    => $rule["masquerade"]    ?? "",
            "snat.port"     => $rule["snat.port"]     ?? "",
            "dnat.addr"     => $rule["dnat.addr"]     ?? "",
            "dnat.port"     => $rule["dnat.port"]     ?? "",
            "redirect"      => $rule["redirect"]     ?? ""
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

function reorderPosition($rulesJson, $id, $position, $family, $table, $chain) {
    $targetPos = (int)$position;

    // Separar reglas del bloque afectado y las demás
    $block = [];
    $others = [];

    foreach ($rulesJson['nftables'] as $entry) {
        $rule = $entry['rule'];
        $rule['position'] = (int)$rule['position'];

        if (
            $rule['family'] === $family &&
            $rule['table'] === $table &&
            $rule['chain'] === $chain
        ) {
            $block[] = $entry;
        } else {
            $others[] = $entry;
        }
    }

    // Buscar la regla objetivo por ID
    $targetIndex = null;
    foreach ($block as $i => $entry) {
        if ((string)$entry['rule']['id'] === (string)$id) {
            $targetIndex = $i;
            break;
        }
    }

    if ($targetIndex === null) {
        return $rulesJson; // No se encontró la regla
    }

    // Aplicar lógica de desplazamiento
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
    usort($block, fn($a, $b) => $a['rule']['position'] <=> $b['rule']['position']);
    $pos = 1;
    foreach ($block as &$entry) {
        $entry['rule']['position'] = $pos++;
    }

    // Reconstruir el JSON con orden físico correcto
    $rulesJson['nftables'] = array_merge($others, $block);
    return $rulesJson;
}









