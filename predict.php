<?php
// Mencegah output HTML error mengotori JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Pembacaan nama bahan
$nama_bahan = trim($_REQUEST['nama_bahan'] ?? '');

if (empty($nama_bahan)) {
    echo json_encode(['success' => false, 'message' => 'Parameter nama_bahan wajib diisi.']);
    exit;
}

try {
    // Database connection
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=warung_jawa;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 1. Get kategori_x for the selected bahan
    $stmt = $pdo->prepare("SELECT kategori_x FROM mapping_bahan WHERE nama_bahan = ?");
    $stmt->execute([$nama_bahan]);
    $mapping = $stmt->fetch();
    if (!$mapping) {
        throw new Exception("Bahan '$nama_bahan' tidak ditemukan dalam mapping.");
    }
    $kategori_x = $mapping['kategori_x'];

    // 2. Get latest model from model_regresi
    $stmtModel = $pdo->query("SELECT * FROM model_regresi ORDER BY id DESC LIMIT 1");
    $model = $stmtModel->fetch();
    if (!$model) {
        throw new Exception("Model regresi belum tersedia. Silakan latih model terlebih dahulu.");
    }

    $beta0 = (float) $model['beta0'];
    $beta1 = (float) $model['beta1'];
    $beta2 = (float) $model['beta2'];
    $beta3 = (float) $model['beta3'];
    $beta4 = (float) $model['beta4'];
    $beta5 = (float) $model['beta5'];
    $beta6 = (float) $model['beta6'];
    $mad = (float) $model['mad'];

    // 3. Get average usage (last 6 weeks) for each ingredient category from stok_keluar
    $stmtUsage = $pdo->query("
        SELECT
            m.kategori_x,
            AVG(s.jumlah_terpakai * m.faktor_konversi) as avg_usage
        FROM stok_keluar s
        JOIN mapping_bahan m ON s.nama_bahan = m.nama_bahan
        WHERE s.tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 WEEK)
        AND m.kategori_x IN ('X1','X2','X3','X4','X5','X6')
        GROUP BY m.kategori_x
    ");
    $avgUsageRows = $stmtUsage->fetchAll();
    $avgUsage = [];
    foreach ($avgUsageRows as $row) {
        $avgUsage[$row['kategori_x']] = (float) $row['avg_usage'];
    }
    // Ensure all categories are present (default 0)
    $categories = ['X1','X2','X3','X4','X5','X6'];
    foreach ($categories as $cat) {
        if (!isset($avgUsage[$cat])) {
            $avgUsage[$cat] = 0.0;
        }
    }

    // 4. Get average sales (last 6 weeks) for scaling
    $avgSalesStmt = $pdo->query("
        SELECT AVG(jumlah_porsi) as avg_sales
        FROM penjualan
        WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 WEEK)
    ");
    $avgSalesResult = $avgSalesStmt->fetch();
    $avgSalesActual = (float)($avgSalesResult['avg_sales'] ?? 0);

    // 5. Calculate predicted sales (total portions) using the model and average ingredient usage
    $predictedSales = $beta0
        + ($beta1 * $avgUsage['X1'])
        + ($beta2 * $avgUsage['X2'])
        + ($beta3 * $avgUsage['X3'])
        + ($beta4 * $avgUsage['X4'])
        + ($beta5 * $avgUsage['X5'])
        + ($beta6 * $avgUsage['X6']);

    // Ensure predicted sales is not negative
    $predictedSales = max(0, $predictedSales);

    // 6. Calculate predicted usage for the selected bahan category
    $ingredientUsage = $avgUsage[$kategori_x] ?? 0.0;
    $scaleFactor = ($avgSalesActual > 0) ? ($predictedSales / $avgSalesActual) : 1.0;
    $predictedUsage = $ingredientUsage * $scaleFactor;

    // 7. Prepare chart data from dataset_regresi
    $stmtChart = $pdo->query("
        SELECT tanggal, x1_ayam, x2_sapi, x3_beras, x4_bumbu_merah, x5_bumbu_bawang, x6_minyak, jumlah_porsi
        FROM dataset_regresi
        ORDER BY tanggal ASC
    ");
    $chartRows = $stmtChart->fetchAll();

    $chartLabels = [];
    $chartActual = [];
    $chartPredicted = [];

    foreach ($chartRows as $row) {
        $chartLabels[] = $row['tanggal'];
        $chartActual[] = (float) $row['jumlah_porsi'];

        // Compute predicted Y for this row using the model
        $yPred = $beta0
            + ($beta1 * (float) $row['x1_ayam'])
            + ($beta2 * (float) $row['x2_sapi'])
            + ($beta3 * (float) $row['x3_beras'])
            + ($beta4 * (float) $row['x4_bumbu_merah'])
            + ($beta5 * (float) $row['x5_bumbu_bawang'])
            + ($beta6 * (float) $row['x6_minyak']);
        $chartPredicted[] = round($yPred, 2);
    }

    // 8. Prepare equation string
    $equation = sprintf(
        "Y' = %.3f + %.3f(X�₁) + %.3f(X₂) + %.3f(X�₃) + %.3f(X�₄) + %.3f(X�₅) + %.3f(X�₆)",
        $beta0, $beta1, $beta2, $beta3, $beta4, $beta5, $beta6
    );

    // Success response
    $response = [
        'success' => true,
        'nama_bahan' => $nama_bahan,
        'kategori_x' => $kategori_x,
        'predicted_usage' => round($predictedUsage, 2),
        'satuan' => 'Kg',
        'mad' => round($mad, 3),
        'equation' => $equation,
        'chart' => [
            'labels' => $chartLabels,
            'actual' => $chartActual,
            'predicted' => $chartPredicted
        ]
    ];

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
}