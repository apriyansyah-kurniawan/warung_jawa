<?php
/**
 * index.php — Dashboard utama dengan KPI cards + panel per role.
 */
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/kpi.php';

cek_login();

$judul_halaman = 'Dashboard';
// Determine active menu from page parameter
$page = $_GET['page'] ?? '';
switch ($page) {
    case 'user':
    case 'users':
        $menu_aktif = 'users';
        break;
    case 'menu':
        // Menu management is inside admin panel, not a separate sidebar item.
        // We'll default to 'beranda' or maybe set to something else.
        // Since there's no sidebar item for menu, we can set to empty and handle via JS to scroll.
        $menu_aktif = 'beranda'; // fallback
        break;
    default:
        $menu_aktif = 'beranda';
        break;
}
$flash         = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Ambil data KPI (termasuk prediksi Python untuk Owner/Admin)
$kpi = ambil_semua_kpi($pdo);

require_once 'includes/header.php';
?>

<?php if ($flash): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            icon: '<?= htmlspecialchars($flash['tipe'] === 'danger' ? 'error' : $flash['tipe']) ?>',
            title: '<?= htmlspecialchars($flash['tipe'] === 'danger' ? 'Gagal' : ucfirst($flash['tipe'])) ?>',
            text: '<?= addslashes(htmlspecialchars($flash['pesan'], ENT_QUOTES)) ?>',
            confirmButtonColor: '#198754',
            timer: 2500,
            timerProgressBar: true
        });
    });
</script>
<?php endif; ?>
<div class="container mx-auto px-4 dashboard-content">

<!-- 4 KPI Cards — langsung tampil setelah login -->
<?php require 'includes/kpi_cards.php'; ?>

<?php
if (adalah_kasir()) {
    require 'includes/panel_kasir.php';
} elseif (adalah_admin() || adalah_owner()) {
    require 'includes/widget_aktivitas.php';
    require 'includes/panel_admin.php';
    require 'includes/panel_owner.php';
    // Teknis section for Admin
    ?>
    <section id="teknis" class="modern-card">
        <div class="card-header">
            <span><i class="bi bi-cpu"></i> Perhitungan Prediksi Kebutuhan Bahan Baku</span>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <button type="button" class="btn btn-primary w-100" id="btnLatihModel">
                        <i class="bi bi-brain me-2"></i> Hitung Prediksi Stok
                    </button>
                </div>
                <div class="col-md-6">
                    <div id="statusModel" class="text-end">
                        </div>
                </div>
            </div>
            <div class="mt-4">
                <small class="text-muted">
                    Model regresi linier berganda digunakan untuk memprediksi kebutuhan bahan baku berdasarkan data konsumsi historis.
                </small>
            </div>
        </div>
    </section>
    <?php
} else {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: "warning",
                title: "Perhatian",
                text: "Role tidak dikenali.",
                confirmButtonColor: "#dc3545"
            });
        });
      </script>';
}
?>

<!-- Script untuk menangani pelatihan model -->
<script>
document.getElementById('btnLatihModel')?.addEventListener('click', function() {
    const btn = this;
    const statusEl = document.getElementById('statusModel');

    // Disable button and show loading state
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-brain me-2"></i> Sedang Melatih...';
    statusEl.innerHTML = '<span class="badge bg-warning">Sedang diproses...</span>';

    // Send request to process_predict.php to train model
    fetch('process_predict.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=train'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusEl.innerHTML = '<span class="badge bg-success">Model berhasil dilatih!</span>';
            // Show success message
            if (data.data && data.data.message) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Model berhasil dilatih: ' + data.data.message,
                    confirmButtonColor: '#198754'
                });
            }

            // Optionally, reload prediction data
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            statusEl.innerHTML = '<span class="badge bg-danger">Gagal melatih model</span>';
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Error: ' + (data.message || 'Unknown error'),
                confirmButtonColor: '#dc3545'
            });
        }
    })
    .catch(error => {
        statusEl.innerHTML = '<span class="badge bg-danger">Terjadi kesalahan</span>';
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan!',
            text: 'Terjadi kesalahan saat menghubungi server',
            confirmButtonColor: '#dc3545'
        });
        // Immediately reset button text on error
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-brain me-2"></i> Hitung Prediksi Stok';
    })
    .finally(() => {
        // Re-enable button after a delay (for success case)
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-brain me-2"></i> Hitung Prediksi Stok';
        }, 3000);
    });
});
</script>

<!-- Modal Edit Stok Keluar -->
<div class="modal fade" id="modalEditStokKeluar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formEditStokKeluar" action="process_edit_stok_keluar.php" method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Edit Stok Keluar</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_stok_id">

          <div class="mb-3">
            <label class="form-label">Tanggal</label>
            <input type="date" name="tanggal" id="edit_stok_tanggal" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Nama Bahan</label>
            <select name="nama_bahan" id="edit_stok_nama_bahan" class="form-select" required>
              <option value="">-- Pilih Bahan --</option>
              <?php
              $koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
              if ($koneksi->connect_error) {
                  die("Koneksi database gagal: " . $koneksi->connect_error);
              }
              $query_bahan = mysqli_query($koneksi, "SELECT DISTINCT nama_bahan FROM mapping_bahan ORDER BY nama_bahan ASC");
              while($b = mysqli_fetch_assoc($query_bahan)) {
                  echo "<option value='".htmlspecialchars($b['nama_bahan'])."'>".htmlspecialchars($b['nama_bahan'])."</option>";
              }
              $koneksi->close();
              ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Jumlah Terpakai</label>
            <input type="number" step="0.01" name="jumlah" id="edit_stok_jumlah" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Satuan</label>
            <select name="satuan" id="edit_stok_satuan" class="form-select" required>
              <option value="Kg">Kg</option>
              <option value="Ikat">Ikat</option>
              <option value="Liter">Liter</option>
              <option value="Pcs">Pcs</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>
</div>
<?php require_once 'includes/footer.php'; ?>
