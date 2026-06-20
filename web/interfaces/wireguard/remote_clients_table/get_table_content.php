<?php
session_start();
if (!isset($_SESSION['username'])) { exit("No autorizado"); }
header('Content-Type: application/json');
require_once __DIR__ . '/../common/wireguard_store.php';
$columns = wireguard_read_structure('wireguard_remote_clients');
$config = wireguard_read_json(WIREGUARD_CONFIG_PATH);
$rows = [];
foreach (($config['remote_clients'] ?? []) as $name => $entry) {
    $entry['name'] = $name;
    $entry = wireguard_mask_row_for_table($entry);
    $row = [];
    foreach ($columns as $column) { $row[$column] = $entry[$column] ?? ''; }
    $rows[] = $row;
}
echo json_encode(['wireguard_remote_clients' => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
