<?php
/*
#############################################################################
#############################################################################
#############################################################################
   Funciones comunes de autenticación y autorización
   Common authentication and authorization helpers

   Este archivo centraliza las comprobaciones de sesión, login y rol.
   This file centralizes session, login and role checks.
#############################################################################
#############################################################################
#############################################################################
*/

/*
#############################################################################
   Asegura que la sesión PHP esté iniciada
   Ensures that the PHP session is started
#############################################################################
*/
function auth_ensure_session_started(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/*
#############################################################################
   Devuelve true si existe un usuario autenticado en sesión
   Returns true if an authenticated user exists in session
#############################################################################
*/
function auth_is_logged_in(): bool {
    auth_ensure_session_started();
    return !empty($_SESSION['username']);
}

/*
#############################################################################
   Devuelve el rol actual del usuario
   Returns the current user role
#############################################################################
*/
function auth_current_role(): string {
    auth_ensure_session_started();
    return (string)($_SESSION['role'] ?? '');
}

/*
#############################################################################
   Respuesta JSON estándar para errores de autenticación/autorización
   Standard JSON response for authentication/authorization errors
#############################################################################
*/
function auth_json_error(int $statusCode, string $message): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'error' => $message
    ]);
    exit;
}

/*
#############################################################################
   Exige usuario autenticado para endpoints JSON
   Requires an authenticated user for JSON endpoints
#############################################################################
*/
function require_login_json(): void {
    if (!auth_is_logged_in()) {
        auth_json_error(401, 'No autorizado');
    }
}

/*
#############################################################################
   Exige rol admin para endpoints JSON administrativos
   Requires admin role for administrative JSON endpoints
#############################################################################
*/
function require_admin_json(): void {
    require_login_json();

    if (auth_current_role() !== 'admin') {
        auth_json_error(403, 'Forbidden: admin role required');
    }
}
