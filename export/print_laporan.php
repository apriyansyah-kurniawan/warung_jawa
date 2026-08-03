<?php
/**
 * export/print_laporan.php — Laporan HTML siap cetak / Save as PDF via browser.
 */
require_once '../config.php';
require_once '../includes/auth.php';

cek_role(['Admin', 'Owner']);

$jenis = $_GET['jenis'] ?? 'penjualan';
catat_aktivitas($pdo, 'Cetak laporan PDF: ' . $jenis);

if ($jenis === 'stok') {
    $judul = 'Laporan Stok Keluar';
    $stmt = $pdo->query('
        SELECT s.tanggal, s.nama_bahan, s.jumlah_terpakai, s.satuan, u.username AS petugas
        FROM stok_keluar s JOIN users u ON s.id_user = u.id
        ORDER BY s.tanggal DESC
    ');
    $kolom = ['Tanggal', 'Nama Bahan', 'Jumlah', 'Satuan', 'Petugas'];
} else {
    $judul = 'Laporan Penjualan';
    $stmt = $pdo->query('
        SELECT p.tanggal, p.nama_menu, p.jumlah_porsi, p.total_harga, u.username AS kasir
        FROM penjualan p JOIN users u ON p.id_user = u.id
        ORDER BY p.tanggal DESC
    ');
    $kolom = ['Tanggal', 'Menu', 'Porsi', 'Total (Rp)', 'Kasir'];
}

$data = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($judul) ?> - Warung Jawa</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; color: #333; }
        h1 { color: #198754; border-bottom: 2px solid #198754; padding-bottom: 0.5rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
        th { background: #198754; color: #fff; }
        tr:nth-child(even) { background: #f9f9f9; }
        .meta { color: #666; font-size: 12px; margin-bottom: 1rem; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()" style="padding:8px 16px;background:#198754;color:#fff;border:none;border-radius:6px;cursor:pointer;margin-bottom:1rem;">
        🖨️ Cetak / Save as PDF
    </button>
    <h1>🍛 <?= htmlspecialchars($judul) ?></h1>
    <p class="meta">Warung Jawa &mdash; Dicetak: <?= date('d/m/Y H:i') ?> &mdash; Oleh: <?= htmlspecialchars(username_user()) ?></p>
    <table>
        <thead><tr><?php foreach ($kolom as $c): ?><th><?= $c ?></th><?php endforeach; ?></tr></thead>
        <tbody>
            <?php foreach ($data as $row): ?>
                <tr>
                    <?php foreach ($row as $val): ?>
                        <td><?= htmlspecialchars((string) $val) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="meta" style="margin-top:1rem">Total baris: <?= count($data) ?></p>
</body>
</html>
