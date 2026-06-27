<?php
require_once __DIR__ . '/../common/security/auth.php';
require_login_page();

$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../lang/es.php";
}
$L = require $langFile;
$currentAlias = "wifis";
$path_get_table_structure = "/interfaces/interfaces_table/get_table_structure.php";
$path_get_table_content = "/interfaces/interfaces_table/get_table_content.php";
$path_get_forms_from_table = "/interfaces/interfaces_table/get_forms_from_table.php";
$path_get_update = "/interfaces/interfaces_table/get_update_interface.php";
$path_get_delete = "/interfaces/interfaces_table/get_delete_interface.php";
// check interfaces scripts add/quit new/old physical interfaces
$script5 = '/usr/bin/python3 /var/www/backend/checks/check_interfaces/main_interfaces_check.py';
shell_exec("sudo $script5 2>&1");
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
  <script>
    const LANG = <?= json_encode($L) ?>;
    const USERNAME = <?= json_encode($username) ?>;
  </script>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="../styles.css">
</head>
<body>
  <section class="cajita-cabecera">
    <h1><?= htmlspecialchars($L['sidebar_wifis']) ?></h1>
  </section>
  <div id="<?= htmlspecialchars($currentAlias) ?>_table"></div>
  <script>
    renderTableGeneric(
      "<?= htmlspecialchars($currentAlias) ?>",
      "<?= htmlspecialchars($path_get_table_structure) ?>",
      "<?= htmlspecialchars($path_get_table_content) ?>",
      "<?= htmlspecialchars($path_get_forms_from_table) ?>",
      "<?= htmlspecialchars($path_get_update) ?>",
      "<?= htmlspecialchars($path_get_delete) ?>"
    );
  </script>
</body>
</html>