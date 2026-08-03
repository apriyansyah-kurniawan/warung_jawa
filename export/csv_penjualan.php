<?php
/**
 * export/csv_penjualan.php — Export data penjualan ke CSV (Excel-compatible).
 */
require_once '../config.php';
require_once '../includes/auth.php';

cek_role(['Admin', 'Owner']);
catat_aktivitas($pdo, 'Export CSV data penjualan');

$stmt = $pdo->query('
    SELECT p.id, p.tanggal, p.nama_menu, p.jumlah_porsi, p.total_harga,
           u.username AS kasir, p.created_at
    FROM penjualan p
    JOIN users u ON p.id_user = u.id
    ORDER BY p.tanggal DESC, p.id DESC
');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="penjualan_warung_jawa_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 untuk Excel

fputcsv($output, ['ID', 'Tanggal', 'Menu', 'Jumlah Porsi', 'Total Harga (Rp)', 'Kasir', 'Dibuat']);

while ($row = $stmt->fetch()) {
    fputcsv($output, [
        $row['id'],
        $row['tanggal'],
        $row['nama_menu'],
        $row['jumlah_porsi'],
        $row['total_harga'],
        $row['kasir'],
        $row['created_at'],
    ]);
}

fclose($output);
exit;
