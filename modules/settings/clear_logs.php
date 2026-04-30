<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();
shell_exec("truncate -s 0 /var/log/apache2/xlim_error.log 2>/dev/null");
shell_exec("truncate -s 0 /var/log/apache2/xlim_access.log 2>/dev/null");
header('Location: index.php?msg=Log berhasil dibersihkan');
exit;
