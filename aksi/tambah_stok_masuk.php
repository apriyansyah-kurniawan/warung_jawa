<?php
/**
 * tambah_stok_masuk.php
 * ---------------------------------------------------------------------
 * Admin mencatat stok masuk bahan baku.
 * ---------------------------------------------------------------------
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

    $tanggal      = $_POST['tanggal'] ?? '';
    $nama_bahan   = trim($_POST['nama_bahan'] ?? '');
    $jumlah_masuk = $_POST['jumlah_masuk'] ?? '';
    $satuan       = trim($_POST['satuan'] ?? '');
    $keterangan   = trim($_POST['keterangan'] ?? '');

    $errors = [];

    if ($tanggal === '') {
        $errors[] = 'Tanggal masuk wajib diisi.';
    }
    if ($nama_bahan === '') {
        $errors[] = 'Bahan baku wajib dipilih.';
    }
    if ($jumlah_masuk === '' || !is_numeric($jumlah_masuk) || (float)$jumlah_masuk <= 0) {
        $errors[] = 'Jumlah masuk harus berupa angka positif.';
    }
    if ($satuan === '') {
        $errors[] = 'Satuan tidak valid.';
    }

    // Optional: validate that the nama_bahan is one of the allowed options
    $allowed_bahan = ['Ayam', 'Daging Sapi', 'Beras', 'Bumbu Merah', 'Bumbu Bawang', 'Minyak', 'Daun Ubi'];
    if (!in_array($nama_bahan, $allowed_bahan, true)) {
        $errors[] = 'Bahan baku tidak valid.';
    }

    if (!empty($errors)) {
        $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => implode(' ', $errors)];
        header('Location: ../index.php');
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO stok_masuk (tanggal, nama_bahan, jumlah_masuk, satuan, keterangan, id_user) 
                           VALUES (:tanggal, :nama_bahan, :jumlah_masuk, :satuan, :keterangan, :id_user)');
    $stmt->execute([
        'tanggal'      => $tanggal,
        'nama_bahan'   => $nama_bahan,
        'jumlah_masuk' => (float)$jumlah_masuk,
        'satuan'       => $satuan,
        'keterangan'   => $keterangan,
        'id_user'      => (int)$_SESSION['id_user'],
    ]);

    catat_aktivitas($pdo, 'Tambah stok masuk: ' . $jumlah_masuk . ' ' . $satuan . ' ' . $nama_bahan);
    $_SESSION['flash'] = ['tipe' => 'success', 'pesan' => "Stok masuk untuk {$nama_bahan} berhasil dicatat."];
} catch (Throwable $e) {
    // Log error for debugging (optional)
    error_log("Error in tambah_stok_masuk.php: " . $e->getMessage());
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Terjadi kesalahan sistem.'];
}

header('Location: ../index.php');
exit;
