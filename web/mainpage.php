<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'];
$role     = $_SESSION['role'];
$language = $_SESSION['language'] ?? 'es';

$langFile = __DIR__ . "/lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/lang/es.php";
}
$L = require $langFile;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($L['title']) ?></title>
    <link rel="stylesheet" href="styles.css">
    <script src="/libraries/chart.umd.js"></script>


</head>
<body>

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

    <div class="top-menu">
        <a href="#" data-page="home.php"><?= htmlspecialchars($L['menu_home']) ?></a>
        <a href="#" data-page="interfaces/interfaces.php"><?= htmlspecialchars($L['menu_interfaces']) ?></a>
        <a href="#" data-page="monitor.php"><?= htmlspecialchars($L['menu_monitor']) ?></a>
        <a href="#" data-page="users.php"><?= htmlspecialchars($L['menu_users']) ?></a>
        <a href="logout.php"><?= htmlspecialchars($L['menu_logout']) ?></a>
    </div>

    <div class="sidebar">
        <a href="#" data-page="dashboard/dashboard.php"><?= htmlspecialchars($L['sidebar_dashboard']) ?></a>
        <a href="#" data-page="policies/policies.php"><?= htmlspecialchars($L['sidebar_rules']) ?></a>
        <a href="#" data-page="logs.php"><?= htmlspecialchars($L['sidebar_logs']) ?></a>
        <a href="#" data-page="settings.php"><?= htmlspecialchars($L['sidebar_settings']) ?></a>
    </div>

    <div class="main-content" id="main-content">
        <p><?= htmlspecialchars($L['main_content']) ?></p>
    </div>

    <!-- JavaScript externo -->
    <script src="javascript.js"></script>
</body>
</html>
