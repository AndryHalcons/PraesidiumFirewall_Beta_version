<?php
require_once __DIR__ . '/../common/security/auth.php';
require_login_page();

$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../lang/es.php";
}
$L = require $langFile;
$currentAlias = "table_users";
$path_get_table_structure = "/users/users_table/get_table_structure.php";
$path_get_table_content = "/users/users_table/get_table_content.php";
$path_get_forms_from_table = "/users/users_table/get_forms_from_table.php";
$path_get_update = "/users/users_table/get_update_user.php";
$path_get_delete = "/users/users_table/get_delete_user.php";

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
  <script>
    window.LANG = <?= json_encode($L) ?>;
    window.USERNAME = <?= json_encode($username) ?>;
    const aliasName = <?= json_encode("alias_service") ?>;
  </script>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="../styles.css">
</head>
<body>
  <section class="cajita-cabecera">
    <h1><?= htmlspecialchars($L['menu_users']) ?></h1>
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
