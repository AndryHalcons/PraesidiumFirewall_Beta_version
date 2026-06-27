<?php
require_once __DIR__ . '/../../common/security/auth.php';
/*
#############################################################################
   Parcial de la pantalla Servicios
   Services screen partial

   Publica las rutas de endpoints que consume renderTableGeneric y añade el
   botón específico para refrescar el estado runtime sin modificar el renderer
   genérico compartido del firewall.

   It publishes the endpoint paths consumed by renderTableGeneric and adds the
   Services-specific runtime refresh button without modifying the firewall
   shared generic renderer.
#############################################################################
*/
require_login_page();


/*
#############################################################################
   Carga traducciones de la sesión con fallback a español
   Loads session translations with Spanish fallback
#############################################################################
*/
$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../../lang/es.php";
}
$L = require $langFile;
/*
#############################################################################
   Rutas de endpoints usadas por la tabla genérica de Servicios
   Endpoint paths used by the generic Services table
#############################################################################
*/
$currentAlias = "services";
$path_get_table_structure = "/system/services/services_table/get_table_structure.php";
$path_get_table_content = "/system/services/services_table/get_table_content.php";
$path_get_forms_from_table = "/system/services/services_table/get_forms_from_table.php";
$path_get_update = "/system/services/services_table/get_update.php";
$path_get_delete = "/system/services/services_table/get_delete.php";
$path_get_runtime_status = "/system/services/services_table/get_runtime_status.php";
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
  <script>
    /*
    ###########################################################################
       Configuración expuesta al JS específico de Servicios
       Configuration exposed to the Services-specific JavaScript
    ###########################################################################
    */
    window.LANG = <?= json_encode($L, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.PraesidiumServicesConfig = {
      currentAlias: <?= json_encode($currentAlias) ?>,
      endpoints: {
        structure: <?= json_encode($path_get_table_structure) ?>,
        content: <?= json_encode($path_get_table_content) ?>,
        forms: <?= json_encode($path_get_forms_from_table) ?>,
        update: <?= json_encode($path_get_update) ?>,
        delete: <?= json_encode($path_get_delete) ?>,
        runtime: <?= json_encode($path_get_runtime_status) ?>
      }
    };
  </script>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="/styles.css">
</head>
<body>
  <section class="cajita-cabecera">
    <h1><?= htmlspecialchars($L['sidebar_services']) ?></h1>
  </section>
  <div class="services-toolbar">
    <button type="button" class="boton-generic" id="services-refresh-runtime">
      <?= htmlspecialchars($L['services_refresh_runtime_status'] ?? 'Actualizar estado') ?>
    </button>
    <span id="services-runtime-message" class="services-runtime-message"></span>
  </div>

  <div id="<?= htmlspecialchars($currentAlias) ?>_table"></div>

  <script src="/system/services/services_table/services.js?v=<?= filemtime(__DIR__ . "/services_table/services.js") ?>"></script>
  <script>
    /*
    ###########################################################################
       Espera defensiva porque el cargador parcial puede ejecutar inline JS
       antes de que termine de cargar services.js.

       Defensive wait because the partial loader can execute inline JS before
       services.js has finished loading.
    ###########################################################################
    */
    (function waitForServicesRenderer(attempt) {
      if (window.PraesidiumServices && typeof window.PraesidiumServices.render === 'function') {
        window.PraesidiumServices.render(window.PraesidiumServicesConfig);
        return;
      }
      if (attempt < 20) {
        window.setTimeout(function () { waitForServicesRenderer(attempt + 1); }, 50);
      }
    })(0);
  </script>
</body>
</html>
