<?php
/**
 * kpi_cards.php — 4 Kartu KPI utama di bagian atas dashboard.
 * Variabel $kpi harus sudah di-set dari ambil_semua_kpi($pdo).
 */
$kpi = $kpi ?? [];
$penjualan = $kpi['penjualan'] ?? ['total_omzet' => 0, 'total_porsi' => 0, 'jumlah_transaksi' => 0];
$stok_tersedia = $kpi['stok_tersedia'] ?? [];
$stok_menipis = $kpi['stok_menipis'] ?? [];
$prediksi = $kpi['prediksi'] ?? ['total' => null, 'hasModel' => false, 'items' => []];
?>

<div class="kpi-grid">
    <!-- KPI 1: Ringkasan Penjualan -->
    <div class="kpi-card kpi-sales">
        <div class="kpi-icon"><i class="bi bi-currency-dollar"></i></div>
        <div class="kpi-label">Ringkasan Penjualan</div>
        <div class="kpi-value"><?= format_rupiah($penjualan['total_omzet']) ?></div>
        <div class="kpi-sub">
            <i class="bi bi-basket"></i>
            <?= number_format($penjualan['total_porsi']) ?> porsi terjual
            &middot; <?= $penjualan['jumlah_transaksi'] ?> transaksi
        </div>
    </div>

    <!-- KPI 2: Stok Tersedia -->
    <div class="kpi-card kpi-stock">
        <div class="kpi-icon"><i class="bi bi-box-seam"></i></div>
        <div class="kpi-label">Stok Tersedia</div>
        <div class="kpi-value"><?= count($stok_tersedia) ?> bahan</div>
        <div class="kpi-sub">Master bahan baku terpantau</div>
    </div>

    <!-- KPI 3: Stok Menipis -->
    <div class="kpi-card kpi-alert">
        <div class="kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="kpi-label">Stok Menipis</div>
        <?php if (empty($stok_menipis)): ?>
            <!-- Tampilkan Aman ✓ dengan subtitle Semua bahan di atas batas minimum -->
            <div class="kpi-value text-success" style="font-size:1.1rem">Aman ✓</div>
            <div class="kpi-sub">Semua bahan di atas batas minimum</div>
        <?php else: ?>
            <div class="kpi-value text-danger"><?= count($stok_menipis) ?> alert</div>
            <ul class="kpi-list">
                <?php foreach ($stok_menipis as $item): ?>
                    <li class="text-danger">
                        <span><?= htmlspecialchars($item['nama_bahan']) ?></span>
                        <strong><?= format_jumlah($item['sisa'], $item['satuan']) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- KPI 4: Hasil Prediksi -->
    <div class="kpi-card kpi-predict">
        <div class="kpi-icon"><i class="bi bi-graph-up"></i></div>
        <div class="kpi-label">Hasil Prediksi</div>
        <?php if (!boleh_prediksi()): ?>
            <div class="kpi-value" style="font-size:1rem">—</div>
            <div class="kpi-sub">Preview prediksi untuk Owner/Admin</div>
        <?php elseif (!$prediksi['hasModel']): ?>
            <div class="kpi-value" style="font-size:1rem">—</div>
            <div class="kpi-sub">Belum ada model prediksi</div>
        <?php elseif (is_null($prediksi['total'])): ?>
            <div class="kpi-value" style="font-size:1rem">—</div>
            <div class="kpi-sub">Data historis belum cukup</div>
        <?php else: ?>
            <div class="kpi-value" style="font-size:1.5rem; font-weight:bold;">
                <?= number_format($prediksi['total'], 2) ?> Porsi
            </div>
            <div class="kpi-sub">Estimasi periode berikutnya</div>
        <?php endif; ?>
    </div>
</div>