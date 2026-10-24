<?php
/**
 * menu.php - CRUD handler untuk master_menu
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
cek_login();

function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

$user_role = strtolower((string) ($_SESSION['role'] ?? ''));
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === '' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $judul_halaman = 'Manajemen Menu';
    $page_aktif = 'menu';
    $daftar_menu = [];
    try {
        $daftar_menu = $pdo->query('SELECT * FROM master_menu ORDER BY nama_menu')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable) {
        $daftar_menu = [];
    }
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 mb-6">
        <?php if (in_array($user_role, ['admin', 'owner'], true)): ?>
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Tambah Menu</h2>
            <form method="POST" action="menu.php" class="grid md:grid-cols-3 gap-4">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Nama Menu</label>
                    <input type="text" name="nama_menu" required class="w-full border border-slate-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Harga</label>
                    <input type="number" name="harga" min="1" required class="w-full border border-slate-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Bahan Resep</label>
                    <input type="text" name="resep_nama_bahan" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Pengali / Porsi</label>
                    <input type="number" name="resep_pengali" step="0.001" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Satuan</label>
                    <select name="resep_satuan" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                        <?php foreach (DAFTAR_SATUAN as $s): ?><option><?= htmlspecialchars($s) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="is_menu_utama" value="1" class="rounded"> Menu utama
                    </label>
                    <button type="submit" class="ml-auto px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg shadow-sm transition">Simpan</button>
                </div>
            </form>
        <?php else: ?>
            <h2 class="text-lg font-semibold text-slate-800 mb-2">Katalog Menu Kasir</h2>
            <p class="text-sm text-slate-500">Pilih menu untuk transaksi harian. Pengelolaan resep hanya untuk Admin/Owner.</p>
        <?php endif; ?>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6">
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($daftar_menu as $menu): ?>
                <div class="border border-slate-200 rounded-xl p-4">
                    <div class="font-semibold text-slate-800"><?= htmlspecialchars($menu['nama_menu']) ?></div>
                    <div class="text-emerald-600 font-medium mt-1">Rp <?= number_format((float) $menu['harga'], 0, ',', '.') ?></div>
                    <?php if (!empty($menu['resep_nama_bahan'])): ?>
                        <div class="text-xs text-slate-500 mt-2">Resep: <?= htmlspecialchars($menu['resep_nama_bahan']) ?></div>
                    <?php endif; ?>
                    <?php if (in_array($user_role, ['admin', 'owner'], true)): ?>
                        <form method="POST" action="menu.php" class="mt-3" onsubmit="return confirm('Hapus menu ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $menu['id'] ?>">
                            <button class="text-sm text-rose-600 hover:underline" type="submit">Hapus</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

if (!in_array($user_role, ['admin', 'owner'], true)) {
    if (isAjax()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    } else {
        $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Akses ditolak. Hanya Admin/Owner yang diizinkan mengubah menu.'];
        header('Location: menu.php');
    }
    exit;
}

if (!in_array($action, ['create', 'update', 'delete'], true)) {
    if (isAjax()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
    } else {
        $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Aksi tidak valid'];
        header('Location: menu.php');
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
                header('Location: menu.php');
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
                header('Location: menu.php');
                exit;
            }
            break;

        case 'delete':
            // Delete menu - support flexible ID parameter names and methods
            $id = $_GET['id'] ?? $_POST['id'] ?? $_GET['id_menu'] ?? $_POST['id_menu'] ?? null;

            // Validate ID target
            if ($id === null || $id === '' || !is_numeric($id) || (int)$id <= 0) {
                throw new Exception('ID menu tidak valid');
            }
            $id = (int)$id;

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
                header('Location: menu.php');
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
        header('Location: menu.php');
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
        header('Location: menu.php');
        exit;
    }
}