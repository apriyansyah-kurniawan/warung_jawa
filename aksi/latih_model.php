<?php
/**
 * latih_model.php - Handler untuk melatih model regresi menggunakan script Python
 */
session_start();
require_once '../config.php';
require_once '../includes/auth.php';

// Hanya boleh diakses oleh Owner atau Admin
if (!(adalah_owner() || adalah_admin())) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

// Pastikan ada koneksi PDO
if (empty($pdo)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Koneksi database tidak tersedia']);
    exit;
}

// Path ke skrip Python dan virtual environment
$pythonPath = __DIR__ . '/../venv/bin/python';
$scriptPath = __DIR__ . '/../train_model.py';

if (!file_exists($pythonPath) || !file_exists($scriptPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Python atau skrip pelatihan tidak ditemukan']);
    exit;
}

// Jalankan skrip Python dan tangkap output
$command = escapeshellcmd($pythonPath . ' ' . $scriptPath);
$output = [];
$returnVar = 0;
exec($command, $output, $returnVar);

// Output dari skrip Python adalah JSON (berdasarkan train_model.py)
$jsonOutput = implode("\n", $output);
$result = json_decode($jsonOutput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    // Jika output bukan JSON, mungkin ada error output
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Output dari skrip Python tidak valid: ' . $jsonOutput]);
    exit;
}

if ($returnVar !== 0 || empty($result['success'])) {
    // Skrip Python mengembalikan kesalahan
    $errorMessage = $result['error'] ?? 'Terjadi kesalahan saat melatih model';
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $errorMessage]);
    exit;
}

// Pelatihan berhasil, catat aktivitas
$deskripsi = 'User Owner/Admin telah melatih model regresi.';
if (!empty($_SESSION['username'])) {
    // Gunakan username dari session
    $deskripsi = 'User ' . htmlspecialchars($_SESSION['username'], ENT_QUOTES) . ' telah melatih model regresi.';
} else {
    $deskripsi = 'User Owner/Admin telah melatih model regresi.';
}
catat_aktivitas($pdo, $deskripsi);

// Kembalikan respons sukses
echo json_encode([
    'success' => true,
    'message' => 'Model regresi berhasil dilatih',
    'model' => $result // Opsional: sertakan detail model jika diperlukan
]);
exit;
?>