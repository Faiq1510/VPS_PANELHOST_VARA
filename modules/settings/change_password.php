<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!password_verify($old, $user['password'])) {
        header('Location: index.php?err=Password lama salah');
        exit;
    }
    if ($new !== $confirm) {
        header('Location: index.php?err=Konfirmasi password tidak cocok');
        exit;
    }
    if (strlen($new) < 6) {
        header('Location: index.php?err=Password minimal 6 karakter');
        exit;
    }

    $hash = password_hash($new, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $_SESSION['user_id']]);
    header('Location: index.php?msg=Password berhasil diubah');
    exit;
}
header('Location: index.php');
