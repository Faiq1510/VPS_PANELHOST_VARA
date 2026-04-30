<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$id = intval($_GET['id'] ?? 0);

if ($id == $_SESSION['user_id']) {
    header('Location: index.php?err=Tidak bisa hapus akun sendiri');
    exit;
}

$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php?err=User tidak ditemukan');
    exit;
}

$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
header('Location: index.php?msg=User ' . $user['username'] . ' berhasil dihapus');
exit;
