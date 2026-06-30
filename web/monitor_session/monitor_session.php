<?php
/*
###############################################################################
  Página Monitor de sesiones conntrack
  Conntrack session monitor page

  Responsabilidades / Responsibilities:
    - Mostrar la tabla de sesiones conntrack generada para el usuario actual.
      Display the conntrack sessions table generated for the current user.
    - Permitir a usuarios admin refrescar el snapshot XML mediante backend Python.
      Allow admin users to refresh the XML snapshot through the Python backend.
    - Renderizar datos XML como HTML escapado, sin mostrar rutas internas.
      Render XML data as escaped HTML without exposing internal paths.

  Límites de seguridad / Security boundaries:
    - El usuario se toma siempre de la sesión PHP, nunca del cliente.
      The user is always taken from the PHP session, never from the client.
    - El POST de refresco exige admin + CSRF.
      The refresh POST requires admin + CSRF.
    - PHP no ejecuta conntrack directamente; sólo invoca el extractor fijo.
      PHP does not run conntrack directly; it only invokes the fixed extractor.
###############################################################################
*/
require_once __DIR__ . '/../common/security/auth.php';
require_once __DIR__ . '/../common/security/csrf.php';

/*
###############################################################################
  Carga el idioma activo después de validar/iniciar la sesión
  Loads the active language after validating/starting the session
###############################################################################
*/
function monitor_session_load_language(): array {
    // La sesión debe estar iniciada por require_login_page() o require_admin_json().
    // The session must be started by require_login_page() or require_admin_json().
    $language = $_SESSION['language'] ?? 'es';
    $langFile = __DIR__ . "/../lang/{$language}.php";
    if (!file_exists($langFile)) {
        $language = 'es';
        $langFile = __DIR__ . "/../lang/es.php";
    }

    return [$language, require $langFile];
}

/*
###############################################################################
  Devuelve un nombre de usuario seguro desde la sesión
  Returns a safe username from the session
###############################################################################
*/
function monitor_session_safe_username(): string {
    // El usuario procede de sesión autenticada, no de POST/GET.
    // The user comes from the authenticated session, not from POST/GET.
    $username = (string)($_SESSION['username'] ?? '');

    // Validación estricta para poder usar el valor en el nombre de fichero.
    // Strict validation so the value can be used in the filename.
    if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $username)) {
        return '';
    }

    return $username;
}

/*
###############################################################################
  Construye la ruta XML runtime del usuario actual
  Builds the runtime XML path for the current user
###############################################################################
*/
function monitor_session_xml_path(string $username): string {
    // Snapshot por usuario para evitar pisadas entre sesiones concurrentes.
    // Per-user snapshot to avoid overwrites between concurrent sessions.
    return "/var/www/config_running/monitor_session/{$username}_session_conntrack.xml";
}

/*
###############################################################################
  Lee texto de un nodo XML mediante XPath con valor por defecto
  Reads text from an XML node through XPath with a default value
###############################################################################
*/
function monitor_session_text(SimpleXMLElement $node, string $xpath, string $default = '-'): string {
    // XPath permite extraer campos de original/reply/independent sin parseos manuales.
    // XPath extracts original/reply/independent fields without manual parsing.
    $result = $node->xpath($xpath);
    if (!$result || !isset($result[0])) {
        return $default;
    }

    // Normaliza espacios y evita celdas vacías accidentales.
    // Normalizes whitespace and avoids accidental empty cells.
    $value = trim((string)$result[0]);
    return $value !== '' ? $value : $default;
}

/*
###############################################################################
  Convierte la presencia de un nodo XML en yes/no
  Converts the presence of an XML node into yes/no
###############################################################################
*/
function monitor_session_bool(SimpleXMLElement $node, string $xpath): string {
    // conntrack usa nodos vacíos como <assured/>; basta comprobar presencia.
    // conntrack uses empty nodes like <assured/>; checking presence is enough.
    $result = $node->xpath($xpath);
    return ($result && isset($result[0])) ? 'yes' : 'no';
}

