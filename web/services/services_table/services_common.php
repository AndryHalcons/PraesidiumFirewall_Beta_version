<?php
/*
#############################################################################
   Utilidades comunes de la sección Servicios
   Common utilities for the Services section

   Centraliza la lista fija de servicios gestionables, lectura/escritura del
   candidate JSON y consulta segura del estado runtime. Los servicios systemd
   usan systemctl; bpfilter y forwarding usan checkers específicos porque no
   son unidades systemd normales.

   It centralizes the fixed managed-service list, candidate JSON read/write and
   safe runtime status checks. systemd services use systemctl; bpfilter and
   forwarding use dedicated checkers because they are not normal systemd units.
#############################################################################
*/

/*
#############################################################################
   Carga las traducciones activas para endpoints JSON de Servicios
   Loads active translations for Services JSON endpoints
#############################################################################
*/
function services_lang(): array {
    $language = $_SESSION['language'] ?? 'es';
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if ($documentRoot === '') {
        $documentRoot = dirname(__DIR__, 2);
    }
    $langFile = $documentRoot . "/lang/{$language}.php";
    if (!file_exists($langFile)) {
        $langFile = $documentRoot . '/lang/es.php';
    }
    $loaded = require $langFile;
    return is_array($loaded) ? $loaded : [];
}

/*
#############################################################################
   Devuelve una traducción de Servicios con fallback controlado
   Returns a Services translation with a controlled fallback
#############################################################################
*/
function services_t(string $key, string $fallback): string {
    $lang = services_lang();
    return (string)($lang[$key] ?? $fallback);
}

/*
#############################################################################
   Devuelve la lista fija de servicios que Praesidium muestra en la pantalla
   Returns the fixed list of services shown by Praesidium in the screen
#############################################################################
*/
function services_catalog(): array {
    /*
    ###########################################################################
       Cada entrada del catálogo declara la clave estable, el mecanismo de
       comprobación runtime, la etiqueta visible y si el usuario puede cambiar
       desired_enabled desde la Web UI.

       Each catalog entry declares the stable key, runtime checker mechanism,
       visible label and whether the user may change desired_enabled from the
       Web UI.
    ###########################################################################
    */
    return [
        // Servicios systemd configurables por commit/apply.
        // Systemd services configurable through commit/apply.
        'dnsmasq' => [
            'service_name' => 'dnsmasq',
            'unit' => 'dnsmasq',
            'checker' => 'systemctl',
            'display_name' => 'dnsmasq',
            'configurable' => 'true',
            'default_enabled' => 'true'
        ],
        'squid' => [
            'service_name' => 'squid',
            'unit' => 'squid',
            'checker' => 'systemctl',
            'display_name' => 'squid',
            'configurable' => 'true',
            'default_enabled' => 'true'
        ],
        'nftables' => [
            'service_name' => 'nftables',
            'unit' => 'nftables',
            'checker' => 'systemctl',
            'display_name' => 'nftables',
            'configurable' => 'true',
            'default_enabled' => 'true'
        ],
        'rsyslog' => [
            'service_name' => 'rsyslog',
            'unit' => 'rsyslog',
            'checker' => 'systemctl',
            'display_name' => 'rsyslog',
            'configurable' => 'true',
            'default_enabled' => 'true'
        ],
        // Servicios críticos de plataforma: solo monitorización para no romper la UI o el host.
        // Critical platform services: monitoring only to avoid breaking the UI or host.
        'apache2' => [
            'service_name' => 'apache2',
            'unit' => 'apache2',
            'checker' => 'systemctl',
            'display_name' => 'apache2',
            'configurable' => 'false',
            'default_enabled' => 'true'
        ],
        'docker' => [
            'service_name' => 'docker',
            'unit' => 'docker',
            'checker' => 'systemctl',
            'display_name' => 'docker',
            'configurable' => 'false',
            'default_enabled' => 'true'
        ],
        // bpfilter requiere checker/applier especial porque no es una unidad systemd.
        // bpfilter requires a special checker/applier because it is not a systemd unit.
        'bpfilter' => [
            'service_name' => 'bpfilter',
            'unit' => 'bpfilter',
            'checker' => 'bpfilter_daemon',
            'display_name' => 'bpfilter',
            'configurable' => 'true',
            'default_enabled' => 'false'
        ],
        // Dependencias de red/administración visibles para diagnóstico, no configurables.
        // Network/administration dependencies visible for diagnostics, not configurable.
        'wg-quick@wgpf1' => [
            'service_name' => 'wg-quick@wgpf1',
            'unit' => 'wg-quick@wgpf1',
            'checker' => 'systemctl',
            'display_name' => 'WireGuard',
            'configurable' => 'false',
            'default_enabled' => 'true'
        ],
        'systemd-networkd' => [
            'service_name' => 'systemd-networkd',
            'unit' => 'systemd-networkd',
            'checker' => 'systemctl',
            'display_name' => 'systemd-networkd',
            'configurable' => 'false',
            'default_enabled' => 'true'
        ],
        'systemd-resolved' => [
            'service_name' => 'systemd-resolved',
            'unit' => 'systemd-resolved',
            'checker' => 'systemctl',
            'display_name' => 'systemd-resolved',
            'configurable' => 'false',
            'default_enabled' => 'true'
        ],
        'ssh' => [
            'service_name' => 'ssh',
            'unit' => 'ssh',
            'checker' => 'systemctl',
            'display_name' => 'ssh',
            'configurable' => 'false',
            'default_enabled' => 'true'
        ],
        // Forwarding del kernel: controles sysctl configurables desde Servicios.
        // Kernel forwarding: sysctl controls configurable from Services.
        'forwarding_ipv4' => [
            'service_name' => 'forwarding_ipv4',
            'unit' => 'net.ipv4.ip_forward',
            'checker' => 'sysctl',
            'display_name' => 'Forwarding IPv4',
            'configurable' => 'true',
            'default_enabled' => 'true'
        ],
        'forwarding_ipv6' => [
            'service_name' => 'forwarding_ipv6',
            'unit' => 'net.ipv6.conf.all.forwarding',
            'checker' => 'sysctl',
            'display_name' => 'Forwarding IPv6',
            'configurable' => 'true',
            'default_enabled' => 'true'
        ]
    ];
}

