<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado"); 

}

$aliasFile = '/var/www/config/alias.json'; 

//validaciones de  borrados:
//delete validations
require_once __DIR__ . '/validation_alias.php';



// Leer el cuerpo del request como JSON
// Read the request body as JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validar que la entrada sea un array válido
// Validate that the input is a valid array
if (!$input || !is_array($input)) {
    http_response_code(400); // Error de cliente
    // Client error
    echo json_encode(['error' => 'Entrada inválida']);
    exit;
}

// Verificar que el archivo de alias existe
// Check that the alias file exists
if (!file_exists($aliasFile)) {
    http_response_code(500); // Error del servidor
    // Server error
    echo json_encode(['error' => 'Archivo de configuración no encontrado']);
    exit;
}

// Cargar el contenido del archivo alias.json
// Load the contents of alias.json
$aliasData = json_decode(file_get_contents($aliasFile), true);

// Validar que la estructura del archivo sea correcta
// Validate that the file structure is correct
if (!$aliasData || !is_array($aliasData)) {
    http_response_code(500);
    echo json_encode(['error' => 'Estructura de alias inválida']);
    exit;
}

// Función para eliminar una entrada de alias_address
// Function to delete an entry from alias_address
function deleteAliasAddress(&$data, $id) {
    isAliasAddressNameInGroupContent($data, $id);
    $data['alias_address'] = array_values(array_filter(
        $data['alias_address'],
        fn($item) => intval($item['id']) !== intval($id)
    ));
}

// Función para eliminar una entrada de alias_addr_group
// Function to delete an entry from alias_addr_group
function deleteAliasAddrGroup(&$data, $id) {
    $data['alias_addr_group'] = array_values(array_filter(
        $data['alias_addr_group'],
        fn($item) => intval($item['id']) !== intval($id)
    ));
}

// Función para eliminar una entrada de alias_service
// Function to delete an entry from alias_service
function deleteAliasService(&$data, $id) {
    isAliasServiceNameInGroupContent($data, $id);
    $data['alias_service'] = array_values(array_filter(
        $data['alias_service'],
        fn($item) => intval($item['id']) !== intval($id)
    ));
}


// Función para eliminar una entrada de alias_service_group
// Function to delete an entry from alias_service_group
function deleteAliasServiceGroup(&$data, $id) {
    $data['alias_service_group'] = array_values(array_filter(
        $data['alias_service_group'],
        fn($item) => intval($item['id']) !== intval($id)
    ));
}

// Determinar qué función ejecutar según el parámetro recibido
// Determine which function to execute based on the received parameter
if (isset($input['alias_address']['id'])) {
    deleteAliasAddress($aliasData, $input['alias_address']['id']);
} elseif (isset($input['alias_addr_group']['id'])) {
    deleteAliasAddrGroup($aliasData, $input['alias_addr_group']['id']);
} elseif (isset($input['alias_service']['id'])) {
    deleteAliasService($aliasData, $input['alias_service']['id']);
} elseif (isset($input['alias_service_group']['id'])) {
    deleteAliasServiceGroup($aliasData, $input['alias_service_group']['id']);
} else {
    http_response_code(400); // Parámetro no válido
    // Invalid parameter
    echo json_encode(['error' => 'Parámetro no reconocido']);
    exit;
}

// Guardar los cambios en el archivo alias.json
// Save changes to the alias.json file
if (file_put_contents($aliasFile, json_encode($aliasData, JSON_PRETTY_PRINT)) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo guardar el archivo']);
    exit;
}

// Respuesta de éxito
// Success response
echo json_encode(['success' => true]);
?>
