<?php
require_once 'config/database.php';
require_once 'config/auth.php';

// ... (kode pengecekan isLoggedIn) ...

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim membantu menghapus spasi di awal/akhir input secara otomatis
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // BERHASIL LOGIN
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username atau password salah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - VPS Panel</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #0f172a; font-family: 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
.card { background: #1e293b; border-radius: 12px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
.logo { text-align: center; margin-bottom: 30px; }
.logo h1 { color: #38bdf8; font-size: 24px; }
.logo p { color: #64748b; font-size: 13px; margin-top: 4px; }
.form-group { margin-bottom: 20px; }
label { display: block; color: #94a3b8; font-size: 13px; margin-bottom: 6px; }
input { width: 100%; padding: 10px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px; outline: none; transition: border-color 0.2s; }
input:focus { border-color: #38bdf8; }
.btn { width: 100%; padding: 12px; background: #38bdf8; color: #0f172a; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
.btn:hover { background: #0ea5e9; }
.error { background: #450a0a; color: #fca5a5; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
</style>
</head>
<body>
<div class="card">
    <div class="logo">
        <h1>⚡ VPS Panel</h1>
        <p>VPS Docker Management</p>
    </div>
    <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Masukkan username" required autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Masukkan password" required>
        </div>
        <button type="submit" class="btn">Login</button>
    </form>
</div>
</body>
</html>
