<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$msg = '';
$err = '';

// Ambil semua settings
$settings = [];
$rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
foreach ($rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Simpan settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['panel_name', 'domain', 'ssh_port_start', 'web_port_start', 'console_port_start', 'docker_image'];
    foreach ($fields as $field) {
        $value = trim($_POST[$field] ?? '');
        $check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
        $check->execute([$field]);
        if ($check->fetchColumn() > 0) {
            $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?")->execute([$value, $field]);
        } else {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)")->execute([$field, $value]);
        }
    }
    // Reload settings
    $rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $msg = 'Pengaturan berhasil disimpan';
}

// Info sistem
$phpVersion = phpversion();
$apacheVersion = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$serverIp = trim(shell_exec("hostname -I | awk '{print $1}'") ?? '');
$diskTotal = disk_total_space('/');
$diskFree = disk_free_space('/');
$diskUsed = $diskTotal - $diskFree;
$diskPct = round(($diskUsed / $diskTotal) * 100);
$ramInfo = file('/proc/meminfo');
$ramTotal = intval(preg_replace('/[^0-9]/', '', $ramInfo[0]));
$ramFree = intval(preg_replace('/[^0-9]/', '', $ramInfo[1]));
$ramUsed = $ramTotal - $ramFree;
$uptime = trim(shell_exec("uptime -p") ?? '');
$dockerVersion = trim(shell_exec("docker --version 2>/dev/null") ?? 'Tidak tersedia');
$totalContainers = $pdo->query("SELECT COUNT(*) FROM containers WHERE status != 'deleted'")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengaturan - VPS Panel</title>
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
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 24px; color: #f1f5f9; }
.page-header p { color: #64748b; font-size: 13px; margin-top: 4px; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.card { background: #1e293b; border-radius: 12px; padding: 24px; margin-bottom: 24px; }
.card-title { font-size: 13px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
.form-group { margin-bottom: 18px; }
label { display: block; color: #94a3b8; font-size: 13px; margin-bottom: 6px; }
input, select { width: 100%; padding: 10px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px; outline: none; transition: border-color 0.2s; }
input:focus { border-color: #38bdf8; }
.hint { color: #475569; font-size: 11px; margin-top: 4px; }
.btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: opacity 0.2s; }
.btn:hover { opacity: 0.85; }
.btn-primary { background: #38bdf8; color: #0f172a; }
.btn-danger { background: #ef4444; color: white; }
.btn-secondary { background: #334155; color: #e2e8f0; }
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }
.alert-success { background: #052e16; color: #4ade80; }
.alert-danger { background: #450a0a; color: #f87171; }
.info-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #334155; font-size: 13px; }
.info-row:last-child { border-bottom: none; }
.info-label { color: #64748b; }
.info-value { color: #e2e8f0; font-weight: 500; font-family: monospace; font-size: 12px; }
.badge-ok { background: #052e16; color: #4ade80; padding: 2px 8px; border-radius: 12px; font-size: 11px; }
.badge-warn { background: #451a03; color: #fb923c; padding: 2px 8px; border-radius: 12px; font-size: 11px; }
.section-divider { border: none; border-top: 1px solid #334155; margin: 20px 0; }
.stat-mini { display: flex; gap: 12px; margin-bottom: 20px; }
.stat-mini-item { flex: 1; background: #0f172a; border-radius: 8px; padding: 12px; text-align: center; }
.stat-mini-value { font-size: 22px; font-weight: 700; color: #38bdf8; }
.stat-mini-label { color: #64748b; font-size: 11px; margin-top: 2px; }
</style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo">
        <h2>⚡ <?= htmlspecialchars($settings['panel_name'] ?? 'VPS Panel') ?></h2>
        <p>VPS Docker Management</p>
    </div>
    <div class="sidebar-menu">
        <div class="menu-label">Main</div>
        <a href="../../index.php" class="menu-item"><span>📊</span> Dashboard</a>
        <a href="../containers/index.php" class="menu-item"><span>🖥️</span> VPS Containers</a>
        <a href="../users/index.php" class="menu-item"><span>👥</span> Manajemen User</a>
        <div class="menu-label" style="margin-top:16px">Tools</div>
        <a href="../voucher/index.php" class="menu-item"><span>🎟️</span> Voucher</a>
        <a href="../terminal/index.php" class="menu-item"><span>💻</span> Terminal</a>
        <a href="index.php" class="menu-item active"><span>⚙️</span> Pengaturan</a>
    </div>
    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
        <div class="user-info">
            <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
            <small>Super Admin</small>
        </div>
        <a href="../../logout.php" class="logout-btn">⏻</a>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <h1>⚙️ Pengaturan Panel</h1>
        <p>Konfigurasi panel dan informasi sistem</p>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
    <div class="alert alert-danger">❌ <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <div class="grid">
        <!-- Kiri: Form Pengaturan -->
        <div>
            <div class="card">
                <div class="card-title">🔧 Konfigurasi Panel</div>
                <form method="POST">
                    <div class="form-group">
                        <label>Nama Panel</label>
                        <input type="text" name="panel_name" value="<?= htmlspecialchars($settings['panel_name'] ?? 'VPS Panel') ?>">
                    </div>
                    <div class="form-group">
                        <label>Domain Utama</label>
                        <input type="text" name="domain" value="<?= htmlspecialchars($settings['domain'] ?? '') ?>">
                        <div class="hint">Domain yang digunakan untuk subdomain container</div>
                    </div>
                    <div class="form-group">
                        <label>Docker Image</label>
                        <input type="text" name="docker_image" value="<?= htmlspecialchars($settings['docker_image'] ?? 'vps-hosting:latest') ?>">
                    </div>
                    <hr class="section-divider">
                    <div class="card-title">🔌 Konfigurasi Port</div>
                    <div class="form-group">
                        <label>SSH Port Awal</label>
                        <input type="number" name="ssh_port_start" value="<?= htmlspecialchars($settings['ssh_port_start'] ?? '12000') ?>">
                        <div class="hint">Port SSH pertama yang digunakan container</div>
                    </div>
                    <div class="form-group">
                        <label>Web Port Awal</label>
                        <input type="number" name="web_port_start" value="<?= htmlspecialchars($settings['web_port_start'] ?? '21000') ?>">
                    </div>
                    <div class="form-group">
                        <label>Console Port Awal</label>
                        <input type="number" name="console_port_start" value="<?= htmlspecialchars($settings['console_port_start'] ?? '4000') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Simpan Pengaturan</button>
                </form>
            </div>

            <!-- Ganti Password Admin -->
            <div class="card">
                <div class="card-title">🔑 Ganti Password Admin</div>
                <form method="POST" action="change_password.php">
                    <div class="form-group">
                        <label>Password Lama</label>
                        <input type="password" name="old_password" placeholder="Password saat ini" required>
                    </div>
                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="new_password" placeholder="Password baru" required>
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" placeholder="Ulangi password baru" required>
                    </div>
                    <button type="submit" class="btn btn-danger">🔑 Ganti Password</button>
                </form>
            </div>
        </div>

        <!-- Kanan: Info Sistem -->
        <div>
            <div class="card">
                <div class="card-title">📊 Statistik Panel</div>
                <div class="stat-mini">
                    <div class="stat-mini-item">
                        <div class="stat-mini-value"><?= $totalUsers ?></div>
                        <div class="stat-mini-label">Total User</div>
                    </div>
                    <div class="stat-mini-item">
                        <div class="stat-mini-value"><?= $totalContainers ?></div>
                        <div class="stat-mini-label">Total Container</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-title">🖥️ Informasi Sistem</div>
                <div class="info-row">
                    <span class="info-label">Server IP</span>
                    <span class="info-value"><?= $serverIp ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">PHP Version</span>
                    <span class="info-value"><?= $phpVersion ?> <span class="badge-ok">OK</span></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Web Server</span>
                    <span class="info-value"><?= $apacheVersion ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Docker</span>
                    <span class="info-value"><?= $dockerVersion ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Server Uptime</span>
                    <span class="info-value"><?= $uptime ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Disk Usage</span>
                    <span class="info-value">
                        <?= round($diskUsed/1024/1024/1024, 1) ?>G / <?= round($diskTotal/1024/1024/1024, 1) ?>G
                        <span class="<?= $diskPct > 80 ? 'badge-warn' : 'badge-ok' ?>"><?= $diskPct ?>%</span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">RAM Usage</span>
                    <span class="info-value">
                        <?= round($ramUsed/1024) ?>MB / <?= round($ramTotal/1024) ?>MB
                        <span class="<?= (($ramUsed/$ramTotal)*100) > 80 ? 'badge-warn' : 'badge-ok' ?>">
                            <?= round(($ramUsed/$ramTotal)*100) ?>%
                        </span>
                    </span>
                </div>
            </div>

            <div class="card">
                <div class="card-title">🌐 Konfigurasi Aktif</div>
                <div class="info-row">
                    <span class="info-label">Nama Panel</span>
                    <span class="info-value"><?= htmlspecialchars($settings['panel_name'] ?? '-') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Domain</span>
                    <span class="info-value"><?= htmlspecialchars($settings['domain'] ?? '-') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Docker Image</span>
                    <span class="info-value"><?= htmlspecialchars($settings['docker_image'] ?? '-') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">SSH Port Range</span>
                    <span class="info-value"><?= $settings['ssh_port_start'] ?? '12000' ?> - <?= ($settings['ssh_port_start'] ?? 12000) + 999 ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Web Port Range</span>
                    <span class="info-value"><?= $settings['web_port_start'] ?? '21000' ?> - <?= ($settings['web_port_start'] ?? 21000) + 999 ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Console Port Range</span>
                    <span class="info-value"><?= $settings['console_port_start'] ?? '4000' ?> - <?= ($settings['console_port_start'] ?? 4000) + 999 ?></span>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card" style="border: 1px solid #450a0a;">
                <div class="card-title" style="color:#f87171">⚠️ Danger Zone</div>
                <p style="color:#64748b;font-size:13px;margin-bottom:16px">Tindakan berikut tidak dapat dibatalkan. Harap berhati-hati.</p>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="clear_logs.php" class="btn btn-secondary" onclick="return confirm('Hapus semua log?')">🗑️ Hapus Log</a>
                    <a href="clear_deleted.php" class="btn btn-danger" onclick="return confirm('Hapus semua data container yang sudah dihapus?')">🧹 Bersihkan DB</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
