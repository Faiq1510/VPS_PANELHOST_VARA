<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireLogin();

$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_code'])) {
    $code = strtoupper(trim($_POST['redeem_code']));
    $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE code = ? AND used_by IS NULL");
    $stmt->execute([$code]);
    $voucher = $stmt->fetch();
    if (!$voucher) {
        $err = 'Kode voucher tidak valid atau sudah digunakan';
    } else {
        $pdo->prepare("UPDATE vouchers SET used_by=?, used_at=NOW() WHERE id=?")->execute([$_SESSION['user_id'], $voucher['id']]);
        $pdo->prepare("UPDATE users SET voucher_balance=voucher_balance+? WHERE id=?")->execute([$voucher['amount'], $_SESSION['user_id']]);
        $msg = 'Voucher berhasil diredeem. Saldo bertambah ' . $voucher['amount'];
    }
}

if (isAdmin()) {
    $vouchers = $pdo->query("SELECT v.*, u.username as used_by_name FROM vouchers v LEFT JOIN users u ON v.used_by=u.id ORDER BY v.created_at DESC")->fetchAll();
    $totalVouchers = $pdo->query("SELECT COUNT(*) FROM vouchers")->fetchColumn();
    $usedVouchers = $pdo->query("SELECT COUNT(*) FROM vouchers WHERE used_by IS NOT NULL")->fetchColumn();
    $availableVouchers = $totalVouchers - $usedVouchers;
} else {
    $stmt = $pdo->prepare("SELECT voucher_balance FROM users WHERE id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $balance = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Voucher - VPS Panel</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0f172a;font-family:'Segoe UI',sans-serif;color:#e2e8f0;display:flex;min-height:100vh}
.sidebar{width:240px;background:#1e293b;padding:20px 0;position:fixed;height:100vh;overflow-y:auto}
.sidebar-logo{padding:0 20px 20px;border-bottom:1px solid #334155}
.sidebar-logo h2{color:#38bdf8;font-size:18px}
.sidebar-logo p{color:#64748b;font-size:11px}
.sidebar-menu{padding:20px 0}
.menu-label{color:#475569;font-size:11px;font-weight:600;padding:0 20px;margin-bottom:8px;text-transform:uppercase;letter-spacing:1px}
.menu-item{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#94a3b8;text-decoration:none;font-size:14px;transition:all .2s}
.menu-item:hover,.menu-item.active{background:#0f172a;color:#38bdf8}
.sidebar-user{position:absolute;bottom:0;left:0;right:0;padding:16px 20px;border-top:1px solid #334155;display:flex;align-items:center;gap:10px}
.user-avatar{width:32px;height:32px;background:#38bdf8;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#0f172a;font-weight:700;font-size:14px}
.user-info small{color:#64748b;font-size:11px;display:block}
.logout-btn{margin-left:auto;color:#ef4444;font-size:18px;text-decoration:none}
.main{margin-left:240px;padding:30px;flex:1}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.page-header h1{font-size:24px;color:#f1f5f9}
.card{background:#1e293b;border-radius:12px;padding:24px;margin-bottom:24px}
.card-title{font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px}
.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
.stat-card{background:#1e293b;border-radius:12px;padding:20px;text-align:center}
.stat-value{font-size:32px;font-weight:700;color:#f1f5f9}
.stat-label{color:#64748b;font-size:12px;margin-top:4px;text-transform:uppercase;letter-spacing:1px}
.btn{padding:8px 16px;border-radius:8px;border:none;cursor:pointer;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:opacity .2s}
.btn:hover{opacity:.85}
.btn-primary{background:#38bdf8;color:#0f172a}
.btn-danger{background:#ef4444;color:#fff}
.btn-secondary{background:#334155;color:#e2e8f0}
table{width:100%;border-collapse:collapse}
th{text-align:left;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:1px;padding:10px 12px;border-bottom:1px solid #334155}
td{padding:12px;border-bottom:1px solid #0f172a;font-size:14px;color:#cbd5e1}
tr:last-child td{border-bottom:none}
tr:hover td{background:#0f172a22}
.badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.badge-available{background:#052e16;color:#4ade80}
.badge-used{background:#1e1e2e;color:#64748b}
.alert{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:13px}
.alert-success{background:#052e16;color:#4ade80}
.alert-danger{background:#450a0a;color:#f87171}
.balance-box{background:#0f172a;border-radius:12px;padding:30px;text-align:center;margin-bottom:24px}
.balance-value{font-size:56px;font-weight:700;color:#38bdf8}
.balance-label{color:#64748b;font-size:14px;margin-top:8px}
.redeem-form{display:flex;gap:10px;justify-content:center;margin-top:20px}
.redeem-form input{padding:10px 14px;background:#1e293b;border:1px solid #334155;border-radius:8px;color:#e2e8f0;font-size:14px;outline:none;width:250px;text-transform:uppercase}
.redeem-form input:focus{border-color:#38bdf8}
.code-text{font-family:monospace;color:#38bdf8;letter-spacing:1px;cursor:pointer}
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
        <?php if(isAdmin()): ?>
        <a href="../users/index.php" class="menu-item"><span>👥</span> Manajemen User</a>
        <?php endif; ?>
        <div class="menu-label" style="margin-top:16px">Tools</div>
        <a href="index.php" class="menu-item active"><span>🎟️</span> Voucher</a>
        <a href="../terminal/index.php" class="menu-item"><span>💻</span> Terminal</a>
        <a href="../settings/index.php" class="menu-item"><span>⚙️</span> Pengaturan</a>
    </div>
    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'],0,1)) ?></div>
        <div class="user-info">
            <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
            <small><?= $_SESSION['role']==='admin'?'Super Admin':'User' ?></small>
        </div>
        <a href="../../logout.php" class="logout-btn">⏻</a>
    </div>
</div>

<div class="main">
    <?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if($err): ?><div class="alert alert-danger">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

    <?php if(isAdmin()): ?>
    <div class="page-header">
        <h1>🎟️ Manajemen Voucher</h1>
        <a href="generate.php" class="btn btn-primary">+ Generate Voucher</a>
    </div>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $totalVouchers ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:#4ade80"><?= $availableVouchers ?></div>
            <div class="stat-label">Tersedia</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:#64748b"><?= $usedVouchers ?></div>
            <div class="stat-label">Terpakai</div>
        </div>
    </div>
    <div class="card">
        <div class="card-title">📋 Daftar Voucher</div>
        <?php if(empty($vouchers)): ?>
        <div style="text-align:center;padding:40px;color:#475569">
            <div style="font-size:48px;margin-bottom:12px">🎟️</div>
            <p>Belum ada voucher. <a href="generate.php" style="color:#38bdf8">Generate sekarang</a></p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nilai</th>
                    <th>Status</th>
                    <th>Digunakan Oleh</th>
                    <th>Tanggal Pakai</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($vouchers as $v): ?>
                <tr>
                    <td><span class="code-text" onclick="navigator.clipboard.writeText('<?= $v['code'] ?>')" title="Klik untuk copy"><?= htmlspecialchars($v['code']) ?></span></td>
                    <td><span style="color:#38bdf8;font-weight:600">+<?= $v['amount'] ?></span></td>
                    <td><?php if($v['used_by']): ?><span class="badge badge-used">✓ Terpakai</span><?php else: ?><span class="badge badge-available">● Tersedia</span><?php endif; ?></td>
                    <td><?= $v['used_by_name'] ? htmlspecialchars($v['used_by_name']) : '-' ?></td>
                    <td style="color:#64748b;font-size:12px"><?= $v['used_at'] ? date('d/m/Y H:i', strtotime($v['used_at'])) : '-' ?></td>
                    <td>
                        <?php if(!$v['used_by']): ?>
                        <a href="delete.php?id=<?= $v['id'] ?>" class="btn btn-danger" style="padding:4px 10px;font-size:12px" onclick="return confirm('Hapus voucher ini?')">Hapus</a>
                        <?php else: ?>
                        <span style="color:#475569;font-size:12px">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="page-header">
        <h1>🎟️ Voucher Saya</h1>
    </div>
    <div class="balance-box">
        <div class="balance-value"><?= $balance ?></div>
        <div class="balance-label">Saldo Voucher Tersisa</div>
        <p style="color:#475569;font-size:12px;margin-top:8px">1 voucher = 1 VPS container</p>
        <form method="POST" class="redeem-form">
            <input type="text" name="redeem_code" placeholder="KODE-VOUCHER" required>
            <button type="submit" class="btn btn-primary">✅ Redeem</button>
        </form>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
