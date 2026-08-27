<?php
/**
 * simpan_transaksi_pos.php — Menyimpan transaksi penjualan dari POS Kasir (bisa beberapa item).
 */
require_once '../config.php';
require_once '../includes/auth.php';

// Pastikan hanya kasir yang boleh mengakses
cek_role(['kasir']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data JSON dari body request
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Data JSON tidak valid.'
        ]);
        exit;
    }

    $cartItems = $input['cart'] ?? [];
    $id_user = $_SESSION['user_id'];

    if (empty($cartItems)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Keranjang belanja kosong.'
        ]);
        exit;
    }

    // Proses setiap item dalam keranjang
    $successCount = 0;
    foreach ($cartItems as $item) {
        $id_menu = $item['id_menu'] ?? null;
        $jumlah = $item['jumlah'] ?? null;
        $harga = $item['harga'] ?? null;

        // Validasi
        if (!$id_menu || !$jumlah || !$harga) {
            continue;
        }

        $jumlah = (int)$jumlah;
        $harga = (float)$harga;
        if ($jumlah <= 0 || $harga <= 0) {
            continue;
        }

        // Ambil nama menu dari id_menu
        $stmt = $pdo->prepare("SELECT nama_menu FROM master_menu WHERE id = ?");
        $stmt->execute([$id_menu]);
        $menu = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$menu) {
            continue;
        }
        $nama_menu = $menu['nama_menu'];
        $total_harga = $harga * $jumlah;

        // Simpan transaksi penjualan
        $stmt = $pdo->prepare("INSERT INTO penjualan (id_user, nama_menu, total_harga, tanggal) VALUES (?, ?, ?, NOW())");
        $result = $stmt->execute([$id_user, $nama_menu, $total_harga]);

        if ($result) {
            $successCount++;
        }
    }

    if ($successCount > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Berhasil menyimpan ' . $successCount . ' item transaksi.',
            'savedCount' => $successCount
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan transaksi.'
        ]);
    }

    exit;
} else {
    // Jika tidak ada POST, return error
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan.'
    ]);
    exit;
}