<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

// 1) Leer parámetro: aceptamos ?table= o ?chain=
$chain = $_GET['table'] ?? $_GET['chain'] ?? '';
$chain = is_string($chain) ? trim($chain) : '';

if ($chain === '') {
    echo json_encode(['error' => 'Parámetro requerido: "table" o "chain"']);
    exit;
}

// 2) Restringir a cadenas permitidas
$allowedChains = ['FORWARDING', 'PREROUTING', 'POSTROUTING', 'input', 'output'];
if (!in_array($chain, $allowedChains, true)) {
    echo json_encode(['error' => 'get_table_content: Parámetro inválido']);
    exit;
}

// 3) Cargar estructura de columnas
$structurePath = '/var/www/backend/checks/system_data/default_tables_structure/structure_tables_policies.json';
if (!file_exists($structurePath)) {
    echo json_encode(['error' => 'Archivo de estructura no encontrado']);
    exit;
}

$structureRaw = file_get_contents($structurePath);
$structureData = json_decode($structureRaw, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($structureData[$chain])) {
    echo json_encode(['error' => 'Estructura inválida o no definida para la cadena']);
    exit;
}

$columns = $structureData[$chain];

// 4) Cargar JSON de nftables
$jsonPath = '/var/www/config/rules_nftables.json';
if (!file_exists($jsonPath)) {
    echo json_encode(['error' => 'Archivo de datos no encontrado']);
    exit;
}

$raw = file_get_contents($jsonPath);
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['nftables']) || !is_array($data['nftables'])) {
    echo json_encode(['error' => 'Formato de datos no válido']);
    exit;
}

// 5) Funciones de satinización
function extract_ip_set($value) {
    if (is_string($value)) return $value;
    if (!isset($value["set"])) return "";

    $ips = [];
    foreach ($value["set"] as $entry) {
        if (isset($entry["prefix"])) {
            $addr = $entry["prefix"]["addr"] ?? "";
            $len = $entry["prefix"]["len"] ?? "";
            $ips[] = "$addr/$len";
        }
    }
    return implode(", ", $ips);
}

function satinize_rule($rule, $columns) {
    $flat = [];
    $otherData = [];

    foreach (["family", "table", "chain", "handle", "position", "comment"] as $key) {
        if (in_array($key, $columns)) {
            $flat[$key] = $rule[$key] ?? "";
        }
    }

    if (in_array("enable", $columns)) {
        $flat["enable"] = "true";
    }

    foreach ($rule["expr"] ?? [] as $expr) {
        if (isset($expr["match"]["left"]["payload"]["field"])) {
            $field = $expr["match"]["left"]["payload"]["field"];
            $value = $expr["match"]["right"] ?? "";
            $op = $expr["match"]["op"] ?? "==";

            if ($field === "protocol" && in_array("ip.protocol", $columns)) {
                $flat["ip.protocol"] = $value;
            }

            if ($field === "saddr") {
                if (in_array("ip.saddr", $columns)) {
                    $flat["ip.saddr"] = extract_ip_set($value);
                }
                $otherData["ip.saddr.op"] = $op;
            }

            if ($field === "daddr") {
                if (in_array("ip.daddr", $columns)) {
                    $flat["ip.daddr"] = extract_ip_set($value);
                }
                $otherData["ip.daddr.op"] = $op;
            }

            if ($field === "sport") {
                if (in_array("sport", $columns)) {
                    $flat["sport"] = is_string($value) ? $value : json_encode($value);
                }
                $otherData["sport.op"] = $op;
            }

            if ($field === "dport") {
                if (in_array("dport", $columns)) {
                    $flat["dport"] = is_string($value) ? $value : json_encode($value);
                }
                $otherData["dport.op"] = $op;
            }
        }

        if (isset($expr["match"]["left"]["meta"]["key"])) {
            $metaKey = $expr["match"]["left"]["meta"]["key"];
            $metaValue = $expr["match"]["right"] ?? "";
            if ($metaKey === "iifname" && in_array("meta.iifname", $columns)) {
                $flat["meta.iifname"] = $metaValue;
            }
            if ($metaKey === "oifname" && in_array("meta.oifname", $columns)) {
                $flat["meta.oifname"] = $metaValue;
            }
        }

        if (isset($expr["match"]["left"]["ct"]["key"]) && $expr["match"]["left"]["ct"]["key"] === "state") {
            if (in_array("ct.state", $columns)) {
                $flat["ct.state"] = is_array($expr["match"]["right"]) ? implode(", ", $expr["match"]["right"]) : $expr["match"]["right"];
            }
        }

        if (isset($expr["dnat"])) {
            if (in_array("dnat.addr", $columns)) {
                $flat["dnat.addr"] = $expr["dnat"]["addr"] ?? "";
            }
            if (in_array("dnat.port", $columns)) {
                $flat["dnat.port"] = $expr["dnat"]["port"] ?? "";
            }
        }

        if (isset($expr["snat"])) {
            if (in_array("snat.addr", $columns)) {
                $flat["snat.addr"] = $expr["snat"]["addr"] ?? "";
            }
        }

        if (isset($expr["counter"])) {
            if (in_array("packets", $columns)) {
                $flat["packets"] = $expr["counter"]["packets"] ?? "";
            }
            if (in_array("bytes", $columns)) {
                $flat["bytes"] = $expr["counter"]["bytes"] ?? "";
            }
        }

        if (in_array("log", $columns)) {
            $hasLogPrefix = false;

            foreach ($rule["expr"] ?? [] as $expr) {
                if (isset($expr["log"]) && isset($expr["log"]["prefix"]) && trim($expr["log"]["prefix"]) !== "") {
                    $hasLogPrefix = true;
                    break;
                }
            }
        
            $flat["log"] = $hasLogPrefix ? "true" : "false";
        }




        foreach (["accept", "drop", "reject"] as $actionType) {
            if (array_key_exists($actionType, $expr) && in_array("action", $columns)) {
                $flat["action"] = $actionType;
            }
        }
    }

    return [$flat, $otherData];
}

// 6) Filtrar y devolver reglas satinizadas
$sanitized = [];
foreach ($data['nftables'] as $item) {
    if (isset($item['rule']) && $item['rule']['chain'] === $chain) {
        list($flat, $otherData) = satinize_rule($item['rule'], $columns);

        // Aseguramos que $flat es un array
        if (!is_array($flat)) {
            $flat = [];
        }

        // Añadimos siempre other_data como objeto vacío si no hay datos
        $flat['other_data'] = !empty($otherData) ? $otherData : new stdClass();

        $sanitized[] = $flat;
    }
}

echo json_encode([$chain => $sanitized], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
