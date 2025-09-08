<?php
    session_start();
    if (!isset($_SESSION['username'])) {
        header('Content-Type: application/json');
        return ['error' => 'No autorizado'];
    }
    header('Content-Type: application/json');
function convert_update_policy_to_backend(): array {

    // Verifica que el usuario tenga sesión activa
    // Check that the user has an active session

    /*
    only if front send
    // Lee el cuerpo de la solicitud y decodifica el JSON
    // Read the request body and decode the JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Verifica que el JSON sea válido y contenga los campos necesarios
    // Validate that the JSON is correct and contains required fields
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['table']) || !isset($data['rule'])) {
        return ['error' => 'Entrada JSON inválida'];
    }
    */
    // Incluye las funciones de validación y sanitización
    // Include validation and sanitization functions
    require __DIR__ . '/convert_policys_validation_to_nft.php';

    // Función para validar la regla recibida
    // Function to validate the received rule
    function validate_nftables_policy(array $rule): array {
        $rule = validation_icmp_no_ports($rule);
        $rule = Main_convert_alias_object_to_network_object($rule);
        $rule = comment_convert_id_name($rule);
        validation_form_field_review($rule);
        $rule = assign_position($rule);
        $rule = log_format_nft($rule);
        return $rule;
    }

    // Ruta del archivo de configuración de reglas
    // Path to the nftables rules configuration file
    $jsonPath = '/var/www/config/rules_nftables.json';
    // Verifica que el archivo exista
    // Check that the file exists
    if (!file_exists($jsonPath)) {
        return ['error' => 'Archivo de reglas no encontrado'];
    }
    // Carga y decodifica el contenido del archivo
    // Load and decode the file content
    $raw = file_get_contents($jsonPath);
    $rulesJson = json_decode($raw, true);


    

    // Verifica que el JSON sea válido y tenga la clave 'nftables'
    // Validate that the JSON is correct and contains the 'nftables' key
    if (json_last_error() !== JSON_ERROR_NONE || !isset($rulesJson['nftables'])) {
        return ['error' => 'JSON de reglas mal formado'];
    }

    // Limpiamos todas las reglas previas Eliminar todas las entradas que tengan la clave "rule" 
    $rulesJson['nftables'] = array_values(
        array_filter(
            $rulesJson['nftables'],
            fn($entry) => !isset($entry['rule'])
        )
    );

    //////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////Archivo para el backend/////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////

    //  leer el archivo human_viewer y procesar todas las reglas
    //read the human_viewer file and process all rules
    $humanPath = '/var/www/config/rules_nftables_human_viewer.json';
    if (!file_exists($humanPath)) {
        return ['error' => 'Archivo human_viewer no encontrado'];
    }

    $humanRaw = file_get_contents($humanPath);
    $humanJson = json_decode($humanRaw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($humanJson['nftables'])) {
        return ['error' => 'JSON human_viewer mal formado'];
    }

    foreach ($humanJson['nftables'] as $entry) {
        if (!isset($entry['rule']) || !is_array($entry['rule'])) {
            continue;
        }
            // 🔹 Filtrar aquí antes de validar/sanitizar
        if (!isset($entry['rule']['enable']) || $entry['rule']['enable'] !== "true") {
            continue;
        }
        $validated = validate_nftables_policy($entry['rule']);
        $sanitized = saniticed_nftables_policy($validated);
        $rulesJson = update_or_insert_nft_rule($sanitized['rule'], $rulesJson);
    }

    // guardar el archivo actualizado
    $saved = file_put_contents(
        $jsonPath,
        json_encode($rulesJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    if ($saved === false) {
        return ['error' => 'No se pudo guardar el archivo'];
    }

    //////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////Archivo para el front///////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////
    // respuesta final al frontend
    return ['success' => true];
}
