<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/csrf.php';
csrf_validate_or_exit();
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////    udpate certificates json  ///////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////

//actualiza y pre-carga el json de certificados para que contemple todos los certificados cargados
// Updates and preloads the certificates JSON to include all loaded certificates
/*
function update_certificates_config_json() {
    // Cargar estructura de columnas desde JSON
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_certificates.json'), true);
    $columns = $structure['certificates'] ?? [];

    $dir = '/var/www/config/certs';
    $files = scandir($dir);
    $existing_files = array_flip(array_filter($files, fn($f) => is_file("$dir/$f")));

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

    $seen = [];

    foreach ($files as $file) {
        $path = "$dir/$file";
        if (!is_file($path)) continue;

        // Evitar duplicados por file_name
        if (isset($seen[$file])) continue;
        $seen[$file] = true;

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

    // Guardar en certificates_config.json
    $output = ['certificates' => $ordered];
    file_put_contents('/var/www/config/certs/certificates_config.json', json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}
*/

function update_certificates_config_json() {
    // Cargar estructura de columnas desde JSON
    // Load column structure from JSON
    $structure = @json_decode(@file_get_contents('/var/www/backend/checks/system_data/default_tables_structure/structure_table_certificates.json'), true);
    $columns = $structure['certificates'] ?? [];

    $dir = '/var/www/config/certs';
    $files = scandir($dir);

    // Extensiones válidas para certificados y claves
    // Valid extensions for certificates and keys
    $validExtensions = [
        'pem', 'crt', 'cer', 'csr', 'req',
        'key', 'srl', 'cnf',
        'pfx', 'p12', 'pkcs12',
        'der', 'jks'
    ];

    // Inicializar agrupación por tipo
    // Initialize grouping by type
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

    $seen = [];

    foreach ($files as $file) {
        $path = "$dir/$file";
        if (!is_file($path)) continue;

        // Filtrar por extensión válida
        // Filter by valid extension
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $validExtensions)) continue;

        // Evitar duplicados por file_name
        // Avoid duplicates by file_name
        if (isset($seen[$file])) continue;
        $seen[$file] = true;

        // Inicializar entrada
        // Initialize entry
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

        //  Certificado PEM, CRT, CER, DER, PFX, P12
        //  PEM, CRT, CER, DER, PFX, P12 Certificate
        if (in_array($ext, ['pem', 'crt', 'cer', 'der', 'pfx', 'p12', 'pkcs12'])) {
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
                // Heuristic classification
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

        //  CSR o REQ
        //  CSR or REQ
        elseif (in_array($ext, ['csr', 'req'])) {
            $cmd = "openssl req -in " . escapeshellarg($path) . " -noout -subject";
            exec($cmd, $lines, $code);
            if ($code === 0 && isset($lines[0])) {
                $entry['subject'] = trim(substr($lines[0], 8));
                $entry['status'] = 'pending';
            }
            $entry['type'] = 'csr';
        }

        //  KEY
        //  KEY
        elseif ($ext === 'key') {
            $entry['type'] = 'key';
        }

        //  SRL
        //  SRL
        elseif ($ext === 'srl') {
            $entry['type'] = 'serial';
            $entry['status'] = 'serial';
        }

        //  CNF
        //  CNF
        elseif ($ext === 'cnf') {
            $entry['type'] = 'config';
        }

        //  JKS (no procesado por OpenSSL)
        //  JKS (not processed by OpenSSL)
        elseif ($ext === 'jks') {
            $entry['type'] = 'unknown';
            $entry['status'] = 'unsupported';
        }

        // Rellenar columnas
        // Fill columns
        $flat = [];
        foreach ($columns as $col) {
            $flat[$col] = $entry[$col] ?? "";
        }

        // Añadir al grupo correspondiente
        // Add to corresponding group
        $grouped[$entry['type']][] = $flat;
    }

    // Combinar todos los grupos en orden
    // Merge all groups in order
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

    // Guardar en certificates_config.json
    // Save to certificates_config.json
    $output = ['certificates' => $ordered];
    file_put_contents('/var/www/config/certs/certificates_config.json', json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}
