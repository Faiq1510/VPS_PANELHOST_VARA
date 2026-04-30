<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT code FROM vouchers WHERE id=? AND used_by IS NULL");
$stmt->execute([$id]);
$v = $stmt->fetch();
if (!$v) {
    header('Location: index.php?err=Voucher tidak ditemukan atau sudah digunakan');
    exit;
}
$pdo->prepare("DELETE FROM vouchers WHERE id=?")->execute([$id]);
header('Location: index.php?msg=Voucher ' . $v['code'] . ' berhasil dihapus');
exit;
