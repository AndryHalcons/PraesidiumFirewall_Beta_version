<?php
require_once __DIR__ . '/../../common/security/session.php';
// Página hija WireGuard: configuración de servidores y clientes de acceso remoto.
// WireGuard child page: remote-access server and client configuration.
// Fase 1: abrir sesión y bloquear acceso no autenticado antes de renderizar HTML.
// Phase 1: open the session and block unauthenticated access before rendering HTML.
praesidium_session_start();
if (!isset($_SESSION['username'])) { exit(htmlspecialchars($L['unauthorized'] ?? 'unauthorized', ENT_QUOTES, 'UTF-8')); }
// Fase 2: cargar el idioma activo para que todo texto visible salga de web/lang.
// Phase 2: load the active language so every visible text comes from web/lang.
$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../../lang/{$language}.php";
if (!file_exists($langFile)) { $langFile = __DIR__ . "/../../lang/es.php"; }
$L = require $langFile;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head><meta charset="UTF-8"></head>
<body>
  <!-- Fase 3: pintar contenido visual usando solo claves de idioma. -->
  <!-- Phase 3: render visual content using only language keys. -->
  <section class="cajita-cabecera">
    <h1><?= htmlspecialchars($L['wireguard_remote_access'] ?? 'wireguard_remote_access') ?></h1>
    <p><?= htmlspecialchars($L['wireguard_remote_access_long_desc'] ?? ($L['wireguard_remote_access_desc'] ?? 'wireguard_remote_access_desc')) ?></p>
  </section>
  <div class="wireguard-help-box">
    <?= htmlspecialchars($L['wireguard_remote_access_form_help'] ?? 'wireguard_remote_access_form_help') ?>
  </div>

  <section class="cajita-cabecera">
    <h2><?= htmlspecialchars($L['wireguard_remote_servers'] ?? 'wireguard_remote_servers') ?></h2>
    <p><?= htmlspecialchars($L['wireguard_remote_servers_help'] ?? 'wireguard_remote_servers_help') ?></p>
  </section>
  <div id="wireguard_remote_access_table"></div>

  <section class="cajita-cabecera">
    <h2><?= htmlspecialchars($L['wireguard_remote_clients'] ?? 'wireguard_remote_clients') ?></h2>
    <p><?= htmlspecialchars($L['wireguard_remote_clients_help'] ?? 'wireguard_remote_clients_help') ?></p>
  </section>
  <div id="wireguard_remote_clients_table"></div>

  <script>
    // Fase 4: publicar LANG global antes de llamar al render genérico.
    // Phase 4: publish global LANG before calling the generic renderer.
    window.LANG = <?= json_encode($L) ?>;
    renderTableGeneric(
      "wireguard_remote_access",
      "/interfaces/wireguard/remote_access_table/get_table_structure.php",
      "/interfaces/wireguard/remote_access_table/get_table_content.php",
      "/interfaces/wireguard/remote_access_table/get_forms_from_table.php",
      "/interfaces/wireguard/remote_access_table/get_update.php",
      "/interfaces/wireguard/remote_access_table/get_delete.php"
    );
    renderTableGeneric(
      "wireguard_remote_clients",
      "/interfaces/wireguard/remote_clients_table/get_table_structure.php",
      "/interfaces/wireguard/remote_clients_table/get_table_content.php",
      "/interfaces/wireguard/remote_clients_table/get_forms_from_table.php",
      "/interfaces/wireguard/remote_clients_table/get_update.php",
      "/interfaces/wireguard/remote_clients_table/get_delete.php"
    );
  </script>
</body>
</html>
