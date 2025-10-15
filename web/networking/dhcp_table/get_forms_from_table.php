<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verifica si el usuario está autenticado
// Check if the user is authenticated
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtiene el parámetro 'table' desde la URL
// Get 'table' parameter from URL
$nftName = $_GET['table'] ?? '';
$nftName = is_string($nftName) ? trim($nftName) : '';

// Valida que el nombre de la tabla esté permitido
// Validate that the table name is allowed
$allowedTables = ['FORWARDING', 'PREROUTING', 'POSTROUTING', 'input', 'output'];
if (!in_array($nftName, $allowedTables, true)) {
    echo json_encode(['error' => 'Parámetro "table" inválido']);
    exit;
}

// Carga el archivo JSON con la estructura de formularios
// Load the JSON file with form structure
$formPath = '/var/www/backend/checks/system_data/default_forms/forms_policies_nft.json';
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
$ifacePath = '/var/www/backend/checks/system_data/data_interfaces/all_interfaces_list.json';
if (file_exists($ifacePath)) {
    $ifaceRaw = file_get_contents($ifacePath);
    $ifaceData = json_decode($ifaceRaw, true);

    // Si el JSON es válido y contiene interfaces, las añadimos
    // If JSON is valid and contains interfaces, we append them
    if (json_last_error() === JSON_ERROR_NONE && isset($ifaceData["all_interfaces"])) {
        $interfaces = $ifaceData["all_interfaces"];

        // Añade interfaces a meta.iifname
        // Append interfaces to meta.iifname
        if (isset($formData["select"]["meta.iifname"])) {
            $formData["select"]["meta.iifname"] = array_merge($formData["select"]["meta.iifname"], $interfaces);
        }

        // Añade interfaces a meta.oifname
        // Append interfaces to meta.oifname
        if (isset($formData["select"]["meta.oifname"])) {
            $formData["select"]["meta.oifname"] = array_merge($formData["select"]["meta.oifname"], $interfaces);
        }
    }
}

// Funciones específicas por tipo de tabla (actualmente idénticas)
// Table-specific functions (currently identical)
function get_forwarding_form($formData) { return $formData; }
function get_prerouting_form($formData) { return $formData; }
function get_postrouting_form($formData) { return $formData; }
function get_input_form($formData) { return $formData; }
function get_output_form($formData) { return $formData; }

// Selecciona la función correspondiente según el tipo de tabla
// Select the appropriate function based on table type
switch ($nftName) {
    case 'FORWARDING':  $result = get_forwarding_form($formData); break;
    case 'PREROUTING':  $result = get_prerouting_form($formData); break;
    case 'POSTROUTING': $result = get_postrouting_form($formData); break;
    case 'input':       $result = get_input_form($formData); break;
    case 'output':      $result = get_output_form($formData); break;
    default:            $result = ['error' => 'Tipo no soportado'];
}

// Devuelve el JSON final al frontend
// Return the final JSON to the frontend
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
