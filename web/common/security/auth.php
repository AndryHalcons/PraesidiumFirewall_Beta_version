<?php
require_once __DIR__ . '/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/audit.php';
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
        praesidium_session_start();
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
    if (!auth_is_logged_in()) {
        audit_admin_event('admin_endpoint_not_authenticated');
        auth_json_error(401, 'No autorizado');
    }

    if (auth_current_role() !== 'admin') {
        audit_admin_event('admin_endpoint_role_denied');
        auth_json_error(403, 'Forbidden: admin role required');
    }

    audit_admin_event('admin_endpoint_access');
}

/*
#############################################################################
   Respuesta de texto plano para errores de autenticación/autorización
   Plain text response for authentication/authorization errors
#############################################################################
*/
function auth_text_error(int $statusCode, string $message): void {
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}

/*
#############################################################################
   Exige usuario autenticado para páginas HTML o fragmentos HTML
   Requires an authenticated user for HTML pages or HTML fragments
#############################################################################
*/
function require_login_page(): void {
    if (!auth_is_logged_in()) {
        auth_text_error(401, 'No autorizado');
    }
}

/*
#############################################################################
   Exige rol admin para páginas HTML administrativas
   Requires admin role for administrative HTML pages
#############################################################################
*/
function require_admin_page(): void {
    if (!auth_is_logged_in()) {
        audit_admin_event('admin_page_not_authenticated');
        auth_text_error(401, 'No autorizado');
    }

    if (auth_current_role() !== 'admin') {
        audit_admin_event('admin_page_role_denied');
        auth_text_error(403, 'Forbidden: admin role required');
    }

    audit_admin_event('admin_page_access');
}

/*
#############################################################################
   Exige usuario autenticado para endpoints de texto/descarga no JSON
   Requires an authenticated user for non-JSON text/download endpoints
#############################################################################
*/
function require_login_text(): void {
    if (!auth_is_logged_in()) {
        auth_text_error(401, 'No autorizado');
    }
}

/*
#############################################################################
   Exige rol admin para endpoints de texto/descarga no JSON
   Requires admin role for non-JSON text/download endpoints
#############################################################################
*/
function require_admin_text(): void {
    if (!auth_is_logged_in()) {
        audit_admin_event('admin_text_endpoint_not_authenticated');
        auth_text_error(401, 'No autorizado');
    }

    if (auth_current_role() !== 'admin') {
        audit_admin_event('admin_text_endpoint_role_denied');
        auth_text_error(403, 'Forbidden: admin role required');
    }

    audit_admin_event('admin_text_endpoint_access');
}

/*
#############################################################################
   Alias explícitos para descargas/ficheros que no deben emitir HTML
   Explicit aliases for downloads/files that must not emit HTML
#############################################################################
*/
function require_login_download(): void {
    require_login_text();
}

function require_admin_download(): void {
    require_admin_text();
}
