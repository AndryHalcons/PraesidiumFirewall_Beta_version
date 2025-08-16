<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../lang/es.php";
}
$L = require $langFile;
?>

<h2><?= htmlspecialchars($L['sidebar_dashboard']) ?></h2>
<!-- CPU WIDGET -->
<div style="max-width: 500px; margin-top: 20px;">
    <h2>CPU</h2>
    <canvas id="cpuChart" width="400" height="200"></canvas>
</div>
<div id="cpu-summary" style="margin-top: 10px; font-weight: bold;">
    <p><?= $L['cpu_total'] ?>: <span id="cpu-total">--</span>%</p>
    <p><?= $L['cpu_average'] ?>: <span id="cpu-average">--</span>%</p>

</div>
<!-- RAM WIDGET -->
<div style="max-width: 500px; margin-top: 40px;">
    <h2>RAM</h2>
    <canvas id="ramChart" width="400" height="200"></canvas>
</div>
<div id="ram-summary" style="margin-top: 10px; font-weight: bold;">
    <p><?= $L['ram_total'] ?>: <span id="ram-total">--</span> MB</p>
    <p><?= $L['ram_used'] ?>: <span id="ram-used">--</span> MB</p>
    <p><?= $L['ram_free'] ?>: <span id="ram-free">--</span> MB</p>
    <p><?= $L['ram_cached'] ?>: <span id="ram-cached">--</span> MB</p>
</div>


<!-- Script específico del dashboard -->
<script src="/dashboard/dashboard.js"></script>
