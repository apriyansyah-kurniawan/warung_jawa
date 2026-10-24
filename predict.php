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

    // 1. Get kategori_x dan satuan for the selected bahan
    $stmt = $pdo->prepare("SELECT kategori_x, satuan FROM mapping_bahan WHERE nama_bahan = ?");
    $stmt->execute([$nama_bahan]);
    $mapping = $stmt->fetch();
    if (!$mapping) {
        throw new Exception("Bahan '$nama_bahan' tidak ditemukan dalam mapping.");
    }
    $kategori_x = $mapping['kategori_x'];
    $satuan_bahan = $mapping['satuan'] ?? 'Kg';

    // 2. Get latest model from model_regresi
    $stmtModel = $pdo->query("SELECT * FROM model_regresi ORDER BY id DESC LIMIT 1");
    $model = $stmtModel->fetch();
    if (!$model) {
        throw new Exception("Model regresi belum tersedia. Silakan latih model terlebih dahulu.");
    }

    $beta0  = (float) ($model['beta0'] ?? 0);
    $beta1  = (float) ($model['beta1'] ?? 0);
    $beta2  = (float) ($model['beta2'] ?? 0);
    $beta3  = (float) ($model['beta3'] ?? 0);
    $beta4  = (float) ($model['beta4'] ?? 0);
    $beta5  = (float) ($model['beta5'] ?? 0);
    $beta6  = (float) ($model['beta6'] ?? 0);
    $beta7  = (float) ($model['beta7'] ?? 0);
    $beta8  = (float) ($model['beta8'] ?? 0);
    $beta9  = (float) ($model['beta9'] ?? 0);
    $beta10 = (float) ($model['beta10'] ?? 0);
    $beta11 = (float) ($model['beta11'] ?? 0);
    $mad    = (float) ($model['mad'] ?? 0);

    // 3. Get average usage (last 6 weeks) for each ingredient category from stok_keluar
    $categories = ['X1','X2','X3','X4','X5','X6','X7','X8','X9','X10','X11'];
    $inClause = "'" . implode("','", $categories) . "'";

    $stmtUsage = $pdo->query("
        SELECT
            m.kategori_x,
            AVG(s.jumlah_terpakai * m.faktor_konversi) as avg_usage
        FROM stok_keluar s
        JOIN mapping_bahan m ON s.nama_bahan = m.nama_bahan
        WHERE s.tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 WEEK)
        AND m.kategori_x IN ($inClause)
        GROUP BY m.kategori_x
    ");
    $avgUsageRows = $stmtUsage->fetchAll();
    $avgUsage = [];
    foreach ($avgUsageRows as $row) {
        $avgUsage[$row['kategori_x']] = (float) $row['avg_usage'];
    }
    // Ensure all categories are present (default 0)
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

    // 5. Calculate predicted sales (total portions) using the updated 11-feature model
    $predictedSales = $beta0
        + ($beta1 * $avgUsage['X1'])
        + ($beta2 * $avgUsage['X2'])
        + ($beta3 * $avgUsage['X3'])
        + ($beta4 * $avgUsage['X4'])
        + ($beta5 * $avgUsage['X5'])
        + ($beta6 * $avgUsage['X6'])
        + ($beta7 * $avgUsage['X7'])
        + ($beta8 * $avgUsage['X8'])
        + ($beta9 * $avgUsage['X9'])
        + ($beta10 * $avgUsage['X10'])
        + ($beta11 * $avgUsage['X11']);

    // Ensure predicted sales is not negative
    $predictedSales = max(0, $predictedSales);

    // 6. Calculate predicted usage for the selected bahan category
    $ingredientUsage = $avgUsage[$kategori_x] ?? 0.0;
    $scaleFactor = ($avgSalesActual > 0) ? ($predictedSales / $avgSalesActual) : 1.0;
    $predictedUsage = $ingredientUsage * $scaleFactor;

    // 7. Prepare chart data from dataset_regresi
    $stmtChart = $pdo->query("
        SELECT 
            tanggal, jumlah_porsi,
            x1_ayam, x2_sapi, x3_beras, x4_bumbu_merah, x5_bumbu_bawang, x6_minyak,
            COALESCE(x7_telur, 0) as x7_telur,
            COALESCE(x8_seafood, 0) as x8_seafood,
            COALESCE(x9_jeroan, 0) as x9_jeroan,
            COALESCE(x10_jengkol, 0) as x10_jengkol,
            COALESCE(x11_tahu_tempe, 0) as x11_tahu_tempe
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

        // Compute predicted Y for this row using the full 11-variable model
        $yPred = $beta0
            + ($beta1 * (float) $row['x1_ayam'])
            + ($beta2 * (float) $row['x2_sapi'])
            + ($beta3 * (float) $row['x3_beras'])
            + ($beta4 * (float) $row['x4_bumbu_merah'])
            + ($beta5 * (float) $row['x5_bumbu_bawang'])
            + ($beta6 * (float) $row['x6_minyak'])
            + ($beta7 * (float) $row['x7_telur'])
            + ($beta8 * (float) $row['x8_seafood'])
            + ($beta9 * (float) $row['x9_jeroan'])
            + ($beta10 * (float) $row['x10_jengkol'])
            + ($beta11 * (float) $row['x11_tahu_tempe']);

        $chartPredicted[] = round(max(0, $yPred), 2);
    }

    // 8. Prepare equation string
    $equation = sprintf(
        "Y' = %.3f + %.3f(X₁) + %.3f(X₂) + %.3f(X₃) + %.3f(X₄) + %.3f(X₅) + %.3f(X₆) + %.3f(X₇) + %.3f(X₈) + %.3f(X₉) + %.3f(X₁₀) + %.3f(X₁₁)",
        $beta0, $beta1, $beta2, $beta3, $beta4, $beta5, $beta6, $beta7, $beta8, $beta9, $beta10, $beta11
    );

    // Success response
    $response = [
        'success' => true,
        'nama_bahan' => $nama_bahan,
        'kategori_x' => $kategori_x,
        'predicted_usage' => round($predictedUsage, 2),
        'satuan' => $satuan_bahan,
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