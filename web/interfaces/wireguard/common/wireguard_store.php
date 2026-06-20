<?php
// Utilidades comunes para WireGuard candidate JSON.
// Common helpers for WireGuard candidate JSON.

const WIREGUARD_CONFIG_PATH = '/var/www/config/wireguard.json';
const WIREGUARD_STRUCTURE_PATH = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_wireguard.json';
const WIREGUARD_FORMS_PATH = '/var/www/backend/checks/system_data/default_forms/forms_wireguard.json';

function wireguard_empty_config(): array {
    return [
        'site_to_site' => [],
        'remote_access' => [],
        'remote_clients' => []
    ];
}

function wireguard_section_for_alias(string $alias): ?string {
    $map = [
        'wireguard_site_to_site' => 'site_to_site',
        'wireguard_remote_access' => 'remote_access',
        'wireguard_remote_clients' => 'remote_clients'
    ];
    return $map[$alias] ?? null;
}

function wireguard_prefix_for_section(string $section): string {
    $map = [
        'site_to_site' => 'wg-s2s',
        'remote_access' => 'wg-ra',
        'remote_clients' => 'wg-client'
    ];
    return $map[$section] ?? 'wg';
}

function wireguard_read_json(string $path): array {
    if (!file_exists($path)) {
        return wireguard_empty_config();
    }
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return wireguard_empty_config();
    }
    return array_replace_recursive(wireguard_empty_config(), $data);
}

function wireguard_read_structure(string $alias): array {
    $raw = file_get_contents(WIREGUARD_STRUCTURE_PATH);
    $data = json_decode($raw, true);
    return is_array($data) ? ($data[$alias] ?? []) : [];
}

function wireguard_read_forms(string $alias): array {
    $raw = file_get_contents(WIREGUARD_FORMS_PATH);
    $data = json_decode($raw, true);
    return is_array($data) ? ($data[$alias] ?? []) : [];
}

function wireguard_mask_row_for_table(array $row): array {
    if (isset($row['private_key']) && trim((string)$row['private_key']) !== '') {
        $row['private_key'] = '********';
    }
    return $row;
}


function wireguard_prepare_for_json(array $config): array {
    foreach (['site_to_site', 'remote_access', 'remote_clients'] as $section) {
        if (!isset($config[$section]) || !is_array($config[$section]) || count($config[$section]) === 0) {
            $config[$section] = new stdClass();
        }
    }
    return $config;
}

function wireguard_make_name(array $config, string $section): string {
    $prefix = wireguard_prefix_for_section($section);
    $index = 0;
    do {
        $candidate = $prefix . $index;
        $index++;
    } while (isset($config[$section][$candidate]));
    return $candidate;
}

function wireguard_validate_bool_string(string $value, string $field): void {
    if ($value !== '' && !in_array($value, ['true', 'false'], true)) {
        echo json_encode(['error' => "Campo booleano inválido: {$field}"]);
        exit;
    }
}

function wireguard_validate_simple_name(string $value, string $field): void {
    if ($value !== '' && !preg_match('/^[A-Za-z0-9_.-]{1,64}$/', $value)) {
        echo json_encode(['error' => "Nombre inválido en {$field}"]);
        exit;
    }
}

function wireguard_validate_csv_cidrs(string $value, string $field, bool $allowDefault = false): void {
    if (trim($value) === '') return;
    foreach (array_map('trim', explode(',', $value)) as $item) {
        if ($allowDefault && $item === 'default') continue;
        if (!preg_match('/^(.+)\/(\d{1,3})$/', $item, $m)) {
            echo json_encode(['error' => "{$field} debe contener IP/CIDR separados por comas"]);
            exit;
        }
        $ip = $m[1];
        $cidr = (int)$m[2];
        $is4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
        $is6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        if ((!$is4 && !$is6) || ($is4 && ($cidr < 0 || $cidr > 32)) || ($is6 && ($cidr < 0 || $cidr > 128))) {
            echo json_encode(['error' => "CIDR inválido en {$field}: {$item}"]);
            exit;
        }
    }
}

function wireguard_validate_csv_ips(string $value, string $field): void {
    if (trim($value) === '') return;
    foreach (array_map('trim', explode(',', $value)) as $item) {
        if (!filter_var($item, FILTER_VALIDATE_IP)) {
            echo json_encode(['error' => "IP inválida en {$field}: {$item}"]);
            exit;
        }
    }
}

function wireguard_validate_port(string $value, string $field): void {
    if (trim($value) === '') return;
    if (!ctype_digit((string)$value) || (int)$value < 1 || (int)$value > 65535) {
        echo json_encode(['error' => "Puerto inválido en {$field}"]);
        exit;
    }
}

function wireguard_validate_int_range(string $value, string $field, int $min, int $max): void {
    if (trim($value) === '') return;
    if (!ctype_digit((string)$value) || (int)$value < $min || (int)$value > $max) {
        echo json_encode(['error' => "Valor fuera de rango en {$field}"]);
        exit;
    }
}

function wireguard_validate_key(string $value, string $field): void {
    if (trim($value) === '' || $value === '********') return;
    if (!preg_match('/^[A-Za-z0-9+\/]{43}=$/', $value)) {
        echo json_encode(['error' => "Clave WireGuard inválida en {$field}"]);
        exit;
    }
}

function wireguard_validate_endpoint(string $value, string $field): void {
    if (trim($value) === '') return;
    if (!preg_match('/^(.+):(\d{1,5})$/', $value, $m)) {
        echo json_encode(['error' => "{$field} debe tener formato host:puerto"]);
        exit;
    }
    wireguard_validate_port($m[2], $field);
}

function wireguard_validate_rule(string $alias, array $rule): void {
    $enabled = (string)($rule['enabled'] ?? '');
    wireguard_validate_bool_string($enabled, 'enabled');

    foreach (['interface', 'vpn'] as $field) {
        if (isset($rule[$field])) wireguard_validate_simple_name((string)$rule[$field], $field);
    }

    foreach (['listen_port'] as $field) {
        if (isset($rule[$field])) wireguard_validate_port((string)$rule[$field], $field);
    }
    foreach (['keepalive'] as $field) {
        if (isset($rule[$field])) wireguard_validate_int_range((string)$rule[$field], $field, 0, 65535);
    }
    foreach (['mtu'] as $field) {
        if (isset($rule[$field])) wireguard_validate_int_range((string)$rule[$field], $field, 576, 9000);
    }

    foreach (['local_tunnel_ip','remote_tunnel_ip','server_vpn_ip','vpn_network','client_vpn_ip','local_networks','remote_networks','internal_networks','allowed_ips'] as $field) {
        if (isset($rule[$field])) wireguard_validate_csv_cidrs((string)$rule[$field], $field, $field === 'allowed_ips');
    }
    if (isset($rule['dns'])) wireguard_validate_csv_ips((string)$rule['dns'], 'dns');
    foreach (['private_key','remote_public_key','client_public_key'] as $field) {
        if (isset($rule[$field])) wireguard_validate_key((string)$rule[$field], $field);
    }
    if (isset($rule['remote_endpoint'])) wireguard_validate_endpoint((string)$rule['remote_endpoint'], 'remote_endpoint');
}
?>
