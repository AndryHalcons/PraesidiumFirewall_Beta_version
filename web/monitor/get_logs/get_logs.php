<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

header('Content-Type: application/json');

// Leer el JSON recibido
$input = json_decode(file_get_contents("php://input"), true);

// Validar que sea un array
if (!is_array($input)) {
    echo json_encode(["error" => "Entrada inválida"]);
    exit;
}

$firewall = $input['Firewall'] ?? null;
$user = $input['user'] ?? null;

//  Función para NFTABLES
function ejecutarNftables($input) {
    $script = '/usr/bin/python3 /var/www/backend/checks/check_monitor_log_extract/extract_monitor_log_nftables_for_get_user.py';
    $json = escapeshellarg(json_encode($input));
    shell_exec("sudo $script $json 2>&1");

    //  Leer el archivo generado solo si se ejecutó esta función
    $user = $input['user'] ?? null;
    if ($user) {
        $outputPath = "/var/www/backend/checks/system_data/data_monitor_logs/{$user}_log_view.json";
        if (file_exists($outputPath)) {
            return file_get_contents($outputPath);
        } else {
            return json_encode(["error" => "Archivo de salida no encontrado"]);
        }
    } else {
        return json_encode(["error" => "Usuario no especificado"]);
    }
}

// 🔧 Función para BPFILTER
function ejecutarBpfilter($input) {
    // lógica  BPFILTER
    return json_encode(["info" => "BPFILTER aún no implementado"]);
}

// 🔧 Función para firewall vacío o no reconocido
function ejecutarSinFirewall($input) {
    return json_encode([]);
}

//  Enrutamiento según el tipo de firewall
switch ($firewall) {
    case "NFTABLES":
        $respuesta = ejecutarNftables($input);
        break;
    case "BPFILTER":
        $respuesta = ejecutarBpfilter($input);
        break;
    case "":
    case null:
        $respuesta = ejecutarSinFirewall($input);
        break;
    default:
        $respuesta = json_encode(["error" => "Tipo de firewall no reconocido"]);
        break;
}

//  Devolver la respuesta al frontend
echo $respuesta;
