<?php
/*
#############################################################################
#############################################################################
#############################################################################
   Gestión centralizada de sesiones Praesidium
   Centralized Praesidium session management

   Este helper debe ser el único punto que inicia sesiones PHP en el WebGUI.
   This helper should be the only point that starts PHP sessions in the WebGUI.
#############################################################################
#############################################################################
#############################################################################
*/

/*
#############################################################################
   Detecta si la petición actual usa HTTPS real o proxy HTTPS
   Detects whether the current request uses real HTTPS or HTTPS through proxy
#############################################################################
*/
function praesidium_session_is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }

    return false;
}

/*
#############################################################################
   Configura los parámetros seguros de cookie antes de iniciar sesión
   Configures secure cookie parameters before starting the session
#############################################################################
*/
function praesidium_session_configure_cookie(): void {
    if (session_status() === PHP_SESSION_ACTIVE || headers_sent()) {
        return;
    }

    $secure = praesidium_session_is_https();

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', $secure ? '1' : '0');
}

/*
#############################################################################
   Inicia la sesión aplicando siempre la política centralizada
   Starts the session while always applying the centralized policy
#############################################################################
*/
function praesidium_session_start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    praesidium_session_configure_cookie();
    session_start();
}
