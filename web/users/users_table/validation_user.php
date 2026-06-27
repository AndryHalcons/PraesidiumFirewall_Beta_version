<?php
require_once __DIR__ . '/../../common/security/auth.php';
require_login_json();
header('Content-Type: application/json');

// Verifica si el usuario tiene sesión activa
// Check if the user has an active session


//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////    Import Json to to consult  ///////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
// Importa el archivo de users y lo devuelve como array
// Imports the users file and returns it as an array
function import_user_json() {
    $jsonPath = '/var/www/config/users.json';

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
////////////////////////////////////    id section  /////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////

// Verifica si la regla tiene un ID válido; si no, le asigna el siguiente disponible
// Checks if the rule has a valid ID; if not, assigns the next available one
function check_user_id(array $rule): array {
    // Si el campo 'id' existe y es un número válido (entero o string numérico)
    // If 'id' exists and is a valid number (integer or numeric string)
    if (isset($rule['id']) && is_numeric($rule['id'])) {
        $rule['id'] = (string)(int)$rule['id']; // Normaliza el ID como string
        // Normalize ID as string

        // Reordenar el array para que 'id' esté al principio
        // Reorder array so 'id' appears first
        $rule = array_merge(
            ['id' => $rule['id']],
            array_diff_key($rule, ['id' => null])
        );

        return $rule;
    }

    // Si no tiene ID, se genera uno nuevo
    // If no ID is present, generate a new one
    $nextId = get_next_id();
    $rule['id'] = (string)$nextId;

    // Reordenar el array para que 'id' esté al principio
    // Reorder array so 'id' appears first
    $rule = array_merge(
        ['id' => $rule['id']],
        array_diff_key($rule, ['id' => null])
    );

    return $rule;
}


// Busca el siguiente ID disponible empezando desde "1"
// Finds the next available ID starting from "1"

function get_next_id(): string {
    $data = import_user_json();

    // Si no se pudo cargar el JSON, se responde con error y se detiene el script
    // If JSON couldn't be loaded, respond with error and stop execution
    if (!$data || !isset($data['table_users']) || !is_array($data['table_users'])) {
        echo json_encode(['error' => 'No se pudo cargar el JSON de usuarios']); // Failed to load user JSON
        exit;
    }

    // Extraemos todos los IDs existentes y los normalizamos como strings
    // Extract all existing IDs and normalize them as strings
    $existingIds = [];
    foreach ($data['table_users'] as $entry) {
        if (isset($entry['id'])) {
            $existingIds[] = (string)$entry['id'];
        }
    }

    // Comenzamos desde "1" y buscamos el primer ID libre
    // Start from "1" and find the first free ID
    $id = 1;
    while (in_array((string)$id, $existingIds, true)) {
        $id++;
    }

    return (string)$id;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////    fields validation section  ///////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////

// Valida rol e idioma de usuario contra listas permitidas antes de guardar.
// Validates user role and language against allowlists before saving.
function validate_user_fields(array $rule): array {
    $allowedRoles = ['admin', 'viewer'];
    $allowedLanguages = ['en', 'es'];

    if (!isset($rule['user_name']) || trim((string)$rule['user_name']) === '') {
        echo json_encode(['error' => 'Nombre de usuario inválido']);
        // Invalid username
        exit;
    }

    if (!isset($rule['user_role']) || !in_array((string)$rule['user_role'], $allowedRoles, true)) {
        echo json_encode(['error' => 'Rol de usuario inválido']);
        // Invalid user role
        exit;
    }

    if (!isset($rule['user_language']) || !in_array((string)$rule['user_language'], $allowedLanguages, true)) {
        echo json_encode(['error' => 'Idioma de usuario inválido']);
        // Invalid user language
        exit;
    }

    return $rule;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////    Hash   section  //////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////

// Hashea el campo 'user_pass' usando SHA-512 si es válido
// Hashes the 'user_pass' field using SHA-512 if valid
function hash_pass(array $rule): array {
    // Verifica que el campo exista y no esté vacío ni enmascarado
    // Check that the field exists and is not empty or masked
    if (
        isset($rule['user_pass']) &&
        trim($rule['user_pass']) !== '' &&
        $rule['user_pass'] !== '******'
    ) {
        // Aplica SHA-512 al valor de la contraseña
        // Apply SHA-512 to the password value
        $rule['user_pass'] = hash('sha512', $rule['user_pass']);
    }

    return $rule; // Devuelve la regla actualizada
    // Return the updated rule
}

//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////    update or add user  section  /////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////


// Actualiza un usuario existente por ID o lo añade si no existe
// Updates an existing user by ID or adds it if not found
function update_or_add_user(array $rule, array $rulesJson): array {
    // Verificamos que 'table_users' exista y sea un array
    // Ensure 'table_users' exists and is an array
    if (!isset($rulesJson['table_users']) || !is_array($rulesJson['table_users'])) {
        $rulesJson['table_users'] = [];
    }

    $id = isset($rule['id']) ? (string)$rule['id'] : null;
    $found = false;

    // Recorremos el array original sin alterar el orden
    // Traverse the original array without altering the order
    foreach ($rulesJson['table_users'] as $i => $user) {
        if (isset($user['id']) && (string)$user['id'] === $id) {
            // Actualizamos el usuario en su posición original
            // Update the user in its original position
            $rulesJson['table_users'][$i] = $rule;
            $found = true;
            break;
        }
    }

    // Si no se encontró el ID, añadimos el nuevo usuario al final
    // If ID not found, append the new user at the end
    if (!$found) {
        $rulesJson['table_users'][] = $rule;
    }

    return $rulesJson; // Devolvemos el JSON actualizado sin alterar el orden
    // Return the updated JSON without altering the order
}


