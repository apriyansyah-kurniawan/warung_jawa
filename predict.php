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

// Jalankan script Python dengan path absolute
$python_bin = '/usr/local/bin/python3';
$script_path = __DIR__ . DIRECTORY_SEPARATOR . 'predict.py';
$command = $python_bin . ' ' . escapeshellarg($script_path) . ' ' . escapeshellarg($nama_bahan) . ' 2>&1';

$output = shell_exec($command);

if ($output === null || trim($output) === '') {
    echo json_encode(['success' => false, 'message' => 'Gagal menjalankan Python script atau output kosong.']);
    exit;
}

$output = trim($output);
$data = json_decode($output, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    echo json_encode([
        'success' => false,
        'message' => 'Respon Python bukan JSON valid.',
        'raw' => $output
    ]);
    exit;
}

if (isset($data['error'])) {
    echo json_encode(['success' => false, 'message' => $data['error']]);
    exit;
}

// Respon sukses
$data['success'] = true;
$data['satuan'] = 'Kg';

echo json_encode($data);
exit;