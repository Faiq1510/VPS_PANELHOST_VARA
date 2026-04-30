<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM containers WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();

if (!$c || (!isAdmin() && $c['user_id'] != $_SESSION['user_id'])) {
    die('Akses ditolak');
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Console - <?= htmlspecialchars($c['container_name']) ?></title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #000; display: flex; flex-direction: column; height: 100vh; }
.topbar { background: #1e293b; padding: 10px 16px; display: flex; align-items: center; gap: 10px; }
.dot { width: 12px; height: 12px; border-radius: 50%; }
.dot-red { background: #ef4444; }
.dot-yellow { background: #f59e0b; }
.dot-green { background: #22c55e; }
.title { color: #94a3b8; font-size: 13px; font-family: monospace; margin-left: 8px; }
iframe { flex: 1; border: none; width: 100%; }
</style>
</head>
<body>
<div class="topbar">
    <div class="dot dot-red"></div>
    <div class="dot dot-yellow"></div>
    <div class="dot dot-green"></div>
    <span class="title">root@<?= htmlspecialchars($c['container_name']) ?> — bash (port <?= $c['console_port'] ?>)</span>
</div>
<iframe src="/shellinabox/"></iframe>
</body>
</html>
