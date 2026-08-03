<?php
/**
 * simpan_stok.php
 * -------------------------------------------------------------------------
 * Handler koreksi manual stok_keluar (HANYA Admin).
 * Kasir tidak lagi input bahan mentah — stok keluar otomatis dari penjualan.
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

$tanggal         = trim($_POST['tanggal'] ?? '');
$nama_bahan      = trim($_POST['nama_bahan'] ?? '');
$jumlah_terpakai = trim($_POST['jumlah_terpakai'] ?? '');
$satuan          = trim($_POST['satuan'] ?? 'Kg');
$id_user         = (int) $_SESSION['user_id'];

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
    INSERT INTO stok_keluar (tanggal, nama_bahan, jumlah_terpakai, satuan, id_user)
    VALUES (:tanggal, :nama_bahan, :jumlah, :satuan, :id_user)
');
$stmt->execute([
    'tanggal'    => $tanggal,
    'nama_bahan' => $nama_bahan,
    'jumlah'     => $jumlah_terpakai,
    'satuan'     => $satuan,
    'id_user'    => $id_user,
]);

catat_aktivitas($pdo, 'Koreksi stok keluar: ' . $nama_bahan . ' ' . format_jumlah($jumlah_terpakai, $satuan));

$_SESSION['flash'] = ['tipe' => 'success', 'pesan' => 'Koreksi stok keluar berhasil disimpan.'];

header('Location: ../index.php');
exit;