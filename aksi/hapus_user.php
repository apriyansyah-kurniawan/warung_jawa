<?php
/**
 * hapus_user.php
 * -------------------------------------------------------------------------
 * Admin menghapus user (tidak boleh hapus diri sendiri).
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

$id_hapus = (int) ($_POST['id'] ?? 0);
$id_login = (int) $_SESSION['user_id'];

if ($id_hapus <= 0) {
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'ID user tidak valid.'];
    header('Location: ../index.php');
    exit;
}

if ($id_hapus === $id_login) {
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Tidak dapat menghapus akun yang sedang login.'];
    header('Location: ../index.php');
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute(['id' => $id_hapus]);

    if ($stmt->rowCount() > 0) {
        catat_aktivitas($pdo, 'Hapus user ID ' . $id_hapus);
        $_SESSION['flash'] = ['tipe' => 'success', 'pesan' => 'User berhasil dihapus.'];
    } else {
        $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'User tidak ditemukan.'];
    }
} catch (PDOException $e) {
    $_SESSION['flash'] = [
        'tipe'  => 'danger',
        'pesan' => 'User tidak dapat dihapus karena masih memiliki data transaksi.',
    ];
}

header('Location: ../index.php');
exit;