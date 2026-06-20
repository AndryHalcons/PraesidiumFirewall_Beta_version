<?php
// Endpoint WireGuard: descarga el archivo .conf completo para un cliente VPN.
// WireGuard endpoint: downloads the full .conf file for one VPN client.

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once __DIR__ . '/../common/wireguard_store.php';
require_admin_json();

$name = trim((string)($_GET['name'] ?? ''));
wireguard_validate_entry_name($name, 'name');
$config = wireguard_read_json(WIREGUARD_CONFIG_PATH);
$export = wireguard_find_client_export($name, $config);
if ($export === null) { http_response_code(404); echo wireguard_t('wireguard_error_client_export_not_found'); exit; }
$clientConfig = wireguard_build_client_config($name, $export['client'], $export['server_name'], $export['server']);
if ($clientConfig === null) { http_response_code(400); echo wireguard_t('wireguard_error_client_export_incomplete'); exit; }
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . wireguard_download_filename($name, 'conf') . '"');
header('X-Content-Type-Options: nosniff');
echo $clientConfig;
?>
