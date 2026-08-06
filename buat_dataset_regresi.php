<?php
/**
 * Buat Dataset Regresi
 * -------------------------------------------------------------------------
 * Skrip ini mengagregasi data dari stok_keluar dan penjualan
 * untuk membentuk dataset yang sesuai dengan persamaan regresi linier berganda:
 * Y = β0 + β1*X1 + β2*X2 + β3*X3 + β4*X4 + β5*X5 + β6*X6
 *
 * X1: Ayam
 * X2: Sapi/Tetelan
 * X3: Beras
 * X4: Bumbu Merah
 * X5: Bumbu Bawang
 * X6: Minyak/Santan
 * Y: jumlah_porsi (total penjualan per hari)
 * -------------------------------------------------------------------------
 */

require_once 'config.php';
require_once 'includes/auth.php';
mulai_session();

// Hanya Admin dan Owner yang bisa mengakses
$user_role = strtolower($_SESSION['role'] ?? '');
if (!in_array($user_role, ['admin', 'owner'])) {
    die("Akses ditolak. Hanya Admin/Owner yang diizinkan.");
}

// Set header untuk response JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // Ambil data stok_keluar yang sudah dikonversi ke kategori X1-X6
    $sqlStok = "
        SELECT
            DATE(s.tanggal) as tanggal,
            SUM(CASE WHEN m.kategori_x = 'X1' THEN s.jumlah_terpakai * m.faktor_konversi ELSE 0 END) as x1_ayam,
            SUM(CASE WHEN m.kategori_x = 'X2' THEN s.jumlah_terpakai * m.faktor_konversi ELSE 0 END) as x2_sapi,
            SUM(CASE WHEN m.kategori_x = 'X3' THEN s.jumlah_terpakai * m.faktor_konversi ELSE 0 END) as x3_beras,
            SUM(CASE WHEN m.kategori_x = 'X4' THEN s.jumlah_terpakai * m.faktor_konversi ELSE 0 END) as x4_bumbu_merah,
            SUM(CASE WHEN m.kategori_x = 'X5' THEN s.jumlah_terpakai * m.faktor_konversi ELSE 0 END) as x5_bumbu_bawang,
            SUM(CASE WHEN m.kategori_x = 'X6' THEN s.jumlah_terpakai * m.faktor_konversi ELSE 0 END) as x6_minyak
        FROM stok_keluar s
        JOIN mapping_bahan m ON s.nama_bahan = m.nama_bahan
        WHERE m.kategori_x IN ('X1','X2','X3','X4','X5','X6')
        GROUP BY DATE(s.tanggal)
    ";

    $stmtStok = $pdo->query($sqlStok);
    $stokData = $stmtStok->fetchAll(PDO::FETCH_ASSOC);

    // Ambil total penjualan per hari
    $sqlPenjualan = "
        SELECT
            DATE(tanggal) as tanggal,
            SUM(jumlah_porsi) as jumlah_porsi
        FROM penjualan
        GROUP BY DATE(tanggal)
    ";

    $stmtPenjualan = $pdo->query($sqlPenjualan);
    $penjualanData = $stmtPenjualan->fetchAll(PDO::FETCH_ASSOC);

    // Buat map untuk penjualan agar mudah dicari berdasarkan tanggal
    $penjualanMap = [];
    foreach ($penjualanData as $row) {
        $penjualanMap[$row['tanggal']] = (int)$row['jumlah_porsi'];
    }

    // Siapkan data untuk disimpan
    $datasetToInsert = [];
    foreach ($stokData as $row) {
        $tanggal = $row['tanggal'];
        $jumlahPorsi = $penjualanMap[$tanggal] ?? 0; // Default 0 jika tidak ada penjualan

        // Hanya masukkan jika ada data stok atau penjualan
        if (
            (float)$row['x1_ayam'] > 0 ||
            (float)$row['x2_sapi'] > 0 ||
            (float)$row['x3_beras'] > 0 ||
            (float)$row['x4_bumbu_merah'] > 0 ||
            (float)$row['x5_bumbu_bawang'] > 0 ||
            (float)$row['x6_minyak'] > 0 ||
            $jumlahPorsi > 0
        ) {
            $datasetToInsert[] = [
                'tanggal' => $tanggal,
                'x1_ayam' => (float)$row['x1_ayam'],
                'x2_sapi' => (float)$row['x2_sapi'],
                'x3_beras' => (float)$row['x3_beras'],
                'x4_bumbu_merah' => (float)$row['x4_bumbu_merah'],
                'x5_bumbu_bawang' => (float)$row['x5_bumbu_bawang'],
                'x6_minyak' => (float)$row['x6_minyak'],
                'jumlah_porsi' => $jumlahPorsi
            ];
        }
    }

    // Kosongkan tabel dataset_regresi sebelum mengisi ulang
    $pdo->exec("TRUNCATE TABLE dataset_regresi");

    // Masukkan data ke dataset_regresi
    if (!empty($datasetToInsert)) {
        $sqlInsert = "
            INSERT INTO dataset_regresi
            (tanggal, x1_ayam, x2_sapi, x3_beras, x4_bumbu_merah, x5_bumbu_bawang, x6_minyak, jumlah_porsi)
            VALUES
            (:tanggal, :x1_ayam, :x2_sapi, :x3_beras, :x4_bumbu_merah, :x5_bumbu_bawang, :x6_minyak, :jumlah_porsi)
        ";

        $stmtInsert = $pdo->prepare($sqlInsert);

        foreach ($datasetToInsert as $data) {
            $stmtInsert->execute($data);
        }
    }

    // Catat aktivitas
    catat_aktivitas($pdo, "Membuat dataset regresi dengan " . count($datasetToInsert) . " baris data");

    echo json_encode([
        'success' => true,
        'message' => 'Dataset regresi berhasil dibuat',
        'jumlah_data' => count($datasetToInsert),
        'data' => $datasetToInsert
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>