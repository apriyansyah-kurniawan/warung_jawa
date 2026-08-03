<?php
/**
 * export/csv_stok_keluar.php — Export data stok_keluar ke CSV.
 */
require_once '../config.php';
require_once '../includes/auth.php';

cek_role(['Admin', 'Owner']);
catat_aktivitas($pdo, 'Export CSV data stok_keluar');

$stmt = $pdo->query('
    SELECT s.id, s.tanggal, s.nama_bahan, s.jumlah_terpakai, s.satuan,
           u.username AS petugas, s.created_at
    FROM stok_keluar s
    JOIN users u ON s.id_user = u.id
    ORDER BY s.tanggal DESC, s.id DESC
');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="stok_keluar_warung_jawa_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['ID', 'Tanggal', 'Nama Bahan', 'Jumlah Terpakai', 'Satuan', 'Petugas', 'Dibuat']);

while ($row = $stmt->fetch()) {
    fputcsv($output, [
        $row['id'],
        $row['tanggal'],
        $row['nama_bahan'],
        $row['jumlah_terpakai'],
        $row['satuan'],
        $row['petugas'],
        $row['created_at'],
    ]);
}

fclose($output);
exit;
