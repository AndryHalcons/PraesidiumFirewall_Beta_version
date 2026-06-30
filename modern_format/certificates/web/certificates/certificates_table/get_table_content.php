<?php
require_once __DIR__ . '/../../common/security/auth.php';
require_login_json();
header('Content-Type: application/json');


$chain = trim($_GET['table'] ?? $_GET['chain'] ?? '');
$allowedChains = ['certificates'];

if ($chain === '' || !in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'Parámetro inválido o ausente']);
    exit;
}

// Dispatcher: solo ejecuta la función

switch ($chain) {
    case 'certificates': get_certificates(); break;
    default:
        echo json_encode(['error' => 'Cadena no soportada']);
        break;
}

// Funciones autónomas por tipo


function get_certificates() {
    require __DIR__ . '/get_update_certificate.php';

    //actualiza y pre-carga el json de certificados para que contemple todos los certificados cargados
    // Updates and preloads the certificates JSON to include all loaded certificates
    update_certificates_config_json();
    
    //ruta al archivo json de certificados
    $json_path = '/var/www/config/certs/certificates_config.json';

    if (!file_exists($json_path)) {
        echo json_encode(['certificates' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return;
    }

    $data = @json_decode(@file_get_contents($json_path), true);

    // Validar que el JSON tenga la estructura esperada
    if (!is_array($data) || !isset($data['certificates']) || !is_array($data['certificates'])) {
        echo json_encode(['certificates' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return;
    }
    
    echo json_encode(['certificates' => $data['certificates']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


