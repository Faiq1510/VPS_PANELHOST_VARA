<?php
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$serverIp = trim(shell_exec("curl -s ifconfig.me 2>/dev/null") ?? '');
$phpVersion = phpversion();
$serverTime = date('d-m-Y H:i:s');

// CPU Usage
$stat1 = file('/proc/stat');
$cpu1 = preg_split('/\s+/', $stat1[0]);
usleep(200000);
$stat2 = file('/proc/stat');
$cpu2 = preg_split('/\s+/', $stat2[0]);
$total1 = array_sum(array_slice($cpu1, 1, 8));
$total2 = array_sum(array_slice($cpu2, 1, 8));
$idle1 = $cpu1[4];
$idle2 = $cpu2[4];
$totalDiff = $total2 - $total1;
$idleDiff = $idle2 - $idle1;
$cpu = ($totalDiff > 0) ? round((($totalDiff - $idleDiff) / $totalDiff) * 100, 1) : 0;

// RAM Usage
$ramTotal = trim(shell_exec("free -m | awk '/Mem:/ {print $2}'"));
$ramUsed = trim(shell_exec("free -m | awk '/Mem:/ {print $3}'"));
$ramFree = $ramTotal - $ramUsed;
$ramPercent = $ramTotal > 0 ? round(($ramUsed / $ramTotal) * 100) : 0;

// Disk Usage
$diskTotal = trim(shell_exec("df -h / | awk 'NR==2 {print $2}'"));
$diskUsed = trim(shell_exec("df -h / | awk 'NR==2 {print $3}'"));
$diskFree = trim(shell_exec("df -h / | awk 'NR==2 {print $4}'"));
$diskPercent = intval(trim(shell_exec("df / | awk 'NR==2 {print $5}' | tr -d '%'")));

// Network
$netRx = trim(shell_exec("cat /proc/net/dev | grep -E 'eth0|ens' | head -1 | awk '{print $2}'"));
$netTx = trim(shell_exec("cat /proc/net/dev | grep -E 'eth0|ens' | head -1 | awk '{print $10}'"));
$netRxMB = $netRx ? round($netRx / 1024 / 1024, 2) : 0;
$netTxMB = $netTx ? round($netTx / 1024 / 1024, 2) : 0;

// Uptime
$uptime = trim(shell_exec("uptime -p"));

// Load Average
$loadAvg = trim(shell_exec("cat /proc/loadavg | awk '{print $1, $2, $3}'"));

