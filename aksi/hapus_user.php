<?php
/**
 * hapus_user.php
 * -------------------------------------------------------------------------
 * Admin menghapus user (tidak boleh hapus diri sendiri).
 * -------------------------------------------------------------------------
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
mulai_session();
$user_role = strtolower($_SESSION['role'] ?? '');
if (!in_array($user_role, ['admin'])) {
    $_SESSION['flash'] = [
        'tipe' => 'danger',
        'pesan' => 'Akses ditolak. Hanya Admin yang diizinkan.'
    ];
    header('Location: ../index.php');
    exit;
}

// Get target user ID from GET or POST with fallback parameter names
$id_target_raw = $_GET['id'] ?? $_POST['id'] ?? $_GET['id_user'] ?? $_POST['id_user'] ?? null;

// Validate ID target
if ($id_target_raw === null || $id_target_raw === '' || !is_numeric($id_target_raw) || (int)$id_target_raw <= 0) {
    $_SESSION['flash'] = [
        'tipe' => 'danger',
        'pesan' => 'ID User tidak valid.'
    ];
    header('Location: ../index.php?page=user');
    exit;
}
$id_target = (int)$id_target_raw;

// Get current user ID
$current_user_id_raw = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if ($current_user_id_raw === null || $current_user_id_raw === '' || !is_numeric($current_user_id_raw)) {
    // Should not happen if logged in, but handle gracefully
    $_SESSION['flash'] = [
        'tipe' => 'danger',
        'pesan' => 'Sesi tidak valid. Silakan login kembali.'
    ];
    header('Location: ../login.php');
    exit;
}
$current_user_id = (int)$current_user_id_raw;

// Prevent self-deletion
if ($id_target === $current_user_id) {
    $_SESSION['flash'] = [
        'tipe' => 'danger',
        'pesan' => 'Tidak dapat menghapus akun yang sedang digunakan!'
    ];
    header('Location: ../index.php?page=user');
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Set user_id to NULL in related tables to preserve data but remove user reference
    $tablesToUpdate = [
        ['table' => 'penjualan', 'column' => 'id_user'],
        ['table' => 'riwayat_aktivitas', 'column' => 'user_id'],
        ['table' => 'stok_keluar', 'column' => 'id_user'],
        ['table' => 'stok_masuk', 'column' => 'id_user']
    ];

    foreach ($tablesToUpdate as $tableInfo) {
        $stmt = $pdo->prepare("UPDATE {$tableInfo['table']} SET {$tableInfo['column']} = NULL WHERE {$tableInfo['column']} = :id");
        $stmt->execute(['id' => $id_target]);
    }

    // Now delete the user
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute(['id' => $id_target]);

    if ($stmt->rowCount() > 0) {
        catat_aktivitas($pdo, 'Hapus user ID ' . $id_target . ' (mengatur ID user menjadi NULL di tabel terkait)');
        $_SESSION['flash'] = [
            'tipe' => 'success',
            'pesan' => 'User berhasil dihapus. Data transaksi terkait telah dipertahankan dengan referensi user yang dihapus.'
        ];
    } else {
        $_SESSION['flash'] = [
            'tipe' => 'danger',
            'pesan' => 'User tidak ditemukan.'
        ];
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash'] = [
        'tipe' => 'danger',
        'pesan' => 'Terjadi kesalahan saat menghapus user: ' . $e->getMessage()
    ];
}

header('Location: ../index.php?page=user');
exit;