<?php
/**
 * stok_masuk.php — Halaman & penyimpanan stok masuk.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
cek_login();

$user_role = strtolower((string) ($_SESSION['role'] ?? ''));
if (!in_array($user_role, ['admin', 'owner', 'kasir'], true)) {
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Akses ditolak.'];
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal      = trim($_POST['tanggal'] ?? '');
    $nama_bahan   = trim($_POST['nama_bahan'] ?? '');
    $jumlah_masuk = trim($_POST['jumlah_masuk'] ?? '');
    $satuan       = trim($_POST['satuan'] ?? 'Kg');
    $keterangan   = trim($_POST['keterangan'] ?? '');
    $id_user      = (int) $_SESSION['user_id'];
    $errors = [];

    if ($tanggal === '') $errors[] = 'Tanggal wajib diisi.';
    if ($nama_bahan === '') $errors[] = 'Nama bahan wajib diisi.';
    if ($jumlah_masuk === '' || !is_numeric($jumlah_masuk) || (float) $jumlah_masuk <= 0) {
        $errors[] = 'Jumlah masuk harus angka lebih dari 0.';
    }
    if (!in_array($satuan, DAFTAR_SATUAN, true)) {
        $errors[] = 'Satuan tidak valid.';
    }

    if (!empty($errors)) {
        $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => implode(' ', $errors)];
        header('Location: stok_masuk.php');
        exit;
    }

    $stmt = $pdo->prepare('
        INSERT INTO stok_masuk (tanggal, nama_bahan, jumlah_masuk, satuan, keterangan, id_user)
        VALUES (:tanggal, :nama_bahan, :jumlah, :satuan, :keterangan, :id_user)
    ');
    $stmt->execute([
        'tanggal'    => $tanggal,
        'nama_bahan' => $nama_bahan,
        'jumlah'     => $jumlah_masuk,
        'satuan'     => $satuan,
        'keterangan' => $keterangan !== '' ? $keterangan : null,
        'id_user'    => $id_user,
    ]);

    catat_aktivitas($pdo, 'Stok masuk: ' . $nama_bahan . ' ' . format_jumlah($jumlah_masuk, $satuan));
    $_SESSION['flash'] = [
        'tipe'  => 'success',
        'pesan' => 'Stok masuk ' . $nama_bahan . ' (' . format_jumlah($jumlah_masuk, $satuan) . ') berhasil dicatat.',
    ];
    header('Location: stok_masuk.php');
    exit;
}

$judul_halaman = 'Stok Masuk';
$page_aktif = 'stok_masuk';
$daftar_bahan = [];
try {
    $daftar_bahan = $pdo->query('SELECT nama_bahan FROM mapping_bahan ORDER BY nama_bahan')->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable) {
    $daftar_bahan = $pdo->query('SELECT DISTINCT nama_bahan FROM stok_masuk ORDER BY nama_bahan')->fetchAll(PDO::FETCH_COLUMN);
}
$riwayat = $pdo->query('
    SELECT s.*, u.username
    FROM stok_masuk s
    LEFT JOIN users u ON u.id = s.id_user
    ORDER BY s.tanggal DESC, s.id DESC
    LIMIT 20
')->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 mb-6">
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Catat Stok Masuk</h2>
    <form method="POST" action="stok_masuk.php" class="grid md:grid-cols-5 gap-4">
        <div>
            <label class="block text-sm text-slate-600 mb-1">Tanggal</label>
            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Nama Bahan</label>
            <input type="text" name="nama_bahan" list="listBahan" required class="w-full border border-slate-300 rounded-lg px-3 py-2">
            <datalist id="listBahan">
                <?php foreach ($daftar_bahan as $b): ?>
                    <option value="<?= htmlspecialchars($b) ?>"></option>
                <?php endforeach; ?>
            </datalist>
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Jumlah</label>
            <input type="number" name="jumlah_masuk" step="0.01" min="0.01" required class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Satuan</label>
            <select name="satuan" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                <?php foreach (DAFTAR_SATUAN as $s): ?><option><?= htmlspecialchars($s) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Keterangan</label>
            <input type="text" name="keterangan" class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </div>
        <div class="md:col-span-5">
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg shadow-sm transition">Simpan</button>
        </div>
    </form>
</div>
<div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6">
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Riwayat Terbaru</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-3 py-2">Tanggal</th>
                    <th class="text-left px-3 py-2">Bahan</th>
                    <th class="text-right px-3 py-2">Jumlah</th>
                    <th class="text-left px-3 py-2">Petugas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($riwayat as $row): ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-2"><?= htmlspecialchars($row['tanggal']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($row['nama_bahan']) ?></td>
                        <td class="px-3 py-2 text-right"><?= htmlspecialchars(format_jumlah($row['jumlah_masuk'], $row['satuan'])) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($row['username'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
