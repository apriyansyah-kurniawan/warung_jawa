<?php
/**
 * Panel ADMIN
 * -------------------------------------------------------------------------
 * Admin mengelola sistem secara global:
 *   - Manajemen user (tambah / hapus)
 *   - Pemantauan data penjualan & stok
 *   - Koreksi manual stok_keluar (jika diperlukan)
 *   - Pengecekan teknis engine Python
 *   - Manajemen menu dan resep (hanya Admin/Owner)
 * -------------------------------------------------------------------------
 */

// Hanya Admin dan Owner yang bisa mengakses panel ini
cek_role(['Admin', 'Owner']);

$daftar_users = $pdo->query('SELECT id, username, role, created_at FROM users ORDER BY id ASC')->fetchAll();

$stmtPenjualan = $pdo->query('
    SELECT p.*, u.username AS kasir FROM penjualan p
    JOIN users u ON p.id_user = u.id ORDER BY p.tanggal DESC LIMIT 10
');
$daftar_penjualan = $stmtPenjualan->fetchAll();

$stmtStok = $pdo->query('
    SELECT s.*, u.username AS petugas FROM stok_keluar s
    JOIN users u ON s.id_user = u.id ORDER BY s.tanggal DESC LIMIT 10
');
$daftar_stok = $stmtStok->fetchAll();

$stmtBahan = $pdo->query('SELECT DISTINCT nama_bahan FROM stok_keluar ORDER BY nama_bahan ASC');
$daftar_bahan = $stmtBahan->fetchAll(PDO::FETCH_COLUMN);

$data_edit = null;
if (!empty($_GET['edit'])) {
    $id_edit = (int) $_GET['edit'];
    $stmtEdit = $pdo->prepare('SELECT * FROM stok_keluar WHERE id = :id');
    $stmtEdit->execute(['id' => $id_edit]);
    $data_edit = $stmtEdit->fetch();
}

// Statistik sistem
$total_users     = count($daftar_users);
$total_penjualan = (float) $pdo->query('SELECT COALESCE(SUM(total_harga),0) FROM penjualan')->fetchColumn();
$total_stok_log  = (int) $pdo->query('SELECT COUNT(*) FROM stok_keluar')->fetchColumn();
?>

<!-- Manajemen User -->
<div class="modern-card" id="users">
    <div class="card-header"><span><i class="bi bi-people"></i> Manajemen User</span></div>
    <div class="card-body">
        <form method="POST" action="aksi/tambah_user.php" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" minlength="6" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="Admin">Admin</option>
                    <option value="Kasir">Kasir</option>
                    <option value="Owner">Owner</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Tambah User</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Dibuat</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftar_users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($user['role']) ?></span></td>
                            <td><?= htmlspecialchars($user['created_at']) ?></td>
                            <td class="text-center">
                                <?php if ((int) $user['id'] !== (int) $_SESSION['user_id']): ?>
                                    <form method="POST" action="aksi/hapus_user.php" class="d-inline"
                                          onsubmit="return confirmDelete(this, 'Hapus user ini?')">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                <?php else: ?>
                                    <span class="text-muted small">(Anda)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Manajemen Menu dan Resep -->
<div class="modern-card" id="menu-management">
    <div class="card-header">
        <span><i class="bi bi-menu-app"></i> Manajemen Menu dan Resep</span>
        <?php if (adalah_admin() || adalah_owner()): ?>
            <button type="button" class="btn btn-sm btn-success ms-2" data-bs-toggle="modal" data-bs-target="#modalTambahMenu">
                + Tambah Menu Baru
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="menuTable">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Menu</th>
                        <th>Harga</th>
                        <th>Resep Bahan</th>
                        <th>Kategori X</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get all menus from master_menu
                    $stmtMenu = $pdo->query("
                        SELECT mm.*,
                               CASE WHEN mm.is_menu_utama = 1 THEN 'Ya' ELSE 'Tidak' END as is_utama_text
                        FROM master_menu mm
                        ORDER BY mm.is_menu_utama DESC, mm.nama_menu
                    ");
                    $daftar_menu = $stmtMenu->fetchAll();

                    $no = 1;
                    foreach ($daftar_menu as $menu):
                        // Get the category for the bahan
                        $kategoriX = '-';
                        if (!empty($menu['resep_nama_bahan'])) {
                            $kategoriX = get_bahan_category($menu['resep_nama_bahan']) ?? '-';
                        }
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($menu['nama_menu']) ?></td>
                        <td><?= format_rupiah((float)$menu['harga']) ?></td>
                        <td>
                            <?php if (!empty($menu['resep_nama_bahan']) && !empty($menu['resep_pengali'])): ?>
                                <?= htmlspecialchars($menu['resep_pengali']) ?>
                                <?= htmlspecialchars($menu['resep_nama_bahan']) ?>
                                (<?= htmlspecialchars($menu['resep_satuan'] ?? 'Kg') ?>)
                            <?php else: ?>
                                <span class="text-muted">Belum diatur</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($kategoriX) && $kategoriX !== '-'): ?>
                                <span class="badge bg-info"><?= htmlspecialchars($kategoriX) ?></span>
                            <?php else: ?>
                                <span class="text-muted">Belum terdeteksi</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int)$menu['is_menu_utama'] === 1): ?>
                                <span class="badge bg-success">Utama</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Tidak Utama</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if (adalah_admin() || adalah_owner()): ?>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditMenu"
                                            data-id="<?= $menu['id'] ?>"
                                            data-nama="<?= htmlspecialchars($menu['nama_menu']) ?>"
                                            data-harga="<?= (float)$menu['harga'] ?>"
                                            data-resep-bahan="<?= htmlspecialchars($menu['resep_nama_bahan'] ?? '') ?>"
                                            data-resep-pengali="<?= (float)$menu['resep_pengali'] ?? 0 ?>"
                                            data-resep-satuan="<?= htmlspecialchars($menu['resep_satuan'] ?? 'Kg') ?>"
                                            data-is-utama="<?= (int)$menu['is_menu_utama'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="aksi/menu.php" class="d-inline"
                                          onsubmit="return confirmDelete(this, 'Hapus menu <?= htmlspecialchars($menu['nama_menu']) ?>?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $menu['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Menu -->
