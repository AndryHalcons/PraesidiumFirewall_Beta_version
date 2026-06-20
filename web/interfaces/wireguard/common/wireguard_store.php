<?php
// Utilidades comunes para WireGuard candidate JSON.
// Common helpers for WireGuard candidate JSON.

const WIREGUARD_CONFIG_PATH = '/var/www/config/wireguard.json';
const WIREGUARD_STRUCTURE_PATH = '/var/www/backend/checks/system_data/default_tables_structure/structure_table_wireguard.json';
const WIREGUARD_FORMS_PATH = '/var/www/backend/checks/system_data/default_forms/forms_wireguard.json';

// Devuelve la estructura base vacía esperada para wireguard.json.
// Returns the empty base structure expected for wireguard.json.
function wireguard_empty_config(): array {
    return ['site_to_site' => [], 'remote_access' => [], 'remote_clients' => []];
}

// Traduce el alias de tabla genérica a la sección real del JSON WireGuard.
// Translates the generic table alias into the real WireGuard JSON section.
function wireguard_section_for_alias(string $alias): ?string {
    return [
        'wireguard_site_to_site' => 'site_to_site',
        'wireguard_remote_access' => 'remote_access',
        'wireguard_remote_clients' => 'remote_clients'
    ][$alias] ?? null;
}

// Devuelve el prefijo usado para autogenerar nombres por sección.
// Returns the prefix used to auto-generate names per section.
function wireguard_prefix_for_section(string $section): string {
    return ['site_to_site' => 'wg-s2s', 'remote_access' => 'wg-ra', 'remote_clients' => 'wg-client'][$section] ?? 'wg';
}

// Lee un JSON de configuración y devuelve la estructura vacía si no existe.
// Reads a configuration JSON and returns the empty structure if it does not exist.
function wireguard_read_json(string $path): array {
    if (!file_exists($path)) return wireguard_empty_config();
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? array_replace_recursive(wireguard_empty_config(), $data) : wireguard_empty_config();
}

// Lee la definición de columnas usada por renderTableGeneric.
// Reads the column definition used by renderTableGeneric.
function wireguard_read_structure(string $alias): array {
    $data = json_decode(file_get_contents(WIREGUARD_STRUCTURE_PATH), true);
    return is_array($data) ? ($data[$alias] ?? []) : [];
}


// Devuelve el nombre de campo para columnas string u objeto de la tabla genérica.
// Returns the field name for string or object columns from the generic table.
function wireguard_column_field($column): string {
    return is_array($column) ? (string)($column['field'] ?? '') : (string)$column;
}

// Devuelve solo campos de datos, excluyendo columnas botón declaradas en JSON.
// Returns only data fields, excluding button columns declared in JSON.
function wireguard_structure_fields(string $alias): array {
    $fields = [];
    foreach (wireguard_read_structure($alias) as $column) {
        if (is_array($column) && ($column['type'] ?? '') === 'button') continue;
        $field = wireguard_column_field($column);
        if ($field !== '') $fields[] = $field;
    }
    return $fields;
}

// Ejecuta wg genkey/wg pubkey para crear un par de claves de cliente.
// Runs wg genkey/wg pubkey to create a client key pair.
function wireguard_generate_keypair(): ?array {
    $private = trim((string)shell_exec('wg genkey 2>/dev/null'));
    if ($private === '') return null;
    $descriptor = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open('wg pubkey', $descriptor, $pipes);
    if (!is_resource($process)) return null;
    fwrite($pipes[0], $private);
    fclose($pipes[0]);
    $public = trim(stream_get_contents($pipes[1]));
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);
    return $code === 0 && $public !== '' ? ['private' => $private, 'public' => $public] : null;
}

// Calcula la clave pública de una clave privada WireGuard sin mostrar el secreto.
// Calculates the public key from a WireGuard private key without displaying the secret.
function wireguard_public_key_from_private(string $private): ?string {
    $descriptor = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open('wg pubkey', $descriptor, $pipes);
    if (!is_resource($process)) return null;
    fwrite($pipes[0], $private);
    fclose($pipes[0]);
    $public = trim(stream_get_contents($pipes[1]));
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);
    return $code === 0 && $public !== '' ? $public : null;
}

