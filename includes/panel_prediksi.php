<?php
/** panel_prediksi.php — Chart.js + AJAX ke predict.php (Owner) */
if (!adalah_owner()) return;

$daftar_bahan_prediksi = $pdo->query('SELECT DISTINCT nama_bahan FROM stok_keluar ORDER BY nama_bahan ASC')->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="modern-card" id="prediksi">
    <div class="card-header">
        <span><i class="bi bi-graph-up-arrow"></i> Panel Analitik Prediksi Stok</span>
        <a href="export/print_laporan.php?jenis=stok" target="_blank" class="btn btn-sm btn-light btn-export">
            <i class="bi bi-printer"></i> Cetak
        </a>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="selectBahan" class="form-label">Nama Bahan</label>
                <select id="selectBahan" class="form-select">
                    <?php foreach ($daftar_bahan_prediksi as $bahan): ?>
                        <option value="<?= htmlspecialchars($bahan) ?>"><?= htmlspecialchars($bahan) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <button id="btnPrediksi" class="btn btn-success w-100">
                    <i class="bi bi-lightning"></i> Hitung Prediksi Kebutuhan Stok
                </button>
            </div>
        </div>
        <div id="loadingIndicator" class="text-center mt-3 d-none">
            <div class="spinner-border text-success"></div>
            <p class="text-muted mt-2 mb-0">Menghitung prediksi via Python...</p>
        </div>
        <div id="errorBox" class="alert alert-danger mt-3 d-none"></div>
    </div>
</div>

<div class="modern-card">
    <div class="card-header">Grafik & Hasil Prediksi</div>
    <div class="card-body">
        <div id="chartContainer"><canvas id="grafikPrediksi"></canvas></div>
        <hr>
        <div id="hasilPrediksi" class="text-center">
            <p class="text-muted mb-0">Klik tombol prediksi untuk melihat grafik regresi linear.</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="assets/js/dashboard.js"></script>
