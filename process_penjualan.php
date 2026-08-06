<?php
/**
 * process_penjualan.php
 * Handles multi-item sales checkout via AJAX.
 * Expects JSON payload: {
 *   items: [{nama_menu, jumlah_porsi, harga_satuan}, ...],
 *   order_type: 'dinein'|'takeaway',
 *   tanggal: 'YYYY-MM-DD'
 * }
 * Uses PDO transaction to insert each item into penjualan and deduct stock via stok_keluar.
 */
require_once 'config.php';
require_once 'includes/auth.php';
mulai_session();

// Enable error display for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$user_role = strtolower($_SESSION['role'] ?? '');
if (!in_array($user_role, ['kasir', 'admin', 'owner'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya Kasir/Admin/Owner yang diizinkan.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$items = $data['items'] ?? [];
$order_type = $data['order_type'] ?? '';
$tanggal = $data['tanggal'] ?? date('Y-m-d');

if (empty($items) || !is_array($items)) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada item yang valid']);
    exit;
}

// Validate each item
foreach ($items as $index => $item) {
    if (!isset($item['nama_menu']) || !isset($item['jumlah_porsi']) || !isset($item['harga_satuan'])) {
        echo json_encode(['success' => false, 'message' => "Item indeks $index tidak valid"]);
        exit;
    }
    $item['jumlah_porsi'] = (int)$item['jumlah_porsi'];
    $item['harga_satuan'] = (float)$item['harga_satuan'];
    if ($item['jumlah_porsi'] <= 0) {
        echo json_encode(['success' => false, 'message' => "Jumlah porsi harus > 0 untuk item $index"]);
        exit;
    }
    $item['total_harga'] = $item['jumlah_porsi'] * $item['harga_satuan'];
    $items[$index] = $item; // ensure we have total_harga
}

// Use the same ambil_menu function as insert_penjualan.php
function ambil_menu($nama_menu) {
    $menu_db = get_menu_from_db();
    if ($menu_db !== null && isset($menu_db[$nama_menu])) {
        return $menu_db[$nama_menu];
    }
    return defined('DAFTAR_MENU') && isset(DAFTAR_MENU[$nama_menu]) ? DAFTAR_MENU[$nama_menu] : null;
}

$pdo = null;
try {
    $dsn = 'mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=' . DB_NAME . ';charset=utf8mb4';
    $opsi_pdo = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opsi_pdo);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $e->getMessage()]);
    exit;
}

$id_user = (int)($_SESSION['user_id'] ?? 0);
if ($id_user <= 0) {
    echo json_encode(['success' => false, 'message' => 'User ID tidak valid']);
    exit;
}

// Wrap the transaction processing in try-catch to catch Throwable
try {
    $pdo->beginTransaction();

    foreach ($items as $item) {
        $nama_menu = trim($item['nama_menu']);
        $jumlah_porsi = $item['jumlah_porsi'];
        $harga_satuan = $item['harga_satuan'];
        $total_harga = $item['total_harga'];

        $menu = ambil_menu($nama_menu);
        if ($menu === null) {
            throw new Exception("Menu '$nama_menu' tidak ditemukan.");
        }

        // Insert penjualan
        $stmtPenjualan = $pdo->prepare('
            INSERT INTO penjualan (tanggal, nama_menu, jumlah_porsi, total_harga, id_user)
            VALUES (:tanggal, :nama_menu, :jumlah_porsi, :total_harga, :id_user)
        ');
        $stmtPenjualan->execute([
            'tanggal'      => $tanggal,
            'nama_menu'    => $nama_menu,
            'jumlah_porsi' => $jumlah_porsi,
            'total_harga'  => $total_harga,
            'id_user'      => $id_user,
        ]);

        // Hitung bahan terpakai dan insert ke stok_keluar
        $resep = $menu['resep'];
        $jumlah_terpakai = round($jumlah_porsi * $resep['pengali'], 2);

        $stmtStok = $pdo->prepare('
            INSERT INTO stok_keluar (tanggal, nama_bahan, jumlah_terpakai, satuan, id_user)
            VALUES (:tanggal, :nama_bahan, :jumlah, :satuan, :id_user)
        ');
        $stmtStok->execute([
            'tanggal'    => $tanggal,
            'nama_bahan' => $resep['nama_bahan'],
            'jumlah'     => $jumlah_terpakai,
            'satuan'     => $resep['satuan'],
            'id_user'    => $id_user,
        ]);
    }

    $pdo->commit();

    // Log activity (optional)
    // catat_aktivitas($pdo, sprintf('Penjualan multi-item: %d items', count($items)));

    echo json_encode(['success' => true, 'message' => 'Transaksi berhasil']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("process_penjualan.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal memproses transaksi: ' . $e->getMessage()
    ]);
    exit;
}
?>