<?php
/**
 * process_edit_stok_keluar.php - Handler untuk mengedit data stok keluar
 * -------------------------------------------------------------------------
 */
require_once '../config.php';
require_once '../includes/auth.php';
mulai_session();

// Hanya role Admin dan Owner yang diizinkan
$user_role = strtolower($_SESSION['role'] ?? '');
if (!in_array($user_role, ['admin', 'owner'])) {
    $_SESSION['flash'] = [
        'tipe' => 'danger',
        'pesan' => 'Akses ditolak. Hanya Admin/Owner yang diizinkan.'
    ];
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// Tangkap dan sanitasi input menggunakan mysqli seperti yang ditunjukkan dalam instruksi
$id = intval($_POST['id'] ?? 0);
$tanggal = $_POST['tanggal'] ?? '';
$nama_bahan = $_POST['nama_bahan'] ?? '';
$jumlah_terpakai = floatval($_POST['jumlah'] ?? 0);
$satuan = $_POST['satuan'] ?? 'Kg';

// Validasi ketat ID
if ($id <= 0) {
    die(json_encode(['success' => false, 'message' => 'Error: ID data tidak valid.']));
}

// Buat koneksi mysqli untuk escape string
$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($koneksi->connect_error) {
    die("Koneksi database gagal: " . $koneksi->connect_error);
}

// Escape string untuk keamanan
$tanggal = $koneksi->real_escape_string($tanggal);
$nama_bahan = $koneksi->real_escape_string($nama_bahan);
$satuan = $koneksi->real_escape_string($satuan);

// Query UPDATE sesuai struktur tabel stok_keluar
$query = "UPDATE stok_keluar SET
            tanggal = '$tanggal',
            nama_bahan = '$nama_bahan',
            jumlah_terpakai = '$jumlah_terpakai',
            satuan = '$satuan'
          WHERE id = $id";

if (mysqli_query($koneksi, $query)) {
    // Log aktivitas (opsional, karena ini adalah file mandiri)
    // Untuk konsistensi dengan kode lain, kita bisa menyertakan log jika perlu
    header("Location: ../index.php?status=success");
    exit;
} else {
    echo "Gagal memperbarui data: " . mysqli_error($koneksi);
}

$koneksi->close();
?>