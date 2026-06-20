<?php
// Endpoint WireGuard: devuelve la estructura de columnas para la tabla genérica.
// WireGuard endpoint: returns the column structure for the generic table.

// Fase 1: abrir sesión y cargar helpers antes de usar textos traducidos.
// Phase 1: open the session and load helpers before using translated text.
session_start();
require_once __DIR__ . '/../common/wireguard_store.php';
if (!isset($_SESSION['username'])) { echo json_encode(['error' => wireguard_t('unauthorized')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
// Fase 2: devolver al render genérico la estructura declarada de columnas.
// Phase 2: return the declared column structure to the generic renderer.
header('Content-Type: application/json');
echo json_encode(['wireguard_site_to_site' => wireguard_read_structure('wireguard_site_to_site')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
