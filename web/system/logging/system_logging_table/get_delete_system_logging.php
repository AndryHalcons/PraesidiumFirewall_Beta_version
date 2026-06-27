<?php
require_once __DIR__ . '/../../../common/security/session.php';
praesidium_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
require_admin_json();
csrf_validate_or_exit();
header('Content-Type: application/json');
http_response_code(405);
echo json_encode(['error' => 'system_logging no permite borrar filas']);
