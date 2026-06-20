<?php
// Página padre WireGuard: muestra las dos tarjetas de navegación principales.
// WireGuard parent page: shows the two main navigation cards.
// Fase 1: abrir sesión y bloquear acceso no autenticado antes de renderizar HTML.
// Phase 1: open the session and block unauthenticated access before rendering HTML.
session_start();
if (!isset($_SESSION['username'])) { exit(htmlspecialchars($L['unauthorized'] ?? 'unauthorized', ENT_QUOTES, 'UTF-8')); }
// Fase 2: cargar el idioma activo para que todo texto visible salga de web/lang.
// Phase 2: load the active language so every visible text comes from web/lang.
$language = $_SESSION['language'] ?? 'es';
$langFile = __DIR__ . "/../../lang/{$language}.php";
if (!file_exists($langFile)) { $langFile = __DIR__ . "/../../lang/es.php"; }
$L = require $langFile;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head><meta charset="UTF-8"></head>
<body>
  <!-- Fase 3: pintar contenido visual usando solo claves de idioma. -->
  <!-- Phase 3: render visual content using only language keys. -->
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
    // Fase 4: publicar LANG global antes de llamar al render genérico.
    // Phase 4: publish global LANG before calling the generic renderer.
    window.LANG = <?= json_encode($L) ?>;
    // Carga una página hija WireGuard dentro del contenedor principal.
    // Loads a WireGuard child page inside the main container.
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
