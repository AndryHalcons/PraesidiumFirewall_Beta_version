<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}
header('Content-Type: application/json');

function validarReglaNftables(array $regla): array {
    $esICMP = false;

    // Detectar si el protocolo es icmp o icmpv6
    foreach ($regla['expr'] ?? [] as $expr) {
        if (
            isset($expr['match']['left']['payload']) &&
            $expr['match']['left']['payload']['field'] === 'protocol' &&
            in_array($expr['match']['right'], ['icmp', 'icmpv6'])
        ) {
            $esICMP = true;
            break;
        }
    }

    if ($esICMP) {
        $exprFiltradas = [];

        foreach ($regla['expr'] as $expr) {
            // Eliminar match con sport o dport
            if (isset($expr['match']['left']['payload']['field'])) {
                $campo = $expr['match']['left']['payload']['field'];
                if (in_array($campo, ['sport', 'dport'])) {
                    continue; // no incluir esta expresión
                }
            }

            // Eliminar solo el 'port' de dnat o snat
            if (isset($expr['dnat']['port'])) {
                unset($expr['dnat']['port']);
            }
            if (isset($expr['snat']['port'])) {
                unset($expr['snat']['port']);
            }

            $exprFiltradas[] = $expr;
        }

        $regla['expr'] = $exprFiltradas;
    }

    return $regla;
}
