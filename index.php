<?php
/**
 * index.php — Dashboard dua kolom: sidebar kiri + analitik.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/regresi.php';
cek_login();

$role = strtolower((string) ($_SESSION['role'] ?? ''));
$base = '';
$page_aktif = 'index';

$kpi = ambil_kpi_dashboard($pdo);
$totalStokKg = $kpi['totalStokKg'];
$penjualanHariIni = $kpi['penjualanHariIni'];
$penjualanSub = $kpi['penjualanSub'];
$prediksiBesok = $kpi['prediksiBesok'];
$bahanStokRendah = $kpi['bahanStokRendah'];
$kpiError = $kpi['error'];
$chart_labels = $kpi['chart']['labels'];
$chart_actual = $kpi['chart']['actual'];
$chart_predicted = $kpi['chart']['predicted'];
$chart_x1 = $kpi['chart']['x']['x1_ayam'];
$chart_x2 = $kpi['chart']['x']['x2_sapi'];
$chart_x3 = $kpi['chart']['x']['x3_beras'];
$chart_x4 = $kpi['chart']['x']['x4_bumbu_merah'];
$chart_x5 = $kpi['chart']['x']['x5_bumbu_bawang'];
$chart_x6 = $kpi['chart']['x']['x6_minyak'];
$chart_x7 = $kpi['chart']['x']['x7_telur'];
$chart_x8 = $kpi['chart']['x']['x8_seafood'];
$chart_x9 = $kpi['chart']['x']['x9_jeroan'];
$chart_x10 = $kpi['chart']['x']['x10_jengkol'];
$chart_x11 = $kpi['chart']['x']['x11_tahu_tempe'];

$badgeRole = match ($role) {
    'admin' => 'bg-emerald-100 text-emerald-800 border border-emerald-300',
    'owner' => 'bg-indigo-100 text-indigo-800 border border-indigo-300',
    'kasir' => 'bg-amber-100 text-amber-800 border border-amber-300',
    default => 'bg-slate-100 text-slate-700 border border-slate-300',
};

$daftarMenuKasir = [];
if ($role === 'kasir') {
    try {
        $daftarMenuKasir = $pdo->query('SELECT id, nama_menu, harga, is_menu_utama FROM master_menu ORDER BY is_menu_utama DESC, nama_menu')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable) {
        foreach (DAFTAR_MENU as $nama => $info) {
            $daftarMenuKasir[] = ['id' => 0, 'nama_menu' => $nama, 'harga' => $info['harga']];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Warung Jawa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: '#059669', secondary: '#6366f1' }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-100 font-sans antialiased">
    <div class="app-shell flex min-h-screen w-full bg-slate-100">
        <?php include 'sidebar.php'; ?>
        <main class="flex-1 p-8 overflow-y-auto min-w-0">
            <header class="bg-white rounded-2xl shadow-sm border border-slate-200/80 px-6 py-4 mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Selamat Datang di Warung Jawa</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Berikut Ringkasan operasional Warung Jawa</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-slate-600"><?= htmlspecialchars($_SESSION['username'] ?? 'Pengguna') ?></span>
                    <span class="<?= $badgeRole ?> px-2 py-0.5 rounded-md text-[11px] font-semibold"><?= htmlspecialchars($_SESSION['role'] ?? '-') ?></span>
                </div>
            </header>

            <?php if (!empty($_SESSION['flash'])): ?>
                <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
                <div class="mb-6 rounded-xl border px-4 py-3 text-sm <?= ($flash['tipe'] ?? '') === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-amber-50 border-amber-200 text-amber-800' ?>">
                    <?= htmlspecialchars($flash['pesan'] ?? '') ?>
                </div>
            <?php endif; ?>

            <?php if (empty($chart_labels)): ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 mb-6">
                    <div class="flex gap-3">
                        <i class="bi bi-exclamation-triangle-fill text-amber-500 mt-0.5"></i>
                        <div>
                            <h3 class="text-sm font-semibold">Peringatan</h3>
                            <p class="mt-1 text-sm">Tidak ada data regresi yang tersedia. Chart tidak akan menampilkan data.<?= $kpiError ? ' (' . htmlspecialchars($kpiError) . ')' : '' ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white rounded-xl border border-slate-200/80 border-l-4 border-l-blue-500 p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center space-x-3">
                        <div class="h-12 w-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="bi bi-box-seam text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500">Total Stok (Kg)</p>
                            <p class="text-2xl font-bold text-slate-900"><?= number_format($totalStokKg, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200/80 border-l-4 border-l-emerald-500 p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center space-x-3">
                        <div class="h-12 w-12 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i class="bi bi-basket text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500">Penjualan Hari Ini (Porsi)</p>
                            <p class="text-2xl font-bold text-slate-900"><?= number_format($penjualanHariIni) ?></p>
                            <p class="text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($penjualanSub) ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200/80 border-l-4 border-l-purple-500 p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center space-x-3">
                        <div class="h-12 w-12 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                            <i class="bi bi-graph-up-arrow text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500">Prediksi Besok (Porsi)</p>
                            <p class="text-2xl font-bold text-slate-900"><?= number_format($prediksiBesok, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200/80 border-l-4 border-l-rose-500 p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center space-x-3">
                        <div class="h-12 w-12 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                            <i class="bi bi-exclamation-triangle text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500">Bahan Stok Rendah</p>
                            <p class="text-2xl font-bold text-rose-600"><?= number_format($bahanStokRendah) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($role === 'kasir'): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 mb-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Transaksi Kasir</h2>
                    <div class="grid md:grid-cols-3 gap-4">
                        <?php foreach ($daftarMenuKasir as $menu): ?>
                            <div class="border border-slate-200 rounded-xl p-4 hover:shadow-sm transition-shadow">
                                <div class="font-medium text-slate-800"><?= htmlspecialchars($menu['nama_menu']) ?></div>
                                <div class="text-emerald-600 font-semibold mt-1">Rp <?= number_format((float) $menu['harga'], 0, ',', '.') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="aksi/menu.php" class="inline-flex mt-4 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg shadow-sm transition">Buka Manajemen Menu / POS</a>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6">
                <div class="flex justify-between items-start mb-4 gap-3">
                    <h2 class="text-xl font-semibold text-slate-800">Grafik Prediksi Multivariabel</h2>
                    <?php if (in_array($role, ['admin', 'owner'], true)): ?>
                        <button id="hitungPrediksiBtn" type="button"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg shadow-sm transition">
                            Hitung Prediksi
                        </button>
                    <?php endif; ?>
                </div>
                <div class="h-96">
                    <canvas id="chartMultivariabel"></canvas>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnHitung = document.getElementById('hitungPrediksiBtn');
            if (btnHitung) {
                btnHitung.addEventListener('click', function () {
                    btnHitung.disabled = true;
                    const originalHtml = btnHitung.innerHTML;
                    btnHitung.innerHTML = 'Melatih Model...';
                    fetch('aksi/latih_model.php', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                    })
                    .then(res => res.text())
                    .then(text => {
                        let data;
                        try { data = JSON.parse(text); }
                        catch (e) { throw new Error(text ? text.slice(0, 280) : 'Respons pelatihan tidak valid'); }
                        if (data.success) {
                            Swal.fire({ icon: 'success', title: 'Berhasil!', text: data.message || 'Model regresi berhasil diperbarui.', timer: 1600, showConfirmButton: false })
                                .then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal!', text: data.message || 'Gagal menghitung model.' });
                        }
                    })
                    .catch(err => Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Terjadi gangguan koneksi.' }))
                    .finally(() => {
                        btnHitung.disabled = false;
                        btnHitung.innerHTML = originalHtml;
                    });
                });
            }

            const canvasChart = document.getElementById('chartMultivariabel');
            if (canvasChart && typeof Chart !== 'undefined') {
                new Chart(canvasChart.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($chart_labels) ?>,
                        datasets: [
                            { label: 'Penjualan Aktual (Y)', data: <?= json_encode($chart_actual) ?>, borderColor: '#e02424', backgroundColor: '#e02424', yAxisID: 'y', tension: 0, pointRadius: 2.5 },
                            { label: "Total Prediksi (Y')", data: <?= json_encode($chart_predicted) ?>, borderColor: '#f59e0b', backgroundColor: '#f59e0b', borderDash: [5, 5], yAxisID: 'y', tension: 0, pointRadius: 2.5 },
                            { label: 'X1: Ayam', data: <?= json_encode($chart_x1) ?>, borderColor: '#3b82f6', yAxisID: 'y1', tension: 0, pointRadius: 2 },
                            { label: 'X2: Sapi/Tetelan', data: <?= json_encode($chart_x2) ?>, borderColor: '#8b5cf6', yAxisID: 'y1', tension: 0, pointRadius: 2 },
                            { label: 'X3: Beras', data: <?= json_encode($chart_x3) ?>, borderColor: '#10b981', yAxisID: 'y1', tension: 0, pointRadius: 2 },
                            { label: 'X4: Bumbu Merah', data: <?= json_encode($chart_x4) ?>, borderColor: '#ec4899', yAxisID: 'y1', tension: 0, pointRadius: 2 },
                            { label: 'X5: Bumbu Bawang', data: <?= json_encode($chart_x5) ?>, borderColor: '#eab308', yAxisID: 'y1', tension: 0, pointRadius: 2 },
                            { label: 'X6: Minyak/Santan', data: <?= json_encode($chart_x6) ?>, borderColor: '#06b6d4', yAxisID: 'y1', tension: 0, pointRadius: 2 },
                            { label: 'X7: Telur', data: <?= json_encode($chart_x7) ?>, borderColor: '#84cc16', yAxisID: 'y1', tension: 0, pointRadius: 2 },
                            { label: 'X8: Seafood', data: <?= json_encode($chart_x8) ?>, borderColor: '#0ea5e9', yAxisID: 'y1', tension: 0, pointRadius: 2 },
                            { label: 'X9: Jeroan', data: <?= json_encode($chart_x9) ?>, borderColor: '#f97316', yAxisID: 'y1', tension: 0, pointRadius: 2 },
                            { label: 'X10: Jengkol', data: <?= json_encode($chart_x10) ?>, borderColor: '#facc15', yAxisID: 'y1', tension: 0, pointRadius: 2 },
                            { label: 'X11: Tahu Tempe', data: <?= json_encode($chart_x11) ?>, borderColor: '#6366f1', yAxisID: 'y1', tension: 0, pointRadius: 2 }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { type: 'linear', position: 'left', title: { display: true, text: 'Penjualan (Porsi)' } },
                            y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Variabel Bahan Baku' }, min: 0 }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