// Normaliza nombres de archivo para descargas de clientes WireGuard.
// Normalizes file names for WireGuard client downloads.
function wireguard_download_filename(string $name, string $extension): string {
    $safe = preg_replace('/[^A-Za-z0-9_.-]/', '_', $name);
    return ($safe ?: 'wireguard-client') . '.' . $extension;
}

// Obtiene el host externo usado por el cliente para conectar con el firewall.
// Gets the external host used by the client to connect to the firewall.
function wireguard_client_endpoint_host(array $server): string {
    $configured = trim((string)($server['public_endpoint'] ?? ''));
    if ($configured !== '') return preg_replace('/:\d+$/', '', $configured);
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    return preg_replace('/:\d+$/', '', (string)$host);
}

// Construye el archivo .conf que se entrega al cliente WireGuard.
// Builds the .conf file delivered to the WireGuard client.
function wireguard_build_client_config(string $clientName, array $client, string $serverName, array $server): ?string {
    $clientPrivate = trim((string)($client['client_private_key'] ?? ''));
    $serverPrivate = trim((string)($server['private_key'] ?? ''));
    if ($clientPrivate === '' || $serverPrivate === '') return null;
    $serverPublic = wireguard_public_key_from_private($serverPrivate);
    if ($serverPublic === null) return null;
    $endpointHost = wireguard_client_endpoint_host($server);
    $listenPort = trim((string)($server['listen_port'] ?? ''));
    if ($endpointHost === '' || $listenPort === '') return null;
    $lines = [
        '# Cliente WireGuard generado por PraesidiumFirewall.',
        '# WireGuard client generated by PraesidiumFirewall.',
        '# Cliente: ' . $clientName . '; VPN: ' . $serverName,
        '[Interface]',
        'PrivateKey = ' . $clientPrivate,
        'Address = ' . trim((string)($client['client_vpn_ip'] ?? '')),
    ];
    $dns = trim((string)($server['dns'] ?? ''));
    if ($dns !== '') $lines[] = 'DNS = ' . $dns;
    $lines[] = '';
    $lines[] = '[Peer]';
    $lines[] = 'PublicKey = ' . $serverPublic;
    $lines[] = 'Endpoint = ' . $endpointHost . ':' . $listenPort;
    $allowed = trim((string)($server['internal_networks'] ?? ''));
    $lines[] = 'AllowedIPs = ' . ($allowed !== '' ? $allowed : '0.0.0.0/0');
    $keepalive = trim((string)($client['keepalive'] ?? ''));
    if ($keepalive !== '') $lines[] = 'PersistentKeepalive = ' . $keepalive;
    return implode("\n", $lines) . "\n";
}

// Busca un cliente y su servidor asociado para exportar configuración.
// Finds a client and its associated server to export configuration.
function wireguard_find_client_export(string $clientName, array $config): ?array {
    if (!isset($config['remote_clients'][$clientName])) return null;
    $client = $config['remote_clients'][$clientName];
    $serverName = (string)($client['vpn'] ?? '');
    if ($serverName === '' || !isset($config['remote_access'][$serverName])) return null;
    return ['client' => $client, 'server_name' => $serverName, 'server' => $config['remote_access'][$serverName]];
}

// Lee la definición de formulario usada por renderTableGeneric.
// Reads the form definition used by renderTableGeneric.
function wireguard_read_forms(string $alias): array {
    $data = json_decode(file_get_contents(WIREGUARD_FORMS_PATH), true);
    return is_array($data) ? ($data[$alias] ?? []) : [];
}

// Enmascara secretos antes de devolver filas a la tabla web.
// Masks secrets before returning rows to the web table.
function wireguard_mask_row_for_table(array $row): array {
    if (isset($row['private_key']) && trim((string)$row['private_key']) !== '') $row['private_key'] = '********';
    if (isset($row['client_private_key']) && trim((string)$row['client_private_key']) !== '') $row['client_private_key'] = '********';
    return $row;
}

// Normaliza secciones vacías para que el candidate mantenga objetos JSON.
// Normalizes empty sections so the candidate keeps JSON objects.
function wireguard_prepare_for_json(array $config): array {
    foreach (['site_to_site', 'remote_access', 'remote_clients'] as $section) {
        if (!isset($config[$section]) || !is_array($config[$section]) || count($config[$section]) === 0) $config[$section] = new stdClass();
    }
    return $config;
}

