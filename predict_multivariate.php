<?php
/**
 * Multivariata Prediksi Stok
 * -------------------------------------------------------------------------
 * Skrip ini menggunakan model regresi linier berganda dari database
 * untuk memprediksi jumlah porsi (Y) berdasarkan input 6 variabel:
 * X1: Ayam, X2: Sapi/Tetelan, X3: Beras, X4: Bumbu Merah, X5: Bumbu Bawang, X6: Minyak/Santan
 * -------------------------------------------------------------------------
 */

// Mencegah output HTML error mengotori JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Pastikan hanya Admin dan Owner yang bisa mengakses endpoint ini
require_once 'config.php';
require_once 'includes/auth.php';
$user_role = strtolower($_SESSION['role'] ?? '');
$is_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['username']);

if (!$is_logged_in || ($user_role === 'kasir')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya Admin/Owner yang diizinkan.']);
    exit;
}

// Fungsi untuk mengambil koefisien aktif dari tabel model_regresi
function ambil_koefisien_aktif($pdo)
{
    try {
        $stmt = $pdo->query('SELECT * FROM model_regresi ORDER BY id DESC LIMIT 1');
        $model = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($model) {
            return [
                'success' => true,
                'beta0' => (float) $model['beta0'],
                'beta1' => (float) $model['beta1'],
                'beta2' => (float) $model['beta2'],
                'beta3' => (float) $model['beta3'],
                'beta4' => (float) $model['beta4'],
                'beta5' => (float) $model['beta5'],
                'beta6' => (float) $model['beta6'],
                'mad' => (float) $model['mad'],
                'r_square' => (float) $model['r_square'],
                'jumlah_data_training' => (int) $model['jumlah_data_training'],
                'tanggal_training' => $model['tanggal_training']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Belum ada model yang dilatih. Silakan latih model terlebih dahulu.'
            ];
        }
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error database: ' . $e->getMessage()
        ];
    }
}

// Fungsi untuk menghitung prediksi berdasarkan koefisien dan input X1-X6
function hitung_prediksi_multivariat($koefisien, $x1, $x2, $x3, $x4, $x5, $x6)
{
    // Rumus: Y�̂ = β0 + β1*X1 + β2*X2 + β3*X3 + β4*X4 + β5*X5 + β6*X6
    $y_pred = $koefisien['beta0']
        + ($koefisien['beta1'] * $x1)
        + ($koefisien['beta2'] * $x2)
        + ($koefisien['beta3'] * $x3)
        + ($koefisien['beta4'] * $x4)
        + ($koefisien['beta5'] * $x5)
        + ($koefisien['beta6'] * $x6);

    // Prediksi tidak boleh negatif
    return max(0, $y_pred);
}

