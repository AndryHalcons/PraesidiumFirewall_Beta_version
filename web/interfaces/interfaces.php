<?php
session_start();
if (!isset($_SESSION['username'])) {
    exit("No autorizado");
}

$language  = $_SESSION['language'] ?? 'es';
$langFile  = __DIR__ . "/../lang/{$language}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../lang/es.php";
}
$L = require $langFile;

$interfacesFile = '/etc/network/interfaces';
$backupFile     = '/etc/network/interfaces.bak';

/**
 * Lee /etc/network/interfaces y devuelve un array de interfaces.
 */
function parseInterfaces(string $file): array {
    $lines      = @file($file);
    $interfaces = [];
    $current    = null;

    if (!$lines) {
        return [];
    }

    foreach ($lines as $line) {
        $t = trim($line);
        if (preg_match('/^(auto|allow-hotplug)\s+(\S+)/', $t, $m)) {
            $current = $m[2];
            $interfaces[$current] = [
                'trigger' => $m[1],
                'options' => []
            ];
        }
        elseif (preg_match('/^iface\s+(\S+)\s+inet\s+(\S+)/', $t, $m)) {
            $current = $m[1];
            $interfaces[$current]['method'] = $m[2];
        }
        elseif ($current && preg_match('/^(\S+)\s+(.+)/', $t, $m)) {
            $interfaces[$current]['options'][] = [
                'key'   => $m[1],
                'value' => $m[2]
            ];
        }
    }

    return $interfaces;
}

/**
 * Obtiene el valor de una opción dentro de una interfaz.
 */
function getOptionValue(array $cfg, string $key): string {
    foreach ($cfg['options'] as $opt) {
        if ($opt['key'] === $key) {
            return $opt['value'];
        }
    }
    return '';
}

$fields = [
    'address','netmask','gateway','network','broadcast','pointopoint',
    'mtu','hwaddress','metric','arp','promisc',
    'dns-nameservers','dns-search','dns-domain',
    'pre-up','up','post-up','pre-down','down','post-down',
    'bond-mode','bond-miimon','bond-slaves',
    'bridge_ports','bridge_stp','bridge_fd',
    'vlan-raw-device','vlan-id','vlan-gtag',
];

// Cuando envían el formulario, generamos de nuevo el contenido y recargamos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newInterfaces = $_POST['interfaces'] ?? [];
    $newContent    = "";

    foreach ($newInterfaces as $cfg) {
        $name = trim($cfg['name']);
        if ($name === '') continue;

        $newContent .= "{$cfg['trigger']} {$name}\n";
        $newContent .= "iface {$name} inet {$cfg['method']}\n";

        foreach ($fields as $key) {
            $v = trim($cfg['options'][$key] ?? '');
            if ($v !== '') {
                $newContent .= "    {$key} {$v}\n";
            }
        }
        $newContent .= "\n";
    }

    // Backup y guardado
    @copy($interfacesFile, $backupFile);
    file_put_contents($interfacesFile, $newContent);
    $output = shell_exec('sudo ifreload -a 2>&1');
}

$interfaces = parseInterfaces($interfacesFile);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
    <meta charset="utf-8">
    <title><?= $L['network_config_title'] ?></title>
    <link rel="stylesheet" href="styles.css">
    <script defer src="interfaces.js"></script>
</head>
<body>
<div class="network-config">
  <h1><?= $L['network_config_title'] ?></h1>

  <form method="POST" id="netcfg-form">
    <button type="button" id="add-btn" class="primary">
      ➕ <?= $L['add_interface'] ?>
    </button>

    <div id="interfaces-container">
      <?php $i = 0; foreach ($interfaces as $name => $cfg): ?>
      <div class="iface-card" data-idx="<?= $i ?>">
        <header>
          <h2><?= htmlspecialchars($name) ?></h2>
          <div class="actions">
            <button type="button" class="toggle-adv">
              ⚙ <?= $L['advanced'] ?>
            </button>
            <button type="button" class="remove-btn">✖</button>
          </div>
        </header>
        <div class="body">
          <div class="field">
            <label><?= $L['name'] ?></label>
            <input name="interfaces[<?= $i ?>][name]"
                   value="<?= htmlspecialchars($name) ?>"
                   required>
          </div>
          <div class="field">
            <label><?= $L['trigger'] ?></label>
            <select name="interfaces[<?= $i ?>][trigger]">
              <option value="auto" <?= $cfg['trigger']==='auto' ? 'selected':'' ?>>auto</option>
              <option value="allow-hotplug" <?= $cfg['trigger']==='allow-hotplug' ? 'selected':'' ?>>allow-hotplug</option>
            </select>
          </div>
          <div class="field">
            <label><?= $L['method'] ?></label>
            <select name="interfaces[<?= $i ?>][method]">
              <option value="dhcp" <?= ($cfg['method']??'')==='dhcp' ? 'selected':'' ?>>dhcp</option>
              <option value="static" <?= ($cfg['method']??'')==='static' ? 'selected':'' ?>>static</option>
            </select>
          </div>

          <div class="advanced" hidden>
            <?php foreach ($fields as $key): ?>
            <div class="field">
              <label><?= htmlspecialchars($key) ?></label>
              <input
                name="interfaces[<?= $i ?>][options][<?= $key ?>]"
                value="<?= htmlspecialchars(getOptionValue($cfg, $key)) ?>">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php $i++; endforeach; ?>
    </div>

    <button type="submit" class="primary">
      <?= $L['save_and_apply'] ?>
    </button>
  </form>

  <?php if (!empty($output)): ?>
  <section class="output">
    <h2><?= $L['ifreload_output'] ?></h2>
    <pre><?= htmlspecialchars($output) ?></pre>
  </section>
  <?php endif; ?>

</div>
</body>
</html>

