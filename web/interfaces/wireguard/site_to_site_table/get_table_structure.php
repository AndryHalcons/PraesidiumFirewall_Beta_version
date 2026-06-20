<?php
// Endpoint WireGuard: devuelve la estructura de columnas para la tabla genérica.
// WireGuard endpoint: returns the column structure for the generic table.

session_start();
require_once __DIR__ . '/../common/wireguard_store.php';
if (!isset($_SESSION['username'])) { echo json_encode(['error' => wireguard_t('unauthorized')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
header('Content-Type: application/json');
echo json_encode(['wireguard_site_to_site' => wireguard_read_structure('wireguard_site_to_site')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