// Docker containers
$totalContainers = $pdo->query("SELECT COUNT(*) FROM containers WHERE status != 'deleted'")->fetchColumn();
$runningContainers = $pdo->query("SELECT COUNT(*) FROM containers WHERE status = 'running'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="refresh" content="10">
<title>Dashboard - VPS Panel</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #0f172a; font-family: 'Segoe UI', sans-serif; color: #e2e8f0; display: flex; min-height: 100vh; }
.sidebar { width: 240px; background: #1e293b; padding: 20px 0; position: fixed; height: 100vh; overflow-y: auto; }
.sidebar-logo { padding: 0 20px 20px; border-bottom: 1px solid #334155; }
.sidebar-logo h2 { color: #38bdf8; font-size: 18px; }
.sidebar-logo p { color: #64748b; font-size: 11px; }
.sidebar-menu { padding: 20px 0; }
.menu-label { color: #475569; font-size: 11px; font-weight: 600; padding: 0 20px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
.menu-item { display: flex; align-items: center; gap: 10px; padding: 10px 20px; color: #94a3b8; text-decoration: none; font-size: 14px; transition: all 0.2s; }
.menu-item:hover, .menu-item.active { background: #0f172a; color: #38bdf8; }
.menu-item span { font-size: 16px; }
.sidebar-user { position: absolute; bottom: 0; left: 0; right: 0; padding: 16px 20px; border-top: 1px solid #334155; display: flex; align-items: center; gap: 10px; }
.user-avatar { width: 32px; height: 32px; background: #38bdf8; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #0f172a; font-weight: 700; font-size: 14px; }
.user-info small { color: #64748b; font-size: 11px; display: block; }
.logout-btn { margin-left: auto; color: #ef4444; font-size: 18px; text-decoration: none; }
.main { margin-left: 240px; padding: 30px; flex: 1; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.page-header h1 { font-size: 24px; color: #f1f5f9; }
.server-ip { color: #38bdf8; font-size: 13px; }
.live-badge { display: inline-flex; align-items: center; gap: 6px; background: #052e16; color: #4ade80; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.live-dot { width: 7px; height: 7px; background: #4ade80; border-radius: 50%; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.3} }

/* Monitor Cards */
.monitor-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
.monitor-card { background: #1e293b; border-radius: 12px; padding: 24px; }
.monitor-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.monitor-title { font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
.monitor-icon { font-size: 20px; }
.monitor-value { font-size: 36px; font-weight: 700; color: #f1f5f9; margin-bottom: 4px; }
.monitor-sub { font-size: 12px; color: #475569; margin-bottom: 16px; }

/* Progress Bar */
.progress-bar { background: #0f172a; border-radius: 6px; height: 8px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 6px; transition: width 0.5s ease; }
.progress-low { background: linear-gradient(90deg, #22c55e, #4ade80); }
.progress-mid { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.progress-high { background: linear-gradient(90deg, #ef4444, #f87171); }
.progress-label { display: flex; justify-content: space-between; font-size: 11px; color: #475569; margin-top: 6px; }

/* Info Grid */
.info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
.info-card { background: #1e293b; border-radius: 12px; padding: 20px; text-align: center; }
.info-icon { font-size: 28px; margin-bottom: 8px; }
.info-value { font-size: 20px; font-weight: 700; color: #38bdf8; }
.info-label { font-size: 12px; color: #64748b; margin-top: 4px; }

/* Server Info */
.server-card { background: #1e293b; border-radius: 12px; padding: 24px; }
.server-title { font-size: 15px; font-weight: 600; color: #f1f5f9; margin-bottom: 16px; }
.info-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #334155; font-size: 13px; }
.info-row:last-child { border-bottom: none; }
.info-key { color: #64748b; }
.info-val { color: #e2e8f0; font-weight: 500; font-family: monospace; }
</style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo">
        <h2>⚡ VPS Panel</h2>
        <p>VPS Docker Management</p>
    </div>
    <div class="sidebar-menu">
        <div class="menu-label">Main</div>
        <a href="index.php" class="menu-item active"><span>📊</span> Dashboard</a>
        <a href="modules/containers/index.php" class="menu-item"><span>🖥️</span> VPS Containers</a>
        <?php if (isAdmin()): ?>
        <a href="modules/users/index.php" class="menu-item"><span>👥</span> Manajemen User</a>
        <?php endif; ?>
        <div class="menu-label" style="margin-top:16px">Tools</div>
        <a href="modules/voucher/index.php" class="menu-item"><span>🎟️</span> Voucher</a>
        <a href="modules/terminal/index.php" class="menu-item"><span>💻</span> Terminal</a>
        <a href="modules/settings/index.php" class="menu-item"><span>⚙️</span> Pengaturan</a>
    </div>
    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
        <div class="user-info">
            <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
            <small><?= $_SESSION['role'] === 'admin' ? 'Super Admin' : 'User' ?></small>
        </div>
        <a href="logout.php" class="logout-btn" title="Logout">⏻</a>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <div style="margin-top:6px">
                <span class="live-badge"><span class="live-dot"></span> Live Monitoring — auto refresh 10s</span>
            </div>
        </div>
        <div style="text-align:right">
            <div class="server-ip">● <?= $serverIp ?></div>
            <small style="color:#64748b"><?= $serverTime ?></small>
        </div>
    </div>

    <!-- Monitor Cards -->
    <div class="monitor-grid">
        <!-- CPU -->
        <?php
        $cpuVal = floatval($cpu);
        $cpuClass = $cpuVal < 50 ? 'progress-low' : ($cpuVal < 80 ? 'progress-mid' : 'progress-high');
        ?>
        <div class="monitor-card">
            <div class="monitor-header">
                <span class="monitor-title">CPU Usage</span>
                <span class="monitor-icon">🔲</span>
            </div>
            <div class="monitor-value"><?= $cpu ?>%</div>
            <div class="monitor-sub">Load Average: <?= $loadAvg ?></div>
            <div class="progress-bar">
                <div class="progress-fill <?= $cpuClass ?>" style="width:<?= min($cpuVal, 100) ?>%"></div>
            </div>
            <div class="progress-label"><span>0%</span><span>100%</span></div>
        </div>

        <!-- RAM -->
        <?php
        $ramClass = $ramPercent < 50 ? 'progress-low' : ($ramPercent < 80 ? 'progress-mid' : 'progress-high');
        ?>
        <div class="monitor-card">
            <div class="monitor-header">
                <span class="monitor-title">RAM Usage</span>
                <span class="monitor-icon">💾</span>
            </div>
            <div class="monitor-value"><?= $ramPercent ?>%</div>
            <div class="monitor-sub">Used: <?= $ramUsed ?>MB / Total: <?= $ramTotal ?>MB — Free: <?= $ramFree ?>MB</div>
            <div class="progress-bar">
                <div class="progress-fill <?= $ramClass ?>" style="width:<?= $ramPercent ?>%"></div>
            </div>
            <div class="progress-label"><span>0 MB</span><span><?= $ramTotal ?> MB</span></div>
        </div>

        <!-- Disk -->
        <?php
        $diskClass = $diskPercent < 50 ? 'progress-low' : ($diskPercent < 80 ? 'progress-mid' : 'progress-high');
        ?>
        <div class="monitor-card">
            <div class="monitor-header">
                <span class="monitor-title">Disk Usage</span>
                <span class="monitor-icon">🗄️</span>
            </div>
            <div class="monitor-value"><?= $diskPercent ?>%</div>
            <div class="monitor-sub">Used: <?= $diskUsed ?> / Total: <?= $diskTotal ?> — Free: <?= $diskFree ?></div>
            <div class="progress-bar">
                <div class="progress-fill <?= $diskClass ?>" style="width:<?= $diskPercent ?>%"></div>
            </div>
            <div class="progress-label"><span>0</span><span><?= $diskTotal ?></span></div>
        </div>

        <!-- Network -->
        <div class="monitor-card">
            <div class="monitor-header">
                <span class="monitor-title">Network</span>
                <span class="monitor-icon">🌐</span>
            </div>
            <div class="monitor-value"><?= $netRxMB ?> MB</div>
            <div class="monitor-sub">
                ↓ Download: <?= $netRxMB ?> MB &nbsp;|&nbsp; ↑ Upload: <?= $netTxMB ?> MB
            </div>
            <div class="progress-bar">
                <div class="progress-fill progress-low" style="width:100%"></div>
            </div>
            <div class="progress-label"><span>Total RX</span><span>Total TX: <?= $netTxMB ?> MB</span></div>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="info-grid">
        <div class="info-card">
            <div class="info-icon">🖥️</div>
            <div class="info-value"><?= $totalContainers ?></div>
            <div class="info-label">Total Container</div>
        </div>
        <div class="info-card">
            <div class="info-icon">✅</div>
            <div class="info-value"><?= $runningContainers ?></div>
            <div class="info-label">Container Running</div>
        </div>
        <div class="info-card">
            <div class="info-icon">⏱️</div>
            <div class="info-value" style="font-size:14px"><?= $uptime ?></div>
            <div class="info-label">Server Uptime</div>
        </div>
    </div>

    <!-- Server Info -->
    <div class="server-card">
        <div class="server-title">🖥️ Info Server</div>
        <div class="info-row"><span class="info-key">Server IP</span><span class="info-val"><?= $serverIp ?></span></div>
        <div class="info-row"><span class="info-key">PHP Version</span><span class="info-val"><?= $phpVersion ?></span></div>
        <div class="info-row"><span class="info-key">Web Server</span><span class="info-val"><?= $_SERVER['SERVER_SOFTWARE'] ?></span></div>
        <div class="info-row"><span class="info-key">OS</span><span class="info-val"><?= trim(shell_exec("lsb_release -d | cut -f2")) ?></span></div>
        <div class="info-row"><span class="info-key">Kernel</span><span class="info-val"><?= trim(shell_exec("uname -r")) ?></span></div>
        <div class="info-row"><span class="info-key">Server Time</span><span class="info-val"><?= $serverTime ?></span></div>
    </div>
</div>
</body>
</html>
