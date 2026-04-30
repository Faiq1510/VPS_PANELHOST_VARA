<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();
$pdo->query("DELETE FROM containers WHERE status = 'deleted'");
header('Location: index.php?msg=Data container yang dihapus berhasil dibersihkan');
exit;
