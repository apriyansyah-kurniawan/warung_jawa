<?php
/**
 * logger.php
 * -------------------------------------------------------------------------
 * Modul audit log untuk mencatat setiap aktivitas penting user.
 * Data disimpan ke tabel riwayat_aktivitas di MySQL.
 * -------------------------------------------------------------------------
 */

/**
 * Mencatat aktivitas user ke database audit log.
 *
 * @param PDO    $pdo         Koneksi database
 * @param string $deskripsi   Penjelasan aksi, contoh: "Login berhasil"
 */
function catat_aktivitas(PDO $pdo, string $deskripsi): void
{
    // Hanya catat jika user sedang login
    if (empty($_SESSION['user_id'])) {
        return;
    }

    try {
        $stmt = $pdo->prepare('
            INSERT INTO riwayat_aktivitas (user_id, username, action_description)
            VALUES (:user_id, :username, :deskripsi)
        ');
        $stmt->execute([
            'user_id'    => (int) $_SESSION['user_id'],
            'username'   => $_SESSION['username'] ?? 'unknown',
            'deskripsi'  => $deskripsi,
        ]);
    } catch (PDOException $e) {
        // Audit log gagal tidak boleh menghentikan alur utama aplikasi
        error_log('Gagal catat aktivitas: ' . $e->getMessage());
    }
}

/**
 * Mengambil riwayat aktivitas terbaru untuk widget dashboard.
 *
 * @param PDO $pdo
 * @param int $limit Jumlah baris
 */
function ambil_riwayat_aktivitas(PDO $pdo, int $limit = 10): array
{
    try {
        $stmt = $pdo->prepare('
            SELECT * FROM riwayat_aktivitas
            ORDER BY created_at DESC
            LIMIT :limit
        ');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}
