<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

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

/*
function get_certificates() {
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_certificates.json'), true);
    $columns = $structure['certificates'] ?? [];

    $dir = '/var/www/config/certs';
    $files = scandir($dir);
    $groups = [];

    // Agrupar archivos por nombre base
    foreach ($files as $file) {
        if (!is_file("$dir/$file")) continue;
        $parts = explode('.', $file);
        $ext = array_pop($parts);
        $base = implode('.', $parts);
        $groups[$base][$ext] = "$dir/$file";
    }

    $result = [];

    foreach ($groups as $base => $items) {
        $entry = [];

        // Detectar archivo principal para file_name
        if (isset($items['pem'])) {
            $entry['file_name'] = basename($items['pem']);
        } elseif (isset($items['csr'])) {
            $entry['file_name'] = basename($items['csr']);
        } elseif (isset($items['srl'])) {
            $entry['file_name'] = basename($items['srl']);
        } elseif (isset($items['key'])) {
            $entry['file_name'] = basename($items['key']);
        } else {
            $first = array_values($items)[0] ?? '';
            $entry['file_name'] = basename($first);
        }

        // PEM → certificado
        if (isset($items['pem'])) {
            $cmd = "openssl x509 -in " . escapeshellarg($items['pem']) . " -noout -subject -issuer -enddate -text";
            exec($cmd, $lines, $code);
            if ($code === 0) {
                foreach ($lines as $line) {
                    if (strpos($line, 'subject=') === 0) {
                        $entry['subject'] = trim(substr($line, 8));
                    } elseif (strpos($line, 'issuer=') === 0) {
                        $entry['issuer'] = trim(substr($line, 7));
                    } elseif (strpos($line, 'notAfter=') === 0) {
                        $entry['expires'] = trim(substr($line, 9));
                        $entry['status'] = strtotime($entry['expires']) < time() ? 'expired' : 'valid';
                    } elseif (strpos($line, 'Signature Algorithm:') !== false && empty($entry['algorithm'])) {
                        $entry['algorithm'] = trim(substr($line, strpos($line, ':') + 1));
                    }
                }
            }
        }

        // CSR → solicitud
        if (isset($items['csr'])) {
            $cmd = "openssl req -in " . escapeshellarg($items['csr']) . " -noout -subject";
            exec($cmd, $lines, $code);
            if ($code === 0 && isset($lines[0])) {
                $entry['subject'] = trim(substr($lines[0], 8));
                $entry['status'] = 'pending';
            }
        }

        // SRL → serial
        if (isset($items['srl'])) {
            $entry['status'] = 'serial';
        }

        // KEY → clave privada
        $entry['key'] = isset($items['key']) ? 'yes' : 'no';

        // CA → heurística simple
        $entry['ca'] = (strpos($base, 'CA') !== false || ($entry['issuer'] ?? '') === ($entry['subject'] ?? '')) ? 'yes' : 'no';

        // Nombre visible
        $entry['name'] = $base;

        // Rellenar columnas
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $entry[$col] ?? "";
        }

        $result[] = $flat;
    }

    echo json_encode(['certificates' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


function get_certificates() {
    // Cargar estructura de columnas desde JSON
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_certificates.json'), true);
    $columns = $structure['certificates'] ?? [];

    $dir = '/var/www/config/certs';
    $files = scandir($dir);
    $result = [];

    foreach ($files as $file) {
        $path = "$dir/$file";
        if (!is_file($path)) continue;

        // Inicializar entrada con campos vacíos
        $entry = [
            'file_name' => $file,
            'name' => pathinfo($file, PATHINFO_FILENAME),
            'key' => '',
            'ca' => '',
            'subject' => '',
            'issuer' => '',
            'expires' => '',
            'status' => '',
            'algorithm' => ''
        ];

        $ext = pathinfo($file, PATHINFO_EXTENSION);

        // 📄 Certificado PEM → extraer subject, issuer, expires, algoritmo
        if ($ext === 'pem') {
            $cmd = "openssl x509 -in " . escapeshellarg($path) . " -noout -subject -issuer -enddate -text";
            exec($cmd, $lines, $code);
            if ($code === 0) {
                foreach ($lines as $line) {
                    if (strpos($line, 'subject=') === 0) {
                        $entry['subject'] = trim(substr($line, 8));
                    } elseif (strpos($line, 'issuer=') === 0) {
                        $entry['issuer'] = trim(substr($line, 7));
                    } elseif (strpos($line, 'notAfter=') === 0) {
                        $entry['expires'] = trim(substr($line, 9));
                        $entry['status'] = strtotime($entry['expires']) < time() ? 'expired' : 'valid';
                    } elseif (strpos($line, 'Signature Algorithm:') !== false && empty($entry['algorithm'])) {
                        $entry['algorithm'] = trim(substr($line, strpos($line, ':') + 1));
                    }
                }
                // Heurística para marcar como CA
                $entry['ca'] = (strpos($file, 'CA') !== false || ($entry['issuer'] === $entry['subject'])) ? 'yes' : 'no';
            }
        }

        // 📄 Solicitud CSR → extraer subject y marcar como pendiente
        elseif ($ext === 'csr') {
            $cmd = "openssl req -in " . escapeshellarg($path) . " -noout -subject";
            exec($cmd, $lines, $code);
            if ($code === 0 && isset($lines[0])) {
                $entry['subject'] = trim(substr($lines[0], 8));
                $entry['status'] = 'pending';
            }
        }

        // 🔑 Clave privada → marcar como presente
        elseif ($ext === 'key') {
            $entry['key'] = 'yes';
        }

        // 🔢 Archivo de serial → marcar como tipo 'serial'
        elseif ($ext === 'srl') {
            $entry['status'] = 'serial';
        }

        // 🧩 Rellenar columnas según estructura
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $entry[$col] ?? "";
        }

        $result[] = $flat;
    }

    // 🧾 Devolver resultado en formato JSON
    echo json_encode(['certificates' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
*/


