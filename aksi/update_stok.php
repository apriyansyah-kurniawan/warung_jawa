<?php
/**
 * aksi/update_stok.php
 * -------------------------------------------------------------------------
 * Handler POST untuk mengubah data stok_keluar.
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

$id              = (int) ($_POST['id'] ?? 0);
$tanggal         = trim($_POST['tanggal'] ?? '');
$nama_bahan      = trim($_POST['nama_bahan'] ?? '');
$jumlah_terpakai = trim($_POST['jumlah_terpakai'] ?? '');
$satuan          = trim($_POST['satuan'] ?? 'Kg');

if ($id <= 0) {
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'ID data tidak valid.'];
    header('Location: ../index.php');
    exit;
}

$errors = [];
if ($tanggal === '') $errors[] = 'Tanggal wajib diisi.';
if ($nama_bahan === '') $errors[] = 'Nama bahan wajib diisi.';
if ($jumlah_terpakai === '' || !is_numeric($jumlah_terpakai) || (float) $jumlah_terpakai <= 0) {
    $errors[] = 'Jumlah terpakai harus angka lebih dari 0.';
}
if (!in_array($satuan, DAFTAR_SATUAN, true)) {
    $errors[] = 'Satuan tidak valid.';
}

if (!empty($errors)) {
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => implode(' ', $errors)];
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare('
    UPDATE stok_keluar
    SET tanggal = :tanggal, nama_bahan = :nama_bahan,
        jumlah_terpakai = :jumlah, satuan = :satuan
    WHERE id = :id
');
$stmt->execute([
    'tanggal'    => $tanggal,
    'nama_bahan' => $nama_bahan,
    'jumlah'     => $jumlah_terpakai,
    'satuan'     => $satuan,
    'id'         => $id,
]);

catat_aktivitas($pdo, 'Update stok keluar ID ' . $id . ': ' . $nama_bahan);

$_SESSION['flash'] = ['tipe' => 'success', 'pesan' => 'Data berhasil diperbarui.'];
header('Location: ../index.php');
exit;