// Genera el siguiente nombre estable para una sección WireGuard.
// Generates the next stable name for a WireGuard section.
function wireguard_make_name(array $config, string $section): string {
    $prefix = wireguard_prefix_for_section($section);
    $index = 0;
    do { $candidate = $prefix . $index; $index++; } while (isset($config[$section][$candidate]));
    return $candidate;
}

// Carga el idioma activo de sesión para textos y validaciones.
// Loads the active session language for text and validations.
function wireguard_lang(): array {
    $language = $_SESSION['language'] ?? 'es';
    $langFile = $_SERVER['DOCUMENT_ROOT'] . "/lang/{$language}.php";
    if (!file_exists($langFile)) $langFile = $_SERVER['DOCUMENT_ROOT'] . "/lang/es.php";
    $lang = require $langFile;
    return is_array($lang) ? $lang : [];
}

// Resuelve una clave de idioma WireGuard y sustituye variables.
// Resolves a WireGuard language key and replaces variables.
function wireguard_t(string $key, array $vars = []): string {
    $lang = wireguard_lang();
    $text = (string)($lang[$key] ?? $key);
    foreach ($vars as $name => $value) {
        $text = str_replace('{' . $name . '}', (string)$value, $text);
    }
    return $text;
}

// Obtiene la etiqueta humana de un campo para mensajes de error.
// Gets the human label of a field for error messages.
function wireguard_label(string $field): string {
    $lang = wireguard_lang();
    return (string)($lang[$field] ?? $field);
}

