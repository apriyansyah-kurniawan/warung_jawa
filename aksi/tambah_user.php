<?php
/**
 * tambah_user.php
 * -------------------------------------------------------------------------
 * Admin menambah user baru ke sistem.
 * -------------------------------------------------------------------------
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
mulai_session();

try {
    $user_role = strtolower($_SESSION['role'] ?? '');
    if (!in_array($user_role, ['admin'])) {
        $_SESSION['flash'] = [
            'tipe'  => 'danger',
            'pesan' => 'Akses ditolak. Hanya Admin yang diizinkan.',
        ];
        header('Location: ../index.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php');
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = trim($_POST['role'] ?? '');

    $role_valid = ['Admin', 'Kasir', 'Owner'];
    $errors = [];

    if ($username === '' || strlen($username) > 50) {
        $errors[] = 'Username wajib diisi (maks 50 karakter).';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }
    if (!in_array($role, $role_valid, true)) {
        $errors[] = 'Role tidak valid.';
    }

    if (!empty($errors)) {
        $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => implode(' ', $errors)];
        header('Location: ../index.php');
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (:username, :password, :role)');
    $stmt->execute([
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
    ]);

    catat_aktivitas($pdo, 'Tambah user: ' . $username . ' (' . $role . ')');
    $_SESSION['flash'] = ['tipe' => 'success', 'pesan' => "User '{$username}' berhasil ditambahkan."];
} catch (Throwable $e) {
    // Log error for debugging (optional)
    error_log("Error in tambah_user.php: " . $e->getMessage());
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Terjadi kesalahan sistem.'];
}

header('Location: ../index.php');
exit;