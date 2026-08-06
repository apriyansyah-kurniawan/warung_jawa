<?php
header('Content-Type: text/html; charset=UTF-8');
/** panel_prediksi.php — Panel Analitik Prediksi Stok (Real-time, 6 bahan baku) */
if (!adalah_owner() && !adalah_admin()) return;

// Fetch latest model
$modelEq = '';
$modelMad = '';
$totalEstimasiPorsi = 0;
$prediksiUsage = [ // X1..X6
    'X1' => 0.0, // Ayam
    'X2' => 0.0, // Sapi/Tetelan
    'X3' => 0.0, // Beras
    'X4' => 0.0, // Bumbu Merah
    'X5' => 0.0, // Bumbu Bawang
    'X6' => 0.0  // Minyak/Santan
];
$chartLabels = [];
$chartActual = []; // Y actual (jumlah_porsi)
$chartPredicted = []; // Y predicted from model

try {
    // 1. Latest model
    $stmtModel = $pdo->query("SELECT * FROM model_regresi ORDER BY id DESC LIMIT 1");
    $model = $stmtModel->fetch();
    if (!$model) {
        throw new Exception("Model belum tersedia");
    }
    $beta0 = (float) $model['beta0'];
    $beta1 = (float) $model['beta1'];
    $beta2 = (float) $model['beta2'];
    $beta3 = (float) $model['beta3'];
    $beta4 = (float) $model['beta4'];
    $beta5 = (float) $model['beta5'];
    $beta6 = (float) $model['beta6'];
    // $mad from model not used; we'll compute our own MAD from dataset_regresi
    $modelEq = sprintf("Y' = %.3f + %.3f(X<sub>1</sub>) + %.3f(X<sub>2</sub>) + %.3f(X<sub>3</sub>) + %.3f(X<sub>4</sub>) + %.3f(X<sub>5</sub>) + %.3f(X<sub>6</sub>)", $beta0, $beta1, $beta2, $beta3, $beta4, $beta5, $beta6);

    // 2. Average usage (last 4 weeks) for each ingredient from dataset_regresi
    $stmtAvg = $pdo->query("
        SELECT
            AVG(x1_ayam) as avg_x1,
            AVG(x2_sapi) as avg_x2,
            AVG(x3_beras) as avg_x3,
            AVG(x4_bumbu_merah) as avg_x4,
            AVG(x5_bumbu_bawang) as avg_x5,
            AVG(x6_minyak) as avg_x6
        FROM dataset_regresi
        WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK)
    ");
    $avgRow = $stmtAvg->fetch();
    if ($avgRow) {
        $avg_x1 = (float) $avgRow['avg_x1'];
        $avg_x2 = (float) $avgRow['avg_x2'];
        $avg_x3 = (float) $avgRow['avg_x3'];
        $avg_x4 = (float) $avgRow['avg_x4'];
        $avg_x5 = (float) $avgRow['avg_x5'];
        $avg_x6 = (float) $avgRow['avg_x6'];
    } else {
        // Fallback to overall average if no recent data
        $stmtAvgAll = $pdo->query("
            SELECT
                AVG(x1_ayam) as avg_x1,
                AVG(x2_sapi) as avg_x2,
                AVG(x3_beras) as avg_x3,
                AVG(x4_bumbu_merah) as avg_x4,
                AVG(x5_bumbu_bawang) as avg_x5,
                AVG(x6_minyak) as avg_x6
            FROM dataset_regresi
        ");
        $avgRowAll = $stmtAvgAll->fetch();
        $avg_x1 = (float) ($avgRowAll['avg_x1'] ?? 0);
        $avg_x2 = (float) ($avgRowAll['avg_x2'] ?? 0);
        $avg_x3 = (float) ($avgRowAll['avg_x3'] ?? 0);
        $avg_x4 = (float) ($avgRowAll['avg_x4'] ?? 0);
        $avg_x5 = (float) ($avgRowAll['avg_x5'] ?? 0);
        $avg_x6 = (float) ($avgRowAll['avg_x6'] ?? 0);
    }

    // 3. Compute predicted total portions (Y_hat) using the model and average ingredient usage
    $predicted_total = $beta0
        + ($beta1 * $avg_x1)
        + ($beta2 * $avg_x2)
        + ($beta3 * $avg_x3)
        + ($beta4 * $avg_x4)
        + ($beta5 * $avg_x5)
        + ($beta6 * $avg_x6);
    $predicted_total = max(0, $predicted_total);
    $totalEstimasiPorsi = round($predicted_total, 2);

    // 4. Estimated usage for each bahan for next week = average from dataset_regresi (last 4 weeks)
    $prediksiUsage['X1'] = round($avg_x1, 2);
    $prediksiUsage['X2'] = round($avg_x2, 2);
    $prediksiUsage['X3'] = round($avg_x3, 2);
    $prediksiUsage['X4'] = round($avg_x4, 2);
    $prediksiUsage['X5'] = round($avg_x5, 2);
    $prediksiUsage['X6'] = round($avg_x6, 2);

    // 5. Compute MAD: mean absolute deviation between actual Y and predicted Y from dataset_regresi (all rows)
    $stmtMad = $pdo->query("
        SELECT jumlah_porsi, x1_ayam, x2_sapi, x3_beras, x4_bumbu_merah, x5_bumbu_bawang, x6_minyak
        FROM dataset_regresi
    ");
    $madRows = $stmtMad->fetchAll();
    $totalError = 0.0;
    $count = 0;
    foreach ($madRows as $row) {
        $yActual = (float) $row['jumlah_porsi'];
        $yPred = $beta0
            + ($beta1 * (float) $row['x1_ayam'])
            + ($beta2 * (float) $row['x2_sapi'])
            + ($beta3 * (float) $row['x3_beras'])
            + ($beta4 * (float) $row['x4_bumbu_merah'])
            + ($beta5 * (float) $row['x5_bumbu_bawang'])
            + ($beta6 * (float) $row['x6_minyak']);
        $totalError += abs($yPred - $yActual);
        $count++;
    }
    $mad = ($count > 0) ? ($totalError / $count) : 0.0;
    $modelMad = number_format($mad, 3);

    // 6. Chart data: historical data from dataset_regresi (all rows, ordered by tanggal)
    $stmtChart = $pdo->query("
        SELECT tanggal, x1_ayam, x2_sapi, x3_beras, x4_bumbu_merah, x5_bumbu_bawang, x6_minyak, jumlah_porsi
        FROM dataset_regresi
        ORDER BY tanggal ASC
    ");
    $chartRows = $stmtChart->fetchAll();
    foreach ($chartRows as $row) {
        $chartLabels[] = $row['tanggal'];
        $chartActual[] = (float) $row['jumlah_porsi'];

        $yPred = $beta0
            + ($beta1 * (float) $row['x1_ayam'])
            + ($beta2 * (float) $row['x2_sapi'])
            + ($beta3 * (float) $row['x3_beras'])
            + ($beta4 * (float) $row['x4_bumbu_merah'])
            + ($beta5 * (float) $row['x5_bumbu_bawang'])
            + ($beta6 * (float) $row['x6_minyak']);
        $chartPredicted[] = round($yPred, 2);
    }

} catch (Exception $e) {
    // Fallback values
    $modelEq = 'Model belum tersedia';
    $modelMad = '-';
    $totalEstimasiPorsi = 0;
    $prediksiUsage = [
        'X1' => 0.0, 'X2' => 0.0, 'X3' => 0.0,
        'X4' => 0.0, 'X5' => 0.0, 'X6' => 0.0
    ];
    $chartLabels = [];
    $chartActual = [];
    $chartPredicted = [];
}
?>

<div class="modern-card" id="prediksi">
    <div class="card-header">
        <span><i class="bi bi-graph-up-arrow"></i> Panel Analitik Prediksi Stok</span>
        <a href="export/print_laporan.php?jenis=stok" target="_blank" class="btn btn-sm btn-light btn-export">
            <i class="bi bi-printer"></i> Cetak
        </a>
    </div>
    <div class="card-body">

        <!-- Tabel Hasil Prediksi Real-time untuk 6 Bahan Baku -->
        <div class="table-responsive mb-4">
            <table class="table table-align-middle table-bordered border-primary">
                <thead class="table-primary">
                    <tr>
                        <th>No</th>
                        <th>Bahan Baku</th>
                        <th>Estimasi Kebutuhan Minggu Depan</th>
                        <th>Satuan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Ayam</td>
                        <td><strong><?= number_format($prediksiUsage['X1'], 2) ?></strong></td>
                        <td>Kg</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Sapi/Tetelan</td>
                        <td><strong><?= number_format($prediksiUsage['X2'], 2) ?></strong></td>
                        <td>Kg</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Beras</td>
                        <td><strong><?= number_format($prediksiUsage['X3'], 2) ?></strong></td>
                        <td>Kg</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>Bumbu Merah</td>
                        <td><strong><?= number_format($prediksiUsage['X4'], 2) ?></strong></td>
                        <td>Kg</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>Bumbu Bawang</td>
                        <td><strong><?= number_format($prediksiUsage['X5'], 2) ?></strong></td>
                        <td>Kg</td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>Minyak/Santan</td>
                        <td><strong><?= number_format($prediksiUsage['X6'], 2) ?></strong></td>
                        <td>Liter</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Card Persamaan Regresi & Akurasi -->
        <div class="mb-4">
            <div class="fw-bold">Model Regresi Linier Berganda:</div>
            <div class="text-muted small" id="modelEquation"><?= htmlspecialchars($modelEq) ?></div>
            <div class="text-muted small">
                Nilai MAD: <span id="modelMad"><?= htmlspecialchars($modelMad) ?></span> &nbsp;|&nbsp;
                Total Estimasi Penjualan: <strong><?= number_format($totalEstimasiPorsi, 2) ?></strong> Porsi
            </div>
        </div>

        <!-- Grafik Tren Penjualan & Prediksi -->
        <div>
            <canvas id="grafikPrediksi"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="assets/js/dashboard.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('grafikPrediksi').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [
                {
                    label: 'Actual (Jumlah Porsi)',
                    data: <?= json_encode($chartActual) ?>,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    tension: 0.1,
                    fill: false
                },
                {
                    label: 'Predicted (Model)',
                    data: <?= json_encode($chartPredicted) ?>,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    tension: 0.1,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Tren Jumlah Porsi (Actual vs Prediksi)'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
});
</script>