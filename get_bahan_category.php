<?php
/**
 * get_bahan_category.php - AJAX endpoint untuk mendapatkan kategori X bahan
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
$user_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($user_role, ['admin', 'owner'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya Admin/Owner yang diizinkan.']);
    exit;
}

// Set header untuk response JSON
header('Content-Type: application/json');

$bahan = $_GET['bahan'] ?? '';
if (!$bahan) {
    echo json_encode(['success' => false, 'message' => 'Parameter bahan diperlukan']);
    exit;
}

try {
    // Use the function we created in config.php
    $kategori = get_bahan_category($bahan);

    if ($kategori) {
        echo json_encode(['success' => true, 'kategori' => $kategori]);
    } else {
        echo json_encode(['success' => false, 'kategori' => '-', 'message' => 'Bahan tidak ditemukan dalam mapping']);
    }
} catch (Exception $e) {
    error_log("Error in get_bahan_category.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error server: ' . $e->getMessage()]);
}