<?php
require_once __DIR__ . '/../common/security/session.php';
praesidium_session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

header('Content-Type: application/json');

function dashboard_unescape_mount_field(string $value): string {
    return preg_replace_callback('/\\\\([0-7]{3})/', function ($match) {
        return chr(octdec($match[1]));
    }, $value);
}

function dashboard_mount_priority(string $target): int {
    $priorities = [
        '/' => 0,
        '/var' => 10,
        '/var/www' => 11,
        '/var/log' => 12,
        '/home' => 20,
        '/boot' => 30,
        '/boot/efi' => 31,
        '/tmp' => 40,
        '/opt' => 50,
        '/srv' => 60,
    ];
    if (isset($priorities[$target])) {
        return $priorities[$target];
    }
    return 100;
}

function dashboard_is_storage_mount(string $fstype, string $target): bool {
    $ignoredFs = [
        'autofs', 'binfmt_misc', 'bpf', 'cgroup', 'cgroup2', 'configfs', 'debugfs',
        'devpts', 'devtmpfs', 'fusectl', 'hugetlbfs', 'mqueue', 'nsfs', 'overlay',
        'proc', 'pstore', 'ramfs', 'rpc_pipefs', 'securityfs', 'squashfs', 'sysfs',
        'tracefs', 'tmpfs'
    ];
    if (in_array($fstype, $ignoredFs, true)) {
        return false;
    }
    if ($target === '' || str_starts_with($target, '/proc') || str_starts_with($target, '/sys') || str_starts_with($target, '/dev') || str_starts_with($target, '/run')) {
        return false;
    }
    return true;
}

function dashboard_is_relevant_mount(string $target, float $usedPercent): bool {
    $important = ['/', '/var', '/var/www', '/var/log', '/home', '/boot', '/boot/efi', '/tmp', '/opt', '/srv'];
    if (in_array($target, $important, true)) {
        return true;
    }
    return $usedPercent >= 80.0;
}

$mountinfo = @file('/proc/self/mountinfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$mounts = [];
$summaryByDevice = [];

if (is_array($mountinfo)) {
    foreach ($mountinfo as $line) {
        $parts = explode(' - ', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $left = preg_split('/\s+/', $parts[0]);
        $right = preg_split('/\s+/', $parts[1]);
        if (count($left) < 5 || count($right) < 3) {
            continue;
        }

        $deviceId = (string)$left[2];
        $target = dashboard_unescape_mount_field((string)$left[4]);
        $fstype = (string)$right[0];
        $source = dashboard_unescape_mount_field((string)$right[1]);

        if (!dashboard_is_storage_mount($fstype, $target)) {
            continue;
        }

        $total = @disk_total_space($target);
        $free = @disk_free_space($target);
        if ($total === false || $free === false || $total <= 0) {
            continue;
        }

        $total = (int)$total;
        $available = (int)$free;
        $used = max(0, $total - $available);
        $usedPercent = $total > 0 ? round(($used / $total) * 100, 2) : 0.0;

        if (!isset($summaryByDevice[$deviceId])) {
            $summaryByDevice[$deviceId] = [
                'total' => $total,
                'used' => $used,
                'available' => $available,
            ];
        }

        if (!dashboard_is_relevant_mount($target, $usedPercent)) {
            continue;
        }

        $status = 'ok';
        $capacityCriticalTargets = ['/', '/var', '/var/www', '/var/log', '/home', '/tmp', '/opt', '/srv'];
        $useAbsoluteFreeThreshold = in_array($target, $capacityCriticalTargets, true);
        if ($usedPercent >= 90.0 || ($useAbsoluteFreeThreshold && $available <= 1073741824)) {
            $status = 'critical';
        } elseif ($usedPercent >= 80.0 || ($useAbsoluteFreeThreshold && $available <= 5368709120)) {
            $status = 'warning';
        }

        $mounts[] = [
            'mountpoint' => $target,
            'source' => $source,
            'fstype' => $fstype,
            'total' => $total,
            'used' => $used,
            'available' => $available,
            'used_percent' => $usedPercent,
            'status' => $status,
            'priority' => dashboard_mount_priority($target),
        ];
    }
}

usort($mounts, function ($a, $b) {
    if ($a['priority'] === $b['priority']) {
        return strcmp($a['mountpoint'], $b['mountpoint']);
    }
    return $a['priority'] <=> $b['priority'];
});

$summaryTotal = 0;
$summaryUsed = 0;
$summaryAvailable = 0;
foreach ($summaryByDevice as $device) {
    $summaryTotal += $device['total'];
    $summaryUsed += $device['used'];
    $summaryAvailable += $device['available'];
}
$summaryPercent = $summaryTotal > 0 ? round(($summaryUsed / $summaryTotal) * 100, 2) : 0.0;

foreach ($mounts as &$mount) {
    unset($mount['priority']);
}
unset($mount);

echo json_encode([
    'summary' => [
        'total' => $summaryTotal,
        'used' => $summaryUsed,
        'available' => $summaryAvailable,
        'used_percent' => $summaryPercent,
        'device_count' => count($summaryByDevice),
    ],
    'mounts' => $mounts,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
