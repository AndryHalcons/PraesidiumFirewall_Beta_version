<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../lang/es.php";
}
$L = require $langFile;
?>
<link rel="stylesheet" href="../styles.css">

<!-- Contenedor que usará el JS -->
<div id="users-container-placeholder"></div>
<script>
    const LANG = <?php echo json_encode($L, JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="/users/table_users/table_users.js"></script>

