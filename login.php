<?php
/**
 * login.php — Halaman login modern dengan audit log & session timeout message.
 */
require_once 'config.php';
require_once 'includes/auth.php';

mulai_session();

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$timeout = isset($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['last_activity'] = time();

            catat_aktivitas($pdo, 'Login berhasil sebagai ' . $user['role']);

            header('Location: index.php');
            exit;
        }

        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Warung Jawa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-header">
            <h1>🍛 Warung Jawa</h1>
            <p>Silakan login untuk mengakses Dashboard Warung Jawa</p>
        </div>
        <div class="login-body">
            <?php if ($timeout): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Sesi Habis',
                            text: 'Sesi Anda telah berakhir. Silakan login kembali.',
                            confirmButtonColor: '#dc3545'
                        });
                    });
                </script>
            <?php endif; ?>
            <?php if ($error): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: <?= json_encode($error) ?>,
                            confirmButtonColor: '#dc3545'
                        });
                    });
                </script>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-success w-100 py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