/*
###############################################################################
  Renderiza la tabla HTML desde el XML conntrack del usuario
  Renders the HTML table from the user's conntrack XML
###############################################################################
*/
function monitor_session_render_table(string $xmlPath, array $L): string {
    // Si aún no hay snapshot, la página muestra una instrucción simple.
    // If there is no snapshot yet, the page shows a simple instruction.
    if ($xmlPath === '' || !file_exists($xmlPath)) {
        return '<p>' . htmlspecialchars($L['monitor_sessions_generate_hint'] ?? 'Reload sessions to generate the table.', ENT_QUOTES, 'UTF-8') . '</p>';
    }

    // El XML fue validado por Python; aquí se vuelve a leer con errores internos.
    // The XML was validated by Python; here it is read again with internal errors.
    libxml_use_internal_errors(true);
    $xml = simplexml_load_file($xmlPath);
    if ($xml === false) {
        return '<p>' . htmlspecialchars($L['monitor_sessions_xml_read_error'] ?? 'Could not read sessions XML.', ENT_QUOTES, 'UTF-8') . '</p>';
    }

    // Cada <flow> representa una entrada de conntrack.
    // Each <flow> represents one conntrack entry.
    $flows = $xml->flow;
    if (count($flows) === 0) {
        return '<p>' . htmlspecialchars($L['monitor_sessions_empty_snapshot'] ?? 'No sessions found.', ENT_QUOTES, 'UTF-8') . '</p>';
    }

    // Cabeceras visibles de la tabla final.
    // Visible headers for the final table.
    $headers = [
        $L['monitor_sessions_col_proto'] ?? 'PROTO',
        $L['monitor_sessions_col_state'] ?? 'STATE',
        $L['monitor_sessions_col_source'] ?? 'SOURCE',
        $L['monitor_sessions_col_source_port'] ?? 'SRC PORT',
        $L['monitor_sessions_col_destination'] ?? 'DESTINATION',
        $L['monitor_sessions_col_destination_port'] ?? 'DST PORT',
        $L['monitor_sessions_col_reply_source'] ?? 'REPLY SOURCE',
        $L['monitor_sessions_col_reply_source_port'] ?? 'REPLY SRC PORT',
        $L['monitor_sessions_col_reply_destination'] ?? 'REPLY DESTINATION',
        $L['monitor_sessions_col_reply_destination_port'] ?? 'REPLY DST PORT',
        $L['monitor_sessions_col_timeout'] ?? 'TIMEOUT',
        $L['monitor_sessions_col_assured'] ?? 'ASSURED',
        $L['monitor_sessions_col_id'] ?? 'ID',
    ];

    // Primera fila de cabecera: nombres de columnas.
    // First header row: column names.
    $html = '<table class="interfaz monitor-session-table"><thead><tr>';
    foreach ($headers as $header) {
        $html .= '<th>' . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . '</th>';
    }

    // Segunda fila de cabecera: filtros cliente por columna, sin tocar generic_table.js.
    // Second header row: client-side per-column filters, without touching generic_table.js.
    $html .= '</tr><tr class="generic-table-filter-row monitor-session-filter-row">';
    foreach ($headers as $index => $header) {
        $placeholder = htmlspecialchars($header, ENT_QUOTES, 'UTF-8');
        $filterPrefix = htmlspecialchars($L['monitor_sessions_filter_prefix'] ?? 'Filter', ENT_QUOTES, 'UTF-8');
        $html .= '<th class="generic-table-filter-cell"><input class="generic-table-filter-input monitor-session-filter-input" type="text" data-monitor-session-filter-column="' . $index . '" placeholder="' . $filterPrefix . ' ' . $placeholder . '" aria-label="' . $filterPrefix . ' ' . $placeholder . '"></th>';
    }
    $html .= '</tr></thead><tbody>';

    // Transforma cada flow XML en una fila HTML escapada.
    // Transforms each XML flow into an escaped HTML row.
    foreach ($flows as $flow) {
        $row = [
            monitor_session_text($flow, 'meta[@direction="original"]/layer4/@protoname'),
            monitor_session_text($flow, 'meta[@direction="independent"]/state'),
            monitor_session_text($flow, 'meta[@direction="original"]/layer3/src'),
            monitor_session_text($flow, 'meta[@direction="original"]/layer4/sport'),
            monitor_session_text($flow, 'meta[@direction="original"]/layer3/dst'),
            monitor_session_text($flow, 'meta[@direction="original"]/layer4/dport'),
            monitor_session_text($flow, 'meta[@direction="reply"]/layer3/src'),
            monitor_session_text($flow, 'meta[@direction="reply"]/layer4/sport'),
            monitor_session_text($flow, 'meta[@direction="reply"]/layer3/dst'),
            monitor_session_text($flow, 'meta[@direction="reply"]/layer4/dport'),
            monitor_session_text($flow, 'meta[@direction="independent"]/timeout'),
            monitor_session_bool($flow, 'meta[@direction="independent"]/assured'),
            monitor_session_text($flow, 'meta[@direction="independent"]/id'),
        ];

        $html .= '<tr>';
        foreach ($row as $value) {
            // Escapado de salida: el XML nunca se inyecta como HTML crudo.
            // Output escaping: XML is never injected as raw HTML.
            $html .= '<td>' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}

/*
###############################################################################
  Endpoint POST de refresco del snapshot conntrack
  POST endpoint for refreshing the conntrack snapshot
###############################################################################
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sólo admin puede lanzar el extractor privilegiado.
    // Only admin can run the privileged extractor.
    require_admin_json();
    csrf_validate_or_exit();
    [$language, $L] = monitor_session_load_language();
    header('Content-Type: application/json; charset=utf-8');

    // El endpoint sólo acepta la acción cerrada "refresh".
    // The endpoint only accepts the closed "refresh" action.
    $action = $_POST['action'] ?? '';
    if ($action !== 'refresh') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => $L['monitor_sessions_command_not_allowed'] ?? 'Command not allowed']);
        exit;
    }

    // Relee el usuario desde sesión para impedir suplantación desde cliente.
    // Re-read the user from session to prevent client-side impersonation.
    $username = monitor_session_safe_username();
    if ($username === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $L['monitor_sessions_invalid_user'] ?? 'Invalid user']);
        exit;
    }

    // Invoca sólo el extractor Python fijo; no ejecuta conntrack directamente.
    // Invoke only the fixed Python extractor; do not run conntrack directly.
    $script = '/var/www/backend/checks/check_sessions_contrack/extract_session_contrack_xml.py';
    $command = '/usr/bin/sudo /usr/bin/python3 ' . escapeshellarg($script) . ' --user ' . escapeshellarg($username) . ' 2>&1';
    $output = [];
    $code = 0;
    exec($command, $output, $code);

    // Si el extractor falla, se devuelve JSON de error sin modificar la tabla.
    // If the extractor fails, return JSON error without modifying the table.
    if ($code !== 0) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $L['monitor_sessions_reload_error'] ?? 'Could not reload sessions',
            'output' => implode("\n", $output),
        ]);
        exit;
    }

    // Devuelve la tabla ya renderizada para reemplazo directo en la WebGUI.
    // Return the already-rendered table for direct replacement in the WebGUI.
    echo json_encode([
        'success' => true,
        'message' => $L['monitor_sessions_reloaded'] ?? 'Sessions reloaded',
        'output' => implode("\n", $output),
        'table_html' => monitor_session_render_table(monitor_session_xml_path($username), $L),
    ]);
    exit;
}

require_login_page();
[$language, $L] = monitor_session_load_language();

// Estado de usuario actual usado para localizar su snapshot XML.
// Current user state used to locate their XML snapshot.
$username = monitor_session_safe_username();
$xmlPath = $username !== '' ? monitor_session_xml_path($username) : '';
$isAdmin = auth_current_role() === 'admin';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($L['menu_monitor_sessions']) ?></title>
</head>
<body>
  <section class="cajita-cabecera">
    <h2><?= htmlspecialchars($L['menu_monitor_sessions']) ?></h2>
    <p><?= htmlspecialchars($L['monitor_sessions_description']) ?></p>
    <?php if ($isAdmin): ?>
      <button type="button" class="boton-generic" id="monitor-session-refresh"><?= htmlspecialchars($L['monitor_sessions_reload'] ?? 'Reload sessions') ?></button>
    <?php else: ?>
      <p><?= htmlspecialchars($L['monitor_sessions_viewer_readonly'] ?? 'Viewer can read the generated table, but cannot reload sessions.') ?></p>
    <?php endif; ?>
  </section>

  <section class="cajita-cabecera" style="margin-top: 1rem;">
    <h3><?= htmlspecialchars($L['monitor_sessions_table_title'] ?? 'Conntrack table') ?></h3>
    <div id="monitor-session-table-wrapper">
      <?= monitor_session_render_table($xmlPath, $L) ?>
    </div>
  </section>

  <script>const LANG = <?= json_encode($L, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
  <script src="/monitor_session/monitor_session.js"></script>
</body>
</html>
