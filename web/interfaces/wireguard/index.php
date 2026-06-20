<?php
session_start();
if (!isset($_SESSION['username'])) { exit(htmlspecialchars($L['unauthorized'] ?? 'unauthorized', ENT_QUOTES, 'UTF-8')); }
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
      <span class="wireguard-kicker"><?= htmlspecialchars($L['wireguard_kicker']) ?></span>
      <h1><?= htmlspecialchars($L['sidebar_wireguard'] ?? 'sidebar_wireguard') ?></h1>
      <p><?= htmlspecialchars($L['wireguard_overview_description'] ?? 'wireguard_overview_description') ?></p>
    </div>
  </section>

  <div class="praesidium-cards wireguard-cards">
    <article class="praesidium-card wireguard-card">
      <div class="wireguard-card-icon">↔</div>
      <h2><?= htmlspecialchars($L['wireguard_site_to_site'] ?? 'wireguard_site_to_site') ?></h2>
      <p><?= htmlspecialchars($L['wireguard_site_to_site_desc'] ?? 'wireguard_site_to_site_desc') ?></p>
      <ul>
        <li><?= htmlspecialchars($L['wireguard_site_to_site_hint_1'] ?? 'wireguard_site_to_site_hint_1') ?></li>
        <li><?= htmlspecialchars($L['wireguard_site_to_site_hint_2'] ?? 'wireguard_site_to_site_hint_2') ?></li>
      </ul>
      <button type="button" onclick="loadWireGuardChild('interfaces/wireguard/site_to_site.php')"><?= htmlspecialchars($L['open'] ?? 'open') ?></button>
    </article>
    <article class="praesidium-card wireguard-card">
      <div class="wireguard-card-icon">◉</div>
      <h2><?= htmlspecialchars($L['wireguard_remote_access'] ?? 'wireguard_remote_access') ?></h2>
      <p><?= htmlspecialchars($L['wireguard_remote_access_desc'] ?? 'wireguard_remote_access_desc') ?></p>
      <ul>
        <li><?= htmlspecialchars($L['wireguard_remote_access_hint_1'] ?? 'wireguard_remote_access_hint_1') ?></li>
        <li><?= htmlspecialchars($L['wireguard_remote_access_hint_2'] ?? 'wireguard_remote_access_hint_2') ?></li>
      </ul>
      <button type="button" onclick="loadWireGuardChild('interfaces/wireguard/remote_access.php')"><?= htmlspecialchars($L['open'] ?? 'open') ?></button>
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
        .catch(() => { document.getElementById('main-content').innerHTML = `<p style="color:red;">${window.LANG?.wireguard_load_error || 'wireguard_load_error'}</p>`; });
    }
  </script>
</body>
</html>
