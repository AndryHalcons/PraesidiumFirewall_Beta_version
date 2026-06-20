<?php
// Endpoint WireGuard: devuelve el contenido candidate enmascarando secretos.
// WireGuard endpoint: returns candidate content while masking secrets.

// Fase 1: abrir sesión y cargar helper común antes del control de acceso.
// Phase 1: open the session and load the common helper before access control.
session_start();
require_once __DIR__ . '/../common/wireguard_store.php';
if (!isset($_SESSION['username'])) { // Fase 5: devolver filas bajo el alias que espera renderTableGeneric.
// Phase 5: return rows under the alias expected by renderTableGeneric.
echo json_encode(['error' => wireguard_t('unauthorized')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
header('Content-Type: application/json');
// Fase 2: leer columnas declaradas y candidate JSON actual.
// Phase 2: read declared columns and the current candidate JSON.
$columns = wireguard_read_structure('wireguard_remote_access');
$config = wireguard_read_json(WIREGUARD_CONFIG_PATH);
// Fase 3: recorrer entradas, inyectar el nombre y enmascarar secretos antes de responder.
// Phase 3: iterate entries, inject the name, and mask secrets before responding.
$rows = [];
foreach (($config['remote_access'] ?? []) as $name => $entry) {
    $entry['name'] = $name;
    $entry = wireguard_mask_row_for_table($entry);
    $row = [];
    // Fase 4: mapear columnas string/objeto al campo real esperado por la tabla genérica.
    // Phase 4: map string/object columns to the real field expected by the generic table.
    foreach ($columns as $column) {
        $field = wireguard_column_field($column);
        if ($field === '') continue;
        $row[$field] = $entry[$field] ?? '';
    }
    $rows[] = $row;
}
echo json_encode(['wireguard_remote_access' => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
