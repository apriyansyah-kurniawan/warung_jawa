<?php
/** Panel KASIR — Form penjualan menu (stok otomatis berkurang via insert_penjualan.php) */
// Ambil menu dari database, fallback ke DAFTAR_MENU jika gagal
$menu_items = get_menu_from_db();
if ($menu_items === null) {
    $menu_items = DAFTAR_MENU;
}
$menu_json = json_encode($menu_items, JSON_UNESCAPED_UNICODE);
$stmtRiwayat = $pdo->prepare('SELECT * FROM penjualan WHERE id_user = :id AND tanggal = CURDATE() ORDER BY id DESC');
$stmtRiwayat->execute(['id' => $_SESSION['user_id']]);
$riwayat_hari_ini = $stmtRiwayat->fetchAll();
?>

<div class="modern-card" id="penjualan">
    <div class="card-header"><span><i class="bi bi-cart-plus"></i> Input Penjualan Menu</span></div>
    <div class="card-body">
        <p class="text-muted mb-3">Catat penjualan menu. Stok bahan berkurang otomatis sesuai resep.</p>
        <form method="POST" action="aksi/insert_penjualan.php" id="formPenjualan">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pilih Menu</label>
                    <select name="nama_menu" id="selectMenu" class="form-select" required>
                        <option value="">-- Pilih Menu --</option>
                        <?php foreach ($menu_items as $nama => $info): ?>
                            <option value="<?= htmlspecialchars($nama) ?>" data-harga="<?= $info['harga'] ?>">
                                <?= htmlspecialchars($nama) ?> (<?= format_rupiah($info['harga']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jumlah Porsi</label>
                    <input type="number" name="jumlah_porsi" id="inputPorsi" class="form-control" min="1" value="1" required>
                </div>
            </div>
            <div class="alert alert-success mt-3 mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-calculator"></i>
                <strong>Total:</strong> <span id="totalHarga" class="fs-5 fw-bold">Rp 0</span>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-success px-4"><i class="bi bi-check-lg"></i> Simpan Penjualan</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($riwayat_hari_ini)): ?>
<div class="modern-card">
    <div class="card-header"><span>Penjualan Hari Ini</span></div>
    <div class="card-body p-0">
        <table class="table table-modern table-hover mb-0">
            <thead><tr><th>Menu</th><th class="text-center">Porsi</th><th class="text-end">Total</th></tr></thead>
            <tbody>
                <?php foreach ($riwayat_hari_ini as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nama_menu']) ?></td>
                        <td class="text-center"><?= (int) $row['jumlah_porsi'] ?></td>
                        <td class="text-end"><?= format_rupiah($row['total_harga']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>window.DAFTAR_MENU = <?= $menu_json ?>;</script>
<script src="assets/js/kasir.js"></script>
