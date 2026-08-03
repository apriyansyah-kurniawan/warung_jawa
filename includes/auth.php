<?php
/**
 * auth.php — Autentikasi, timeout session, dan kontrol role.
 */
require_once __DIR__ . '/logger.php';

function mulai_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Validasi login + cek timeout 15 menit.
 * Redirect ke login.php jika sesi habis atau belum login.
 */
function cek_login(): void
{
    mulai_session();

    // Cek timeout session (15 menit tidak ada aktivitas)
    if (!empty($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
        $idle = time() - (int) $_SESSION['last_activity'];
        if ($idle > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            header('Location: login.php?timeout=1');
            exit;
        }
    }

    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    // Perbarui timestamp aktivitas terakhir
    $_SESSION['last_activity'] = time();
}

function cek_role(array $role_diizinkan): void
{
    cek_login();
    $role_user = $_SESSION['role'] ?? '';
    $role_user_lower = strtolower($role_user);
    $allowed_lower = array_map('strtolower', $role_diizinkan);

    if (!in_array($role_user_lower, $allowed_lower, true)) {
        global $pdo;
        if (isset($pdo)) {
            catat_aktivitas($pdo, 'Akses ditolak (role: ' . $role_user . ')');
        }
        $_SESSION['flash'] = [
            'tipe'  => 'danger',
            'pesan' => 'Anda tidak memiliki hak akses ke halaman ini.',
        ];
        header('Location: index.php');
        exit;
    }
}

function role_user(): string { return $_SESSION['role'] ?? ''; }
function username_user(): string { return $_SESSION['username'] ?? ''; }
function adalah_kasir(): bool { return strtolower(role_user()) === 'kasir'; }
function adalah_admin(): bool { return strtolower(role_user()) === 'admin'; }
function adalah_owner(): bool { return strtolower(role_user()) === 'owner'; }
function boleh_prediksi(): bool { return in_array(strtolower(role_user()), ['admin','owner'], true); }
function boleh_jual(): bool { return strtolower(role_user()) === 'kasir'; }
function boleh_export(): bool { return in_array(strtolower(role_user()), ['admin','owner'], true); }