// Handle request
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Ambil input X1-X6 dari form
    $x1 = isset($_POST['x1']) ? floatval($_POST['x1']) : 0;
    $x2 = isset($_POST['x2']) ? floatval($_POST['x2']) : 0;
    $x3 = isset($_POST['x3']) ? floatval($_POST['x3']) : 0;
    $x4 = isset($_POST['x4']) ? floatval($_POST['x4']) : 0;
    $x5 = isset($_POST['x5']) ? floatval($_POST['x5']) : 0;
    $x6 = isset($_POST['x6']) ? floatval($_POST['x6']) : 0;

    // Ambil koefisien aktif dari database
    $koefisien_result = ambil_koefisien_aktif($pdo);

    // Jika gagal mendapatkan koefisien
    if (!$koefisien_result['success']) {
        // Catat aktivitas untuk debug
        catat_aktivitas($pdo,
            "Gagal mendapatkan koefisien untuk prediksi multivariat: " . $koefisien_result['message'] .
            ". Menggunakan prediksi default 0.0 untuk X1=$x1, X2=$x2, X3=$x3, X4=$x4, X5=$x5, X6=$x6"
        );

        echo json_encode([
            'success' => true,
            'prediksi' => 0.0,
            'input' => [
                'x1' => $x1,
                'x2' => $x2,
                'x3' => $x3,
                'x4' => $x4,
                'x5' => $x5,
                'x6' => $x6
            ],
            'koefisien_used' => [
                'beta0' => 0.0,
                'beta1' => 0.0,
                'beta2' => 0.0,
                'beta3' => 0.0,
                'beta4' => 0.0,
                'beta5' => 0.0,
                'beta6' => 0.0
            ],
            'model_info' => [
                'mad' => 0.0,
                'r_square' => 0.0,
                'jumlah_data_training' => 0
            ],
            'message' => 'Koefisien model tidak tersedia. Menggunakan prediksi default 0.0.'
        ]);
        exit;
    }

    // Hitung prediksi
    try {
        $prediksi = hitung_prediksi_multivariat(
            $koefisien_result,
            $x1, $x2, $x3, $x4, $x5, $x6
        );
    } catch (Exception $e) {
        // Jika gagal menghitung prediksi, kembalikan fallback 0.00
        // Catat aktivitas untuk debug
        catat_aktivitas($pdo,
            "Gagal menghitung prediksi multivariat: " . $e->getMessage() .
            ". Menggunakan prediksi default 0.0 untuk X1=$x1, X2=$x2, X3=$x3, X4=$x4, X5=$x5, X6=$x6"
        );

        echo json_encode([
            'success' => true,
            'prediksi' => 0.0,
            'input' => [
                'x1' => $x1,
                'x2' => $x2,
                'x3' => $x3,
                'x4' => $x4,
                'x5' => $x5,
                'x6' => $x6
            ],
            'koefisien_used' => [
                'beta0' => $koefisien_result['beta0'],
                'beta1' => $koefisien_result['beta1'],
                'beta2' => $koefisien_result['beta2'],
                'beta3' => $koefisien_result['beta3'],
                'beta4' => $koefisien_result['beta4'],
                'beta5' => $koefisien_result['beta5'],
                'beta6' => $koefisien_result['beta6']
            ],
            'model_info' => [
                'mad' => $koefisien_result['mad'],
                'r_square' => $koefisien_result['r_square'],
                'jumlah_data_training' => $koefisien_result['jumlah_data_training']
            ],
            'message' => 'Error dalam perhitungan prediksi. Menggunakan prediksi default 0.0.'
        ]);
        exit;
    }

    // Catat aktivitas
    catat_aktivitas($pdo,
        "Menghitung prediksi multivariat stok: X1=$x1, X2=$x2, X3=$x3, X4=$x4, X5=$x5, X6=$x6 => Y�̂=$prediksi"
    );

    echo json_encode([
        'success' => true,
        'prediksi' => $prediksi,
        'input' => [
            'x1' => $x1,
            'x2' => $x2,
            'x3' => $x3,
            'x4' => $x4,
            'x5' => $x5,
            'x6' => $x6
        ],
        'koefisien_used' => [
            'beta0' => $koefisien_result['beta0'],
            'beta1' => $koefisien_result['beta1'],
            'beta2' => $koefisien_result['beta2'],
            'beta3' => $koefisien_result['beta3'],
            'beta4' => $koefisien_result['beta4'],
            'beta5' => $koefisien_result['beta5'],
            'beta6' => $koefisien_result['beta6']
        ],
        'model_info' => [
            'mad' => $koefisien_result['mad'],
            'r_square' => $koefisien_result['r_square'],
            'jumlah_data_training' => $koefisien_result['jumlah_data_training']
        ]
    ]);
    exit;
} else {
    // GET request - informasi geral
    $model_info = ambil_koefisien_aktif($pdo);

    echo json_encode([
        'success' => true,
        'message' => 'Endpoint untuk prediksi multivariat menggunakan model regresi linier berganda',
        'available_actions' => [
            'POST' => 'Mengirimkan input X1-X6 untuk mendapatkan prediksi Y (jumlah porsi)'
        ],
        'model_status' => $model_info['success'] ? 'tersedia' : 'belum tersedia',
        'model_info' => $model_info['success'] ? [
            'beta0' => $model_info['beta0'],
            'beta1' => $model_info['beta1'],
            'beta2' => $model_info['beta2'],
            'beta3' => $model_info['beta3'],
            'beta4' => $model_info['beta4'],
            'beta5' => $model_info['beta5'],
            'beta6' => $model_info['beta6'],
            'mad' => $model_info['mad'],
            'r_square' => $model_info['r_square'],
            'jumlah_data_training' => $model_info['jumlah_data_training'],
            'tanggal_training' => $model_info['tanggal_training']
        ] : null
    ]);
    exit;
}
?>