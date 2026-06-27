<?php
// Endpoint WireGuard: descarga el archivo .conf completo para un cliente VPN.
// WireGuard endpoint: downloads the full .conf file for one VPN client.

require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once __DIR__ . '/../common/wireguard_store.php';
require_admin_json();

// Fase 1: validar el nombre recibido desde el botón de la fila.
// Phase 1: validate the name received from the row button.
$name = trim((string)($_GET['name'] ?? ''));
wireguard_validate_entry_name($name, 'name');
// Fase 2: cargar candidate y resolver cliente + servidor asociado.
// Phase 2: load candidate and resolve client + associated server.
$config = wireguard_read_json(WIREGUARD_CONFIG_PATH);
$export = wireguard_find_client_export($name, $config);
if ($export === null) { http_response_code(404); echo wireguard_t('wireguard_error_client_export_not_found'); exit; }
// Fase 3: construir el .conf completo que se entregará al usuario final.
// Phase 3: build the full .conf that will be delivered to the final user.
$clientConfig = wireguard_build_client_config($name, $export['client'], $export['server_name'], $export['server']);
if ($clientConfig === null) { http_response_code(400); echo wireguard_t('wireguard_error_client_export_incomplete'); exit; }
// Fase 4: forzar descarga para que el navegador no muestre la clave privada en pantalla.
// Phase 4: force download so the browser does not display the private key on screen.
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . wireguard_download_filename($name, 'conf') . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
echo $clientConfig;
?>
