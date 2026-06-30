<?php
require_once __DIR__ . '/../../../common/security/auth.php';
// Endpoint WireGuard: devuelve la estructura del formulario asociado a la tabla.
// WireGuard endpoint: returns the form structure associated with the table.

// Fase 1: abrir sesión y cargar helpers de WireGuard.
// Phase 1: open the session and load WireGuard helpers.
require_login_json();
require_once __DIR__ . '/../common/wireguard_store.php';

// Fase 2: devolver campos/selects que usará el modal genérico.
// Phase 2: return fields/selects used by the generic modal.
header('Content-Type: application/json');
echo json_encode(wireguard_read_forms('wireguard_remote_clients'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
