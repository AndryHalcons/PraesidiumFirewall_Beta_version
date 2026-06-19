<?php
/*
#############################################################################
#############################################################################
#############################################################################
   Auditoría administrativa básica
   Basic administrative audit logging

   Este archivo registra eventos de seguridad del panel web sin guardar secretos.
   This file records web panel security events without storing secrets.
#############################################################################
#############################################################################
#############################################################################
*/

/*
#############################################################################
   Obtiene la IP origen de la petición
   Gets the request source IP address
#############################################################################
*/
function audit_get_source_ip(): string {
    return (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

/*
#############################################################################
   Registra un evento administrativo en formato JSON Lines
   Records an administrative event using JSON Lines format
#############################################################################
*/
function audit_admin_event(string $event, array $details = []): void {
    $directory = '/var/www/config/security_audit';
    $path = $directory . '/admin_audit.jsonl';

    if (!is_dir($directory)) {
        @mkdir($directory, 0775, true);
    }

    $entry = [
        'date' => gmdate('c'),
        'event' => $event,
        'user' => (string)($_SESSION['username'] ?? ''),
        'role' => (string)($_SESSION['role'] ?? ''),
        'source_ip' => audit_get_source_ip(),
        'method' => (string)($_SERVER['REQUEST_METHOD'] ?? ''),
        'uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
        'details' => $details
    ];

    $json = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return;
    }

    @file_put_contents($path, $json . PHP_EOL, FILE_APPEND | LOCK_EX);
}