/*
#############################################################################
   Devuelve la ruta del candidate JSON de servicios
   Returns the services candidate JSON path
#############################################################################
*/
function services_candidate_path(): string {
    return '/var/www/config/services.json';
}

/*
#############################################################################
   Carga el candidate JSON y crea valores por defecto si faltan servicios
   Loads candidate JSON and creates default values when services are missing
#############################################################################
*/
function services_load_candidate(): array {
    $path = services_candidate_path();
    $data = ['services' => []];

    if (file_exists($path)) {
        $raw = file_get_contents($path);
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $data = $decoded;
        }
    }

    if (!isset($data['services']) || !is_array($data['services'])) {
        $data['services'] = [];
    }

    foreach (services_catalog() as $name => $definition) {
        if (!isset($data['services'][$name]) || !is_array($data['services'][$name])) {
            $data['services'][$name] = ['desired_enabled' => $definition['default_enabled']];
        }
        $value = (string)($data['services'][$name]['desired_enabled'] ?? $definition['default_enabled']);
        $data['services'][$name]['desired_enabled'] = $value === 'true' ? 'true' : 'false';
    }

    return $data;
}

/*
#############################################################################
   Consulta systemctl para saber si una unidad está activa ahora mismo
   Queries systemctl to know whether a unit is active right now
#############################################################################
*/
function services_systemctl_runtime_status(string $unit): string {
    $safeUnit = escapeshellarg($unit);
    $output = [];
    $code = 0;
    exec("/usr/bin/systemctl is-active {$safeUnit} 2>/dev/null", $output, $code);
    $status = trim((string)($output[0] ?? ''));

    if ($status === '') {
        return 'unknown';
    }

    return $status;
}

