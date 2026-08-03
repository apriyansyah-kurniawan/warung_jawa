<?php
/**
 * insert_penjualan.php
 * -------------------------------------------------------------------------
 * Handler transaksi penjualan Kasir.
 *
 * Alur bisnis (SATU database transaction):
 *   1. Validasi input menu & jumlah porsi
 *   2. INSERT ke tabel penjualan (data keuangan)
 *   3. Hitung bahan terpakai berdasarkan resep (config.php -> DAFTAR_MENU)
 *   4. INSERT otomatis ke stok_keluar (data historis untuk predict.py)
 *   5. COMMIT jika semua berhasil, ROLLBACK jika ada error
 *
 * Contoh resep:
 *   Nasi Ayam Jawa x 4 porsi -> stok_keluar: Ayam 1.00 Kg (4 × 0.25)
 * -------------------------------------------------------------------------
 */
require_once '../config.php';
require_once '../includes/auth.php';
mulai_session();
$user_role = strtolower($_SESSION['role'] ?? '');
if (!in_array($user_role, ['kasir'])) {
    $_SESSION['flash'] = [
        'tipe'  => 'danger',
        'pesan' => 'Akses ditolak. Hanya Kasir yang diizinkan.',
    ];
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$tanggal      = trim($_POST['tanggal'] ?? '');
$nama_menu    = trim($_POST['nama_menu'] ?? '');
$jumlah_porsi = trim($_POST['jumlah_porsi'] ?? '');
$id_user      = (int) $_SESSION['user_id'];

$errors = [];

if ($tanggal === '') {
    $errors[] = 'Tanggal wajib diisi.';
}

$menu = ambil_menu($nama_menu);
if ($menu === null) {
    $errors[] = 'Menu tidak valid.';
}

if ($jumlah_porsi === '' || !ctype_digit($jumlah_porsi) || (int) $jumlah_porsi <= 0) {
    $errors[] = 'Jumlah porsi harus bilangan bulat lebih dari 0.';
}

if (!empty($errors)) {
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => implode(' ', $errors)];
    header('Location: ../index.php');
    exit;
}

$jumlah_porsi = (int) $jumlah_porsi;
$total_harga  = $jumlah_porsi * $menu['harga'];

// Hitung bahan baku yang otomatis berkurang
$resep           = $menu['resep'];
$jumlah_terpakai = round($jumlah_persi * $resep['pengali'], 2);

try {
    // Mulai transaksi database: semua query sukses atau tidak sama sekali
    $pdo->beginTransaction();

    // LANGKAH A: Simpan data penjualan (keuangan)
    $stmtPenjualan = $pdo->prepare('
        INSERT INTO penjualan (tanggal, nama_menu, jumlah_porsi, total_harga, id_user)
        VALUES (:tanggal, :nama_menu, :jumlah_porsi, :total_harga, :id_user)
    ');
    $stmtPenjualan->execute([
        'tanggal'      => $tanggal,
        'nama_menu'    => $nama_menu,
        'jumlah_porsi' => $jumlah_porsi,
        'total_harga'  => $total_harga,
        'id_user'      => $id_user,
    ]);

    // LANGKAH B: Kurangi stok bahan otomatis (historis untuk Python)
    $stmtStok = $pdo->prepare('
        INSERT INTO stok_keluar (tanggal, nama_bahan, jumlah_terpakai, satuan, id_user)
        VALUES (:tanggal, :nama_bahan, :jumlah, :satuan, :id_user)
    ');
    $stmtStok->execute([
        'tanggal'    => $tanggal,
        'nama_bahan' => $resep['nama_bahan'],
        'jumlah'     => $jumlah_terpakai,
        'satuan'     => $resep['satuan'],
        'id_user'    => $id_user,
    ]);

    $pdo->commit();

    catat_aktivitas($pdo, sprintf(
        'Penjualan: %s, %d porsi, %s (stok %s -%s)',
        $nama_menu, $jumlah_porsi, format_rupiah($total_harga),
        $resep['nama_bahan'], format_jumlah($jumlah_terpakai, $resep['satuan'])
    ));

    $_SESSION['flash'] = [
        'tipe'  => 'success',
        'pesan' => sprintf(
            'Penjualan %s (%d porsi, %s) berhasil. Stok %s otomatis berkurang %s.',
            $nama_menu,
            $jumlah_porsi,
            format_rupiah($total_harga),
            $resep['nama_bahan'],
            format_jumlah($jumlah_terpakai, $resep['satuan'])
        ),
    ];

} catch (PDOException $e) {
    // Batalkan semua perubahan jika terjadi error SQL
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['flash'] = [
        'tipe'  => 'danger',
        'pesan' => 'Transaksi gagal: ' . $e->getMessage(),
    ];
}

header('Location: ../index.php');
exit;