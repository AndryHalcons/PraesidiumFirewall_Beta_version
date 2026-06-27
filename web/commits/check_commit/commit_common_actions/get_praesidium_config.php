<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/security/auth.php';

/*
################################################################################
################################################################################
##############################   PRAESIDIUM CONFIG VIEWER   ####################
################################################################################
################################################################################

Español:
Endpoint de solo lectura para mostrar en el comparador de Commit una vista
resumida y segura de la configuración candidate/running.

English:
Read-only endpoint used by the Commit comparator to display a summarized and
safe view of candidate/running configuration.

Notas de seguridad / Security notes:
- No devuelve claves privadas, certificados, locks ni auditoría.
- Does not return private keys, certificates, lock files or audit logs.
- Solo admite modos explícitos: candidate o running.
- Only explicit modes are allowed: candidate or running.
*/

require_login_json();

header('Content-Type: text/plain; charset=utf-8');

$mode = $_POST['mode'] ?? $_GET['mode'] ?? '';

$roots = [
    'candidate' => '/var/www/config',
    'running' => '/var/www/config_running',
];

if (!isset($roots[$mode])) {
    http_response_code(400);
    echo "Modo inválido. Use candidate o running.";
    exit;
}

$root = realpath($roots[$mode]);
if ($root === false || !is_dir($root)) {
    http_response_code(404);
    echo "Directorio de configuración no encontrado.";
    exit;
}

$allowedExtensions = ['json', 'yml', 'yaml', 'conf', 'txt'];
$blockedExtensions = ['key', 'pem', 'crt', 'cer', 'csr', 'req', 'pfx', 'p12', 'pkcs12', 'der', 'jks', 'srl', 'lock', 'jsonl'];
$blockedPathFragments = [
    '/commit_history/',
    '/security_audit/',
    '/certs/',
    '/conf.d/certs/',
];

// Filtra archivos del comparador para mostrar solo configuración segura.
// Filters comparator files so only safe configuration is displayed.
function praesidium_config_is_safe_file(string $filePath, string $root, array $allowedExtensions, array $blockedExtensions, array $blockedPathFragments): bool
{
    $realPath = realpath($filePath);
    if ($realPath === false || !is_file($realPath)) {
        return false;
    }

    if (strpos($realPath, $root . DIRECTORY_SEPARATOR) !== 0) {
        return false;
    }

    $relative = substr($realPath, strlen($root));
    $relativeForCheck = str_replace('\\', '/', $relative);

    foreach ($blockedPathFragments as $fragment) {
        if (strpos($relativeForCheck, $fragment) !== false) {
            return false;
        }
    }

    $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
    if (in_array($extension, $blockedExtensions, true)) {
        return false;
    }

    return in_array($extension, $allowedExtensions, true);
}


// Redacta secretos dentro de JSON antes de mostrarlos en el comparador.
// Redacts secrets inside JSON before showing them in the comparator.
function praesidium_config_redact_json_secrets($value)
{
    if (is_array($value)) {
        $redacted = [];
        foreach ($value as $key => $item) {
            $keyString = strtolower((string)$key);
            if (in_array($keyString, ['private_key', 'privatekey', 'key.private'], true)) {
                $redacted[$key] = '********';
            } else {
                $redacted[$key] = praesidium_config_redact_json_secrets($item);
            }
        }
        return $redacted;
    }
    return $value;
}

// Redacta secretos en texto plano, especialmente configs WireGuard .conf.
// Redacts secrets in plain text, especially WireGuard .conf configs.
function praesidium_config_redact_text_secrets(string $content): string
{
    return preg_replace('/^(\s*(?:PrivateKey|private_key|client_private_key|key\.private)\s*=\s*).+$/mi', '$1********', $content);
}

// Formatea un archivo seguro del comparador con JSON bonito y secretos ocultos.
// Formats a safe comparator file with pretty JSON and hidden secrets.
function praesidium_config_format_file(string $filePath, string $root): string
{
    $relative = ltrim(substr(realpath($filePath), strlen($root)), DIRECTORY_SEPARATOR);
    $content = file_get_contents($filePath);

    if ($content === false) {
        return "### {$relative}\nERROR: no se pudo leer el archivo\n";
    }

    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if ($extension === 'json') {
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $decoded = praesidium_config_redact_json_secrets($decoded);
            $content = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    } else {
        $content = praesidium_config_redact_text_secrets($content);
    }

    return "### {$relative}\n{$content}\n";
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$files = [];
foreach ($iterator as $fileInfo) {
    $path = $fileInfo->getPathname();
    if (praesidium_config_is_safe_file($path, $root, $allowedExtensions, $blockedExtensions, $blockedPathFragments)) {
        $files[] = $path;
    }
}

sort($files, SORT_NATURAL | SORT_FLAG_CASE);

if (empty($files)) {
    echo "No hay archivos de configuración seguros para mostrar en {$mode}.";
    exit;
}

echo "# Praesidium {$mode} config\n\n";
foreach ($files as $filePath) {
    echo praesidium_config_format_file($filePath, $root);
    echo "\n";
}