function get_certificates() {
    // Cargar estructura de columnas desde JSON
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_certificates.json'), true);
    $columns = $structure['certificates'] ?? [];

    $dir = '/var/www/config/certs';
    $files = scandir($dir);

    // Inicializar agrupación por tipo
    $grouped = [
        'root' => [],
        'intermediate' => [],
        'issuer' => [],
        'client' => [],
        'csr' => [],
        'key' => [],
        'serial' => [],
        'config' => [],
        'unknown' => []
    ];

    foreach ($files as $file) {
        $path = "$dir/$file";
        if (!is_file($path)) continue;

        $entry = [
            'file_name' => $file,
            'name' => pathinfo($file, PATHINFO_FILENAME),
            'subject' => '',
            'issuer' => '',
            'expires' => '',
            'status' => '',
            'algorithm' => '',
            'type' => 'unknown'
        ];

        $ext = pathinfo($file, PATHINFO_EXTENSION);

        // 📄 Certificado PEM
        if ($ext === 'pem') {
            $cmd = "openssl x509 -in " . escapeshellarg($path) . " -noout -subject -issuer -enddate -text";
            exec($cmd, $lines, $code);
            if ($code === 0) {
                foreach ($lines as $line) {
                    if (strpos($line, 'subject=') === 0) {
                        $entry['subject'] = trim(substr($line, 8));
                    } elseif (strpos($line, 'issuer=') === 0) {
                        $entry['issuer'] = trim(substr($line, 7));
                    } elseif (strpos($line, 'notAfter=') === 0) {
                        $entry['expires'] = trim(substr($line, 9));
                        $entry['status'] = strtotime($entry['expires']) < time() ? 'expired' : 'valid';
                    } elseif (strpos($line, 'Signature Algorithm:') !== false && empty($entry['algorithm'])) {
                        $entry['algorithm'] = trim(substr($line, strpos($line, ':') + 1));
                    }
                }

                // Clasificación por heurística
                if ($entry['issuer'] === $entry['subject']) {
                    $entry['type'] = 'root';
                } elseif (stripos($file, 'intermediate') !== false) {
                    $entry['type'] = 'intermediate';
                } elseif (stripos($file, 'emisor') !== false || stripos($file, 'issuer') !== false) {
                    $entry['type'] = 'issuer';
                } else {
                    $entry['type'] = 'client';
                }
            }
        }

        // 📄 CSR
        elseif ($ext === 'csr') {
            $cmd = "openssl req -in " . escapeshellarg($path) . " -noout -subject";
            exec($cmd, $lines, $code);
            if ($code === 0 && isset($lines[0])) {
                $entry['subject'] = trim(substr($lines[0], 8));
                $entry['status'] = 'pending';
            }
            $entry['type'] = 'csr';
        }

        // 🔑 KEY
        elseif ($ext === 'key') {
            $entry['type'] = 'key';
        }

        // 🔢 SRL
        elseif ($ext === 'srl') {
            $entry['type'] = 'serial';
            $entry['status'] = 'serial';
        }

        // ⚙️ CNF
        elseif ($ext === 'cnf') {
            $entry['type'] = 'config';
        }

        // Rellenar columnas
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $entry[$col] ?? "";
        }

        // Añadir al grupo correspondiente
        $grouped[$entry['type']][] = $flat;
    }

    // Combinar todos los grupos en orden
    $ordered = array_merge(
        $grouped['root'],
        $grouped['intermediate'],
        $grouped['issuer'],
        $grouped['client'],
        $grouped['csr'],
        $grouped['key'],
        $grouped['serial'],
        $grouped['config'],
        $grouped['unknown']
    );

    echo json_encode(['certificates' => $ordered], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
