<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../api/docker.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT c.*, u.username FROM containers c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();

if (!$c || (!isAdmin() && $c['user_id'] != $_SESSION['user_id'])) {
    header('Location: index.php?err=Container tidak ditemukan');
    exit;
}

$stats = getContainerStats($c['container_name']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Detail VPS - VPS Panel</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #0f172a; font-family: 'Segoe UI', sans-serif; color: #e2e8f0; display: flex; min-height: 100vh; }
.sidebar { width: 240px; background: #1e293b; padding: 20px 0; position: fixed; height: 100vh; }
.sidebar-logo { padding: 0 20px 20px; border-bottom: 1px solid #334155; }
.sidebar-logo h2 { color: #38bdf8; font-size: 18px; }
.sidebar-logo p { color: #64748b; font-size: 11px; }
.sidebar-menu { padding: 20px 0; }
.menu-label { color: #475569; font-size: 11px; font-weight: 600; padding: 0 20px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
.menu-item { display: flex; align-items: center; gap: 10px; padding: 10px 20px; color: #94a3b8; text-decoration: none; font-size: 14px; }
.menu-item:hover, .menu-item.active { background: #0f172a; color: #38bdf8; }
.sidebar-user { position: absolute; bottom: 0; left: 0; right: 0; padding: 16px 20px; border-top: 1px solid #334155; display: flex; align-items: center; gap: 10px; }
.user-avatar { width: 32px; height: 32px; background: #38bdf8; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #0f172a; font-weight: 700; font-size: 14px; }
.user-info small { color: #64748b; font-size: 11px; display: block; }
.logout-btn { margin-left: auto; color: #ef4444; font-size: 18px; text-decoration: none; }
.main { margin-left: 240px; padding: 30px; flex: 1; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header h1 { font-size: 24px; color: #f1f5f9; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.card { background: #1e293b; border-radius: 12px; padding: 24px; margin-bottom: 20px; }
.card-title { font-size: 15px; font-weight: 600; color: #94a3b8; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 1px; font-size: 12px; }
.info-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #334155; font-size: 14px; }
.info-row:last-child { border-bottom: none; }
.info-label { color: #64748b; }
.info-value { color: #e2e8f0; font-weight: 500; }
.badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-running { background: #052e16; color: #4ade80; }
.badge-stopped { background: #450a0a; color: #f87171; }
.btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-right: 8px; }
.btn-secondary { background: #334155; color: #e2e8f0; }
.btn-danger { background: #ef4444; color: white; }
.btn-warning { background: #f59e0b; color: #0f172a; }
.btn-success { background: #22c55e; color: white; }
.console-btn { background: #7c3aed; color: white; }
code { background: #0f172a; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-family: monospace; }
.ssh-box { background: #0f172a; border-radius: 8px; padding: 16px; font-family: monospace; font-size: 13px; color: #4ade80; margin-top: 12px; }
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
        <a href="../../index.php" class="menu-item"><span>📊</span> Dashboard</a>
        <a href="index.php" class="menu-item active"><span>🖥️</span> VPS Containers</a>
        <?php if (isAdmin()): ?>
        <a href="../users/index.php" class="menu-item"><span>👥</span> Manajemen User</a>
        <?php endif; ?>
        <div class="menu-label" style="margin-top:16px">Tools</div>
        <a href="../voucher/index.php" class="menu-item"><span>🎟️</span> Voucher</a>
        <a href="../terminal/index.php" class="menu-item"><span>💻</span> Terminal</a>
        <a href="../settings/index.php" class="menu-item"><span>⚙️</span> Pengaturan</a>
    </div>
    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
        <div class="user-info">
            <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
            <small><?= $_SESSION['role'] === 'admin' ? 'Super Admin' : 'User' ?></small>
        </div>
        <a href="../../logout.php" class="logout-btn">⏻</a>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <div>
            <h1>🖥️ <?= htmlspecialchars($c['container_name']) ?></h1>
            <p style="color:#64748b;font-size:13px;margin-top:4px">Detail dan manajemen container</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-secondary">← Kembali</a>
            <?php if ($c['status'] === 'running'): ?>
            <a href="action.php?action=stop&id=<?= $c['id'] ?>" class="btn btn-warning" onclick="return confirm('Stop container?')">⏹ Stop</a>
            <?php else: ?>
            <a href="action.php?action=start&id=<?= $c['id'] ?>" class="btn btn-success" onclick="return confirm('Start container?')">▶ Start</a>
            <?php endif; ?>
            <a href="action.php?action=delete&id=<?= $c['id'] ?>" class="btn btn-danger" onclick="return confirm('Hapus container? Data hilang permanen.')">🗑 Hapus</a>
        </div>
    </div>

    <div class="grid">
        <div>
            <div class="card">
                <div class="card-title">📋 Informasi Container</div>
                <div class="info-row"><span class="info-label">Nama</span><span class="info-value"><?= htmlspecialchars($c['container_name']) ?></span></div>
                <div class="info-row"><span class="info-label">Status</span><span class="badge badge-<?= $c['status'] ?>">● <?= ucfirst($c['status']) ?></span></div>
                <div class="info-row"><span class="info-label">Domain</span><span class="info-value"><?= htmlspecialchars($c['domain']) ?></span></div>
                <div class="info-row"><span class="info-label">SSH Port</span><span class="info-value"><?= $c['ssh_port'] ?></span></div>
                <div class="info-row"><span class="info-label">Web Port</span><span class="info-value"><?= $c['web_port'] ?></span></div>
                <div class="info-row"><span class="info-label">Console Port</span><span class="info-value"><?= $c['console_port'] ?></span></div>
                <div class="info-row"><span class="info-label">Root Password</span><code><?= htmlspecialchars($c['root_password']) ?></code></div>
                <div class="info-row"><span class="info-label">Dibuat</span><span class="info-value"><?= $c['created_at'] ?></span></div>
                <?php if (isAdmin()): ?>
                <div class="info-row"><span class="info-label">User</span><span class="info-value"><?= htmlspecialchars($c['username']) ?></span></div>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-title">📊 Resource Usage</div>
                <div class="info-row"><span class="info-label">CPU</span><span class="info-value"><?= $stats['cpu'] ?></span></div>
                <div class="info-row"><span class="info-label">Memory</span><span class="info-value"><?= $stats['mem'] ?></span></div>
            </div>

            <div class="card">
                <div class="card-title">🔑 Cara Akses SSH</div>
                <div class="ssh-box">
                    ssh root@xlim.pentester.biz.id -p <?= $c['ssh_port'] ?><br>
                    Password: <?= htmlspecialchars($c['root_password']) ?>
                </div>
            </div>

            <div class="card">
                <div class="card-title">💻 Virtual Console</div>
                <p style="color:#64748b;font-size:13px;margin-bottom:12px">Akses terminal langsung dari browser tanpa SSH client</p>
                <a href="console.php?id=<?= $c['id'] ?>" target="_blank" class="btn console-btn">🖥️ Buka Console</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
