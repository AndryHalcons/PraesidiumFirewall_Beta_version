<?php
// Endpoint WireGuard: devuelve el contenido candidate enmascarando secretos.
// WireGuard endpoint: returns candidate content while masking secrets.

session_start();
require_once __DIR__ . '/../common/wireguard_store.php';
if (!isset($_SESSION['username'])) { echo json_encode(['error' => wireguard_t('unauthorized')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
header('Content-Type: application/json');
$columns = wireguard_read_structure('wireguard_remote_access');
$config = wireguard_read_json(WIREGUARD_CONFIG_PATH);
$rows = [];
foreach (($config['remote_access'] ?? []) as $name => $entry) {
    $entry['name'] = $name;
    $entry = wireguard_mask_row_for_table($entry);
    $row = [];
    foreach ($columns as $column) {
        $field = wireguard_column_field($column);
        if ($field === '') continue;
        $row[$field] = $entry[$field] ?? '';
    }
    $rows[] = $row;
}
echo json_encode(['wireguard_remote_access' => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
