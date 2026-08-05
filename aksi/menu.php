<?php
/**
 * menu.php - CRUD handler untuk master_menu
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

// Helper to check if request is AJAX
function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Role check: only admin and owner allowed
$user_role = strtolower($_SESSION['role'] ?? '');
if (!in_array($user_role, ['admin', 'owner'])) {
    if (isAjax()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    } else {
        $_SESSION['flash'] = [
            'tipe' => 'danger',
            'pesan' => 'Akses ditolak. Hanya Admin/Owner yang diizinkan.'
        ];
        header('Location: ../index.php');
    }
    exit;
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Validate action
if (!in_array($action, ['create', 'update', 'delete'])) {
    if (isAjax()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
    } else {
        $_SESSION['flash'] = [
            'tipe' => 'danger',
            'pesan' => 'Aksi tidak valid'
        ];
        header('Location: ../index.php');
    }
    exit;
}

try {
    switch ($action) {
        case 'create':
            // Create new menu
            $nama = trim($_POST['nama_menu'] ?? '');
            $harga = (float)($_POST['harga'] ?? 0);
            $isUtama = isset($_POST['is_menu_utama']) ? (int)$_POST['is_menu_utama'] : 0;
            $bahan = trim($_POST['resep_nama_bahan'] ?? '');
            $pengali = (float)($_POST['resep_pengali'] ?? 0);
            $satuan = trim($_POST['resep_satuan'] ?? 'Kg');

            // Validation
            if ($nama === '') {
                throw new Exception('Nama menu harus diisi');
            }
            if ($harga <= 0) {
                throw new Exception('Harga harus lebih besar dari 0');
            }

            // Optional validation: warn if bahan not in mapping
            if ($bahan !== '' && !get_bahan_category($bahan)) {
                // Just log warning, don't prevent saving
                error_log("WARNING: Bahan '$bahan' tidak ditemukan dalam mapping_bahan untuk menu '$nama'");
            }

            $stmt = $pdo->prepare('
                INSERT INTO master_menu (nama_menu, harga, is_menu_utama, resep_nama_bahan, resep_pengali, resep_satuan)
                VALUES (:nama, :harga, :isUtama, :bahan, :pengali, :satuan)
            ');
            $stmt->execute([
                'nama' => $nama,
                'harga' => $harga,
                'isUtama' => $isUtama,
                'bahan' => $bahan,
                'pengali' => $pengali,
                'satuan' => $satuan
            ]);

            $id = $pdo->lastInsertId();

            // Log activity
            catat_aktivitas($pdo, "Menambah menu: $nama (Harga: Rp " . number_format($harga,0,',','.') . ")");

            if (isAjax()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu berhasil ditambahkan',
                    'data' => ['id' => $id]
                ]);
            } else {
                $_SESSION['flash'] = [
                    'tipe' => 'success',
                    'pesan' => 'Menu berhasil ditambahkan'
                ];
                header('Location: ../index.php?page=menu');
                exit;
            }
            break;

        case 'update':
            // Update existing menu
            $id = (int)($_POST['menu_id'] ?? 0);
            $nama = trim($_POST['nama_menu'] ?? '');
            $harga = (float)($_POST['harga'] ?? 0);
            $isUtama = isset($_POST['is_menu_utama']) ? (int)$_POST['is_menu_utama'] : 0;
            $bahan = trim($_POST['resep_nama_bahan'] ?? '');
            $pengali = (float)($_POST['resep_pengali'] ?? 0);
            $satuan = trim($_POST['resep_satuan'] ?? 'Kg');

            // Validation
            if ($id <= 0) {
                throw new Exception('ID menu tidak valid');
            }
            if ($nama === '') {
                throw new Exception('Nama menu harus diisi');
            }
            if ($harga <= 0) {
                throw new Exception('Harga harus lebih besar dari 0');
            }

            // Optional validation: warn if bahan not in mapping
            if ($bahan !== '' && !get_bahan_category($bahan)) {
                // Just log warning, don't prevent saving
                error_log("WARNING: Bahan '$bahan' tidak ditemukan dalam mapping_bahan untuk menu '$nama'");
            }

            $stmt = $pdo->prepare('
                UPDATE master_menu
                SET nama_menu = :nama,
                    harga = :harga,
                    is_menu_utama = :isUtama,
                    resep_nama_bahan = :bahan,
                    resep_pengali = :pengali,
                    resep_satuan = :satuan
                WHERE id = :id
            ');
            $stmt->execute([
                'nama' => $nama,
                'harga' => $harga,
                'isUtama' => $isUtama,
                'bahan' => $bahan,
                'pengali' => $pengali,
                'satuan' => $satuan,
                'id' => $id
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Menu tidak ditemukan atau tidak ada perubahan');
            }

            // Log activity
            catat_aktivitas($pdo, "Mengupdate menu ID $id: $nama");

            if (isAjax()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu berhasil diperbarui'
                ]);
            } else {
                $_SESSION['flash'] = [
                    'tipe' => 'success',
                    'pesan' => 'Menu berhasil diperbarui'
                ];
                header('Location: ../index.php?page=menu');
                exit;
            }
            break;

        case 'delete':
            // Delete menu
            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID menu tidak valid');
            }

            // Check if menu exists first
            $checkStmt = $pdo->prepare('SELECT nama_menu FROM master_menu WHERE id = :id');
            $checkStmt->execute(['id' => $id]);
            $menu = $checkStmt->fetch();

            if (!$menu) {
                throw new Exception('Menu tidak ditemukan');
            }

            // Delete the menu
            $stmt = $pdo->prepare('DELETE FROM master_menu WHERE id = :id');
            $stmt->execute(['id' => $id]);

            // Log activity
            catat_aktivitas($pdo, "Menghapus menu: " . $menu['nama_menu']);

            if (isAjax()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu berhasil dihapus'
                ]);
            } else {
                $_SESSION['flash'] = [
                    'tipe' => 'success',
                    'pesan' => 'Menu berhasil dihapus'
                ];
                header('Location: ../index.php?page=menu');
                exit;
            }
            break;
    }
} catch (Exception $e) {
    if (isAjax()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } else {
        $_SESSION['flash'] = [
            'tipe' => 'danger',
            'pesan' => $e->getMessage()
        ];
        header('Location: ../index.php?page=menu');
        exit;
    }
} catch (PDOException $e) {
    if (isAjax()) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    } else {
        $_SESSION['flash'] = [
            'tipe' => 'danger',
            'pesan' => 'Database error: ' . $e->getMessage()
        ];
        header('Location: ../index.php?page=menu');
        exit;
    }
}