<div class="modal fade" id="modalTambahMenu" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formTambahMenu" method="POST" action="aksi/menu.php">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Menu Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Menu *</label>
                            <input type="text" name="nama_menu" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Harga (Rp) *</label>
                            <input type="number" name="harga" class="form-control" min="0" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Bahan Baku *</label>
                            <input type="text" name="resep_nama_bahan" class="form-control" list="listBahanMenu" required>
                            <datalist id="listBahanMenu">
                                <?php foreach ($daftar_bahan as $bahan): ?>
                                    <option value="<?= htmlspecialchars($bahan) ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <small class="text-muted">Pilih bahan dari daftar stok</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pengali (per porsi)</label>
                            <input type="number" step="0.01" min="0" name="resep_pengali" class="form-control" value="1.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Satuan</label>
                            <select name="resep_satuan" class="form-select">
                                <?php foreach (DAFTAR_SATUAN as $satuan): ?>
                                    <option value="<?= $satuan ?>" <?= $satuan === 'Kg' ? 'selected' : '' ?>><?= $satuan ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Isi Menu Utama (untuk kasir)</label>
                            <div class="form-check">
                                <input type="checkbox" name="is_menu_utama" value="1" class="form-check-input" checked>
                                <label class="form-check-label">Tampilkan di menu kasir</label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3">
                        <strong>Catatan:</strong> Bahan baku akan otomatidikaitkan ke variabel X1-X6 berdasarkan tabel mapping_bahan.
                        Jika bahan tidak ditemukan di mapping, Anda akan mendapat peringatan untuk menambahkannya terlebih dahulu.
                    </div>

                    <div id="kategoriInfo" class="mt-2 p-3 bg-light rounded">
                        <strong>Kategori Terdeteksi:</strong> <span id="kategoriXValue">-</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="action" value="create" class="btn btn-primary">Simpan Menu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Menu -->
