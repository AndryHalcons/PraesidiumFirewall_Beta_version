<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

function validate_bpfilter_rule($rawJson) {
    $input = json_decode($rawJson, true);
    $errors = [];

    // 🧩 Validar estructura básica
    if (!isset($input["hook"])) {
        $errors[] = "Falta el campo 'hook'";
    }

    if (!isset($input["rule"]["id"])) {
        $errors[] = "Falta el campo 'rule.id'";
    }

    if (!isset($input["rule"]["action"])) {
        $errors[] = "Falta el campo 'rule.action'";
    }

    if (!isset($input["rule"]["match"]) || !is_array($input["rule"]["match"])) {
        $errors[] = "Falta el campo 'rule.match' o no es un objeto";
    }

    // 🧩 Validar campos reconocidos por el parser
    $validFields = [
        "iface", "l3_proto", "l4_proto", "probability",
        "ip4_saddr", "ip4_daddr", "ip4_snet", "ip4_dnet", "ip4_proto",
        "ip6_saddr", "ip6_daddr", "ip6_snet", "ip6_dnet", "ip6_nexthdr",
        "tcp_sport", "tcp_dport", "tcp_flags",
        "udp_sport", "udp_dport",
        "icmp_type", "icmp_code", "icmpv6_type", "icmpv6_code"
    ];

    $match = $input["rule"]["match"] ?? [];

    foreach ($match as $key => $value) {
        // Ignorar campos vacíos o nulos
        if ($value === "" || $value === null) {
            continue;
        }

        if (!in_array($key, $validFields)) {
            $errors[] = "Campo no reconocido en 'match': $key";
            continue;
        }

        // Validaciones específicas por tipo
        if (in_array($key, ["ip4_saddr", "ip4_daddr"])) {
            if (!filter_var($value, FILTER_VALIDATE_IP)) {
                $errors[] = "Valor inválido para $key: $value";
            }
        }

        if (in_array($key, ["ip4_snet", "ip4_dnet"])) {
            if (!preg_match('/^\d{1,3}(\.\d{1,3}){3}\/\d{1,2}$/', $value)) {
                $errors[] = "Valor inválido para red $key: $value";
            }
        }

        if ($key === "probability") {
            if (!preg_match('/^\d+%$/', $value)) {
                $errors[] = "Probabilidad debe ser un entero entre 0 y 100 terminar con simbolo  %: $value";
            }
        }

        if (in_array($key, ["tcp_sport", "tcp_dport", "udp_sport", "udp_dport"])) {
            if (!preg_match('/^\d+$/', $value)) {
                $errors[] = "Puerto inválido en $key: $value";
            }
        }

        if (in_array($key, ["icmp_type", "icmp_code", "icmpv6_type", "icmpv6_code"])) {
            if (!preg_match('/^\d+$/', $value)) {
                $errors[] = "Código ICMP inválido en $key: $value";
            }
        }

        if ($key === "tcp_flags") {
            if (!preg_match('/^[a-zA-Z]+(,[a-zA-Z]+)*$/', $value)) {
                $errors[] = "Flags TCP inválidas: $value";
            }
        }
    }
    // 🧩 Validaciones personales
    //Solo uno de los campos de tráfico origen puede tener contenido
    if (
        (isset($match["ip4_saddr"]) && $match["ip4_saddr"] !== "" && $match["ip4_saddr"] !== null ? 1 : 0) +
        (isset($match["ip4_snet"])  && $match["ip4_snet"]  !== "" && $match["ip4_snet"]  !== null ? 1 : 0) +
        (isset($match["ip6_saddr"]) && $match["ip6_saddr"] !== "" && $match["ip6_saddr"] !== null ? 1 : 0) +
        (isset($match["ip6_snet"])  && $match["ip6_snet"]  !== "" && $match["ip6_snet"]  !== null ? 1 : 0)
        > 1
    ) {
        $errors[] = "Solo uno de los campos de tráfico origen puede tener contenido: ip4_saddr, ip4_snet, ip6_saddr, ip6_snet";
    }
    //Solo uno de los campos de tráfico destino puede tener contenido
    if (
        (isset($match["ip4_daddr"]) && $match["ip4_daddr"] !== "" && $match["ip4_daddr"] !== null ? 1 : 0) +
        (isset($match["ip4_dnet"])  && $match["ip4_dnet"]  !== "" && $match["ip4_dnet"]  !== null ? 1 : 0) +
        (isset($match["ip6_daddr"]) && $match["ip6_daddr"] !== "" && $match["ip6_daddr"] !== null ? 1 : 0) +
        (isset($match["ip6_dnet"])  && $match["ip6_dnet"]  !== "" && $match["ip6_dnet"]  !== null ? 1 : 0)
        > 1
    ) {
        $errors[] = "Solo uno de los campos de tráfico destino puede tener contenido: ip4_daddr, ip4_dnet, ip6_daddr, ip6_dnet";
    }
    // Validación: solo uno entre tcp_sport y udp_sport puede tener contenido
    if (
        (isset($match["tcp_sport"]) && $match["tcp_sport"] !== "" && $match["tcp_sport"] !== null ? 1 : 0) +
        (isset($match["udp_sport"]) && $match["udp_sport"] !== "" && $match["udp_sport"] !== null ? 1 : 0)
        > 1
    ) {
        $errors[] = "Solo uno de los campos puede tener contenido: tcp_sport, udp_sport";
    }

    // Validación: solo uno entre tcp_sport y udp_dport puede tener contenido
    if (
        (isset($match["tcp_sport"]) && $match["tcp_sport"] !== "" && $match["tcp_sport"] !== null ? 1 : 0) +
        (isset($match["udp_dport"]) && $match["udp_dport"] !== "" && $match["udp_dport"] !== null ? 1 : 0)
        > 1
    ) {
        $errors[] = "Solo uno de los campos puede tener contenido: tcp_sport, udp_dport";
    }
    // Validación: no se pueden mezclar puertos TCP y UDP
    if (
        (
            (isset($match["tcp_sport"]) && $match["tcp_sport"] !== "" && $match["tcp_sport"] !== null) ||
            (isset($match["tcp_dport"]) && $match["tcp_dport"] !== "" && $match["tcp_dport"] !== null)
        ) &&
        (
            (isset($match["udp_sport"]) && $match["udp_sport"] !== "" && $match["udp_sport"] !== null) ||
            (isset($match["udp_dport"]) && $match["udp_dport"] !== "" && $match["udp_dport"] !== null)
        )
    ) {
        $errors[] = "No se pueden definir puertos TCP y UDP al mismo tiempo";
    }
    // Validación: l3_proto no puede ser null ni estar vacío
    if (!isset($match["l3_proto"]) || $match["l3_proto"] === "" || $match["l3_proto"] === null) {
        $errors[] = "El campo l3_proto es obligatorio y no puede estar vacío";
    }

    // Validación: si l3_proto = IPv6, no puede haber campos IPv4 con datos
    if (
        isset($match["l3_proto"]) && $match["l3_proto"] === "IPv6" &&
        (
            (isset($match["ip4_saddr"]) && $match["ip4_saddr"] !== "" && $match["ip4_saddr"] !== null) ||
            (isset($match["ip4_daddr"]) && $match["ip4_daddr"] !== "" && $match["ip4_daddr"] !== null) ||
            (isset($match["ip4_snet"])  && $match["ip4_snet"]  !== "" && $match["ip4_snet"]  !== null) ||
            (isset($match["ip4_dnet"])  && $match["ip4_dnet"]  !== "" && $match["ip4_dnet"]  !== null) ||
            (isset($match["ip4_proto"]) && $match["ip4_proto"] !== "" && $match["ip4_proto"] !== null)
        )
    ) {
        $errors[] = "Si l3_proto es IPv6, no se permiten campos IPv4 con datos";
    }
    // Validación: si l3_proto = IPv4, no puede haber campos IPv6 con datos
    if (
        isset($match["l3_proto"]) && $match["l3_proto"] === "IPv4" &&
        (
            (isset($match["ip6_saddr"]) && $match["ip6_saddr"] !== "" && $match["ip6_saddr"] !== null) ||
            (isset($match["ip6_daddr"]) && $match["ip6_daddr"] !== "" && $match["ip6_daddr"] !== null) ||
            (isset($match["ip6_snet"])  && $match["ip6_snet"]  !== "" && $match["ip6_snet"]  !== null) ||
            (isset($match["ip6_dnet"])  && $match["ip6_dnet"]  !== "" && $match["ip6_dnet"]  !== null) ||
            (isset($match["ip6_nexthdr"]) && $match["ip6_nexthdr"] !== "" && $match["ip6_nexthdr"] !== null)
        )
    ) {
        $errors[] = "Si l3_proto es IPv4, no se permiten campos IPv6 con datos";
    }

    // Validación: si ip4_proto = TCP, los campos UDP, ICMP e ICMPv6 deben estar vacíos
    if (
        (
            (isset($match["ip4_proto"]) && strtoupper(trim($match["ip4_proto"])) === "TCP") ||
            (isset($match["l4_proto"]) && strtoupper(trim($match["l4_proto"])) === "TCP")
        ) &&
        (
            (isset($match["udp_sport"]) && $match["udp_sport"] !== "" && $match["udp_sport"] !== null) ||
            (isset($match["udp_dport"]) && $match["udp_dport"] !== "" && $match["udp_dport"] !== null) ||
            (isset($match["icmp_type"]) && $match["icmp_type"] !== "" && $match["icmp_type"] !== null) ||
            (isset($match["icmp_code"]) && $match["icmp_code"] !== "" && $match["icmp_code"] !== null) ||
            (isset($match["icmpv6_type"]) && $match["icmpv6_type"] !== "" && $match["icmpv6_type"] !== null) ||
            (isset($match["icmpv6_code"]) && $match["icmpv6_code"] !== "" && $match["icmpv6_code"] !== null)
        )
    ) {
        $errors[] = "Si ip4_proto o l4_proto es TCP, no se permiten campos UDP, ICMP o ICMPv6 con datos";
    }
    
    
    // Validación: si ip4_proto = UDP, los campos TCP, ICMP e ICMPv6 deben estar vacíos
    if (
        (
        (isset($match["ip4_proto"]) && strtoupper(trim($match["ip4_proto"])) === "UDP") ||
        (isset($match["l4_proto"]) && strtoupper(trim($match["l4_proto"])) === "UDP")
        ) &&
        (
            (isset($match["tcp_sport"]) && $match["tcp_sport"] !== "" && $match["tcp_sport"] !== null) ||
            (isset($match["tcp_dport"]) && $match["tcp_dport"] !== "" && $match["tcp_dport"] !== null) ||
            (isset($match["tcp_flags"]) && $match["tcp_flags"] !== "" && $match["tcp_flags"] !== null) ||
            (isset($match["icmp_type"]) && $match["icmp_type"] !== "" && $match["icmp_type"] !== null) ||
            (isset($match["icmp_code"]) && $match["icmp_code"] !== "" && $match["icmp_code"] !== null) ||
            (isset($match["icmpv6_type"]) && $match["icmpv6_type"] !== "" && $match["icmpv6_type"] !== null) ||
            (isset($match["icmpv6_code"]) && $match["icmpv6_code"] !== "" && $match["icmpv6_code"] !== null)
        )
    ) {
        $errors[] = "Si ip4_proto o l4_proto es UDP, no se permiten campos TCP, ICMP o ICMPv6 con datos";
    }

    
    // Validación: si ip4_proto = ICMP, los campos TCP, UDP e ICMPv6 deben estar vacíos
    if (
        (
            (isset($match["ip4_proto"]) && strtoupper(trim($match["ip4_proto"])) === "ICMP") ||
            (isset($match["l4_proto"]) && strtoupper(trim($match["l4_proto"])) === "ICMP")
        ) &&

        (
            (isset($match["tcp_sport"]) && $match["tcp_sport"] !== "" && $match["tcp_sport"] !== null) ||
            (isset($match["tcp_dport"]) && $match["tcp_dport"] !== "" && $match["tcp_dport"] !== null) ||
            (isset($match["tcp_flags"]) && $match["tcp_flags"] !== "" && $match["tcp_flags"] !== null) ||
            (isset($match["udp_sport"]) && $match["udp_sport"] !== "" && $match["udp_sport"] !== null) ||
            (isset($match["udp_dport"]) && $match["udp_dport"] !== "" && $match["udp_dport"] !== null) ||
            (isset($match["icmpv6_type"]) && $match["icmpv6_type"] !== "" && $match["icmpv6_type"] !== null) ||
            (isset($match["icmpv6_code"]) && $match["icmpv6_code"] !== "" && $match["icmpv6_code"] !== null)
        )
    ) {
        $errors[] = "Si ip4_proto o l4_proto es ICMP, no se permiten campos TCP, UDP o ICMPv6 con datos";
    }
    
    // Validación: si ip4_proto = ICMPv6, los campos TCP, UDP e ICMP deben estar vacíos
    if (
        (
            (isset($match["ip4_proto"]) && strtoupper(trim($match["ip4_proto"])) === "ICMPv6") ||
            (isset($match["l4_proto"]) && strtoupper(trim($match["l4_proto"])) === "ICMPv6")
        ) &&

        (
            (isset($match["tcp_sport"]) && $match["tcp_sport"] !== "" && $match["tcp_sport"] !== null) ||
            (isset($match["tcp_dport"]) && $match["tcp_dport"] !== "" && $match["tcp_dport"] !== null) ||
            (isset($match["tcp_flags"]) && $match["tcp_flags"] !== "" && $match["tcp_flags"] !== null) ||
            (isset($match["udp_sport"]) && $match["udp_sport"] !== "" && $match["udp_sport"] !== null) ||
            (isset($match["udp_dport"]) && $match["udp_dport"] !== "" && $match["udp_dport"] !== null) ||
            (isset($match["icmp_type"]) && $match["icmp_type"] !== "" && $match["icmp_type"] !== null) ||
            (isset($match["icmp_code"]) && $match["icmp_code"] !== "" && $match["icmp_code"] !== null)
        )
    ) {
        $errors[] = "Si ip4_proto o l4_proto es ICMPv6, no se permiten campos TCP, UDP o ICMP con datos";
    }
    // Validación: si l3_proto es IPv6, ip4_proto debe estar vacío
    if (
        (
            isset($match["l3_proto"]) && strtoupper(trim($match["l3_proto"])) === "IPv6"
        ) &&
        (
            isset($match["ip4_proto"]) && $match["ip4_proto"] !== "" && $match["ip4_proto"] !== null
        )
    ) {
        $errors[] = "Si l3_proto es IPv6, ip4_proto debe estar vacío";
    }




    // 🧩 Resultado
    if (empty($errors)) {
        return ["status" => "OK"];
    } else {
        return [
            "status" => "ERROR",
            "errors" => $errors
        ];
    }
}
