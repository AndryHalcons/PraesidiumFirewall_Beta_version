<?php
// Endpoint WireGuard: devuelve la estructura del formulario asociado a la tabla.
// WireGuard endpoint: returns the form structure associated with the table.

session_start();
require_once __DIR__ . '/../common/wireguard_store.php';
if (!isset($_SESSION['username'])) { echo json_encode(['error' => wireguard_t('unauthorized')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
header('Content-Type: application/json');
echo json_encode(wireguard_read_forms('wireguard_remote_clients'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
