<?php

function dhcp_import_config(): array {
    $path = '/var/www/config/dhcp.json';
    if (!file_exists($path)) {
        return ['dhcp' => []];
    }
    $json = json_decode((string)file_get_contents($path), true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['dhcp']) || !is_array($json['dhcp'])) {
        throw new RuntimeException('JSON DHCP mal formado');
    }
    return $json;
}

function dhcp_import_interfaces(): array {
    $interfaces = [];
    $path = '/var/www/backend/checks/system_data/data_interfaces/all_interfaces_list.json';
    if (file_exists($path)) {
        $json = json_decode((string)file_get_contents($path), true);
        if (json_last_error() === JSON_ERROR_NONE && isset($json['all_interfaces']) && is_array($json['all_interfaces'])) {
            $interfaces = array_merge($interfaces, array_map('strval', $json['all_interfaces']));
        }
    }
    foreach (glob('/sys/class/net/*') ?: [] as $ifaceDir) {
        $name = basename($ifaceDir);
        if ($name !== 'lo') {
            $interfaces[] = $name;
        }
    }
    $interfaces = array_values(array_unique(array_filter($interfaces, fn($v) => $v !== '')));
    sort($interfaces, SORT_NATURAL);
    return $interfaces;
}

function dhcp_fail(string $message): void {
    http_response_code(400);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function dhcp_clean_string($value): string {
    return trim((string)($value ?? ''));
}

function dhcp_validate_ip(string $value, string $field, bool $required = false, bool $allowSpecial = false): string {
    $value = dhcp_clean_string($value);
    if ($value === '') {
        if ($required) dhcp_fail("{$field} es obligatorio");
        return '';
    }
    // DHCP v4/dnsmasq scope UI accepts IPv4 only. IPv6 must not be mixed into these fields.
    // La UI de ámbitos DHCP v4/dnsmasq solo acepta IPv4. No se permite mezclar IPv6 en estos campos.
    if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        dhcp_fail("{$field} debe ser una IPv4 válida; IPv6 no está soportado en esta sección DHCPv4");
    }
    if (!$allowSpecial) {
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        // We do not use the PHP flags because private RFC1918 addresses are valid for DHCP scopes.
        // No usamos esos flags porque las privadas RFC1918 son válidas para ámbitos DHCP.
        if (!dhcp_is_unicast_ipv4($value)) {
            dhcp_fail("{$field} debe ser una IPv4 unicast utilizable");
        }
    }
    return $value;
}

function dhcp_is_unicast_ipv4(string $ip): bool {
    $long = dhcp_ip_to_long($ip);
    // 0.0.0.0/8, 127.0.0.0/8, 169.254.0.0/16, multicast 224.0.0.0/4, reserved 240.0.0.0/4, broadcast.
    // 0.0.0.0/8, loopback, link-local, multicast, reservado y broadcast no son direcciones de host DHCP válidas.
    $badRanges = [
        ['0.0.0.0', '0.255.255.255'],
        ['127.0.0.0', '127.255.255.255'],
        ['169.254.0.0', '169.254.255.255'],
        ['224.0.0.0', '239.255.255.255'],
        ['240.0.0.0', '255.255.255.255'],
    ];
    foreach ($badRanges as [$start, $end]) {
        if ($long >= dhcp_ip_to_long($start) && $long <= dhcp_ip_to_long($end)) return false;
    }
    return true;
}

function dhcp_validate_netmask(string $value, bool $required = false): string {
    $value = dhcp_clean_string($value);
    if ($value === '') {
        if ($required) dhcp_fail('netmask es obligatorio');
        return '';
    }
    if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        dhcp_fail('netmask debe ser una IPv4 válida');
    }
    $long = ip2long($value);
    $bin = str_pad(decbin($long), 32, '0', STR_PAD_LEFT);
    if (!preg_match('/^1*0*$/', $bin)) {
        dhcp_fail('netmask debe ser una máscara IPv4 contigua');
    }
    return $value;
}

function dhcp_validate_lease(string $value): string {
    $value = dhcp_clean_string($value);
    if ($value === '') return '12h';
    if (!preg_match('/^[1-9][0-9]*[mhdw]$/', $value)) {
        dhcp_fail('lease_time debe tener formato dnsmasq válido, por ejemplo 30m, 12h, 7d o 1w');
    }
    return $value;
}

