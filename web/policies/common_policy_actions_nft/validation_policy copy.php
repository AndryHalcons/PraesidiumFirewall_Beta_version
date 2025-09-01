<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

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

//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////PORTS VALIDATION SECTION/////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
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
    echo json_encode(["error" => "alias port '{$value}' not found"]);
    exit;
}
// Convierte una lista de puertos, alias y grupos en puertos reales
// Converts a list of ports, aliases, and groups into real port numbers
function convert_alias_port_group_to_network_port(string $value): string {
    // Importa el archivo JSON con los alias definidos
    // Imports the JSON file containing defined aliases
    $aliasJsonData = import_alias_json();

    // Si no se pudo cargar el archivo, se detiene el script
    // If the file couldn't be loaded, stop the script
    if (!$aliasJsonData) {
        echo json_encode(["error" => "alias file not found or invalid"]);
        exit;
    }

    $finalPorts = []; // Lista final de puertos convertidos
    $items = array_map('trim', explode(',', $value)); // Divide la cadena por comas y elimina espacios

    // Recorre cada elemento recibido (puerto, alias o grupo)
    // Iterate over each received item (port, alias, or group)
    foreach ($items as $item) {
        // Si el valor es numérico o un rango válido, se valida y se conserva
        // If the value is numeric or a valid range, validate and keep it
        if (ctype_digit($item) || preg_match('/^\d+-\d+$/', $item)) {
            validation_ports_range($item); // Valida el rango o puerto individual
            $finalPorts[] = $item;
            continue;
        }

        $foundGroup = false; // Bandera para saber si se encontró como grupo

        // Verifica si el valor es un grupo de alias de servicio
        // Check if the value is a service alias group
        if (isset($aliasJsonData['alias_service_group'])) {
            foreach ($aliasJsonData['alias_service_group'] as $group) {
                if (isset($group['name']) && $group['name'] === $item) {
                    // Si coincide, procesa cada elemento del grupo
                    // If matched, process each item in the group
                    foreach ($group['content'] as $entry) {
                        if (ctype_digit($entry) || preg_match('/^\d+-\d+$/', $entry)) {
                            validation_ports_range($entry); // Valida cada entrada del grupo
                            $finalPorts[] = $entry;
                        } else {
                            $resolved = convert_alias_port_to_network_port($entry);
                            validation_ports_range($resolved); // Valida el resultado del alias
                            $finalPorts[] = $resolved;
                        }
                    }
                    $foundGroup = true;
                    break;
                }
            }
        }

        // Si no es grupo, se trata como alias individual
        // If not a group, treat as individual alias
        if (!$foundGroup) {
            $resolved = convert_alias_port_to_network_port($item);
            validation_ports_range($resolved); // Valida el resultado del alias
            $finalPorts[] = $resolved;
        }
    }

    // Limpia duplicados y solapamientos antes de devolver
    // Clean duplicates and overlaps before returning
    $cleaned = validation_not_duplicate_ports(implode(',', $finalPorts));

    // Devuelve la lista final como cadena optimizada
    // Return the final optimized list as a comma-separated string
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
    // Divide la cadena por comas y elimina espacios
    // Splits the string by commas and trims whitespace
    $items = array_map('trim', explode(',', $value));
    $normalized = [];

    foreach ($items as $item) {
        // IP sin CIDR → se normaliza como /32 (IPv4) o /128 (IPv6)
        // IP without CIDR → normalized as /32 (IPv4) or /128 (IPv6)
        if (filter_var($item, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $normalized[] = "{$item}/32";
        } elseif (filter_var($item, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $normalized[] = "{$item}/128";
        }
        // IP con CIDR → se valida y se agrega si es válida
        // IP with CIDR → validated and added if correct
        elseif (preg_match('/^(.+)\/(\d{1,3})$/', $item, $matches)) {
            $ip = $matches[1];
            $cidr = (int)$matches[2];

            // Verifica que la IP y la máscara sean válidas para IPv4
            // Checks that IP and mask are valid for IPv4
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $cidr >= 0 && $cidr <= 32) {
                $normalized[] = "{$ip}/{$cidr}";
            }
            // Verifica que la IP y la máscara sean válidas para IPv6
            // Checks that IP and mask are valid for IPv6
            elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && $cidr >= 0 && $cidr <= 128) {
                $normalized[] = "{$ip}/{$cidr}";
            }
            // CIDR inválido → se muestra error y se detiene
            // Invalid CIDR → shows error and exits
            else {
                echo json_encode(["error" => "invalid CIDR '{$item}'"]);
                exit;
            }
        }
        // Formato inválido → se muestra error y se detiene
        // Invalid format → shows error and exits
        else {
            echo json_encode(["error" => "invalid IP format '{$item}'"]);
            exit;
        }
    }

    // Elimina duplicados exactos
    // Removes exact duplicates
    $normalized = array_unique($normalized);

    // Ordena por máscara ascendente (más amplias primero)
    // Sorts by ascending mask (broader networks first)
    usort($normalized, function ($a, $b) {
        [$ipA, $maskA] = explode('/', $a);
        [$ipB, $maskB] = explode('/', $b);
        return (int)$maskA <=> (int)$maskB;
    });

    $final = [];

    foreach ($normalized as $candidate) {
        $contained = false;

        // Verifica si la red candidata ya está contenida en alguna existente
        // Checks if the candidate network is already contained in an existing one
        foreach ($final as $existing) {
            if (cidr_contains($existing, $candidate)) {
                $contained = true;
                break;
            }
        }

        // Si no está contenida, se agrega al resultado final
        // If not contained, adds it to the final result
        if (!$contained) {
            $final[] = $candidate;
        }
    }

    // Devuelve las redes normalizadas y filtradas como cadena
    // Returns the normalized and filtered networks as a string
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

function convert_alias_ip_to_ip(string $value): string {
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
                return $entry['content'][0] ?? '';
            }
        }
    }

    // Si no se encuentra, se detiene el script y se devuelve error
    // If not found, stop the script and return error
    echo json_encode(["error" => "alias IP '{$value}' not found"]);
    exit;
}





