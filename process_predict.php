<?php
/**
 * Proses Prediksi dan Pelatihan Model
 * -------------------------------------------------------------------------
 * File ini menangani:
 * 1. Pemanggilan pelatihan model (train_model.py) via shell_exec
 * 2. API untuk mengambil koefisien aktif dari model_regresi
 * 3. API untuk menghitung prediksi berdasarkan input X1-X6
 * -------------------------------------------------------------------------
prediksi berdasarkan input X1-X6
 * -------------------------------------------------------------------------
 */

// Pastikan session sudah aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan hanya Admin dan Owner yang bisa mengakses endpoint ini
require_once 'config.php';
require_once 'includes/auth.php';
$user_role = strtolower($_SESSION['role'] ?? $_SESSION['user_role'] ?? '');
$is_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['username']);

if (!$is_logged_in || ($user_role === 'kasir')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya Admin/Owner yang diizinkan.']);
    exit;
}

// Set header untuk response JSON
header('Content-Type: application/json; charset=utf-8');

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
function hitung_prediksi($koefisien, $x1, $x2, $x3, $x4, $x5, $x6)
{
    // Rumus: Ŷ = β0 + β1*X1 + β2*X2 + β3*X3 + β4*X4 + β5*X5 + β6*X6
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

// Handle different actions based on POST parameter
$action = $_POST['action'] ?? $_GET['action'] ?? 'info';

switch ($action) {
    case 'train':
        // Trigger pelatihan model dengan menjalankan train_model.py
        try {
            $script_path = __DIR__ . DIRECTORY_SEPARATOR . 'train_model.py';

            // Check if the Python script exists
            if (!file_exists($script_path)) {
                throw new Exception('File Python script tidak ditemukan: ' . $script_path);
            }

            // Check if the Python script is readable
            if (!is_readable($script_path)) {
                throw new Exception('File Python script tidak readable: ' . $script_path);
            }

            // Check if shell_exec function is available
            if (!function_exists('shell_exec')) {
                throw new Exception('Fungsi shell_exec adalah disabled pada server ini.');
            }

            $command = escapeshellcmd(PYTHON_PATH . ' ' . escapeshellarg($script_path) . ' 2>&1');

            // Jalankan script Python dan tangkap output
            $output = shell_exec($command);

            if ($output === null) {
                // Provide more detailed error information
                $errorMsg = 'Gagal menjalankan Python script. ';
                if (!function_exists('shell_exec')) {
                    $errorMsg .= 'Fungsi shell_exec adalah disabled pada server ini. ';
                } else {
                    $errorMsg .= 'Periksa PYTHON_PATH dan pastikan train_model.py memiliki izin eksekusi. ';
                }
                $errorMsg .= 'PYTHON_PATH yang digunakan: ' . PYTHON_PATH;
                throw new Exception($errorMsg);
            }

            $output = trim($output);
            $data = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE || $data === null) {
                throw new Exception('Output Python bukan JSON valid: ' . $output);
            }

            if (isset($data['error'])) {
                throw new Exception($data['error']);
            }

            // Catat aktivitas
            catat_aktivitas($pdo, 'Melatih model regresi linier berganda via train_model.py');

            echo json_encode([
                'success' => true,
                'message' => 'Model berhasil dilatih!',
                'data' => $data
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error saat melatih model: ' . $e->getMessage()
            ]);
        }
        break;

    case 'get_coefficients':
        // Ambil koefisien aktif dari database
        $result = ambil_koefisien_aktif($pdo);

        if ($result['success']) {
            catat_aktivitas($pdo, 'Mengambil koefisien model regresi aktif');
        }

        echo json_encode($result);
        break;

    case 'predict':
        // Hitung prediksi berdasarkan input X1-X6
        // Validasi input
        $x1 = isset($_POST['x1']) ? floatval($_POST['x1']) : 0;
        $x2 = isset($_POST['x2']) ? floatval($_POST['x2']) : 0;
        $x3 = isset($_POST['x3']) ? floatval($_POST['x3']) : 0;
        $x4 = isset($_POST['x4']) ? floatval($_POST['x4']) : 0;
        $x5 = isset($_POST['x5']) ? floatval($_POST['x5']) : 0;
        $x6 = isset($_POST['x6']) ? floatval($_POST['x6']) : 0;

        // Ambil koefisien aktif
        $koefisien_result = ambil_koefisien_aktif($pdo);

        // Jika gagal mendapatkan koefisien, kembalikan fallback 0.00
        if (!$koefisien_result['success']) {
            // Catat aktivitas untuk debug
            catat_aktivitas($pdo,
                "Gagal mendapatkan koefisien untuk prediksi: " . $koefisien_result['message'] .
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
            break;
        }

        // Hitung prediksi
        try {
            $prediksi = hitung_prediksi(
                $koefisien_result,
                $x1, $x2, $x3, $x4, $x5, $x6
            );
        } catch (Exception $e) {
            // Jika gagal menghitung prediksi, kembalikan fallback 0.00
            // Catat aktivitas untuk debug
            catat_aktivitas($pdo,
                "Gagal menghitung prediksi: " . $e->getMessage() .
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
            break;
        }

        // Catat aktivitas
        catat_aktivitas($pdo,
            "Menghitung prediksi stok: X1=$x1, X2=$x2, X3=$x3, X4=$x4, X5=$x5, X6=$x6 => Ŷ=$prediksi"
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
        break;

    case 'info':
    default:
        // Informasi geral tentang endpoint ini
        $model_info = ambil_koefisien_aktif($pdo);

        echo json_encode([
            'success' => true,
            'message' => 'Endpoint untuk pelatihan dan prediksi model regresi',
            'available_actions' => [
                'train' => 'Melatih model dengan menjalankan train_model.py',
                'get_coefficients' => 'Mengambil koefisien aktif dari model_regresi',
                'predict' => 'Menghitung prediksi berdasarkan input X1-X6'
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
        break;
}
?>