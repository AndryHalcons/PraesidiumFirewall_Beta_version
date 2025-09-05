<?php
session_start();
// Verifica si el usuario está autenticado
// Check if the user is authenticated
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtiene el parámetro 'table' desde la URL
// Get 'table' parameter from URL
$bpfName = $_GET['table'] ?? '';
$bpfName = is_string($bpfName) ? trim($bpfName) : '';

// Valida que el nombre de la tabla esté permitido
// Validate that the table name is allowed
$allowedTables = ['BF_HOOK_XDP', 'BF_HOOK_TC_INGRESS', 'BF_HOOK_TC_EGRESS'];
if (!in_array($bpfName, $allowedTables, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

// Carga el archivo JSON con la estructura de formularios
// Load the JSON file with form structure
$formPath = '/var/www/backend/checks/system_data/default_forms/forms_policies_bpf.json';
if (!file_exists($formPath)) {
    echo json_encode(['error' => 'Archivo de configuración no encontrado']);
    exit;
}

$formRaw = file_get_contents($formPath);
$formData = json_decode($formRaw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'JSON mal formado']);
    exit;
}

// Carga el archivo con la lista de interfaces de red
// Load the file with the list of network interfaces
$ifacePath = '/var/www/backend/checks/system_data/data_interfaces/physical_interfaces_list.json';
if (file_exists($ifacePath)) {
    $ifaceRaw = file_get_contents($ifacePath);
    $ifaceData = json_decode($ifaceRaw, true);

    // Si el JSON es válido y contiene interfaces físicas, las procesamos
    if (json_last_error() === JSON_ERROR_NONE && isset($ifaceData["physical_interfaces"])) {
        $interfaces = [];

        // Extrae solo los nombres de las interfaces
        foreach ($ifaceData["physical_interfaces"] as $iface) {
            if (isset($iface["name"])) {
                $interfaces[] = $iface["name"];
            }
        }

        // Añade interfaces a interface
        if (isset($formData["select"]["interface"])) {
            $formData["select"]["interface"] = array_merge($formData["select"]["interface"], $interfaces);
        }
    }
}



// Carga las cadenas chain disponibles al formulario segun el "bpfName" recibido como parametro
// Loads the available chain strings to the form according to the "bpfName" received as a parameter
$chainPath = '/var/www/config/rules_bpfilter_chain.json';
if (file_exists($chainPath)) {
    $chainRaw = file_get_contents($chainPath);
    $chainData = json_decode($chainRaw, true);

    // Si el JSON es válido y contiene cadenas para el hook actual
    if (json_last_error() === JSON_ERROR_NONE && isset($chainData['chain'][$bpfName])) {
        $chains = $chainData['chain'][$bpfName];

        // Reemplaza el campo "chain" eliminando el valor vacío original
        if (isset($formData["select"]["chain"])) {
            // Ignora completamente lo que venía y construye desde cero
            $formData["select"]["chain"] = $chains;
        }
    }
}




// Funciones específicas por tipo de tabla (actualmente idénticas)
// Table-specific functions (currently identical)
function get_bpfilter_xdp_form($formData) { return $formData; }
function get_policies_tc_ingress_form($formData) { return $formData; }
function get_policies_tc_egress_form($formData) { return $formData; }

// Selecciona la función correspondiente según el tipo de tabla
// Select the appropriate function based on table type
switch ($bpfName) {
    case 'BF_HOOK_XDP':  $result = get_bpfilter_xdp_form($formData); break;
    case 'BF_HOOK_TC_INGRESS':  $result = get_policies_tc_ingress_form($formData); break;
    case 'BF_HOOK_TC_EGRESS': $result = get_policies_tc_egress_form($formData); break;
    default:            $result = ['error' => 'Tipo no soportado'];
}

// Devuelve el JSON final al frontend
// Return the final JSON to the frontend
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