<div class="modal fade" id="modalEditMenu" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formEditMenu" method="POST" action="aksi/menu.php">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="menu_id" id="editMenuId">
                    <input type="hidden" name="action" value="update">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Menu *</label>
                            <input type="text" name="nama_menu" class="form-control" id="editNamaMenu" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Harga (Rp) *</label>
                            <input type="number" name="harga" class="form-control" min="0" id="editHarga" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Bahan Baku *</label>
                            <input type="text" name="resep_nama_bahan" class="form-control" id="editResepBahan" list="listBahanMenuEdit" required>
                            <datalist id="listBahanMenuEdit">
                                <?php foreach ($daftar_bahan as $bahan): ?>
                                    <option value="<?= htmlspecialchars($bahan) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pengali (per porsi)</label>
                            <input type="number" step="0.01" min="0" name="resep_pengali" class="form-control" id="editResepPengali" value="1.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Satuan</label>
                            <select name="resep_satuan" class="form-select" id="editResepSatuan">
                                <?php foreach (DAFTAR_SATUAN as $satuan): ?>
                                    <option value="<?= $satuan ?>" <?= $satuan === 'Kg' ? 'selected' : '' ?>><?= $satuan ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Isi Menu Utama (untuk kasir)</label>
                            <div class="form-check">
                                <input type="checkbox" name="is_menu_utama" value="1" class="form-check-input" id="editIsUtama" checked>
                                <label class="form-check-label">Tampilkan di menu kasir</label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3">
                        <strong>Catatan:</strong> Bahan baku akan otomatidikaitkan ke variabel X1-X6 berdasarkan tabel mapping_bahan.
                        Jika bahan tidak ditemukan di mapping, Anda akan mendapat peringatan untuk menambahkannya terlebih dahulu.
                    </div>

                    <div id="kategoriInfoEdit" class="mt-2 p-3 bg-light rounded">
                        <strong>Kategori Terdeteksi:</strong> <span id="kategoriXValueEdit">-</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Perbarui Menu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Monitoring penjualan & stok -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="modern-card h-100">
            <div class="card-header">
                <span>Penjualan Terbaru</span>
                <a href="export/csv_penjualan.php" class="btn btn-sm btn-light btn-export">CSV</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Tanggal</th><th>Menu</th><th class="text-end">Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar_penjualan as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['tanggal']) ?></td>
                                <td><?= htmlspecialchars($row['nama_menu']) ?></td>
                                <td class="text-end"><?= format_rupiah($row['total_harga']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="modern-card h-100">
            <div class="card-header">
                <span>Stok Keluar Terbaru</span>
                <a href="export/csv_stok_keluar.php" class="btn btn-sm btn-light btn-export">CSV</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Tanggal</th><th>Bahan</th><th class="text-end">Jumlah</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar_stok as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['tanggal']) ?></td>
                                <td><?= htmlspecialchars($row['nama_bahan']) ?></td>
                                <td class="text-end"><?= format_jumlah($row['jumlah_terpakai'], $row['satuan']) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-stok-keluar"
                                            data-id="<?= $row['id'] ?>"
                                            data-tanggal="<?= $row['tanggal'] ?>"
                                            data-nama-bahan="<?= htmlspecialchars($row['nama_bahan']) ?>"
                                            data-jumlah="<?= $row['jumlah_terpakai'] ?>"
                                            data-satuan="<?= htmlspecialchars($row['satuan']) ?>">Edit</button>
                                    <button class="btn btn-sm btn-link text-danger p-0" data-bs-toggle="modal"
                                            data-bs-target="#modalHapusAdmin"
                                            data-id="<?= $row['id'] ?>"
                                            data-info="<?= htmlspecialchars($row['nama_bahan']) ?>"
                                            data-type="stok_keluar">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="modalHapusAdmin" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Hapus Stok Keluar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">Hapus data stok: <strong id="infoHapusAdmin"></strong>?</div>
            <div class="modal-footer">
                <form method="POST" action="aksi/hapus_stok.php">
                    <input type="hidden" name="id" id="idHapusAdmin">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Load DataTables CSS and JS dynamically if not already loaded
