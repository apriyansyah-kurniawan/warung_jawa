<?php
/**
 * hapus_stok_masuk.php
 * -------------------------------------------------------------------------
 * Admin menghapus data stok masuk.
 * Hanya role Owner yang diizinkan.
 * -------------------------------------------------------------------------
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
mulai_session();

// Hanya role Owner yang diizinkan
$user_role = strtolower($_SESSION['role'] ?? '');
if (!in_array($user_role, ['owner'])) {
    $_SESSION['flash'] = [
        'tipe' => 'danger',
        'pesan' => 'Akses ditolak. Hanya Owner yang diizinkan.'
    ];
    header('Location: ../index.php');
    exit;
}

// Get target stok masuk ID from GET or POST with fallback parameter names
$id_target = $_GET['id'] ?? $_POST['id'] ?? $_GET['id_stok_masuk'] ?? $_POST['id_stok_masuk'] ?? null;

// Validate ID target
if ($id_target === null || $id_target === '' || !is_numeric($id_target) || (int)$id_target <= 0) {
    $_SESSION['flash'] = [
        'tipe' => 'danger',
        'pesan' => 'ID stok masuk tidak valid.'
    ];
    header('Location: ../index.php');
    exit;
}
$id_target = (int)$id_target;

try {
    $stmt = $pdo->prepare('DELETE FROM stok_masuk WHERE id = :id');
    $stmt->execute(['id' => $id_target]);

    if ($stmt->rowCount() > 0) {
        catat_aktivitas($pdo, 'Hapus stok masuk ID ' . $id_target);
        $_SESSION['flash'] = [
            'tipe' => 'success',
            'pesan' => 'Stok masuk berhasil dihapus.'
        ];
    } else {
        $_SESSION['flash'] = [
            'tipe' => 'danger',
            'pesan' => 'Stok masuk tidak ditemukan.'
        ];
    }
} catch (PDOException $e) {
    $_SESSION['flash'] = [
        'tipe' => 'danger',
        'pesan' => 'Data tidak dapat dihapus karena masih memiliki dependensi.'
    ];
}

header('Location: ../index.php');
exit;
?>