<?php
/**
 * latih_model.php — Latih model regresi (Python jika ada, fallback PHP OLS).
 */
ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/regresi.php';

cek_login();

if (!(adalah_owner() || adalah_admin())) {
    http_response_code(403);
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

try {
    $hasil = latih_model_regresi($pdo);
    if (function_exists('catat_aktivitas')) {
        catat_aktivitas($pdo, 'Melatih model regresi (' . ($hasil['engine'] ?? 'php') . ')');
    }
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Model regresi berhasil dilatih (' . ($hasil['engine'] ?? 'php') . '). R² ' . number_format((float) ($hasil['r_square'] ?? 0), 4) . ', MAD ' . number_format((float) ($hasil['mad'] ?? 0), 4),
        'model' => $hasil,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