function loadjQueryAndDataTables() {
    // Load jQuery if not present
    if (typeof jQuery === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
        script.onload = function() {
            loadDataTablesPlugin();
        };
        document.head.appendChild(script);
    } else {
        loadDataTablesPlugin();
    }
}

function loadDataTablesPlugin() {
    if (!jQuery.fn.dataTable) {
        // Load DataTables CSS
        var css = document.createElement('link');
        css.rel = 'stylesheet';
        css.type = 'text/css';
        css.href = 'https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css';
        document.head.appendChild(css);

        // Load DataTables JS
        jQuery.getScript("https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js")
            .done(function() {
                jQuery.getScript("https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js", function() {
                    initializeDataTable();
                });
            });
    } else {
        initializeDataTable();
    }
}

// Check if we need to load DataTables (requires jQuery)
if (typeof jQuery === 'undefined' || !jQuery.fn.dataTable) {
    loadjQueryAndDataTables();
} else {
    initializeDataTable();
}
    // Load DataTables CSS
    $('head').append('<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">');

    // Load DataTables JS
    $.getScript("https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js")
        .done(function() {
            $.getScript("https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js", function() {
                initializeDataTable();
            });
        });
} else {
    initializeDataTable();
}

function initializeDataTable() {
    $('#menuTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json"
        },
        "pageLength": 10,
        "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ],
        "responsive": true,
        "autoWidth": false,
        "columnDefs": [
            { "width": "5%", "targets": 0 },   // No
            { "width": "20%", "targets": 1 },  // Nama Menu
            { "width": "15%", "targets": 2 },  // Harga
            { "width": "20%", "targets": 3 },  // Resep Bahan
            { "width": "15%", "targets": 4 },  // Kategori X
            { "width": "15%", "targets": 5 },  // Status
            { "width": "10%", "targets": 6 }   // Aksi
        ]
    });
}

// Function to get category for bahan
async function getBahanCategory(bahan) {
    if (!bahan) return '';
    try {
        const response = await fetch('get_bahan_category.php?bahan=' + encodeURIComponent(bahan));
        const data = await response.json();
        return data.success ? data.kategori : '-';
    } catch (error) {
        console.error('Error fetching kategori:', error);
        return '-';
    }
}

// Tambah Menu form handler
document.getElementById('formTambahMenu')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const bahan = this.querySelector('[name="resep_nama_bahan"]').value;
    const kategoriSpan = document.getElementById('kategoriXValue');

    if (bahan) {
        const kategori = await getBahanCategory(bahan);
        kategoriSpan.textContent = kategori;
        if (kategori === '-') {
            kategoriSpan.style.color = 'orange';
            kategoriSpan.innerHTML += ' <small>(Belum terpetakan ke X1-X6)</small>';
        } else {
            kategoriSpan.style.color = 'green';
        }
    }

    // Submit the form normally after checking
    this.submit();
});

// Edit Menu form handler
document.getElementById('formEditMenu')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const bahan = this.querySelector('[name="resep_nama_bahan"]').value;
    const kategoriSpan = document.getElementById('kategoriXValueEdit');

    if (bahan) {
        const kategori = await getBahanCategory(bahan);
        kategoriSpan.textContent = kategori;
        if (kategori === '-') {
            kategoriSpan.style.color = 'orange';
            kategoriSpan.innerHTML += ' <small>(Belum terpetakan ke X1-X6)</small>';
        } else {
            kategoriSpan.style.color = 'green';
        }
    }

    // Submit the form normally after checking
    this.submit();
});

