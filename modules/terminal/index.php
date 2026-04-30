<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Terminal - VPS Panel</title>
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
.terminal-card { background: #1e293b; border-radius: 12px; overflow: hidden; }
.terminal-topbar { background: #0f172a; padding: 12px 16px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #334155; }
.dot { width: 12px; height: 12px; border-radius: 50%; }
.dot-red { background: #ef4444; }
.dot-yellow { background: #f59e0b; }
.dot-green { background: #22c55e; }
.terminal-title { color: #64748b; font-size: 13px; margin-left: 8px; }
.terminal-frame { width: 100%; height: calc(100vh - 220px); border: none; background: #000; }
.terminal-note { background: #172033; padding: 12px 20px; font-size: 12px; color: #475569; border-top: 1px solid #334155; }
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
        <?php if (isAdmin()): ?>
        <a href="../users/index.php" class="menu-item"><span>👥</span> Manajemen User</a>
        <?php endif; ?>
        <div class="menu-label" style="margin-top:16px">Tools</div>
        <a href="../voucher/index.php" class="menu-item"><span>🎟️</span> Voucher</a>
        <a href="../terminal/index.php" class="menu-item active"><span>💻</span> Terminal</a>
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
        <h1>💻 Terminal</h1>
        <p style="color:#64748b;font-size:13px;margin-top:4px">Akses terminal server langsung dari browser</p>
    </div>

    <div class="terminal-card">
        <div class="terminal-topbar">
            <div class="dot dot-red"></div>
            <div class="dot dot-yellow"></div>
            <div class="dot dot-green"></div>
            <span class="terminal-title">root@<?= gethostname() ?> — bash</span>
        </div>
        <iframe 
            src= "/shellinabox/" 
            class="terminal-frame"
            allow="clipboard-read; clipboard-write">
        </iframe>
        <div class="terminal-note">
            ⚠️ Terminal ini mengakses langsung ke server host. Gunakan dengan hati-hati.
        </div>
    </div>
</div>
</body>
</html>
