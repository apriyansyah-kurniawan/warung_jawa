<?php
/**
 * aksi/hapus_stok.php
 * -------------------------------------------------------------------------
 * Handler POST untuk menghapus data stok_keluar.
 * Hanya role Admin dan Owner yang diizinkan.
 * -------------------------------------------------------------------------
 */
require_once '../config.php';
require_once '../includes/auth.php';
mulai_session();
// Hanya role Admin dan Owner yang diizinkan
cek_role(['admin', 'owner']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'ID data tidak valid.'];
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare('DELETE FROM stok_keluar WHERE id = :id');
$stmt->execute(['id' => $id]);

if ($stmt->rowCount() > 0) {
    catat_aktivitas($pdo, 'Hapus stok keluar ID ' . $id);
    $_SESSION['flash'] = ['tipe' => 'success', 'pesan' => 'Data berhasil dihapus.'];
} else {
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Data tidak ditemukan.'];
}

header('Location: ../index.php');
exit;