// Update kategori display when bahan changes in Tambah Menu
document.querySelector('#formTambahMenu [name="resep_nama_bahan"]')?.addEventListener('input', async function() {
    const bahan = this.value;
    const kategoriSpan = document.getElementById('kategoriXValue');

    if (!bahan) {
        kategoriSpan.textContent = '-';
        return;
    }

    const kategori = await getBahanCategory(bahan);
    kategoriSpan.textContent = kategori;
    if (kategori === '-') {
        kategoriSpan.style.color = 'orange';
        kategoriSpan.innerHTML += ' <small>(Belum terpetakan ke X1-X6)</small>';
    } else {
        kategoriSpan.style.color = 'green';
    }
});

// Update kategori display when bahan changes in Edit Menu
document.querySelector('#formEditMenu [name="resep_nama_bahan"]')?.addEventListener('input', async function() {
    const bahan = this.value;
    const kategoriSpan = document.getElementById('kategoriXValueEdit');

    if (!bahan) {
        kategoriSpan.textContent = '-';
        return;
    }

    const kategori = await getBahanCategory(bahan);
    kategoriSpan.textContent = kategori;
    if (kategori === '-') {
        kategoriSpan.style.color = 'orange';
        kategoriSpan.innerHTML += ' <small>(Belum terpetakan ke X1-X6)</small>';
    } else {
        kategoriSpan.style.color = 'green';
    }
});

// Initialize Edit Modal
document.getElementById('modalEditMenu')?.addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    document.getElementById('editMenuId').value = btn.dataset.id;
    document.getElementById('editNamaMenu').value = btn.dataset.nama;
    document.getElementById('editHarga').value = btn.dataset.harga;
    document.getElementById('editResepBahan').value = btn.dataset.resepBahan;
    document.getElementById('editResepPengali').value = btn.dataset.resepPengali;
    document.getElementById('editResepSatuan').value = btn.dataset.resepSatuan;
    document.getElementById('editIsUtama').checked = btn.dataset.isUtama === '1';

    // Update kategori display
    const bahan = btn.dataset.resepBahan;
    const kategoriSpan = document.getElementById('kategoriXValueEdit');
    if (bahan) {
        fetch('get_bahan_category.php?bahan=' + encodeURIComponent(bahan))
            .then(r => r.json())
            .then(data => {
                kategoriSpan.textContent = data.success ? data.kategori : '-';
                if (data.kategori === '-') {
                    kategoriSpan.style.color = 'orange';
                    kategoriSpan.innerHTML += ' <small>(Belum terpetakan ke X1-X6)</small>';
                } else {
                    kategoriSpan.style.color = 'green';
                }
            });
    } else {
        kategoriSpan.textContent = '-';
    }
});

// Initialize Hapus Stok modal
document.getElementById('modalHapusAdmin')?.addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    console.log('Modal triggered by button:', btn);
    console.log('Button dataset id:', btn.dataset.id);
    console.log('Button dataset type:', btn.dataset.type);

    // Set the ID value
    document.getElementById('idHapusAdmin').value = btn.dataset.id;
    document.getElementById('infoHapusAdmin').textContent = btn.dataset.info;

    // Set the form action based on data type
    const form = document.querySelector('#modalHapusAdmin form');
    if (btn.dataset.type === 'stok_masuk') {
        form.action = 'aksi/hapus_stok_masuk.php';
    } else {
        // Default to stok keluar for backward compatibility
        form.action = 'aksi/hapus_stok.php';
    }
});
$('.btn-edit-stok-keluar').on('click', function() {
    var id = $(this).attr('data-id');
    var tanggal = $(this).attr('data-tanggal');
    var namaBahan = $(this).attr('data-nama-bahan');
    var jumlah = $(this).attr('data-jumlah');
    var satuan = $(this).attr('data-satuan');

    $('#edit_stok_id').val(id);
    $('#edit_stok_tanggal').val(tanggal);
    $('#edit_stok_nama_bahan').val(namaBahan);
    $('#edit_stok_jumlah').val(jumlah);
    $('#edit_stok_satuan').val(satuan);

    $('#modalEditStokKeluar').modal('show');
});

function confirmDelete(form, message) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
    return false;
}
</script>
