<?php
/**
 * stok_masuk.php
 * -------------------------------------------------------------------------
 * Handler form Stok Masuk / Refill Inventory oleh Owner.
 * Mencatat bahan yang datang ke gudang (TIDAK mempengaruhi predict.py).
 * -------------------------------------------------------------------------
 */
require_once '../config.php';
require_once '../includes/auth.php';
mulai_session();
$user_role = strtolower($_SESSION['role'] ?? '');
if (!in_array($user_role, ['owner'])) {
    $_SESSION['flash'] = [
        'tipe'  => 'danger',
        'pesan' => 'Akses ditolak. Hanya Owner yang diizinkan.',
    ];
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$tanggal      = trim($_POST['tanggal'] ?? '');
$nama_bahan   = trim($_POST['nama_bahan'] ?? '');
$jumlah_masuk = trim($_POST['jumlah_masuk'] ?? '');
$satuan       = trim($_POST['satuan'] ?? 'Kg');
$keterangan   = trim($_POST['keterangan'] ?? '');
$id_user      = (int) $_SESSION['user_id'];

$errors = [];

if ($tanggal === '') $errors[] = 'Tanggal wajib diisi.';
if ($nama_bahan === '') $errors[] = 'Nama bahan wajib diisi.';
if ($jumlah_masuk === '' || !is_numeric($jumlah_masuk) || (float) $jumlah_masuk <= 0) {
    $errors[] = 'Jumlah masuk harus angka lebih dari 0.';
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
    INSERT INTO stok_masuk (tanggal, nama_bahan, jumlah_masuk, satuan, keterangan, id_user)
    VALUES (:tanggal, :nama_bahan, :jumlah, :satuan, :keterangan, :id_user)
');
$stmt->execute([
    'tanggal'    => $tanggal,
    'nama_bahan' => $nama_bahan,
    'jumlah'     => $jumlah_masuk,
    'satuan'     => $satuan,
    'keterangan' => $keterangan !== '' ? $keterangan : null,
    'id_user'    => $id_user,
]);

catat_aktivitas($pdo, 'Stok masuk: ' . $nama_bahan . ' ' . format_jumlah($jumlah_masuk, $satuan));

$_SESSION['flash'] = [
    'tipe'  => 'success',
    'pesan' => 'Stok masuk ' . $nama_bahan . ' (' . format_jumlah($jumlah_masuk, $satuan) . ') berhasil dicatat.',
];

header('Location: ../index.php');
exit;