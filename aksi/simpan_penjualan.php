<?php
/**
 * simpan_penjualan.php — Menyimpan transaksi penjualan baru oleh kasir.
 */
require_once '../config.php';
require_once '../includes/auth.php';

// Pastikan hanya kasir yang boleh mengakses
cek_role(['kasir']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $id_menu = $_POST['id_menu'] ?? null;
    $jumlah = $_POST['jumlah'] ?? null;
    $total = $_POST['total'] ?? null;
    $id_user = $_POST['id_user'] ?? $_SESSION['user_id'];

    // Validasi input
    if (!$id_menu || !$jumlah || !$total) {
        $_SESSION['flash'] = [
            'tipe'  => 'danger',
            'pesan' => 'Data input tidak lengkap. Menu, jumlah, dan total harus diisi.'
        ];
        header('Location: ../index.php');
        exit;
    }

    // Pastikan jumlah dan total adalah angka positif
    $jumlah = (float)$jumlah;
    $total  = (float)$total;
    if ($jumlah <= 0 || $total <= 0) {
        $_SESSION['flash'] = [
            'tipe'  => 'danger',
            'pesan' => 'Jumlah dan total harus lebih besar dari nol.'
        ];
        header('Location: ../index.php');
        exit;
    }

    // Ambil nama menu dari id_menu untuk disimpan ke tabel penjualan
    $stmt = $pdo->prepare("SELECT nama_menu FROM master_menu WHERE id = ?");
    $stmt->execute([$id_menu]);
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$menu) {
        $_SESSION['flash'] = [
            'tipe'  => 'danger',
            'pesan' => 'Menu tidak ditemukan.'
        ];
        header('Location: ../index.php');
        exit;
    }
    $nama_menu = $menu['nama_menu'];

    // Simpan transaksi penjualan
    $stmt = $pdo->prepare("INSERT INTO penjualan (id_user, nama_menu, total_harga, tanggal) VALUES (?, ?, ?, NOW())");
    $result = $stmt->execute([$id_user, $nama_menu, $total]);

    if ($result) {
        $_SESSION['flash'] = [
            'tipe'  => 'success',
            'pesan' => 'Transaksi penjualan berhasil disimpan.'
        ];
    } else {
        $_SESSION['flash'] = [
            'tipe'  => 'danger',
            'pesan' => 'Gagal menyimpan transaksi penjualan.'
        ];
    }

    header('Location: ../index.php');
    exit;
} else {
    // Jika tidak ada POST, redirect ke index
    header('Location: ../index.php');
    exit;
}