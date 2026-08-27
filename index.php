<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/kpi.php';

cek_login();

$judul_halaman = 'Dashboard';
$page = $_GET['page'] ?? 'beranda';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$kpi = ambil_semua_kpi($pdo);

// -------------------------------------------------------------
// 1. DATA MODEL REGRESI & ANALITIK ESTIMASI (UNTUK OWNER / UMUM)
// -------------------------------------------------------------
$q_model = $pdo->query("SELECT * FROM model_regresi ORDER BY id DESC LIMIT 1");
$data_model = $q_model->fetch(PDO::FETCH_ASSOC);

if ($data_model) {
    $b0  = (float)($data_model['beta0'] ?? 6.480);
    $b1  = (float)($data_model['beta1'] ?? 3.723);
    $b2  = (float)($data_model['beta2'] ?? 0.108);
    $b3  = (float)($data_model['beta3'] ?? 5.097);
    $b4  = (float)($data_model['beta4'] ?? -5.872);
    $b5  = (float)($data_model['beta5'] ?? -1.077);
    $b6  = (float)($data_model['beta6'] ?? -4.749);
    $mad = round((float)($data_model['mad'] ?? 1.127), 3);
    $r2  = round((float)($data_model['r_square'] ?? 0.982), 3);
    $model_tersedia = true;
} else {
    $b0 = 6.480; $b1 = 3.723; $b2 = 0.108; $b3 = 5.097; $b4 = -5.872; $b5 = -1.077; $b6 = -4.749;
    $mad = 1.127; $r2 = 0.982;
    $model_tersedia = false;
}

