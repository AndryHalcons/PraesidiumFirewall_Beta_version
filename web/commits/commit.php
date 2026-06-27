<?php
require_once __DIR__ . '/../common/security/auth.php';
require_login_page();

$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../lang/es.php";
}
$L = require $langFile;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
  <script>
    const LANG = <?= json_encode($L) ?>;
  </script>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($L['menu_commit']) ?></title>
  <link rel="stylesheet" href="../styles.css">
</head>
<body>
  <section class="cajita-cabecera">
    <h2><?= htmlspecialchars($L['menu_commit']) ?></h2>
  </section>
  <div id="commit-table"></div>
  <script src="/commits/commit.js"></script>

</div>


</body>
</html>
