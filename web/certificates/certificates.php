<?php
require_once __DIR__ . '/../common/security/auth.php';
require_login_page();

$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../lang/es.php";
}

$L = require $langFile;
$currentAlias = "certificates";
$path_get_table_structure = "/certificates/certificates_table/get_table_structure.php";
$path_get_table_content = "/certificates/certificates_table/get_table_content.php";
$path_get_forms_from_table = "/certificates/certificates_table/get_forms_from_table.php";
$path_get_update = "/certificates/certificates_table/get_update_certificate.php";
$path_get_delete = "/certificates/certificates_table/get_delete_certificates.php";
$path_download_certificates = "/certificates/certificates_table/get_download_certificate.php";
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
   <section class="cajita-cabecera">
     <h1><?= htmlspecialchars($L['sidebar_certificates']) ?></h1>
   </section>
</head>
<div id="upload_container"></div>
    <script>
     upload_certs(
      <?= json_encode($currentAlias) ?>,
      <?= json_encode($path_get_table_structure) ?>,
      <?= json_encode($path_get_table_content) ?>,
      <?= json_encode($path_get_forms_from_table) ?>,
      <?= json_encode($path_get_update) ?>,
      <?= json_encode($path_get_delete) ?>
    );
  </script>
<body>
 
  <div id="<?= htmlspecialchars($currentAlias) ?>_table"></div>
  <script>
    renderTable_certs(
      "<?= htmlspecialchars($currentAlias) ?>",
      "<?= htmlspecialchars($path_get_table_structure) ?>",
      "<?= htmlspecialchars($path_get_table_content) ?>",
      "<?= htmlspecialchars($path_get_forms_from_table) ?>",
      "<?= htmlspecialchars($path_get_update) ?>",
      "<?= htmlspecialchars($path_get_delete) ?>",
      "<?= htmlspecialchars($path_download_certificates) ?>"
    );
  </script>
</body>
</html>