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
<head><meta charset="UTF-8">
</head>
<body>
  <h1><?= htmlspecialchars($L['sidebar_wireguard'] ?? 'WireGuard') ?></h1>
  <p><?= htmlspecialchars($L['wireguard_overview_description'] ?? 'Configure WireGuard VPN scenarios.') ?></p>
  <div class="praesidium-cards">
    <article class="praesidium-card">
      <h2><?= htmlspecialchars($L['wireguard_site_to_site'] ?? 'Site to site') ?></h2>
      <p><?= htmlspecialchars($L['wireguard_site_to_site_desc'] ?? 'Create point-to-point VPNs between two offices or networks.') ?></p>
      <button type="button" onclick="loadWireGuardChild('interfaces/wireguard/site_to_site.php')"><?= htmlspecialchars($L['open'] ?? 'Open') ?></button>
    </article>
    <article class="praesidium-card">
      <h2><?= htmlspecialchars($L['wireguard_remote_access'] ?? 'Remote access') ?></h2>
      <p><?= htmlspecialchars($L['wireguard_remote_access_desc'] ?? 'Create remote-access VPN servers and clients.') ?></p>
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
