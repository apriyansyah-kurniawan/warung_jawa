<?php
header('Content-Type: text/html; charset=UTF-8');
/** panel_prediksi.php — Panel Analitik Prediksi Stok (Real-time, 6 bahan baku) */
if (!adalah_owner() && !adalah_admin()) return;

// Fetch latest model
$modelEq = '';
$modelMad = '';
$modelR2 = '';
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
    $stmtModel = $pdo->query("SELECT beta0, beta1, beta2, beta3, beta4, beta5, beta6, mad, r_square FROM model_regresi ORDER BY id DESC LIMIT 1");
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
    $modelMad = isset($model['mad']) ? number_format((float)$model['mad'], 3) : '0.000';
    $modelR2 = isset($model['r_square']) ? number_format((float)$model['r_square'], 3) : '0.000';
    // Use Unicode subscript for clarity
    $modelEq = sprintf("Y' = %.3f + %.3f(X<sub>1</sub>) + %.3f(X<sub>2</sub>) + %.3f(X<sub>3</sub>) + %.3f(X<sub>4</sub>) + %.3f(X<sub>5</sub>) + %.3f(X<sub>6</sub>)", $beta0, $beta1, $beta2, $beta3, $beta4, $beta5, $beta6);

    // 2. Average usage (last 7 days) for each ingredient and average daily portions from dataset_regresi
    $stmtAvg = $pdo->prepare("
        SELECT
            AVG(x1_ayam) as avg_x1,
            AVG(x2_sapi) as avg_x2,
            AVG(x3_beras) as avg_x3,
            AVG(x4_bumbu_merah) as avg_x4,
            AVG(x5_bumbu_bawang) as avg_x5,
            AVG(x6_minyak) as avg_x6,
            AVG(jumlah_porsi) as avg_y
        FROM dataset_regresi
        WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmtAvg->execute();
    $avgRow = $stmtAvg->fetch();
    if ($avgRow && ($avgRow['avg_x1'] !== null || $avgRow['avg_y'] !== null)) {
        $avg_x1 = (float) ($avgRow['avg_x1'] ?? 0);
        $avg_x2 = (float) ($avgRow['avg_x2'] ?? 0);
        $avg_x3 = (float) ($avgRow['avg_x3'] ?? 0);
        $avg_x4 = (float) ($avgRow['avg_x4'] ?? 0);
        $avg_x5 = (float) ($avgRow['avg_x5'] ?? 0);
        $avg_x6 = (float) ($avgRow['avg_x6'] ?? 0);
        $avg_y  = (float) ($avgRow['avg_y']  ?? 0);
    } else {
        // Fallback to overall average if no recent data
        $stmtAvgAll = $pdo->query("
            SELECT
                AVG(x1_ayam) as avg_x1,
                AVG(x2_sapi) as avg_x2,
                AVG(x3_beras) as avg_x3,
                AVG(x4_bumbu_merah) as avg_x4,
                AVG(x5_bumbu_bawang) as avg_x5,
                AVG(x6_minyak) as avg_x6,
                AVG(jumlah_porsi) as avg_y
            FROM dataset_regresi
        ");
        $avgRowAll = $stmtAvgAll->fetch();
        $avg_x1 = (float) ($avgRowAll['avg_x1'] ?? 0);
        $avg_x2 = (float) ($avgRowAll['avg_x2'] ?? 0);
        $avg_x3 = (float) ($avgRowAll['avg_x3'] ?? 0);
        $avg_x4 = (float) ($avgRowAll['avg_x4'] ?? 0);
        $avg_x5 = (float) ($avgRowAll['avg_x5'] ?? 0);
        $avg_x6 = (float) ($avgRowAll['avg_x6'] ?? 0);
        $avg_y  = (float) ($avgRowAll['avg_y']  ?? 0);
    }

    // 3. Predicted daily portions using model and average ingredient usage
    $predYDay = $beta0
        + ($beta1 * $avg_x1)
        + ($beta2 * $avg_x2)
        + ($beta3 * $avg_x3)
        + ($beta4 * $avg_x4)
        + ($beta5 * $avg_x5)
        + ($beta6 * $avg_x6);
    // Estimated weekly portions (next week)
    $weeklyPortions = $predYDay * 7;
    $weeklyPortions = max(0, $weeklyPortions);
    $totalEstimasiPorsi = round($weeklyPortions, 2);

    // 4. Estimated weekly usage for each bahan
    // Ingredient per portion ratio (if avg_y > 0)
    if ($avg_y > 0) {
        $prediksiUsage['X1'] = round($avg_x1 * 7, 2); // weekly usage = avg per day * 7
        $prediksiUsage['X2'] = round($avg_x2 * 7, 2);
        $prediksiUsage['X3'] = round($avg_x3 * 7, 2);
        $prediksiUsage['X4'] = round($avg_x4 * 7, 2);
        $prediksiUsage['X5'] = round($avg_x5 * 7, 2);
        $prediksiUsage['X6'] = round($avg_x6 * 7, 2);
    } else {
        // Avoid division by zero
        $prediksiUsage['X1'] = $prediksiUsage['X2'] = $prediksiUsage['X3'] = $prediksiUsage['X4'] = $prediksiUsage['X5'] = $prediksiUsage['X6'] = 0.0;
    }

    // 5. Chart data: historical data from dataset_regresi (last 30 days, ordered by tanggal)
    $stmtChart = $pdo->query("
        SELECT tanggal, x1_ayam, x2_sapi, x3_beras, x4_bumbu_merah, x5_bumbu_bawang, x6_minyak, jumlah_porsi
        FROM dataset_regresi
        WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
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
    $modelR2 = '-';
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
        <div class="table-responsive mb-0">
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

        <!-- Line Chart Estimasi 6 Bahan Baku (Mingguan) -->
        <div class="chart-container" style="position: relative; height: 220px; width: 100%;">
            <canvas id="grafikBar"></canvas>
        </div>

        <!-- Card Persamaan Regresi & Akurasi -->
        <div class="mb-4">
            <div class="fw-bold">Model Regresi Linier Berganda:</div>
            <div class="text-muted small" id="modelEquation"><?= $modelEq ?></div>
            <div class="text-muted small">
                Nilai MAD: <span id="modelMad"><?= htmlspecialchars($modelMad) ?></span> &nbsp;|&nbsp;
                R²: <span id="modelR2"><?= htmlspecialchars($modelR2) ?></span> &nbsp;|&nbsp;
                Total Estimasi Penjualan: <strong><?= number_format($totalEstimasiPorsi, 2) ?></strong> Porsi
            </div>
        </div>

    </div>
</div>

<script src="assets/js/dashboard.js" defer></script>
<script>

    // Line Chart for estimated weekly usage
    const ctxBar = document.getElementById('grafikBar').getContext('2d');
    new Chart(ctxBar, {
        type: 'line',
        data: {
            labels: ['Ayam', 'Sapi/Tetelan', 'Beras', 'Bumbu Merah', 'Bumbu Bawang', 'Minyak/Santan'],
            datasets: [{
                data: [
                    <?= number_format($prediksiUsage["X1"], 2) ?>,
                    <?= number_format($prediksiUsage["X2"], 2) ?>,
                    <?= number_format($prediksiUsage["X3"], 2) ?>,
                    <?= number_format($prediksiUsage["X4"], 2) ?>,
                    <?= number_format($prediksiUsage["X5"], 2) ?>,
                    <?= number_format($prediksiUsage["X6"], 2) ?>
                ],
                tension: 0.3,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                pointRadius: 5,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Estimasi Kebutuhan Bahan Baku Minggu Depan'
                },
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Kg / Liter'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Bahan Baku'
                    }
                }
            }
        }
    });
</script>