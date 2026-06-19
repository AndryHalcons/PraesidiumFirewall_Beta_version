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
<section class="dashboard-shell" id="praesidium-dashboard">
  <div class="dashboard-header">
    <div>
      <h2><?= htmlspecialchars($L['sidebar_dashboard']) ?></h2>
      <p><?= htmlspecialchars($L['dashboard_subtitle'] ?? '') ?></p>
    </div>
    <span class="dashboard-refresh-pill" id="dashboard-refresh-status"><?= htmlspecialchars($L['dashboard_loading'] ?? 'Cargando...') ?></span>
  </div>

  <div class="dashboard-grid">
    <article class="dashboard-card dashboard-card-wide">
      <div class="dashboard-card-header">
        <h3><?= htmlspecialchars($L['dashboard_cpu_per_core'] ?? 'CPU por core') ?></h3>
        <span id="dashboard-cpu-average">--%</span>
      </div>
      <div class="dashboard-chart-box dashboard-chart-box-cpu"><canvas id="cpuChart"></canvas></div>
      <div class="dashboard-kpi-row" id="dashboard-cpu-list"></div>
    </article>

    <article class="dashboard-card">
      <div class="dashboard-card-header">
        <h3><?= htmlspecialchars($L['dashboard_ram_usage'] ?? 'RAM') ?></h3>
        <span id="dashboard-ram-used-percent">--%</span>
      </div>
      <div class="dashboard-chart-box dashboard-chart-box-ram"><canvas id="ramChart"></canvas></div>
      <div class="dashboard-metric-list">
        <div><span><?= htmlspecialchars($L['ram_total'] ?? 'Total') ?></span><strong id="ram-total">-- MB</strong></div>
        <div><span><?= htmlspecialchars($L['ram_used'] ?? 'Used') ?></span><strong id="ram-used">-- MB</strong></div>
        <div><span><?= htmlspecialchars($L['ram_free'] ?? 'Free') ?></span><strong id="ram-free">-- MB</strong></div>
        <div><span><?= htmlspecialchars($L['ram_cached'] ?? 'Cached') ?></span><strong id="ram-cached">-- MB</strong></div>
      </div>
    </article>

    <article class="dashboard-card dashboard-card-wide">
      <div class="dashboard-card-header">
        <h3><?= htmlspecialchars($L['dashboard_bandwidth_by_interface'] ?? 'Ancho de banda por interfaz') ?></h3>
        <span><?= htmlspecialchars($L['dashboard_refresh_interval'] ?? 'Actualización cada 5s') ?></span>
      </div>
      <div class="dashboard-table-wrap">
        <table class="dashboard-table" id="bandwidth-table">
          <thead>
            <tr>
              <th><?= htmlspecialchars($L['dashboard_interface'] ?? 'Interfaz') ?></th>
              <th><?= htmlspecialchars($L['dashboard_rx_rate'] ?? 'Entrada') ?></th>
              <th><?= htmlspecialchars($L['dashboard_tx_rate'] ?? 'Salida') ?></th>
              <th><?= htmlspecialchars($L['dashboard_rx_total'] ?? 'Recibido') ?></th>
              <th><?= htmlspecialchars($L['dashboard_tx_total'] ?? 'Enviado') ?></th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="5"><?= htmlspecialchars($L['dashboard_loading'] ?? 'Cargando...') ?></td></tr>
          </tbody>
        </table>
      </div>
    </article>
  </div>
</section>
<script>
  window.PRAESIDIUM_DASHBOARD_I18N = {
    loading: <?= json_encode($L['dashboard_loading'] ?? 'Cargando...') ?>,
    updated: <?= json_encode($L['dashboard_updated'] ?? 'Actualizado') ?>,
    error: <?= json_encode($L['dashboard_error'] ?? 'Error al cargar métricas') ?>,
    noInterfaces: <?= json_encode($L['dashboard_no_interfaces'] ?? 'Sin interfaces') ?>,
    coreLabel: <?= json_encode($L['dashboard_core_label'] ?? 'Core') ?>,
    cpuPercentLabel: <?= json_encode($L['dashboard_cpu_percent_label'] ?? 'CPU %') ?>,
    ramUsedLabel: <?= json_encode($L['dashboard_ram_used_label'] ?? 'Usada') ?>,
    ramFreeLabel: <?= json_encode($L['dashboard_ram_free_label'] ?? 'Libre') ?>,
    ramCachedLabel: <?= json_encode($L['dashboard_ram_cached_label'] ?? 'Reservada') ?>
  };
</script>
