<?php
/**
 * header.php — Layout sidebar modern + topbar responsif.
 */
$judul_halaman = $judul_halaman ?? 'Dashboard';
$menu_aktif    = $menu_aktif ?? 'beranda';
$base          = $base ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($judul_halaman) ?> - Warung Jawa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $base ?>assets/css/app.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app-wrapper">
    <!-- Sidebar gelap -->
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-brand">
            🍛 <span>Warung</span> Jawa
        </div>
        <nav class="sidebar-nav" id="sidebarNav">
            <a href="<?= $base ?>index.php" class="nav-link" data-nav="beranda">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            <?php if (adalah_kasir()): ?>
                <a href="<?= $base ?>index.php#penjualan" class="nav-link" data-nav="penjualan">
                    <i class="bi bi-cart-plus"></i> Input Penjualan
                </a>
            <?php endif; ?>
            <?php if (adalah_owner() || adalah_admin()): ?>
                <a href="<?= $base ?>index.php#stok-masuk" class="nav-link" data-nav="stok-masuk">
                    <i class="bi bi-box-arrow-in-down"></i> Stok Masuk
                </a>
                <a href="<?= $base ?>index.php#prediksi" class="nav-link" data-nav="prediksi">
                    <i class="bi bi-graph-up-arrow"></i> Prediksi Stok
                </a>
            <?php endif; ?>
            <?php if (adalah_admin()): ?>
                <a href="<?= $base ?>index.php#users" class="nav-link" data-nav="users">
                    <i class="bi bi-people"></i> Manajemen User
                </a>
                <a href="<?= $base ?>index.php#teknis" class="nav-link" data-nav="teknis">
                    <i class="bi bi-cpu"></i> Cek Python
                </a>
            <?php endif; ?>
            <?php if (boleh_export()): ?>
                <hr class="border-secondary mx-2 opacity-25">
                <a href="<?= $base ?>export/csv_penjualan.php" class="nav-link">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Penjualan
                </a>
                <a href="<?= $base ?>export/csv_stok_keluar.php" class="nav-link">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Stok Keluar
                </a>
                <a href="<?= $base ?>export/print_laporan.php?jenis=penjualan" target="_blank" class="nav-link">
                    <i class="bi bi-printer"></i> Cetak Laporan
                </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-user">
            <div class="text-white fw-semibold"><?= htmlspecialchars(username_user()) ?></div>
            <span class="badge bg-success"><?= htmlspecialchars(role_user()) ?></span>
            <a href="<?= $base ?>logout.php" class="btn btn-sm btn-outline-light w-100 mt-2">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- Konten utama -->
    <div class="app-main">
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="mb-0 fw-semibold text-success"><?= htmlspecialchars($judul_halaman) ?></h5>
            </div>
            <small class="text-muted d-none d-md-block">
                <i class="bi bi-clock"></i> Sesi aktif 15 menit
            </small>
        </header>
        <main class="app-content">
