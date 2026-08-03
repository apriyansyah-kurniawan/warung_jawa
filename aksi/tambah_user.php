<?php
/**
 * tambah_user.php
 * -------------------------------------------------------------------------
 * Admin menambah user baru ke sistem.
 * -------------------------------------------------------------------------
 */
require_once '../config.php';
require_once '../includes/auth.php';
mulai_session();
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

try {
    $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (:username, :password, :role)');
    $stmt->execute([
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role'role,
    ]);

    catat_aktivitas($pdo, 'Tambah user: ' . $username . ' (' . $role . ')');
    $_SESSION['flash'] = ['tipe' => 'success', 'pesan' => "User '{$username}' berhasil ditambahkan."];
} catch (PDOException $e) {
    $pesan = str_contains($e->getMessage(), 'Duplicate') ? 'Username sudah digunakan.' : 'Gagal menambah user.';
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => $pesan];
}

header('Location: ../index.php');
exit;