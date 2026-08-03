<?php
$base = '';
require_once 'config.php';
require_once 'includes/auth.php';
$user_role = strtolower($_SESSION['role'] ?? '');
if (!in_array($user_role, ['admin', 'owner'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya Admin/Owner yang diizinkan.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$nama_bahan = trim($_REQUEST['nama_bahan'] ?? '');

if ($nama_bahan === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parameter nama_bahan wajib diisi.']);
    exit;
}

// Cek apakah bahan ada di database
$stmtCek = $pdo->prepare('SELECT COUNT(*) FROM stok_keluar WHERE nama_bahan = :nama');
$stmtCek->execute(['nama' => $nama_bahan]);

if ((int) $stmtCek->fetchColumn() === 0) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => "Data untuk bahan '{$nama_bahan}' tidak ditemukan.",
    ]);
    exit;
}

// Ambil satuan dominan untuk bahan ini (untuk ditampilkan di grafik)
$stmtSatuan = $pdo->prepare('
    SELECT satuan FROM stok_keluar
    WHERE nama_bahan = :nama
    GROUP BY satuan
    ORDER BY COUNT(*) DESC
    LIMIT 1
');
$stmtSatuan->execute(['nama' => $nama_bahan]);
$satuan_bahan = $stmtSatuan->fetchColumn() ?: 'Kg';

// Jalankan script Python secara aman dengan escapeshellarg()
$path_script = __DIR__ . DIRECTORY_SEPARATOR . 'predict.py';
$command = PYTHON_PATH . ' ' . escapeshellarg($path_script) . ' ' . escapeshellarg($nama_bahan) . ' 2>&1';

$output = shell_exec($command);

if ($output === null) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menjalankan Python. Periksa PYTHON_PATH dan fungsi shell_exec().',
    ]);
    exit;
}

$output = trim($output);
$data = json_decode($output, true);

if (json_last_error() !== JSON_ERROR_NONE || $data === null) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Output Python bukan JSON valid.',
        'raw_output' => $output,
    ]);
    exit;
}

// Jika Python mengembalikan field error
if (isset($data['error'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $data['error']]);
    exit;
}

$data['success'] = true;
$data['satuan'] = $satuan_bahan;

catat_aktivitas($pdo, 'Menjalankan prediksi Python untuk bahan: ' . $nama_bahan);

// Reset cache KPI prediksi agar dashboard menampilkan data terbaru
unset($_SESSION['kpi_prediksi_cache']);

echo json_encode($data);
