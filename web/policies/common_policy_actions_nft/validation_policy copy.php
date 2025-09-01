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



// Convierte alias en objetos de red reales usando funciones auxiliares
// Converts aliases into real network objects using helper functions
function convert_alias_object_to_network_object(array $rule): array {
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
            $rule[$field] = convert_alias_group_to_ips($rule[$field]);
        }
    }

    return $rule;
}
function convert_alias_group_to_ips(string $value){}

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


// Convierte una lista de puertos y alias en puertos reales
// Converts a list of ports and aliases into real port numbers
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

    // Devuelve la lista final como cadena separada por comas
    // Return the final list as a comma-separated string
    return implode(',', $finalPorts);
}






// Simulación de datos de entrada
$fakeRule = [
    'sport' => 'HTTPS',
    'dport' => '22-50',
    'dnat.port' => '50-155,SSH,22,443,Management,HTTPS,Management,90',
];

// Ejecuta la conversión
$convertedRule = convert_alias_object_to_network_object($fakeRule);

// Muestra el resultado
echo "<pre>";
print_r($convertedRule);
echo "</pre>";














