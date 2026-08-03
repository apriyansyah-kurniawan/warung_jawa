<?php
/**
 * index.php — Dashboard utama dengan KPI cards + panel per role.
 */
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/kpi.php';

cek_login();

$judul_halaman = 'Dashboard';
$menu_aktif    = 'beranda';
$flash         = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Ambil data KPI (termasuk prediksi Python untuk Owner/Admin)
$kpi = ambil_semua_kpi($pdo);

require_once 'includes/header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['tipe']) ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['pesan']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- 4 KPI Cards — langsung tampil setelah login -->
<?php require 'includes/kpi_cards.php'; ?>

<?php
if (adalah_kasir()) {
    require 'includes/panel_kasir.php';
} elseif (adalah_admin()) {
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
} elseif (adalah_owner()) {
    require 'includes/widget_aktivitas.php';
    require 'includes/panel_owner.php';
} else {
    echo '<div class="alert alert-warning">Role tidak dikenali.</div>';
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
                alert('Model berhasil dilatih: ' + data.data.message);
            }

            // Optionally, reload prediction data
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            statusEl.innerHTML = '<span class="badge bg-danger">Gagal melatih model</span>';
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        statusEl.innerHTML = '<span class="badge bg-danger">Terjadi kesalahan</span>';
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menghubungi server');
    })
    .finally(() => {
        // Re-enable button after a delay
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-brain me-2"></i> Latih Model / Update Prediksi';
        }, 3000);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