function convert_alias_group_to_Network_ips(string $value): string {
    $aliasJsonData = import_alias_json();

    if (!$aliasJsonData) {
        echo json_encode(["error" => "alias file not found or invalid"]);
        exit;
    }

    $items = array_map('trim', explode(',', $value)); // Divide por comas
    $resolvedIps = [];

    foreach ($items as $item) {
        // Si es IP o CIDR válida, se conserva
        if (validate_ip_or_cidr($item)) {
            $resolvedIps[] = $item;
            continue;
        }

        $foundGroup = false;

        // Verifica si es un grupo
        if (isset($aliasJsonData['alias_addr_group'])) {
            foreach ($aliasJsonData['alias_addr_group'] as $group) {
                if (isset($group['name']) && $group['name'] === $item) {
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
        if (!$foundGroup) {
            $ip = convert_alias_ip_to_ip($item);
            if ($ip !== '') {
                $resolvedIps[] = $ip;
                continue;
            }

            // Si no se pudo resolver, se lanza error
            echo json_encode(["error" => "alias or group '{$item}' not found or invalid"]);
            exit;
        }
    }

    return implode(',', $resolvedIps);
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




// Simulación de datos de entrada
$fakeRule = [
    'ip.daddr'=> '192.168.1.1',  
    'ip.saddr'=> '10.10.10.10',  
    'dnat.addr'=> 'Google_DNS', 
    'snat.addr' => 'Private-networks,Google_DNS,7.7.7.7/26,10.0.0.1/24,10.50.100.1',
    'ifname' => 'ens21',
    'sport' => 'HTTPS',
    'dport' => '22-50,77,45-80,100-200,98',
    'dnat.port' => '50-155,SSH,22,443,Management,HTTPS,Management,90',
];

// Ejecuta la conversión
$convertedRule = Main_convert_alias_object_to_network_object($fakeRule);

// Muestra el resultado
echo "<pre>";
print_r($convertedRule);
echo "</pre>";