function dhcp_ip_to_long(string $ip): int {
    $n = ip2long($ip);
    if ($n === false) dhcp_fail("IP inválida: {$ip}");
    return (int)sprintf('%u', $n);
}

function dhcp_network_bounds(string $gateway, string $netmask): array {
    $network = dhcp_ip_to_long($gateway) & dhcp_ip_to_long($netmask);
    $broadcast = $network | (~dhcp_ip_to_long($netmask) & 0xFFFFFFFF);
    return [$network, $broadcast];
}

function dhcp_same_network(string $ip, string $gateway, string $netmask): bool {
    [$network, $broadcast] = dhcp_network_bounds($gateway, $netmask);
    $candidate = dhcp_ip_to_long($ip);
    return $candidate >= $network && $candidate <= $broadcast;
}

function dhcp_is_network_or_broadcast(string $ip, string $gateway, string $netmask): bool {
    [$network, $broadcast] = dhcp_network_bounds($gateway, $netmask);
    $candidate = dhcp_ip_to_long($ip);
    return $candidate === $network || $candidate === $broadcast;
}

function dhcp_range_contains(string $ip, string $start, string $end): bool {
    $candidate = dhcp_ip_to_long($ip);
    return $candidate >= dhcp_ip_to_long($start) && $candidate <= dhcp_ip_to_long($end);
}

function dhcp_netmask_prefix(string $netmask): int {
    return substr_count(str_pad(decbin(dhcp_ip_to_long($netmask)), 32, '0', STR_PAD_LEFT), '1');
}

function dhcp_ranges_overlap(array $a, array $b): bool {
    return dhcp_ip_to_long($a['range_start']) <= dhcp_ip_to_long($b['range_end']) && dhcp_ip_to_long($b['range_start']) <= dhcp_ip_to_long($a['range_end']);
}

function check_create_id(array $rule, string $chain): array {
    if (isset($rule['id']) && is_numeric($rule['id']) && (string)$rule['id'] !== '') {
        $rule['id'] = (string)(int)$rule['id'];
        return $rule;
    }
    $json = dhcp_import_config();
    $used = [];
    foreach ($json[$chain] ?? [] as $entry) {
        $id = $entry['rule']['id'] ?? null;
        if (is_numeric($id)) $used[(int)$id] = true;
    }
    $next = 1;
    while (isset($used[$next])) $next++;
    $rule['id'] = (string)$next;
    return $rule;
}

