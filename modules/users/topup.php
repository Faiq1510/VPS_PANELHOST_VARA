<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php?err=User tidak ditemukan');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = intval($_POST['amount'] ?? 0);
    if ($amount > 0) {
        $pdo->prepare("UPDATE users SET voucher_balance = voucher_balance + ? WHERE id = ?")->execute([$amount, $id]);
        header('Location: index.php?msg=Berhasil topup ' . $amount . ' voucher ke ' . $user['username']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Topup Voucher - VPS Panel</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #0f172a; font-family: 'Segoe UI', sans-serif; color: #e2e8f0; display: flex; min-height: 100vh; }
.sidebar { width: 240px; background: #1e293b; padding: 20px 0; position: fixed; height: 100vh; }
.sidebar-logo { padding: 0 20px 20px; border-bottom: 1px solid #334155; }
.sidebar-logo h2 { color: #38bdf8; font-size: 18px; }
.sidebar-logo p { color: #64748b; font-size: 11px; }
.sidebar-menu { padding: 20px 0; }
.menu-label { color: #475569; font-size: 11px; font-weight: 600; padding: 0 20px; margin-bottom: 8px; text-transform: uppercase; }
.menu-item { display: flex; align-items: center; gap: 10px; padding: 10px 20px; color: #94a3b8; text-decoration: none; font-size: 14px; }
.menu-item:hover, .menu-item.active { background: #0f172a; color: #38bdf8; }
.sidebar-user { position: absolute; bottom: 0; left: 0; right: 0; padding: 16px 20px; border-top: 1px solid #334155; display: flex; align-items: center; gap: 10px; }
.user-avatar { width: 32px; height: 32px; background: #38bdf8; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #0f172a; font-weight: 700; font-size: 14px; }
.user-info small { color: #64748b; font-size: 11px; display: block; }
.logout-btn { margin-left: auto; color: #ef4444; font-size: 18px; text-decoration: none; }
.main { margin-left: 240px; padding: 30px; flex: 1; }
.card { background: #1e293b; border-radius: 12px; padding: 30px; max-width: 450px; }
.form-group { margin-bottom: 20px; }
label { display: block; color: #94a3b8; font-size: 13px; margin-bottom: 6px; }
input { width: 100%; padding: 10px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px; outline: none; }
input:focus { border-color: #38bdf8; }
.btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
.btn-success { background: #22c55e; color: white; }
.btn-secondary { background: #334155; color: #e2e8f0; }
.user-info-box { background: #0f172a; border-radius: 8px; padding: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
.big-avatar { width: 48px; height: 48px; background: #38bdf8; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #0f172a; font-weight: 700; font-size: 20px; }
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
        <a href="../containers/index.php" class="menu-item"><span>🖥️</span> VPS Containers</a>
        <a href="index.php" class="menu-item active"><span>👥</span> Manajemen User</a>
        <div class="menu-label" style="margin-top:16px">Tools</div>
        <a href="../voucher/index.php" class="menu-item"><span>🎟️</span> Voucher</a>
        <a href="../terminal/index.php" class="menu-item"><span>💻</span> Terminal</a>
        <a href="../settings/index.php" class="menu-item"><span>⚙️</span> Pengaturan</a>
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
    <h1 style="font-size:24px;color:#f1f5f9;margin-bottom:24px">🎟️ Topup Voucher</h1>

    <div class="card">
        <div class="user-info-box">
            <div class="big-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
            <div>
                <div style="font-weight:600;font-size:16px"><?= htmlspecialchars($user['username']) ?></div>
                <div style="color:#64748b;font-size:13px">Saldo saat ini: <span style="color:#38bdf8;font-weight:600"><?= $user['voucher_balance'] ?> voucher</span></div>
            </div>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Jumlah Voucher yang Ditambahkan</label>
                <input type="number" name="amount" min="1" value="1" required>
            </div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-success">➕ Topup Sekarang</button>
                <a href="index.php" class="btn btn-secondary">← Batal</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
