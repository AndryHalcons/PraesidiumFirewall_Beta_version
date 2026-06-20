<?php
// Endpoint WireGuard: descarga un QR PNG con la configuración completa del cliente.
// WireGuard endpoint: downloads a PNG QR containing the full client configuration.

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
$descriptor = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$command = 'qrencode -t PNG -o -';
$process = proc_open($command, $descriptor, $pipes);
if (!is_resource($process)) {
    $process = proc_open('PYTHONPATH=/var/www/backend/vendor/python python3 -c "import sys,qrcode; img=qrcode.make(sys.stdin.read()); img.save(sys.stdout.buffer, \"PNG\")"', $descriptor, $pipes);
}
if (!is_resource($process)) { http_response_code(500); echo wireguard_t('wireguard_error_qr_tool_missing'); exit; }
fwrite($pipes[0], $clientConfig);
fclose($pipes[0]);
$png = stream_get_contents($pipes[1]);
fclose($pipes[1]);
$error = stream_get_contents($pipes[2]);
fclose($pipes[2]);
$code = proc_close($process);
if ($code !== 0 || $png === '') {
    $process = proc_open('PYTHONPATH=/var/www/backend/vendor/python python3 -c "import sys,qrcode; img=qrcode.make(sys.stdin.read()); img.save(sys.stdout.buffer, \"PNG\")"', $descriptor, $pipes);
    if (!is_resource($process)) { http_response_code(500); echo wireguard_t('wireguard_error_qr_tool_missing'); exit; }
    fwrite($pipes[0], $clientConfig);
    fclose($pipes[0]);
    $png = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);
}
if ($code !== 0 || $png === '') { http_response_code(500); echo wireguard_t('wireguard_error_qr_tool_missing'); exit; }
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="' . wireguard_download_filename($name, 'png') . '"');
header('X-Content-Type-Options: nosniff');
echo $png;
?>