/*
#############################################################################
   Comprueba bpfilter como daemon: binarios, proceso y socket runtime
   Checks bpfilter as a daemon: binaries, process and runtime socket
#############################################################################
*/
function services_bpfilter_runtime_status(): string {
    // Fase 1: confirmar que los binarios instalados por Praesidium existen.
    // Phase 1: confirm the binaries installed by Praesidium exist.
    if (!is_executable('/usr/local/bin/bpfilter') || !is_executable('/usr/local/bin/bfcli')) {
        return 'not-found';
    }

    // Fase 2: confirmar que el daemon está vivo por nombre exacto de proceso.
    // Phase 2: confirm the daemon is alive by exact process name.
    $output = [];
    $code = 0;
    exec('/usr/bin/pgrep -x bpfilter 2>/dev/null', $output, $code);
    if ($code !== 0 || empty($output)) {
        return 'inactive';
    }

    // Fase 3: confirmar que el socket CLI existe; es lo que usa bfcli.
    // Phase 3: confirm the CLI socket exists; this is what bfcli uses.
    if (!file_exists('/run/bpfilter/daemon.sock') || filetype('/run/bpfilter/daemon.sock') !== 'socket') {
        return 'inactive';
    }

    return 'active';
}

/*
#############################################################################
   Consulta sysctl para saber si una opción forwarding está activa
   Queries sysctl to know whether a forwarding option is active
#############################################################################
*/
function services_sysctl_runtime_status(string $key): string {
    // Lista blanca defensiva: evita consultar claves sysctl arbitrarias desde catálogo/URL.
    // Defensive allowlist: avoids querying arbitrary sysctl keys from catalog/URL.
    $allowed = ['net.ipv4.ip_forward', 'net.ipv6.conf.all.forwarding'];
    if (!in_array($key, $allowed, true)) {
        return 'unknown';
    }

    $safeKey = escapeshellarg($key);
    $output = [];
    $code = 0;
    exec("/usr/sbin/sysctl -n {$safeKey} 2>/dev/null", $output, $code);
    if ($code !== 0 || !isset($output[0])) {
        return 'unknown';
    }

    // Normaliza sysctl 1/0 al mismo contrato active/inactive que systemctl.
    // Normalizes sysctl 1/0 to the same active/inactive contract as systemctl.
    $value = trim((string)$output[0]);
    if ($value === '1') {
        return 'active';
    }
    if ($value === '0') {
        return 'inactive';
    }
    return 'unknown';
}

/*
#############################################################################
   Despacha la consulta runtime según el tipo de servicio
   Dispatches the runtime query according to the service type
#############################################################################
*/
function services_runtime_status(array $definition): string {
    // Selecciona el checker runtime declarado por cada entrada del catálogo.
    // Selects the runtime checker declared by each catalog entry.
    $checker = (string)($definition['checker'] ?? 'systemctl');

    if ($checker === 'bpfilter_daemon') {
        return services_bpfilter_runtime_status();
    }

    if ($checker === 'sysctl') {
        return services_sysctl_runtime_status((string)$definition['unit']);
    }

    return services_systemctl_runtime_status((string)$definition['unit']);
}


/*
#############################################################################
   Convierte el estado runtime interno en una etiqueta visible de tabla
   Converts the internal runtime status into a visible table label
#############################################################################
*/
function services_runtime_label(string $status): string {
    $normalised = strtolower($status);
    if ($normalised === 'active') {
        return 'ON';
    }
    if ($normalised === 'unknown' || $normalised === 'not-found') {
        return 'UNKNOWN';
    }
    return 'OFF';
}

/*
#############################################################################
   Convierte booleanos de catálogo en etiquetas visibles
   Converts catalog booleans into visible labels
#############################################################################
*/
function services_bool_label(string $value): string {
    if ($value === 'true') {
        return services_t('services_yes', 'Yes');
    }
    return services_t('services_no', 'No');
}

/*
#############################################################################
   Construye las filas de tabla mezclando candidate y runtime status
   Builds table rows by combining candidate and runtime status
#############################################################################
*/
function services_build_rows(): array {
    $candidate = services_load_candidate();
    $rows = [];

    foreach (services_catalog() as $name => $definition) {
        $runtimeStatus = services_runtime_status($definition);
        $rows[] = [
            'service_name' => $definition['service_name'],
            'display_name' => $definition['display_name'],
            'runtime_status' => services_runtime_label($runtimeStatus),
            'desired_enabled' => $candidate['services'][$name]['desired_enabled'] ?? $definition['default_enabled'],
            'configurable' => services_bool_label($definition['configurable'])
        ];
    }

    return $rows;
}
