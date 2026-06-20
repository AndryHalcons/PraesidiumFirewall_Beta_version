<?php
session_start();
if (!isset($_SESSION['username'])) { exit("No autorizado"); }
header('Content-Type: application/json');
require_once __DIR__ . '/../common/wireguard_store.php';
echo json_encode(wireguard_read_forms('wireguard_remote_clients'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
