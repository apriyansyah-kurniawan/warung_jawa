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

<!-- 4 KPI Cards — langsung tampil setelah login -->
<?php require 'includes/kpi_cards.php'; ?>

<?php
if (adalah_kasir()) {
    require 'includes/panel_kasir.php';
} elseif (adalah_admin() || adalah_owner()) {
    require 'includes/widget_aktivitas.php';
    require 'includes/panel_admin.php';
    // Teknis section for Admin
    ?>
    <section id="teknis" class="modern-card">
        <div class="card-header">
            <span><i class="bi bi-cpu"></i> Pengecekan Teknis Engine Python</span>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <button type="button" class="btn btn-primary w-100" id="btnLatihModel">
                        <i class="bi bi-brain me-2"></i> Latih Model / Update Prediksi
                    </button>
                </div>
                <div class="col-md-6">
                    <div id="statusModel" class="text-end">
                        <span class="badge bg-secondary">Siap</span>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <small class="text-muted">
                    Model regresi linier bergoda digunakan untuk memprediksi kebutuhan bahan baku
                    berdasarkan konsumsi historis. Latih model secara berkala untuk akurasi terbaik.
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
        btn.innerHTML = '<i class="bi bi-brain me-2"></i> Latih Model / Update Prediksi';
    })
    .finally(() => {
        // Re-enable button after a delay (for success case)
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-brain me-2"></i> Latih Model / Update Prediksi';
        }, 3000);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
