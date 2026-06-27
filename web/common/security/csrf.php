<?php
require_once __DIR__ . '/session.php';
/*
#############################################################################
#############################################################################
#############################################################################
   Protección CSRF para peticiones que modifican estado
   CSRF protection for state-changing requests

   Este archivo centraliza la generación y validación del token CSRF.
   This file centralizes CSRF token generation and validation.
#############################################################################
#############################################################################
#############################################################################
*/

/*
#############################################################################
   Devuelve el token CSRF actual o genera uno nuevo
   Returns the current CSRF token or generates a new one
#############################################################################
*/
function csrf_get_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        praesidium_session_start();
    }

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/*
#############################################################################
   Lee el token CSRF recibido en la petición
   Reads the CSRF token received in the request
#############################################################################
*/
function csrf_get_request_token(): string {
    $headers = function_exists('getallheaders') ? getallheaders() : [];

    foreach ($headers as $name => $value) {
        if (strtolower((string)$name) === 'x-csrf-token') {
            return trim((string)$value);
        }
    }

    if (isset($_POST['csrf_token'])) {
        return trim((string)$_POST['csrf_token']);
    }

    return '';
}

/*
#############################################################################
   Valida el token CSRF o detiene la petición con error JSON
   Validates the CSRF token or stops the request with a JSON error
#############################################################################
*/
function csrf_validate_or_exit(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        praesidium_session_start();
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $requestToken = csrf_get_request_token();

    if (
        !is_string($sessionToken) ||
        $sessionToken === '' ||
        $requestToken === '' ||
        !hash_equals($sessionToken, $requestToken)
    ) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'error' => 'Invalid CSRF token'
        ]);
        exit;
    }
}
