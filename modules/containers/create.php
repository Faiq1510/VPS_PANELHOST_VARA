<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../api/docker.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $pdo->prepare("SELECT voucher_balance FROM users WHERE id = ?");
    $user->execute([$_SESSION['user_id']]);
    $balance = $user->fetchColumn();

    if ($balance <= 0 && !isAdmin()) {
        $error = 'Saldo voucher tidak cukup. Minta voucher ke admin.';
    } else {
        $result = createContainer($pdo, $_SESSION['user_id']);
        if ($result['success']) {
            if (!isAdmin()) {
                $pdo->prepare("UPDATE users SET voucher_balance = voucher_balance - 1 WHERE id = ?")->execute([$_SESSION['user_id']]);
            }
            $success = "Container berhasil dibuat! Nama: {$result['container']} | SSH Port: {$result['ssh_port']} | Password: {$result['password']}";
        } else {
            $error = 'Gagal membuat container: ' . $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Buat VPS - VPS Panel</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #0f172a; font-family: 'Segoe UI', sans-serif; color: #e2e8f0; display: flex; min-height: 100vh; }
.sidebar { width: 240px; background: #1e293b; padding: 20px 0; position: fixed; height: 100vh; }
.sidebar-logo { padding: 0 20px 20px; border-bottom: 1px solid #334155; }
.sidebar-logo h2 { color: #38bdf8; font-size: 18px; }
.sidebar-logo p { color: #64748b; font-size: 11px; }
.sidebar-menu { padding: 20px 0; }
.menu-label { color: #475569; font-size: 11px; font-weight: 600; padding: 0 20px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
.menu-item { display: flex; align-items: center; gap: 10px; padding: 10px 20px; color: #94a3b8; text-decoration: none; font-size: 14px; transition: all 0.2s; }
.menu-item:hover, .menu-item.active { background: #0f172a; color: #38bdf8; }
.sidebar-user { position: absolute; bottom: 0; left: 0; right: 0; padding: 16px 20px; border-top: 1px solid #334155; display: flex; align-items: center; gap: 10px; }
.user-avatar { width: 32px; height: 32px; background: #38bdf8; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #0f172a; font-weight: 700; font-size: 14px; }
.user-info small { color: #64748b; font-size: 11px; display: block; }
.logout-btn { margin-left: auto; color: #ef4444; font-size: 18px; text-decoration: none; }
.main { margin-left: 240px; padding: 30px; flex: 1; }
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 24px; color: #f1f5f9; }
.card { background: #1e293b; border-radius: 12px; padding: 30px; max-width: 600px; }
.btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
.btn-primary { background: #38bdf8; color: #0f172a; }
.btn-secondary { background: #334155; color: #e2e8f0; }
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }
.alert-success { background: #052e16; color: #4ade80; }
.alert-danger { background: #450a0a; color: #f87171; }
.spec-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 20px 0; }
.spec-item { background: #0f172a; border-radius: 8px; padding: 16px; text-align: center; }
.spec-value { font-size: 20px; font-weight: 700; color: #38bdf8; }
.spec-label { color: #64748b; font-size: 12px; margin-top: 4px; }
.divider { border: none; border-top: 1px solid #334155; margin: 20px 0; }
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
        <h1>➕ Buat VPS Baru</h1>
        <p style="color:#64748b;font-size:13px;margin-top:4px">Container Docker baru akan dibuat otomatis</p>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success">
        ✅ <?= htmlspecialchars($success) ?><br>
        <a href="index.php" style="color:#4ade80;margin-top:8px;display:inline-block">← Kembali ke daftar container</a>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <div class="card">
        <h3 style="color:#f1f5f9;margin-bottom:16px">Spesifikasi VPS</h3>
        <div class="spec-grid">
            <div class="spec-item">
                <div class="spec-value">512 MB</div>
                <div class="spec-label">RAM</div>
            </div>
            <div class="spec-item">
                <div class="spec-value">0.5 CPU</div>
                <div class="spec-label">vCPU</div>
            </div>
            <div class="spec-item">
                <div class="spec-value">Ubuntu 22.04</div>
                <div class="spec-label">OS</div>
            </div>
            <div class="spec-item">
                <div class="spec-value">SSH + Console</div>
                <div class="spec-label">Akses</div>
            </div>
        </div>
        <hr class="divider">
        <p style="color:#64748b;font-size:13px;margin-bottom:20px">
            Container akan dibuat otomatis dengan port SSH, web, dan console yang unik.
            Password root akan di-generate secara acak.
        </p>
        <form method="POST" style="display:flex;gap:10px">
            <button type="submit" class="btn btn-primary">🚀 Buat VPS Sekarang</button>
            <a href="index.php" class="btn btn-secondary">← Batal</a>
        </form>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
