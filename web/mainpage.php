<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

// Datos de sesión
$username = $_SESSION['username'];
$role     = $_SESSION['role'];
$language = $_SESSION['language'] ?? 'es'; // Idioma por defecto si no está definido

// Ruta del archivo de idioma
$langFile = __DIR__ . "/lang/{$language}.php";

// Si no existe el idioma solicitado, cargar español como fallback
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/lang/es.php";
}

// Cargar el array de traducciones
$L = require $langFile;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($L['title']) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <!-- Encabezado superior -->
    <div class="header-top">
        <div class="header-left">
            <h1><?= htmlspecialchars($L['title']) ?></h1>
            <h2><?= htmlspecialchars($L['welcome']) ?>, <?= htmlspecialchars($username) ?>!</h2>
            <div class="user-info">
                <p><?= htmlspecialchars($L['role']) ?>: <?= htmlspecialchars($role) ?></p>
                <p><?= htmlspecialchars($L['language']) ?>: <?= htmlspecialchars($language) ?></p>
            </div>
        </div>
    </div>

    <!-- Menú horizontal debajo del encabezado -->
    <div class="top-menu">
        <a href="#"><?= htmlspecialchars($L['menu_home']) ?></a>
        <a href="#"><?= htmlspecialchars($L['menu_monitor']) ?></a>
        <a href="#"><?= htmlspecialchars($L['menu_users']) ?></a>
        <a href="logout.php"><?= htmlspecialchars($L['menu_logout']) ?></a>
    </div>

    <!-- Menú lateral vertical -->
    <div class="sidebar">
        <a href="#"><?= htmlspecialchars($L['sidebar_dashboard']) ?></a>
        <a href="#"><?= htmlspecialchars($L['sidebar_rules']) ?></a>
        <a href="#"><?= htmlspecialchars($L['sidebar_logs']) ?></a>
        <a href="#"><?= htmlspecialchars($L['sidebar_settings']) ?></a>
    </div>

    <!-- Contenido principal -->
    <div class="main-content">
        <p><?= htmlspecialchars($L['main_content']) ?></p>
    </div>

</body>
</html>
