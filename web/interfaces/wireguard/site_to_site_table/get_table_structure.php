<?php
session_start();
if (!isset($_SESSION['username'])) { echo json_encode(['error' => wireguard_t('unauthorized')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
header('Content-Type: application/json');
require_once __DIR__ . '/../common/wireguard_store.php';
echo json_encode(['wireguard_site_to_site' => wireguard_read_structure('wireguard_site_to_site')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
