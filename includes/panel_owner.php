<?php
/** Panel OWNER — penjualan, stok masuk, log read-only, prediksi */
$stmtPenjualan = $pdo->query('
    SELECT p.*, u.username AS kasir FROM penjualan p
    JOIN users u ON p.id_user = u.id ORDER BY p.tanggal DESC, p.id DESC LIMIT 20
');
$daftar_penjualan = $stmtPenjualan->fetchAll();

$log_stok_keluar = $pdo->query('
    SELECT s.*, u.username AS petugas FROM stok_keluar s
    JOIN users u ON s.id_user = u.id ORDER BY s.tanggal DESC LIMIT 15
')->fetchAll();

$log_stok_masuk = $pdo->query('
    SELECT s.*, u.username AS petugas FROM stok_masuk s
    JOIN users u ON s.id_user = u.id ORDER BY s.tanggal DESC LIMIT 15
')->fetchAll();

$daftar_bahan = $pdo->query('SELECT DISTINCT nama_bahan FROM stok_keluar ORDER BY nama_bahan')->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="modern-card" id="stok-masuk">
    <div class="card-header"><span><i class="bi bi-box-arrow-in-down"></i> Stok Masuk / Refill</span></div>
    <div class="card-body">
        <form method="POST" action="aksi/stok_masuk.php">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nama Bahan</label>
                    <input type="text" name="nama_bahan" class="form-control" list="listBahanOwner" required>
                    <datalist id="listBahanOwner">
                        <?php foreach ($daftar_bahan as $b): ?><option value="<?= htmlspecialchars($b) ?>"><?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jumlah</label>
                    <input type="number" name="jumlah_masuk" class="form-control" step="0.01" min="0.01" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Satuan</label>
                    <select name="satuan" class="form-select">
                        <?php foreach (DAFTAR_SATUAN as $s): ?><option><?= $s ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Opsional">
                </div>
            </div>
            <button type="submit" class="btn btn-success mt-3"><i class="bi bi-plus-lg"></i> Simpan Stok Masuk</button>
        </form>
    </div>
</div>

<div class="modern-card">
    <div class="card-header">
        <span><i class="bi bi-currency-dollar"></i> Data Penjualan</span>
        <div class="d-flex gap-1">
            <a href="export/csv_penjualan.php" class="btn btn-sm btn-light btn-export"><i class="bi bi-filetype-csv"></i> CSV</a>
            <a href="export/print_laporan.php?jenis=penjualan" target="_blank" class="btn btn-sm btn-light btn-export"><i class="bi bi-printer"></i> PDF</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-modern table-hover mb-0">
                <thead><tr><th>Tanggal</th><th>Menu</th><th class="text-center">Porsi</th><th class="text-end">Total</th><th>Kasir</th></tr></thead>
                <tbody>
                    <?php if (empty($daftar_penjualan)): ?>
                        <tr><td colspan="5" class="text-muted p-3">Belum ada data.</td></tr>
                    <?php else: foreach ($daftar_penjualan as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['tanggal']) ?></td>
                            <td><?= htmlspecialchars($row['nama_menu']) ?></td>
                            <td class="text-center"><?= (int) $row['jumlah_porsi'] ?></td>
                            <td class="text-end"><?= format_rupiah($row['total_harga']) ?></td>
                            <td><?= htmlspecialchars($row['kasir']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="modern-card h-100">
            <div class="card-header">
                <span>Log Stok Keluar</span>
                <a href="export/csv_stok_keluar.php" class="btn btn-sm btn-light btn-export">CSV</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-modern table-sm mb-0">
                    <thead><tr><th>Tanggal</th><th>Bahan</th><th class="text-end">Jumlah</th></tr></thead>
                    <tbody>
                        <?php foreach ($log_stok_keluar as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['tanggal']) ?></td>
                                <td><?= htmlspecialchars($row['nama_bahan']) ?></td>
                                <td class="text-end"><?= format_jumlah($row['jumlah_terpakai'], $row['satuan']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="modern-card h-100">
            <div class="card-header">Log Stok Masuk</div>
            <div class="card-body p-0">
                <table class="table table-modern table-sm mb-0">
                    <thead><tr><th>Tanggal</th><th>Bahan</th><th class="text-end">Jumlah</th></tr></thead>
                    <tbody>
                        <?php if (empty($log_stok_masuk)): ?>
                            <tr><td colspan="3" class="text-muted p-3">Belum ada data.</td></tr>
                        <?php else: foreach ($log_stok_masuk as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['tanggal']) ?></td>
                                <td><?= htmlspecialchars($row['nama_bahan']) ?></td>
                                <td class="text-end"><?= format_jumlah($row['jumlah_masuk'], $row['satuan']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/panel_prediksi.php'; ?>
