<?php
require_once __DIR__ . '/../common/security/auth.php';
require_login_page();

$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../lang/es.php";
}
$L = require $langFile;
$currentAlias = "FORWARDING";
$path_get_table_structure = "/policies/common_policy_actions_nft/get_table_structure.php";
$path_get_table_content = "/policies/common_policy_actions_nft/get_table_content.php";
$path_get_forms_from_table = "/policies/common_policy_actions_nft/get_forms_from_table.php";
$path_get_update = "/policies/common_policy_actions_nft/get_update_policy.php";
$path_get_delete = "/policies/common_policy_actions_nft/get_delete_policy.php";
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
  <script>
    const LANG = <?= json_encode($L) ?>;
    const USERNAME = <?= json_encode($username) ?>;
    const aliasName = <?= json_encode("alias_service") ?>;
  </script>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="../styles.css">
</head>
<body>
  <section class="cajita-cabecera">
    <h1><?= htmlspecialchars($L['sidebar_nftables_forwarding']) ?></h1>
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


