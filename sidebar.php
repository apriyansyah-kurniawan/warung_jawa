<?php
/**
 * sidebar.php — Sidebar navigasi berdasarkan role user.
 */
$base = $base ?? '';
?>
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
            <a href="<?= $base ?>index.php" class="nav-link" data-nav="penjualan">
                <i class="bi bi-cart-plus"></i> Input Penjualan
            </a>
        <?php endif; ?>
        <?php if (adalah_owner()): ?>
            <a href="<?= $base ?>index.php?page=menu" class="nav-link" data-nav="menu">
                <i class="bi bi-menu-app"></i> Manajemen Menu
            </a>
            <a href="<?= $base ?>index.php#resep" class="nav-link" data-nav="resep">
                <i class="bi bi-card-list"></i> Manajemen Resep
            </a>
            <a href="<?= $base ?>index.php#prediksi" class="nav-link" data-nav="prediksi">
                <i class="bi bi-graph-up-arrow"></i> Prediksi Stok
            </a>
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
        <?php elseif (adalah_admin()): ?>
            <a href="<?= $base ?>index.php#stok-masuk" class="nav-link" data-nav="stok-masuk">
                <i class="bi bi-box-arrow-in-down"></i> Stok Masuk
            </a>
            <a href="<?= $base ?>index.php?page=menu" class="nav-link" data-nav="menu">
                <i class="bi bi-menu-app"></i> Manajemen Menu
            </a>
            <a href="<?= $base ?>index.php#resep" class="nav-link" data-nav="resep">
                <i class="bi bi-card-list"></i> Manajemen Resep
            </a>
            <a href="<?= $base ?>index.php#prediksi" class="nav-link" data-nav="prediksi">
                <i class="bi bi-graph-up-arrow"></i> Prediksi Stok
            </a>
            <a href="<?= $base ?>index.php#users" class="nav-link" data-nav="users">
                <i class="bi bi-people"></i> Manajemen User
            </a>
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
        <?php elseif (adalah_kasir()): ?>
            <!-- Kasir already handled above with Input Penjualan -->
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