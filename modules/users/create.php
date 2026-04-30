<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $voucher = intval($_POST['voucher'] ?? 0);

    if (!$username || !$password) {
        $error = 'Username dan password wajib diisi';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
            $error = 'Username sudah digunakan';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, voucher_balance) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $email, $role, $voucher]);
            header('Location: index.php?msg=User ' . $username . ' berhasil dibuat');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tambah User - VPS Panel</title>
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
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 24px; color: #f1f5f9; }
.card { background: #1e293b; border-radius: 12px; padding: 30px; max-width: 550px; }
.form-group { margin-bottom: 20px; }
label { display: block; color: #94a3b8; font-size: 13px; margin-bottom: 6px; }
input, select { width: 100%; padding: 10px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px; outline: none; transition: border-color 0.2s; }
input:focus, select:focus { border-color: #38bdf8; }
select option { background: #1e293b; }
.btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
.btn-primary { background: #38bdf8; color: #0f172a; }
.btn-secondary { background: #334155; color: #e2e8f0; }
.alert-danger { background: #450a0a; color: #f87171; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
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
    <div class="page-header">
        <h1>➕ Tambah User Baru</h1>
    </div>

    <?php if ($error): ?>
    <div class="alert-danger">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" placeholder="username" required>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" placeholder="password" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="email@domain.com">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Voucher Balance</label>
                    <input type="number" name="voucher" value="0" min="0">
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:8px">
                <button type="submit" class="btn btn-primary">💾 Simpan User</button>
                <a href="index.php" class="btn btn-secondary">← Batal</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
