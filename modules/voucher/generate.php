<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$generated = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jumlah = min(intval($_POST['jumlah'] ?? 1), 100);
    $amount = intval($_POST['amount'] ?? 1);
    $prefix = strtoupper(trim($_POST['prefix'] ?? 'VPS'));

    for ($i = 0; $i < $jumlah; $i++) {
        $code = $prefix . '-' . strtoupper(bin2hex(random_bytes(4)));
        $pdo->prepare("INSERT INTO vouchers (code, amount) VALUES (?, ?)")->execute([$code, $amount]);
        $generated[] = $code;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Generate Voucher - VPS Panel</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0f172a;font-family:'Segoe UI',sans-serif;color:#e2e8f0;display:flex;min-height:100vh}
.sidebar{width:240px;background:#1e293b;padding:20px 0;position:fixed;height:100vh}
.sidebar-logo{padding:0 20px 20px;border-bottom:1px solid #334155}
.sidebar-logo h2{color:#38bdf8;font-size:18px}
.sidebar-logo p{color:#64748b;font-size:11px}
.sidebar-menu{padding:20px 0}
.menu-label{color:#475569;font-size:11px;font-weight:600;padding:0 20px;margin-bottom:8px;text-transform:uppercase}
.menu-item{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#94a3b8;text-decoration:none;font-size:14px}
.menu-item:hover,.menu-item.active{background:#0f172a;color:#38bdf8}
.sidebar-user{position:absolute;bottom:0;left:0;right:0;padding:16px 20px;border-top:1px solid #334155;display:flex;align-items:center;gap:10px}
.user-avatar{width:32px;height:32px;background:#38bdf8;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#0f172a;font-weight:700;font-size:14px}
.user-info small{color:#64748b;font-size:11px;display:block}
.logout-btn{margin-left:auto;color:#ef4444;font-size:18px;text-decoration:none}
.main{margin-left:240px;padding:30px;flex:1}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.page-header h1{font-size:24px;color:#f1f5f9}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}
.card{background:#1e293b;border-radius:12px;padding:24px}
.card-title{font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px}
.form-group{margin-bottom:18px}
label{display:block;color:#94a3b8;font-size:13px;margin-bottom:6px}
input{width:100%;padding:10px 14px;background:#0f172a;border:1px solid #334155;border-radius:8px;color:#e2e8f0;font-size:14px;outline:none}
input:focus{border-color:#38bdf8}
.hint{color:#475569;font-size:11px;margin-top:4px}
.btn{padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn-primary{background:#38bdf8;color:#0f172a}
.btn-secondary{background:#334155;color:#e2e8f0}
.voucher-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:16px}
.voucher-item{background:#0f172a;border-radius:8px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;border:1px dashed #334155}
.voucher-code{font-family:monospace;color:#38bdf8;font-size:13px;letter-spacing:1px}
.copy-btn{background:#334155;border:none;color:#e2e8f0;padding:4px 8px;border-radius:6px;cursor:pointer;font-size:11px}
.copy-btn:hover{background:#38bdf8;color:#0f172a}
.success-bar{background:#052e16;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#4ade80;font-size:13px;display:flex;justify-content:space-between;align-items:center}
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
        <a href="../users/index.php" class="menu-item"><span>👥</span> Manajemen User</a>
        <div class="menu-label" style="margin-top:16px">Tools</div>
        <a href="index.php" class="menu-item active"><span>🎟️</span> Voucher</a>
        <a href="../terminal/index.php" class="menu-item"><span>💻</span> Terminal</a>
        <a href="../settings/index.php" class="menu-item"><span>⚙️</span> Pengaturan</a>
    </div>
    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'],0,1)) ?></div>
        <div class="user-info">
            <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
            <small>Super Admin</small>
        </div>
        <a href="../../logout.php" class="logout-btn">⏻</a>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <h1>🎟️ Generate Voucher</h1>
        <a href="index.php" class="btn btn-secondary">← Kembali</a>
    </div>

    <div class="grid">
        <div class="card">
            <div class="card-title">⚙️ Pengaturan Generate</div>
            <form method="POST">
                <div class="form-group">
                    <label>Prefix Kode</label>
                    <input type="text" name="prefix" value="VPS" maxlength="10" style="text-transform:uppercase">
                    <div class="hint">Contoh: VPS → VPS-XXXXXXXX</div>
                </div>
                <div class="form-group">
                    <label>Jumlah Voucher</label>
                    <input type="number" name="jumlah" value="5" min="1" max="100">
                    <div class="hint">Maksimal 100 voucher sekali generate</div>
                </div>
                <div class="form-group">
                    <label>Nilai per Voucher</label>
                    <input type="number" name="amount" value="1" min="1" max="100">
                    <div class="hint">Berapa VPS yang bisa dibuat per voucher</div>
                </div>
                <button type="submit" class="btn btn-primary">🎟️ Generate Sekarang</button>
            </form>
        </div>

        <div class="card">
            <div class="card-title">📋 Hasil Generate</div>
            <?php if(!empty($generated)): ?>
            <div class="success-bar">
                ✅ <?= count($generated) ?> voucher berhasil dibuat
                <button class="copy-btn" onclick="copyAll()">📋 Copy Semua</button>
            </div>
            <div class="voucher-grid">
                <?php foreach($generated as $code): ?>
                <div class="voucher-item">
                    <span class="voucher-code"><?= htmlspecialchars($code) ?></span>
                    <button class="copy-btn" onclick="navigator.clipboard.writeText('<?= $code ?>')">Copy</button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:40px;color:#475569">
                <div style="font-size:48px;margin-bottom:12px">🎟️</div>
                <p>Isi form dan klik Generate</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
function copyAll(){
    const codes=<?= json_encode($generated) ?>;
    navigator.clipboard.writeText(codes.join('\n')).then(()=>alert('Semua kode berhasil dicopy'));
}
</script>
</body>
</html>