function validate_dhcp_rule(array $rule, ?array $existing = null): array {
    $rule = check_create_id($rule, 'dhcp');
    $id = (string)$rule['id'];
    $enable = strtolower(dhcp_clean_string($rule['enable'] ?? 'true'));
    if (!in_array($enable, ['true', 'false'], true)) dhcp_fail('enable debe ser true o false');
    $mode = strtolower(dhcp_clean_string($rule['mode'] ?? 'server'));
    if (!in_array($mode, ['server', 'relay'], true)) dhcp_fail('mode debe ser server o relay');

    $interfaces = dhcp_import_interfaces();
    $interface = dhcp_clean_string($rule['interface'] ?? '');
    if ($interface === '') dhcp_fail('interface es obligatorio');
    if ($interfaces && !in_array($interface, $interfaces, true)) dhcp_fail("interface '{$interface}' no existe");

    $normalized = [
        'id' => $id,
        'enable' => $enable,
        'mode' => $mode,
        'interface' => $interface,
        'range_start' => '',
        'range_end' => '',
        'lease_time' => '',
        'gateway' => '',
        'netmask' => '',
        'dns_primary' => '',
        'dns_secondary' => '',
        'ntp_server' => '',
        'relay_local_ip' => '',
        'relay_dest_server' => '',
    ];

    if ($mode === 'server') {
        if (dhcp_clean_string($rule['relay_local_ip'] ?? '') !== '' || dhcp_clean_string($rule['relay_dest_server'] ?? '') !== '') {
            dhcp_fail('Una entrada server no puede tener relay_local_ip ni relay_dest_server');
        }
        $normalized['range_start'] = dhcp_validate_ip($rule['range_start'] ?? '', 'range_start', $enable === 'true');
        $normalized['range_end'] = dhcp_validate_ip($rule['range_end'] ?? '', 'range_end', $enable === 'true');
        $normalized['gateway'] = dhcp_validate_ip($rule['gateway'] ?? '', 'gateway', $enable === 'true');
        $normalized['netmask'] = dhcp_validate_netmask($rule['netmask'] ?? '', $enable === 'true');
        $normalized['lease_time'] = dhcp_validate_lease($rule['lease_time'] ?? '');
        $normalized['dns_primary'] = dhcp_validate_ip($rule['dns_primary'] ?? '', 'dns_primary', false);
        $normalized['dns_secondary'] = dhcp_validate_ip($rule['dns_secondary'] ?? '', 'dns_secondary', false);
        $normalized['ntp_server'] = dhcp_validate_ip($rule['ntp_server'] ?? '', 'ntp_server', false);

        if ($enable === 'true') {
            if (dhcp_netmask_prefix($normalized['netmask']) > 30) {
                dhcp_fail('netmask no deja suficientes direcciones útiles para un ámbito DHCP');
            }
            if (dhcp_ip_to_long($normalized['range_start']) > dhcp_ip_to_long($normalized['range_end'])) {
                dhcp_fail('range_start no puede ser mayor que range_end');
            }
            foreach (['gateway', 'range_start', 'range_end'] as $field) {
                if (!dhcp_same_network($normalized[$field], $normalized['gateway'], $normalized['netmask'])) {
                    dhcp_fail("{$field} debe estar en la misma red que gateway/netmask");
                }
                if (dhcp_is_network_or_broadcast($normalized[$field], $normalized['gateway'], $normalized['netmask'])) {
                    dhcp_fail("{$field} no puede ser la dirección de red ni broadcast");
                }
            }
            if (dhcp_range_contains($normalized['gateway'], $normalized['range_start'], $normalized['range_end'])) {
                dhcp_fail('gateway no puede estar dentro del rango DHCP entregado a clientes');
            }
            foreach (['dns_primary', 'dns_secondary', 'ntp_server'] as $field) {
                if ($normalized[$field] !== '' && dhcp_is_network_or_broadcast($normalized[$field], $normalized['gateway'], $normalized['netmask'])) {
                    dhcp_fail("{$field} no puede ser la dirección de red ni broadcast");
                }
            }
        }
    } else {
        foreach (['range_start','range_end','gateway','netmask','dns_primary','dns_secondary','ntp_server'] as $field) {
            if (dhcp_clean_string($rule[$field] ?? '') !== '') {
                dhcp_fail('Una entrada relay no puede tener campos de scope/rango DHCP');
            }
        }
        $normalized['relay_local_ip'] = dhcp_validate_ip($rule['relay_local_ip'] ?? '', 'relay_local_ip', $enable === 'true');
        $normalized['relay_dest_server'] = dhcp_validate_ip($rule['relay_dest_server'] ?? '', 'relay_dest_server', $enable === 'true');
        if ($enable === 'true' && $normalized['relay_local_ip'] === $normalized['relay_dest_server']) {
            dhcp_fail('relay_local_ip y relay_dest_server no pueden ser la misma IP');
        }
    }

    if ($existing !== null && $enable === 'true') {
        foreach ($existing as $entry) {
            $other = $entry['rule'] ?? [];
            if (!is_array($other) || ($other['id'] ?? '') === $id || ($other['enable'] ?? 'true') !== 'true') continue;
            if (($other['interface'] ?? '') === $interface) {
                if (($other['mode'] ?? 'server') !== $mode) {
                    dhcp_fail("La interfaz {$interface} no puede mezclar server y relay");
                }
                if ($mode === 'relay') {
                    dhcp_fail("Solo se permite un relay activo por interfaz {$interface}");
                }
                if ($mode === 'server' && !empty($other['range_start']) && !empty($other['range_end']) && dhcp_ranges_overlap($normalized, $other)) {
                    dhcp_fail("El rango DHCP se solapa con otra entrada activa en {$interface}");
                }
            }
        }
    }

    return $normalized;
}
