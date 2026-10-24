<?php
/**
 * sidebar.php — Navigasi kiri berdasarkan role, selalu di atas layer konten.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (!isset($base) || $base === '') {
    $base = (str_contains($scriptName, '/aksi/') || str_contains($scriptName, '/export/')) ? '../' : '';
}

$page_aktif = $page_aktif ?? basename($_SERVER['PHP_SELF'], '.php');
$role = strtolower((string) ($_SESSION['role'] ?? (function_exists('role_user') ? role_user() : '')));
$namaUser = htmlspecialchars(function_exists('username_user') ? username_user() : (string) ($_SESSION['username'] ?? 'Pengguna'), ENT_QUOTES, 'UTF-8');
$labelRole = htmlspecialchars(function_exists('role_user') ? role_user() : (string) ($_SESSION['role'] ?? '-'), ENT_QUOTES, 'UTF-8');

$isAdmin = $role === 'admin';
$isOwner = $role === 'owner';
$isKasir = $role === 'kasir';

$badgeRole = match ($role) {
    'admin' => 'bg-emerald-100 text-emerald-800 border border-emerald-300',
    'owner' => 'bg-indigo-100 text-indigo-800 border border-indigo-300',
    'kasir' => 'bg-amber-100 text-amber-800 border border-amber-300',
    default => 'bg-slate-100 text-slate-700 border border-slate-300',
};

$linkDasar = 'flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm text-gray-300 hover:bg-gray-700 hover:text-white transition cursor-pointer relative z-40';
$linkAktif = 'flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm bg-emerald-600 text-white shadow-md cursor-pointer relative z-40';

$menuUtama = [
    ['key' => 'index', 'href' => 'index.php', 'label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'roles' => ['admin', 'owner', 'kasir']],
    ['key' => 'stok_masuk', 'href' => 'aksi/stok_masuk.php', 'label' => 'Stok Masuk', 'icon' => 'bi-box-arrow-in-down', 'roles' => ['admin', 'owner', 'kasir']],
    ['key' => 'predict_multivariate', 'href' => 'predict_multivariate.php', 'label' => 'Prediksi Stok', 'icon' => 'bi-graph-up-arrow', 'roles' => ['admin', 'owner']],
    ['key' => 'menu', 'href' => 'aksi/menu.php', 'label' => 'Manajemen Menu', 'icon' => 'bi-menu-app', 'roles' => ['admin', 'owner', 'kasir']],
    ['key' => 'tambah_user', 'href' => 'aksi/tambah_user.php', 'label' => 'Manajemen User', 'icon' => 'bi-people', 'roles' => ['admin']],
    ['key' => 'test_kpi', 'href' => 'test_kpi.php', 'label' => 'Cek Python / KPI', 'icon' => 'bi-cpu', 'roles' => ['admin']],
];

$menuExport = [
    ['key' => 'csv_penjualan', 'href' => 'export/csv_penjualan.php', 'label' => 'Export Penjualan', 'icon' => 'bi-file-earmark-spreadsheet'],
    ['key' => 'csv_stok_keluar', 'href' => 'export/csv_stok_keluar.php', 'label' => 'Export Stok Keluar', 'icon' => 'bi-file-earmark-spreadsheet'],
    ['key' => 'print_laporan', 'href' => 'export/print_laporan.php', 'label' => 'Cetak Laporan', 'icon' => 'bi-printer'],
];

$bolehExport = $isAdmin || $isOwner;
?>
<aside class="w-64 min-h-screen bg-[#1f242d] text-gray-200 flex flex-col justify-between shrink-0 relative z-30 shadow-lg border-r border-slate-800">
    <div>
        <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-800">
            <span class="text-2xl">🍛</span>
            <div>
                <div class="font-semibold text-white leading-tight">Warung <span class="text-emerald-400">Jawa</span></div>
            </div>
        </div>
        <nav class="flex flex-col gap-1 p-3 relative z-40">
            <?php foreach ($menuUtama as $item): ?>
                <?php if (!in_array($role, $item['roles'], true)) continue; ?>
                <?php $aktif = $page_aktif === $item['key']; ?>
                <a href="<?= htmlspecialchars($base . $item['href'], ENT_QUOTES, 'UTF-8') ?>"
                   class="<?= $aktif ? $linkAktif : $linkDasar ?>">
                    <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?> text-base <?= $aktif ? 'text-white' : 'text-emerald-400' ?>"></i>
                    <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>

            <?php if ($bolehExport): ?>
                <div class="my-2 border-t border-slate-800"></div>
                <p class="px-4 pb-1 text-[10px] uppercase tracking-wider text-slate-500">Laporan</p>
                <?php foreach ($menuExport as $item): ?>
                    <?php $aktif = $page_aktif === $item['key']; ?>
                    <a href="<?= htmlspecialchars($base . $item['href'], ENT_QUOTES, 'UTF-8') ?>"
                       class="<?= $aktif ? $linkAktif : $linkDasar ?>">
                        <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?> text-base <?= $aktif ? 'text-white' : 'text-amber-400' ?>"></i>
                        <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </nav>
    </div>
    <div class="p-4 border-t border-slate-800 relative z-40">
        <div class="flex items-center justify-between gap-2 mb-3">
            <div class="text-sm font-semibold text-white truncate"><?= $namaUser ?></div>
            <span class="shrink-0 <?= $badgeRole ?> px-2 py-0.5 rounded-md text-[11px] font-semibold"><?= $labelRole ?></span>
        </div>
        <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>logout.php"
           class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm text-gray-300 hover:bg-rose-600 hover:text-white transition cursor-pointer relative z-40 border border-slate-700">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
