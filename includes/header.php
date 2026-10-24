<?php
/**
 * header.php — Kerangka halaman dua kolom (sidebar gelap + konten).
 */
if (!function_exists('cek_login')) {
    require_once __DIR__ . '/auth.php';
}
cek_login();

$judul_halaman = $judul_halaman ?? 'Dashboard';
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$base = (str_contains($scriptName, '/aksi/') || str_contains($scriptName, '/export/')) ? '../' : ($base ?? '');
$page_aktif = $page_aktif ?? basename($_SERVER['PHP_SELF'], '.php');
$role = strtolower((string) ($_SESSION['role'] ?? ''));
$badgeRole = match ($role) {
    'admin' => 'bg-emerald-100 text-emerald-800 border border-emerald-300',
    'owner' => 'bg-indigo-100 text-indigo-800 border border-indigo-300',
    'kasir' => 'bg-amber-100 text-amber-800 border border-amber-300',
    default => 'bg-slate-100 text-slate-700 border border-slate-300',
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($judul_halaman) ?> - Warung Jawa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($base) ?>assets/css/app.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#059669',
                        secondary: '#6366f1'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-100 font-sans antialiased">
<div class="app-shell flex min-h-screen w-full bg-slate-100">
    <?php require_once __DIR__ . '/../sidebar.php'; ?>
    <main class="flex-1 p-8 overflow-y-auto min-w-0">
        <header class="bg-white rounded-2xl shadow-sm border border-slate-200/80 px-6 py-4 mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($judul_halaman) ?></h1>
            <div class="flex items-center gap-2">
                <span class="text-sm text-slate-600"><?= htmlspecialchars($_SESSION['username'] ?? 'Pengguna') ?></span>
                <span class="<?= $badgeRole ?> px-2 py-0.5 rounded-md text-[11px] font-semibold"><?= htmlspecialchars($_SESSION['role'] ?? '-') ?></span>
            </div>
        </header>
        <?php if (!empty($_SESSION['flash'])): ?>
            <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
            <div class="mb-6 rounded-xl border px-4 py-3 text-sm <?= ($flash['tipe'] ?? '') === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?>">
                <?= htmlspecialchars($flash['pesan'] ?? '') ?>
            </div>
        <?php endif; ?>
