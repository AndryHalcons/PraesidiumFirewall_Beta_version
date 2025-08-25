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
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">
<head>
  <script>
    const LANG = <?= json_encode($L) ?>;
  </script>

  <meta charset="UTF-8">
  <title><?= htmlspecialchars($L['sidebar_XDP_policies']) ?></title>
  <link rel="stylesheet" href="../styles.css">
</head>
<body>
  <h2><?= htmlspecialchars($L['sidebar_XDP_policies']) ?></h2>
  <div id="rules-output"></div>

  <script src="/policies/policies_xdp/policies_xdp.js"></script>
  

</body>
Action → define qué hacer con el paquete: ACCEPT, DROP, LOG, etc.<br>
Enabled? → indica si la regla estará habilitada o no.<br>
Interface → especifica la interfaz de red sobre la que se aplica la regla (ej. eth0).<br>
L3 Protocol → protocolo de capa 3: IPv4, IPv6, ARP, etc.<br>
L4 Protocol → protocolo de capa 4: TCP, UDP, ICMP, ICMPv6.<br>
IPv4 Source → dirección IP de origen específica en tráfico IPv4.<br>
IPv4 Destination → dirección IP de destino específica en tráfico IPv4.<br>
IPv4 Source Network → red de origen en formato CIDR (ej. 192.168.1.0/24).<br>
IPv4 Destination Network → red de destino en formato CIDR.<br>
IPv4 Protocol → número de protocolo IPv4 (ej. 6 para TCP, 17 para UDP).<br>
IPv6 Source → dirección IP de origen específica en tráfico IPv6.<br>
IPv6 Destination → dirección IP de destino específica en tráfico IPv6.<br>
IPv6 Source Network → red de origen IPv6 en formato CIDR (ej. 2001:db8::/64).<br>
IPv6 Destination Network → red de destino IPv6 en formato CIDR.<br>
IPv6 Next Header → tipo de encabezado siguiente en IPv6 (ej. TCP, UDP, ICMPv6).<br>
TCP Source Port → puerto de origen en tráfico TCP (ej. 80, 443).<br>
TCP Destination Port → puerto de destino en tráfico TCP.<br>
TCP Flags → banderas TCP como SYN, ACK, FIN, RST, etc.<br>
UDP Source Port → puerto de origen en tráfico UDP.<br>
UDP Destination Port → puerto de destino en tráfico UDP.<br>
ICMP Type → tipo de mensaje ICMP (ej. echo-request, destination-unreachable).<br>
ICMP Code → código específico dentro del tipo ICMP.<br>
ICMPv6 Type → tipo de mensaje ICMPv6.<br>
ICMPv6 Code → código específico dentro del tipo ICMPv6.<br>
Probability → probabilidad de que la regla se aplique (valor entre 0.0 y 1.0).<br>


</html>
