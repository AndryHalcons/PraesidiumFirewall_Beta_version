<?php
session_start();
if (!isset($_SESSION['username'])) { exit("No autorizado"); }
$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../../lang/{$language}.php";
if (!file_exists($langFile)) { $langFile = __DIR__ . "/../../lang/es.php"; }
$L = require $langFile;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head><meta charset="UTF-8"></head>
<body>
  <section class="wireguard-hero">
    <div>
      <span class="wireguard-kicker">VPN</span>
      <h1><?= htmlspecialchars($L['sidebar_wireguard'] ?? 'WireGuard') ?></h1>
      <p><?= htmlspecialchars($L['wireguard_overview_description'] ?? 'Configure WireGuard VPN scenarios.') ?></p>
    </div>
  </section>

  <div class="praesidium-cards wireguard-cards">
    <article class="praesidium-card wireguard-card">
      <div class="wireguard-card-icon">↔</div>
      <h2><?= htmlspecialchars($L['wireguard_site_to_site'] ?? 'Site to site') ?></h2>
      <p><?= htmlspecialchars($L['wireguard_site_to_site_desc'] ?? 'Create point-to-point VPNs between two offices or networks.') ?></p>
      <ul>
        <li><?= htmlspecialchars($L['wireguard_site_to_site_hint_1'] ?? 'Join two locations with a dedicated tunnel.') ?></li>
        <li><?= htmlspecialchars($L['wireguard_site_to_site_hint_2'] ?? 'Validate tunnel IPs, remote networks and endpoint.') ?></li>
      </ul>
      <button type="button" onclick="loadWireGuardChild('interfaces/wireguard/site_to_site.php')"><?= htmlspecialchars($L['open'] ?? 'Open') ?></button>
    </article>
    <article class="praesidium-card wireguard-card">
      <div class="wireguard-card-icon">◉</div>
      <h2><?= htmlspecialchars($L['wireguard_remote_access'] ?? 'Remote access') ?></h2>
      <p><?= htmlspecialchars($L['wireguard_remote_access_desc'] ?? 'Create remote-access VPN servers and clients.') ?></p>
      <ul>
        <li><?= htmlspecialchars($L['wireguard_remote_access_hint_1'] ?? 'Create the VPN server first.') ?></li>
        <li><?= htmlspecialchars($L['wireguard_remote_access_hint_2'] ?? 'Then add clients associated with that server.') ?></li>
      </ul>
      <button type="button" onclick="loadWireGuardChild('interfaces/wireguard/remote_access.php')"><?= htmlspecialchars($L['open'] ?? 'Open') ?></button>
    </article>
  </div>
  <script>
    window.LANG = <?= json_encode($L) ?>;
    function loadWireGuardChild(page) {
      fetch(page)
        .then(res => { if (!res.ok) throw new Error(`HTTP ${res.status}`); return res.text(); })
        .then(html => {
          const main = document.getElementById('main-content');
          main.innerHTML = html;
          const temp = document.createElement('div');
          temp.innerHTML = html;
          temp.querySelectorAll('script').forEach(script => {
            const next = document.createElement('script');
            if (script.src) next.src = script.src;
            else next.textContent = script.textContent;
            if (script.type) next.type = script.type;
            document.body.appendChild(next);
          });
        })
        .catch(() => { document.getElementById('main-content').innerHTML = '<p style="color:red;">No se pudo cargar el contenido.</p>'; });
    }
  </script>
</body>
</html>
