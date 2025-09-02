<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Importa el archivo de reglas actual para consultas
// Imports the current rules file for queries
function import_policy_nft_json() {
    $jsonPath = '/var/www/config/rules_nftables.json';

    if (!file_exists($jsonPath)) {
        return false;
    }

    $raw = file_get_contents($jsonPath);
    $aliasJsonData = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    return $aliasJsonData;
}
function get_id_from_policy(): string {
    $data = import_policy_nft_json();
    if (!$data || !isset($data['nftables']) || !is_array($data['nftables'])) {
        return "1"; // fallback si no se puede leer el archivo
    }

    $usedIds = [];

    foreach ($data['nftables'] as $entry) {
        if (isset($entry['rule']) && isset($entry['rule']['comment'])) {
            $comment = $entry['rule']['comment'];
            if (preg_match("/id='(\d+)'/", $comment, $match)) {
                $usedIds[] = (int)$match[1];
            }
        }
    }

    // Busca el primer ID libre empezando desde 1
    $id = 1;
    while (in_array($id, $usedIds, true)) {
        $id++;
    }

    return (string)$id;
}

$myvariable = get_id_from_policy();
echo $myvariable;


// Simulación de datos de entrada
$fakeRule = [
    'ip.daddr'=> '192.168.1.1',  
    'ip.saddr'=> '10.10.10.10',  
    'dnat.addr'=> 'Google_DNS', 
    'snat.addr' => 'Private-networks,Google_DNS,7.7.7.7/26,10.0.0.1/24,10.50.100.1,cloudflare,3.3.3.3,1.1.1.2',
    'ifname' => 'ens21',
    'sport' => 'HTTPS',
    'dport' => '22-50,77,45-80,100-200,98',
    'dnat.port' => '50-155,SSH,22,443,Management,HTTPS,Management,90',
];

// Ejecuta la conversión
$convertedRule = validate_nftables_policy($fakeRule);

// Muestra el resultado
echo "<pre>";
print_r($convertedRule);
echo "</pre>";