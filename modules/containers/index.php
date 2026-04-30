<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireLogin();

if (isAdmin()) {
    $containers = $pdo->query("SELECT c.*, u.username FROM containers c JOIN users u ON c.user_id = u.id WHERE c.status != 'deleted' ORDER BY c.created_at DESC")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM containers WHERE user_id = ? AND status != 'deleted' ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $containers = $stmt->fetchAll();
}

$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VPS Containers - VPS Panel</title>
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
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header h1 { font-size: 24px; color: #f1f5f9; }
.card { background: #1e293b; border-radius: 12px; padding: 24px; }
.btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: opacity 0.2s; }
.btn:hover { opacity: 0.85; }
.btn-primary { background: #38bdf8; color: #0f172a; }
.btn-danger { background: #ef4444; color: white; }
.btn-success { background: #22c55e; color: white; }
.btn-secondary { background: #334155; color: #e2e8f0; }
.btn-warning { background: #f59e0b; color: #0f172a; }
table { width: 100%; border-collapse: collapse; }
th { text-align: left; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; padding: 10px 12px; border-bottom: 1px solid #334155; }
td { padding: 12px; border-bottom: 1px solid #0f172a; font-size: 14px; color: #cbd5e1; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: #0f172a33; }
.badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-running { background: #052e16; color: #4ade80; }
.badge-stopped { background: #450a0a; color: #f87171; }
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }
.alert-success { background: #052e16; color: #4ade80; }
.alert-danger { background: #450a0a; color: #f87171; }
.empty { text-align: center; padding: 40px; color: #475569; }
.empty-icon { font-size: 48px; margin-bottom: 12px; }
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
        <a href="../../logout.php" class="logout-btn" title="Logout">⏻</a>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <h1>🖥️ VPS Containers</h1>
        <a href="create.php" class="btn btn-primary">+ Buat VPS</a>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
    <div class="alert alert-danger">❌ <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <div class="card">
        <?php if (empty($containers)): ?>
        <div class="empty">
            <div class="empty-icon">🖥️</div>
            <p>Belum ada container</p>
            <a href="create.php" class="btn btn-primary" style="margin-top:16px">+ Buat VPS Sekarang</a>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Container</th>
                    <th>Domain</th>
                    <th>SSH Port</th>
                    <th>Password</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($containers as $c): ?>
                <tr>
                    <td style="color:#38bdf8;font-weight:600"><?= htmlspecialchars($c['container_name']) ?></td>
                    <td><?= htmlspecialchars($c['domain']) ?></td>
                    <td><?= $c['ssh_port'] ?></td>
                    <td><code style="background:#0f172a;padding:2px 8px;border-radius:4px;font-size:12px"><?= htmlspecialchars($c['root_password']) ?></code></td>
                    <td><span class="badge badge-<?= $c['status'] ?>">● <?= ucfirst($c['status']) ?></span></td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap">
                        <a href="detail.php?id=<?= $c['id'] ?>" class="btn btn-secondary">Detail</a>
                        <?php if ($c['status'] === 'running'): ?>
                        <a href="action.php?action=stop&id=<?= $c['id'] ?>" class="btn btn-warning" onclick="return confirm('Stop container ini?')">Stop</a>
                        <?php else: ?>
                        <a href="action.php?action=start&id=<?= $c['id'] ?>" class="btn btn-success" onclick="return confirm('Start container ini?')">Start</a>
                        <?php endif; ?>
                        <a href="action.php?action=delete&id=<?= $c['id'] ?>" class="btn btn-danger" onclick="return confirm('Hapus container ini? Data akan hilang permanen.')">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