// Rata-rata 7 hari terakhir dari dataset_regresi untuk estimasi pekan depan
$q_avg = $pdo->query("
    SELECT
        AVG(x1_ayam) * 7 AS est_ayam,
        AVG(x2_sapi) * 7 AS est_sapi,
        AVG(x3_beras) * 7 AS est_beras,
        AVG(x4_bumbu_merah) * 7 AS est_merah,
        AVG(x5_bumbu_bawang) * 7 AS est_bawang,
        AVG(x6_minyak) * 7 AS est_minyak
    FROM (
        SELECT * FROM dataset_regresi ORDER BY tanggal DESC LIMIT 7
    ) AS sub
");
$est = $q_avg->fetch(PDO::FETCH_ASSOC);

$est_ayam   = round((float)($est['est_ayam'] ?? 61.97), 2);
$est_sapi   = round((float)($est['est_sapi'] ?? 24.51), 2);
$est_beras  = round((float)($est['est_beras'] ?? 76.94), 2);
$est_merah  = round((float)($est['est_merah'] ?? 10.20), 2);
$est_bawang = round((float)($est['est_bawang'] ?? 6.44), 2);
$est_minyak = round((float)($est['est_minyak'] ?? 16.67), 2);

$total_estimasi_porsi = round(($b0 * 7) + ($b1 * $est_ayam) + ($b2 * $est_sapi) + ($b3 * $est_beras) + ($b4 * $est_merah) + ($b5 * $est_bawang) + ($b6 * $est_minyak), 2);

// -------------------------------------------------------------
// 2. DATASET UNTUK GRAFIK MULTIVARIABEL (30 HARI TERAKHIR)
// -------------------------------------------------------------
$chart_labels    = [];
$chart_actual    = [];
$chart_predicted = [];
$chart_x1 = []; $chart_x2 = []; $chart_x3 = [];
$chart_x4 = []; $chart_x5 = []; $chart_x6 = [];

$q_chart = $pdo->query("
    SELECT * FROM (
        SELECT * FROM dataset_regresi ORDER BY tanggal DESC LIMIT 30
    ) AS sub ORDER BY tanggal ASC
");
$rows_chart = $q_chart->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows_chart as $r) {
    $chart_labels[] = date('d M', strtotime($r['tanggal']));
    $porsi          = (float)($r['jumlah_porsi'] ?? $r['y_porsi'] ?? 0);
    $chart_actual[] = $porsi;

    $x1 = (float)($r['x1_ayam'] ?? 0);
    $x2 = (float)($r['x2_sapi'] ?? 0);
    $x3 = (float)($r['x3_beras'] ?? 0);
    $x4 = (float)($r['x4_bumbu_merah'] ?? 0);
    $x5 = (float)($r['x5_bumbu_bawang'] ?? 0);
    $x6 = (float)($r['x6_minyak'] ?? 0);

    $chart_x1[] = $x1;
    $chart_x2[] = $x2;
    $chart_x3[] = $x3;
    $chart_x4[] = $x4;
    $chart_x5[] = $x5;
    $chart_x6[] = $x6;

    $y_pred = $b0 + ($b1 * $x1) + ($b2 * $x2) + ($b3 * $x3) + ($b4 * $x4) + ($b5 * $x5) + ($b6 * $x6);
    $chart_predicted[] = round($y_pred, 2);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($judul_halaman) ?> - Warung Jawa</title>
    <?php require_once 'includes/header.php'; ?>
</head>
<body>
<div class="container mx-auto px-4 dashboard-content my-4">

    <!-- 4 KPI Cards Atas -->
    <?php require 'includes/kpi_cards.php'; ?>

    <?php if ($page === 'menu'): ?>
        <!-- ================= HALAMAN MANAJEMEN MENU ================= -->
        <div class="modern-card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-card-checklist me-2"></i> Manajemen Menu dan Resep</span>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahMenu">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Menu Baru
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="menuTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">No</th>
                                <th>Nama Menu</th>
                                <th>Harga</th>
                                <th>Resep Bahan</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th style="width: 15%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $q_m = $pdo->query("SELECT * FROM master_menu ORDER BY id ASC");
                        $menus = $q_m->fetchAll(PDO::FETCH_ASSOC);
                        $no = 1;
                        if (empty($menus)):
                        ?>
                            <tr><td colspan="7" class="text-center text-muted py-3">Belum ada data menu.</td></tr>
                        <?php else: foreach ($menus as $m): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($m['nama_menu'] ?? '') ?></strong></td>
                                <td>Rp <?= number_format($m['harga'] ?? 0, 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars($m['resep_bahan'] ?? '-') ?></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($m['kategori_x'] ?? 'X1') ?></span></td>
                                <td><span class="badge bg-success"><?= htmlspecialchars($m['status'] ?? 'Utama') ?></span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary btn-edit-menu"
                                            data-id="<?= $m['id'] ?>"
                                            data-nama="<?= htmlspecialchars($m['nama_menu'] ?? '') ?>"
                                            data-harga="<?= $m['harga'] ?? 0 ?>"
                                            data-resep="<?= htmlspecialchars($m['resep_bahan'] ?? '') ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalEditMenu">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <a href="aksi/hapus_menu.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus menu ini?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php elseif (adalah_kasir()): ?>
        <!-- ================= ROLE: KASIR ================= -->
        <?php require_once 'includes/panel_kasir.php'; ?>

    <?php elseif (adalah_admin()): ?>
        <!-- ================= ROLE: ADMIN ================= -->
        <div class="modern-card mt-4">
            <div class="card-header"><span><i class="bi bi-clock me-2"></i> Operasional Harian</span></div>
            <div class="card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#modalTambahStokMasuk">
                            <i class="bi bi-box-arrow-in-down me-2"></i> Tambah Stok Masuk
                        </button>
                    </div>
                    <div class="col-md-6">
                        <div class="text-end">
                            <strong>Bahan Mendekati Batas Minimum:</strong>
                            <span class="bg-warning text-dark ms-2 px-2 py-1 rounded">3 bahan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        require 'includes/widget_aktivitas.php';
        require 'includes/panel_admin.php';
        ?>

    <?php else: ?>
        <!-- ================= ROLE: OWNER ================= -->
        <!-- Panel Analitik Prediksi Bahan Baku Minggu Depan -->
        <div class="modern-card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up me-2"></i> Panel Analitik Prediksi Stok</span>
                <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i> Cetak</button>
            </div>
            <div class="card-body">
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle text-center mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>No</th>
                                <th>Bahan Baku</th>
                                <th>Estimasi Kebutuhan Minggu Depan</th>
                                <th>Satuan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>1</td><td>Ayam</td><td><strong><?= number_format($est_ayam, 2) ?></strong></td><td>Kg</td></tr>
                            <tr><td>2</td><td>Sapi/Tetelan</td><td><strong><?= number_format($est_sapi, 2) ?></strong></td><td>Kg</td></tr>
                            <tr><td>3</td><td>Beras</td><td><strong><?= number_format($est_beras, 2) ?></strong></td><td>Kg</td></tr>
                            <tr><td>4</td><td>Bumbu Merah</td><td><strong><?= number_format($est_merah, 2) ?></strong></td><td>Kg</td></tr>
                            <tr><td>5</td><td>Bumbu Bawang</td><td><strong><?= number_format($est_bawang, 2) ?></strong></td><td>Kg</td></tr>
                            <tr><td>6</td><td>Minyak/Santan</td><td><strong><?= number_format($est_minyak, 2) ?></strong></td><td>Liter</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 bg-light rounded border mb-3">
                    <div class="small fw-bold text-dark">Model Regresi Linier Berganda:</div>
                    <div class="text-secondary small font-monospace">
                        Y' = <?= number_format($b0, 3) ?> + <?= number_format($b1, 3) ?>(X₁) + <?= number_format($b2, 3) ?>(X₂) + <?= number_format($b3, 3) ?>(X₃) <?= $b4 >= 0 ? "+ $b4" : "- " . abs($b4) ?>(X₄) <?= $b5 >= 0 ? "+ $b5" : "- " . abs($b5) ?>(X₅) <?= $b6 >= 0 ? "+ $b6" : "- " . abs($b6) ?>(X₆)
                    </div>
                    <div class="small mt-1 text-muted">
                        Nilai MAD: <strong><?= $mad ?></strong> | R²: <strong><?= $r2 ?></strong> | Total Estimasi Penjualan: <strong><?= number_format($total_estimasi_porsi, 2) ?> Porsi</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Perhitungan Prediksi & Grafik Multivariabel 8 Garis -->
        <section id="teknis" class="modern-card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <span><i class="bi bi-cpu me-2"></i> Perhitungan Prediksi Kebutuhan Bahan Baku</span>
                    <span class="badge bg-info text-dark ms-2">R²: <?= $r2 ?></span>
                    <span class="badge bg-secondary ms-1">MAD: <?= $mad ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-center mb-3">
                    <div class="col-md-6">
                        <button type="button" id="hitungPrediksiBtn" class="btn btn-primary w-100">
                            <i class="bi bi-brain me-2"></i> Hitung Prediksi Stok
                        </button>
                    </div>
                    <div class="col-md-6">
                        <div id="statusModel" class="text-end small text-muted">Model aktif menggunakan data historis 30 hari.</div>
                    </div>
                </div>

                <div class="border rounded p-3 bg-white">
                    <h6 class="mb-3"><i class="bi bi-graph-up-arrow me-2"></i> Grafik Multivariabel Penjualan dan Variabel Bahan Baku</h6>
                    <div style="position: relative; height: 380px; width: 100%;">
                        <canvas id="chartMultivariabel"></canvas>
                    </div>
                </div>
            </div>
            <?php
            require 'includes/widget_aktivitas.php';
            require 'includes/panel_owner.php';
            ?>
        </section>
    <?php endif; ?>

</div>

<!-- ================= MODAL TAMBAH MENU ================= -->
<div class="modal fade" id="modalTambahMenu" tabindex="-1" aria-labelledby="modalTambahMenuLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="aksi/simpan_menu_baru.php">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTambahMenuLabel">Tambah Menu Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
              <label class="form-label">Nama Menu *</label>
              <input type="text" class="form-control" name="nama_menu" placeholder="Contoh: Ayam Bakar Madu" required>
          </div>
          <div class="mb-3">
              <label class="form-label">Harga (Rp) *</label>
              <input type="number" min="0" step="100" class="form-control" name="harga" placeholder="25000" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label">Bahan Baku *</label>
              <select class="form-select" name="nama_bahan" id="selectBahanMenu" onchange="autoDetectKategori()" required>
                <option value="" disabled selected>-- Pilih Bahan --</option>
                <option value="Ayam" data-kat="X1" data-sat="Kg">Ayam (X1)</option>
                <option value="Daging Sapi" data-kat="X2" data-sat="Kg">Daging Sapi (X2)</option>
                <option value="Beras" data-kat="X3" data-sat="Kg">Beras (X3)</option>
                <option value="Bumbu Merah" data-kat="X4" data-sat="Kg">Bumbu Merah (X4)</option>
                <option value="Bumbu Bawang" data-kat="X5" data-sat="Kg">Bumbu Bawang (X5)</option>
                <option value="Minyak" data-kat="X6" data-sat="Liter">Minyak Goreng/Santan (X6)</option>
                <option value="Daun Ubi" data-kat="X1" data-sat="Ikat">Daun Ubi</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Pengali</label>
              <input type="number" step="0.001" min="0" class="form-control" name="resep_pengali" value="0.250" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Satuan</label>
              <input type="text" class="form-control bg-light" name="satuan" id="inputSatuanMenu" value="Kg" readonly>
            </div>
          </div>
          <input type="hidden" name="kategori_x" id="inputKategoriMenu" value="X1">
          <div class="p-2 bg-light border rounded small mb-3">
            <strong>Kategori Terdeteksi: </strong><span id="badgeKategoriMenu" class="bg-primary">X1</span>
          </div>
          <div class="form-check">
            <input type="checkbox" class="form-check-input" name="status_kasir" value="Utama" id="isMenuUtama" checked>
            <label class="form-check-label small" for="isMenuUtama">Tampilkan di menu kasir</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="simpan_menu_baru" class="btn btn-primary">Simpan Menu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= MODAL EDIT MENU ================= -->
<div class="modal fade" id="modalEditMenu" tabindex="-1" aria-labelledby="modalEditMenuLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="aksi/update_menu.php">
        <div class="modal-header">
          <h5 class="modal-title" id="modalEditMenuLabel">Edit Menu</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_menu" id="editIdMenu">
          <div class="mb-3">
              <label class="form-label">Nama Menu</label>
              <input type="text" class="form-control" name="nama_menu" id="editNamaMenu" required>
          </div>
          <div class="mb-3">
              <label class="form-label">Harga (Rp)</label>
              <input type="number" min="0" class="form-control" name="harga" id="editHarga" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Update Menu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= MODAL TAMBAH STOK MASUK ADMIN ================= -->
<div class="modal fade" id="modalTambahStokMasuk" tabindex="-1" aria-labelledby="modalTambahStokMasukLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="aksi/tambah_stok_masuk.php">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="modalTambahStokMasukLabel"><i class="bi bi-box-seam me-2"></i> Input Stok Masuk</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
              <label class="form-label">Tanggal Masuk</label>
              <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="mb-3">
              <label class="form-label">Bahan Baku</label>
              <select class="form-select" name="nama_bahan" id="bahanSelectAdmin" onchange="autoSatuanStokAdmin()" required>
                <option value="" disabled selected>-- Pilih Bahan --</option>
                <option value="Ayam" data-sat="Kg">Ayam (X1)</option>
                <option value="Daging Sapi" data-sat="Kg">Daging Sapi (X2)</option>
                <option value="Beras" data-sat="Kg">Beras (X3)</option>
                <option value="Bumbu Merah" data-sat="Kg">Bumbu Merah (X4)</option>
                <option value="Bumbu Bawang" data-sat="Kg">Bumbu Bawang (X5)</option>
                <option value="Minyak" data-sat="Liter">Minyak Goreng/Santan (X6)</option>
                <option value="Daun Ubi" data-sat="Ikat">Daun Ubi</option>
              </select>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-8">
              <label class="form-label">Jumlah Masuk</label>
              <input type="number" step="0.01" min="0.01" class="form-control" name="jumlah_masuk" placeholder="0.00" required>
            </div>
            <div class="col-4">
              <label class="form-label">Satuan</label>
              <input type="text" class="form-control bg-light" name="satuan" id="satuanDisplayAdmin" readonly placeholder="Satuan">
            </div>
          </div>
          <div class="mb-3">
              <label class="form-label">Keterangan / Sumber</label>
              <input type="text" class="form-control" name="keterangan" placeholder="Contoh: Belanja Pasar Pagi / Suplai">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="simpan_stok_masuk" class="btn btn-success">Simpan Stok Masuk</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ================= JAVASCRIPT LOGIC ================= -->
<script>
function autoDetectKategori() {
    const sel = document.getElementById('selectBahanMenu');
    const opt = sel.options[sel.selectedIndex];
    const kat = opt.getAttribute('data-kat') || 'X1';
    const sat = opt.getAttribute('data-sat') || 'Kg';

    document.getElementById('inputKategoriMenu').value = kat;
    document.getElementById('badgeKategoriMenu').textContent = kat;
    document.getElementById('inputSatuanMenu').value = sat;
}

function autoSatuanStokAdmin() {
    const sel = document.getElementById('bahanSelectAdmin');
    const opt = sel.options[sel.selectedIndex];
    const sat = opt.getAttribute('data-sat') || 'Kg';
    document.getElementById('satuanDisplayAdmin').value = sat;
}

document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi Tombol Hitung Prediksi
    const btnHitung = document.getElementById('hitungPrediksiBtn');
    if (btnHitung) {
        btnHitung.addEventListener('click', function () {
            btnHitung.disabled = true;
            const originalHtml = btnHitung.innerHTML;
            btnHitung.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Melatih Model...';

            fetch('aksi/latih_model.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message || 'Model regresi berhasil diperbarui.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: data.message || 'Gagal menghitung model.' });
                }
                btnHitung.disabled = false;
                btnHitung.innerHTML = originalHtml;
            })
            .catch(err => {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi gangguan koneksi.' });
                btnHitung.disabled = false;
                btnHitung.innerHTML = originalHtml;
            });
        });
    }

    // 2. Inisialisasi Grafik Multivariabel 8 Garis (Jika canvas ada)
    const canvasChart = document.getElementById('chartMultivariabel');
    if (canvasChart) {
        const ctx = canvasChart.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [
                    {
                        label: 'Penjualan Aktual (Y)',
                        data: <?= json_encode($chart_actual) ?>,
                        borderColor: '#e02424',
                        backgroundColor: '#e02424',
                        yAxisID: 'y',
                        tension: 0,
                        pointRadius: 2.5
                    },
                    {
                        label: 'Total Prediksi (Y\')',
                        data: <?= json_encode($chart_predicted) ?>,
                        borderColor: '#f59e0b',
                        backgroundColor: '#f59e0b',
                        borderDash: [5, 5],
                        yAxisID: 'y',
                        tension: 0,
                        pointRadius: 2.5
                    },
                    { label: 'X1: Ayam', data: <?= json_encode($chart_x1) ?>, borderColor: '#3b82f6', yAxisID: 'y1', tension: 0, pointRadius: 2 },
                    { label: 'X2: Sapi/Tetelan', data: <?= json_encode($chart_x2) ?>, borderColor: '#8b5cf6', yAxisID: 'y1', tension: 0, pointRadius: 2 },
                    { label: 'X3: Beras', data: <?= json_encode($chart_x3) ?>, borderColor: '#10b981', yAxisID: 'y1', tension: 0, pointRadius: 2 },
                    { label: 'X4: Bumbu Merah', data: <?= json_encode($chart_x4) ?>, borderColor: '#ec4899', yAxisID: 'y1', tension: 0, pointRadius: 2 },
                    { label: 'X5: Bumbu Bawang', data: <?= json_encode($chart_x5) ?>, borderColor: '#eab308', yAxisID: 'y1', tension: 0, pointRadius: 2 },
                    { label: 'X6: Minyak/Santan', data: <?= json_encode($chart_x6) ?>, borderColor: '#06b6d4', yAxisID: 'y1', tension: 0, pointRadius: 2 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        title: { display: true, text: 'Penjualan (Porsi)' }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: 'Variabel Bahan Baku' },
                        min: 0,
                        max: 15
                    }
                }
            }
        });
    }

    // 3. Edit Menu Handler
    document.querySelectorAll('.btn-edit-menu').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('editIdMenu').value = this.dataset.id;
            document.getElementById('editNamaMenu').value = this.dataset.nama;
            document.getElementById('editHarga').value = this.dataset.harga;
        });

    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
