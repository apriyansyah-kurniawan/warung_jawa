<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/regresi.php';
cek_role(['Admin']);

$judul_halaman = 'Cek Python / KPI';
$page_aktif = 'test_kpi';
$kpi = ambil_kpi_dashboard($pdo);

$pythonPath = defined('PYTHON_PATH') ? PYTHON_PATH : 'python3';
$pythonExists = is_file($pythonPath) || $pythonPath === 'python3';
$scriptExists = is_file(__DIR__ . '/train_model.py');
$pythonOut = '';
if (function_exists('exec') && $pythonExists) {
    $cmd = escapeshellarg($pythonPath) . ' -c ' . escapeshellarg('import sys; print(sys.version)') . ' 2>&1';
    $lines = [];
    $code = 0;
    exec($cmd, $lines, $code);
    $pythonOut = trim(implode("\n", $lines)) . " (exit {$code})";
} else {
    $pythonOut = 'exec() tidak tersedia atau path Python tidak valid';
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">Nilai KPI Dashboard</h2>
        <ul class="space-y-2 text-sm text-slate-700">
            <li>Total stok: <strong><?= number_format($kpi['totalStokKg'], 2) ?> Kg</strong></li>
            <li>Penjualan: <strong><?= number_format($kpi['penjualanHariIni']) ?></strong> <span class="text-slate-400">(<?= htmlspecialchars($kpi['penjualanSub']) ?>)</span></li>
            <li>Prediksi besok: <strong><?= number_format($kpi['prediksiBesok'], 2) ?> porsi</strong></li>
            <li>Bahan stok rendah: <strong class="text-rose-600"><?= number_format($kpi['bahanStokRendah']) ?></strong></li>
            <li>Titik data grafik: <strong><?= count($kpi['chart']['labels']) ?></strong></li>
        </ul>
        <?php if ($kpi['error']): ?>
            <p class="mt-3 text-sm text-rose-600"><?= htmlspecialchars($kpi['error']) ?></p>
        <?php endif; ?>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">Mesin Prediksi</h2>
        <ul class="space-y-2 text-sm text-slate-700">
            <li>PYTHON_PATH: <code class="text-xs"><?= htmlspecialchars($pythonPath) ?></code></li>
            <li>File Python: <?= $pythonExists ? 'ada' : 'tidak ditemukan' ?></li>
            <li>train_model.py: <?= $scriptExists ? 'ada' : 'tidak ditemukan' ?></li>
            <li>Output versi: <?= htmlspecialchars($pythonOut) ?></li>
        </ul>
        <p class="mt-4 text-xs text-slate-500">Jika Python gagal, pelatihan otomatis memakai OLS PHP native.</p>
        <button id="btnLatih" type="button" class="mt-4 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg shadow-sm transition">Latih Ulang Model</button>
        <p id="hasilLatih" class="mt-3 text-sm text-slate-600"></p>
    </div>
</div>
<script>
document.getElementById('btnLatih').addEventListener('click', function () {
    const btn = this;
    btn.disabled = true;
    fetch('aksi/latih_model.php', { method: 'POST', headers: { 'Accept': 'application/json' } })
        .then(r => r.text())
        .then(t => {
            const data = JSON.parse(t);
            document.getElementById('hasilLatih').textContent = data.message || 'Selesai';
            if (data.success) setTimeout(() => location.reload(), 800);
        })
        .catch(err => { document.getElementById('hasilLatih').textContent = err.message; })
        .finally(() => { btn.disabled = false; });
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