// Devuelve un error JSON amigable y detiene el endpoint.
// Returns a friendly JSON error and stops the endpoint.
function wireguard_error(string $message, ?string $field = null): void {
    echo json_encode(['error' => $message, 'field' => $field], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Limpia espacios de todos los valores recibidos desde el formulario.
// Trims spaces from every value received from the form.
function wireguard_trim_rule(array $rule): array {
    foreach ($rule as $key => $value) if (is_string($value)) $rule[$key] = trim($value);
    return $rule;
}

// Comprueba si una regla está marcada como activa.
// Checks whether a rule is marked as enabled.
function wireguard_is_enabled(array $rule): bool { return (string)($rule['enabled'] ?? '') === 'true'; }

// Exige campos obligatorios cuando una entrada activa depende de ellos.
// Requires mandatory fields when an active entry depends on them.
function wireguard_required(array $rule, array $fields): void {
    foreach ($fields as $field) {
        if (!isset($rule[$field]) || trim((string)$rule[$field]) === '') {
            wireguard_error(wireguard_t('wireguard_error_required', ['field' => wireguard_label($field)]), $field);
        }
    }
}

// Valida booleanos serializados como cadenas true/false.
// Validates booleans serialized as true/false strings.
function wireguard_validate_bool_string(string $value, string $field): void {
    if ($value !== '' && !in_array($value, ['true', 'false'], true)) wireguard_error(wireguard_t('wireguard_error_bool', ['field' => wireguard_label($field)]), $field);
}

// Valida nombres internos sin espacios ni caracteres peligrosos.
// Validates internal names without spaces or dangerous characters.
function wireguard_validate_entry_name(string $value, string $field = 'name'): void {
    if ($value !== '' && !preg_match('/^[A-Za-z0-9_.-]{1,64}$/', $value)) wireguard_error(wireguard_t('wireguard_error_name_format', ['field' => wireguard_label($field)]), $field);
}

// Valida nombres de interfaz compatibles con Linux.
// Validates interface names compatible with Linux.
function wireguard_validate_interface_name(string $value, string $field = 'interface'): void {
    if ($value === '') return;
    if (!preg_match('/^[A-Za-z0-9_.:-]{1,15}$/', $value)) wireguard_error(wireguard_t('wireguard_error_interface_format'), $field);
}

// Divide valores separados por comas para redes, IPs y listas.
// Splits comma-separated values for networks, IPs and lists.
function wireguard_split_csv(string $value): array {
    if (trim($value) === '') return [];
    $items = array_map('trim', explode(',', $value));
    return array_values(array_filter($items, fn($item) => $item !== ''));
}

// Valida un CIDR y devuelve IP/máscara normalizadas para comprobaciones IPv4.
// Validates a CIDR and returns normalized IP/prefix for IPv4 checks.
function wireguard_parse_cidr(string $item, string $field, bool $allowDefault = false): array {
    if ($allowDefault && $item === 'default') return ['ip' => 'default', 'prefix' => 0, 'version' => 'default', 'raw' => $item];
    if (!preg_match('/^(.+)\/(\d{1,3})$/', $item, $m)) wireguard_error(wireguard_t('wireguard_error_cidr_format', ['field' => wireguard_label($field)]), $field);
    $ip = $m[1]; $prefix = (int)$m[2];
    $is4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    $is6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    if (!$is4 && !$is6) wireguard_error(wireguard_t('wireguard_error_invalid_ip_in_field', ['ip' => $ip, 'field' => wireguard_label($field)]), $field);
    if ($is4 && ($prefix < 0 || $prefix > 32)) wireguard_error(wireguard_t('wireguard_error_ipv4_prefix', ['field' => wireguard_label($field)]), $field);
    if ($is6 && ($prefix < 0 || $prefix > 128)) wireguard_error(wireguard_t('wireguard_error_ipv6_prefix', ['field' => wireguard_label($field)]), $field);
    return ['ip' => $ip, 'prefix' => $prefix, 'version' => $is4 ? 4 : 6, 'raw' => $item];
}

// Valida una lista de CIDRs separados por comas.
// Validates a comma-separated list of CIDRs.
function wireguard_validate_csv_cidrs(string $value, string $field, bool $allowDefault = false): array {
    $parsed = [];
    foreach (wireguard_split_csv($value) as $item) $parsed[] = wireguard_parse_cidr($item, $field, $allowDefault);
    return $parsed;
}

// Valida una lista de direcciones IP sin máscara.
// Validates a list of IP addresses without prefix length.
function wireguard_validate_csv_ips(string $value, string $field): void {
    foreach (wireguard_split_csv($value) as $item) if (!filter_var($item, FILTER_VALIDATE_IP)) wireguard_error(wireguard_t('wireguard_error_invalid_ip_in_field', ['ip' => $item, 'field' => wireguard_label($field)]), $field);
}

// Valida puertos de escucha permitidos.
// Validates allowed listen ports.
function wireguard_validate_port(string $value, string $field): void {
    if (trim($value) === '') return;
    if (!ctype_digit((string)$value) || (int)$value < 1 || (int)$value > 65535) wireguard_error(wireguard_t('wireguard_error_port_range'), $field);
}

// Valida números acotados usados en MTU y keepalive.
// Validates bounded numbers used by MTU and keepalive.
function wireguard_validate_int_range(string $value, string $field, int $min, int $max, string $friendlyName): void {
    if (trim($value) === '') return;
    if (!ctype_digit((string)$value) || (int)$value < $min || (int)$value > $max) wireguard_error(wireguard_t('wireguard_error_int_range', ['field' => $friendlyName, 'min' => $min, 'max' => $max]), $field);
}

// Valida el formato externo de claves WireGuard.
// Validates the external format of WireGuard keys.
function wireguard_validate_key(string $value, string $field): void {
    if (trim($value) === '' || $value === '********') return;
    if (!preg_match('/^[A-Za-z0-9+\/]{43}=$/', $value)) wireguard_error(wireguard_t('wireguard_error_key_format', ['field' => wireguard_label($field)]), $field);
}

// Valida endpoints remotos host:puerto antes de guardar candidate.
// Validates remote host:port endpoints before saving candidate.
function wireguard_validate_endpoint(string $value, string $field): void {
    if (trim($value) === '') return;
    if (preg_match('/^\[([0-9A-Fa-f:.]+)\]:(\d{1,5})$/', $value, $m)) {
        if (filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) wireguard_error(wireguard_t('wireguard_error_endpoint_ipv6'), $field);
        wireguard_validate_port($m[2], $field); return;
    }
    if (preg_match('/^([^:\s]+):(\d{1,5})$/', $value, $m)) {
        if (!preg_match('/^[A-Za-z0-9.-]+$/', $m[1]) && filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) wireguard_error(wireguard_t('wireguard_error_endpoint_host'), $field);
        wireguard_validate_port($m[2], $field); return;
    }
    wireguard_error(wireguard_t('wireguard_error_endpoint_format'), $field);
}

// Convierte IPv4 a entero sin signo para cálculos de red.
// Converts IPv4 to an unsigned integer for network calculations.
function wireguard_ipv4_to_uint(string $ip): int { return (int)sprintf('%u', ip2long($ip)); }
// Calcula red/broadcast IPv4 a partir de un CIDR validado.
// Calculates IPv4 network/broadcast from a validated CIDR.
function wireguard_ipv4_network(array $cidr): ?array {
    if (($cidr['version'] ?? null) !== 4) return null;
    $prefix = (int)$cidr['prefix']; $ip = wireguard_ipv4_to_uint($cidr['ip']);
    $mask = $prefix === 0 ? 0 : ((0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF);
    $network = $ip & $mask; $broadcast = $network | (~$mask & 0xFFFFFFFF);
    return ['network' => $network, 'broadcast' => $broadcast, 'prefix' => $prefix];
}
// Comprueba si una IP pertenece a una red IPv4.
// Checks whether an IP belongs to an IPv4 network.
function wireguard_ipv4_contains(array $networkCidr, array $hostCidr): bool {
    $network = wireguard_ipv4_network($networkCidr); if ($network === null) return false;
    $hostIp = wireguard_ipv4_to_uint($hostCidr['ip']);
    return $hostIp >= $network['network'] && $hostIp <= $network['broadcast'];
}
// Comprueba si dos redes IPv4 se solapan.
// Checks whether two IPv4 networks overlap.
function wireguard_ipv4_overlap(array $a, array $b): bool {
    $na = wireguard_ipv4_network($a); $nb = wireguard_ipv4_network($b);
    if ($na === null || $nb === null) return false;
    return $na['network'] <= $nb['broadcast'] && $nb['network'] <= $na['broadcast'];
}

// Exige que las IPs local/remota del túnel pertenezcan a la misma red.
// Requires local/remote tunnel IPs to belong to the same network.
function wireguard_require_same_tunnel_network(array $localList, array $remoteList): void {
    if (count($localList) !== 1 || count($remoteList) !== 1) wireguard_error(wireguard_t('wireguard_error_single_tunnel_ip'), 'local_tunnel_ip');
    $local = $localList[0]; $remote = $remoteList[0];
    if ($local['version'] !== $remote['version']) wireguard_error(wireguard_t('wireguard_error_tunnel_same_family'), 'remote_tunnel_ip');
    if ($local['version'] === 4) {
        $ln = wireguard_ipv4_network($local); $rn = wireguard_ipv4_network($remote);
        if ($ln['network'] !== $rn['network'] || $local['prefix'] !== $remote['prefix']) wireguard_error(wireguard_t('wireguard_error_tunnel_same_network'), 'remote_tunnel_ip');
    }
}

// Bloquea solapes entre redes que no deben mezclarse.
// Blocks overlaps between networks that must not mix.
function wireguard_check_network_overlap(array $left, array $right, string $leftField, string $rightField): void {
    foreach ($left as $a) foreach ($right as $b) if (($a['version'] ?? null) === 4 && ($b['version'] ?? null) === 4 && wireguard_ipv4_overlap($a, $b)) wireguard_error(wireguard_t('wireguard_error_network_overlap', ['left' => wireguard_label($leftField), 'right' => wireguard_label($rightField)]), $rightField);
}

// Evita interfaces WireGuard duplicadas dentro del candidate.
// Prevents duplicated WireGuard interfaces inside the candidate.
function wireguard_validate_no_duplicate_interface(array $config, string $section, string $entryName, string $interface): void {
    if ($interface === '') return;
    foreach (['site_to_site', 'remote_access'] as $otherSection) foreach (($config[$otherSection] ?? []) as $name => $entry) {
        if ($otherSection === $section && $name === $entryName) continue;
        if (($entry['interface'] ?? '') === $interface) wireguard_error(wireguard_t('wireguard_error_duplicate_interface', ['interface' => $interface]), 'interface');
    }
}

// Evita puertos de escucha duplicados dentro del candidate.
// Prevents duplicated listen ports inside the candidate.
function wireguard_validate_no_duplicate_port(array $config, string $section, string $entryName, string $port): void {
    if ($port === '') return;
    foreach (['site_to_site', 'remote_access'] as $otherSection) foreach (($config[$otherSection] ?? []) as $name => $entry) {
        if ($otherSection === $section && $name === $entryName) continue;
        if (($entry['listen_port'] ?? '') === $port) wireguard_error(wireguard_t('wireguard_error_duplicate_port', ['port' => $port]), 'listen_port');
    }
}

// Evita IPs y claves públicas duplicadas entre clientes remotos.
// Prevents duplicated IPs and public keys between remote clients.
function wireguard_validate_unique_client_values(array $config, string $entryName, array $rule): void {
    foreach (($config['remote_clients'] ?? []) as $name => $entry) {
        if ($name === $entryName) continue;
        if (($rule['client_vpn_ip'] ?? '') !== '' && ($entry['client_vpn_ip'] ?? '') === ($rule['client_vpn_ip'] ?? '')) wireguard_error(wireguard_t('wireguard_error_duplicate_client_ip'), 'client_vpn_ip');
        if (($rule['client_public_key'] ?? '') !== '' && ($entry['client_public_key'] ?? '') === ($rule['client_public_key'] ?? '')) wireguard_error(wireguard_t('wireguard_error_duplicate_client_key'), 'client_public_key');
    }
}

// Comprueba que el cliente apunte a una VPN existente y use su red.
// Checks that the client points to an existing VPN and uses its network.
function wireguard_validate_remote_client_vpn(array $config, array $rule): void {
    $vpn = (string)($rule['vpn'] ?? ''); if ($vpn === '') return;
    if (!isset($config['remote_access'][$vpn])) wireguard_error(wireguard_t('wireguard_error_missing_client_vpn'), 'vpn');
    if (($rule['client_vpn_ip'] ?? '') !== '' && ($config['remote_access'][$vpn]['vpn_network'] ?? '') !== '') {
        $serverNets = wireguard_validate_csv_cidrs((string)$config['remote_access'][$vpn]['vpn_network'], 'vpn_network');
        $clientIps = wireguard_validate_csv_cidrs((string)$rule['client_vpn_ip'], 'client_vpn_ip');
        if (count($clientIps) !== 1) wireguard_error(wireguard_t('wireguard_error_single_client_ip'), 'client_vpn_ip');
        $inside = false;
        foreach ($serverNets as $net) if (($net['version'] ?? null) === 4 && ($clientIps[0]['version'] ?? null) === 4 && wireguard_ipv4_contains($net, $clientIps[0])) $inside = true;
        if (!$inside && ($clientIps[0]['version'] ?? null) === 4) wireguard_error(wireguard_t('wireguard_error_client_ip_outside_vpn'), 'client_vpn_ip');
    }
}

// Valida reglas específicas de túneles WireGuard sede-a-sede.
// Validates specific rules for site-to-site WireGuard tunnels.
function wireguard_validate_site_to_site(array $rule, array $config, string $entryName): void {
    if (wireguard_is_enabled($rule)) wireguard_required($rule, ['interface','local_tunnel_ip','remote_tunnel_ip','local_networks','remote_networks','listen_port','remote_endpoint','private_key','remote_public_key']);
    wireguard_validate_interface_name((string)($rule['interface'] ?? ''));
    wireguard_validate_no_duplicate_interface($config, 'site_to_site', $entryName, (string)($rule['interface'] ?? ''));
    wireguard_validate_no_duplicate_port($config, 'site_to_site', $entryName, (string)($rule['listen_port'] ?? ''));
    $localTunnel = wireguard_validate_csv_cidrs((string)($rule['local_tunnel_ip'] ?? ''), 'local_tunnel_ip');
    $remoteTunnel = wireguard_validate_csv_cidrs((string)($rule['remote_tunnel_ip'] ?? ''), 'remote_tunnel_ip');
    if ($localTunnel && $remoteTunnel) wireguard_require_same_tunnel_network($localTunnel, $remoteTunnel);
    $localNets = wireguard_validate_csv_cidrs((string)($rule['local_networks'] ?? ''), 'local_networks');
    $remoteNets = wireguard_validate_csv_cidrs((string)($rule['remote_networks'] ?? ''), 'remote_networks');
    if ($localNets && $remoteNets) wireguard_check_network_overlap($localNets, $remoteNets, 'local_networks', 'remote_networks');
}

// Valida reglas específicas de servidores WireGuard de acceso remoto.
// Validates specific rules for remote-access WireGuard servers.
function wireguard_validate_remote_access(array $rule, array $config, string $entryName): void {
    if (wireguard_is_enabled($rule)) wireguard_required($rule, ['interface','server_vpn_ip','vpn_network','listen_port','internal_networks','private_key']);
    wireguard_validate_interface_name((string)($rule['interface'] ?? ''));
    wireguard_validate_no_duplicate_interface($config, 'remote_access', $entryName, (string)($rule['interface'] ?? ''));
    wireguard_validate_no_duplicate_port($config, 'remote_access', $entryName, (string)($rule['listen_port'] ?? ''));
    $serverIps = wireguard_validate_csv_cidrs((string)($rule['server_vpn_ip'] ?? ''), 'server_vpn_ip');
    $vpnNets = wireguard_validate_csv_cidrs((string)($rule['vpn_network'] ?? ''), 'vpn_network');
    if (count($serverIps) > 1) wireguard_error(wireguard_t('wireguard_error_single_server_ip'), 'server_vpn_ip');
    if ($serverIps && $vpnNets && ($serverIps[0]['version'] ?? null) === 4) {
        $inside = false; foreach ($vpnNets as $net) if (($net['version'] ?? null) === 4 && wireguard_ipv4_contains($net, $serverIps[0])) $inside = true;
        if (!$inside) wireguard_error(wireguard_t('wireguard_error_server_ip_outside_vpn'), 'server_vpn_ip');
    }
    $internal = wireguard_validate_csv_cidrs((string)($rule['internal_networks'] ?? ''), 'internal_networks');
    if ($vpnNets && $internal) wireguard_check_network_overlap($vpnNets, $internal, 'vpn_network', 'internal_networks');
}

// Valida reglas específicas de clientes WireGuard remotos.
// Validates specific rules for remote WireGuard clients.
function wireguard_validate_remote_client(array $rule, array $config, string $entryName): void {
    if (wireguard_is_enabled($rule)) wireguard_required($rule, ['vpn','client_vpn_ip','client_private_key','client_public_key','allowed_ips']);
    wireguard_validate_entry_name((string)($rule['vpn'] ?? ''), 'vpn');
    wireguard_validate_remote_client_vpn($config, $rule);
    wireguard_validate_unique_client_values($config, $entryName, $rule);
}

// Valida una regla recibida por endpoint según la tabla de origen.
// Validates a rule received by an endpoint according to its source table.
function wireguard_validate_rule(string $alias, array $rule, ?array $config = null, string $entryName = ''): array {
    $rule = wireguard_trim_rule($rule); $config = $config ?? wireguard_empty_config(); $section = wireguard_section_for_alias($alias) ?? '';
    $allowed = wireguard_structure_fields($alias);
    foreach (array_keys($rule) as $field) {
        if (!in_array($field, $allowed, true)) wireguard_error(wireguard_t('wireguard_error_unknown_field', ['field' => $field]), $field);
        if (is_string($rule[$field]) && strlen($rule[$field]) > 512) wireguard_error(wireguard_t('wireguard_error_field_too_long', ['field' => wireguard_label($field)]), $field);
    }
    wireguard_validate_bool_string((string)($rule['enabled'] ?? ''), 'enabled');
    if (isset($rule['listen_port'])) wireguard_validate_port((string)$rule['listen_port'], 'listen_port');
    if (isset($rule['keepalive'])) wireguard_validate_int_range((string)$rule['keepalive'], 'keepalive', 0, 65535, 'Keepalive');
    if (isset($rule['mtu'])) wireguard_validate_int_range((string)$rule['mtu'], 'mtu', 576, 9000, 'MTU');
    if (isset($rule['dns'])) wireguard_validate_csv_ips((string)$rule['dns'], 'dns');
    foreach (['private_key','remote_public_key','client_private_key','client_public_key'] as $field) if (isset($rule[$field])) wireguard_validate_key((string)$rule[$field], $field);
    if (isset($rule['remote_endpoint'])) wireguard_validate_endpoint((string)$rule['remote_endpoint'], 'remote_endpoint');
    if ($section === 'site_to_site') wireguard_validate_site_to_site($rule, $config, $entryName);
    if ($section === 'remote_access') wireguard_validate_remote_access($rule, $config, $entryName);
    if ($section === 'remote_clients') wireguard_validate_remote_client($rule, $config, $entryName);
    return $rule;
}

// Comprueba dependencias antes de borrar una entrada WireGuard.
// Checks dependencies before deleting a WireGuard entry.
function wireguard_can_delete(string $section, string $name, array $config): void {
    if ($section === 'remote_access') foreach (($config['remote_clients'] ?? []) as $client) if (($client['vpn'] ?? '') === $name) wireguard_error(wireguard_t('wireguard_error_delete_server_has_clients'), 'vpn');
}
?>
