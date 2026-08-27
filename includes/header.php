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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app-wrapper">
    <!-- Sidebar gelap -->
    <?php require_once 'sidebar.php'; ?>

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
