<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../api/docker.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM containers WHERE id = ?");
$stmt->execute([$id]);
$container = $stmt->fetch();

if (!$container) {
    header('Location: index.php?err=Container tidak ditemukan');
    exit;
}

if (!isAdmin() && $container['user_id'] != $_SESSION['user_id']) {
    header('Location: index.php?err=Akses ditolak');
    exit;
}

$name = $container['container_name'];

switch ($action) {
    case 'stop':
        if (stopContainer($name)) {
            $pdo->prepare("UPDATE containers SET status = 'stopped' WHERE id = ?")->execute([$id]);
            header("Location: index.php?msg=Container {$name} berhasil dihentikan");
        } else {
            header("Location: index.php?err=Gagal menghentikan container {$name}");
        }
        break;

    case 'start':
        if (startContainer($name)) {
            $pdo->prepare("UPDATE containers SET status = 'running' WHERE id = ?")->execute([$id]);
            header("Location: index.php?msg=Container {$name} berhasil dijalankan");
        } else {
            header("Location: index.php?err=Gagal menjalankan container {$name}");
        }
        break;

    case 'delete':
        if (deleteContainer($name)) {
            $pdo->prepare("UPDATE containers SET status = 'deleted' WHERE id = ?")->execute([$id]);
            header("Location: index.php?msg=Container {$name} berhasil dihapus");
        } else {
            header("Location: index.php?err=Gagal menghapus container {$name}");
        }
        break;

    default:
        header('Location: index.php');
}
exit;
