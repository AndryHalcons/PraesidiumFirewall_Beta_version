<?php

// Ruta al archivo JSON
$jsonFile = '/var/www/config/interfaces.json';

// Encabezado CGI
header("Content-Type: text/html");

// Verificar existencia del archivo
if (!file_exists($jsonFile)) {
    echo "<p>⚠️ No se encontró el archivo de configuración.</p>";
    exit(0);
}

// Leer el archivo JSON
$jsonContent = file_get_contents($jsonFile);
$jsonData = json_decode($jsonContent, true);
$interfaces = $jsonData['interfaces'] ?? [];

// Grupos de campos
$grupo_general = ['name', 'auto', 'family', 'method'];
$grupo_red = [
    'address', 'netmask', 'gateway', 'broadcast', 'network',
    'dns-nameservers', 'dns-search', 'mtu', 'metric', 'scope'
];
$grupo_avanzado = [
    'hwaddress', 'hostname', 'domain', 'source',
    'pre-up', 'up', 'post-up', 'down', 'post-down',
    'vlan-raw-device',
    'bridge_ports', 'bridge_fd', 'bridge_maxwait', 'bridge_stp',
    'bond-mode', 'bond-miimon', 'bond-slaves', 'bond-primary', 'bond-xmit_hash_policy',
    'wireless-essid', 'wireless-mode', 'wireless-key', 'wpa-ssid', 'wpa-psk',
    'accept_ra', 'autoconf', 'privext'
];

// Dividir grupo avanzado
$mid = intdiv(count($grupo_avanzado), 2);
$grupo_avanzado1 = array_slice($grupo_avanzado, 0, $mid);
$grupo_avanzado2 = array_slice($grupo_avanzado, $mid);

// Generar tabla
echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; width: 100%; margin-bottom: 30px;'>";

foreach ($interfaces as $index => $iface) {
    $iface_id = "iface_$index";
    $nombre = htmlspecialchars($iface['name'] ?? '-');

    echo <<<HTML
    <thead>
        <tr style='background-color: #f0f0f0;'>
            <th colspan='100%'>
                Interfaz: {$nombre}
                <button type='button' class='editar-btn' data-id='{$iface_id}'>Editar</button>
                <button type='button' class='guardar-btn' data-id='{$iface_id}' style='display:none;'>Guardar</button>
            </th>
        </tr>
    </thead>
    <tbody id='{$iface_id}'>
    HTML;

    function imprimir_fila($campos, $tipo, $iface) {
        echo "<tr>";
        foreach ($campos as $campo) {
            if ($tipo === 'general') {
                $valor = ($campo === 'auto' && !empty($iface['auto'])) ? 'Sí' : ($iface[$campo] ?? '');
            } else {
                $valor = $iface['options'][$campo] ?? '';
            }
            $valor_html = htmlspecialchars((string)$valor);
            echo "<td data-campo='{$campo}' data-tipo='{$tipo}'><strong>{$campo}:</strong> <span class='valor'>{$valor_html}</span></td>";
        }
        echo "</tr>";
    }

    imprimir_fila($grupo_general, 'general', $iface);
    imprimir_fila($grupo_red, 'options', $iface);
    imprimir_fila($grupo_avanzado1, 'options', $iface);
    imprimir_fila($grupo_avanzado2, 'options', $iface);

    echo "</tbody>";
}

echo "</table>";

// Incluir el script JS
echo "<script src='/interfaces/table_interfaces.js'></script>";
?>
