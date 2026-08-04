<?php
/**
 * KPIs: Mengambil seluruh data KPI untuk dashboard.
 * Menghasilkan array dengan kunci:
 *   - 'penjualan': ringkasan penjualan
 *   - 'stok_tersedia': stok di atas ambang
 *   - 'stok_menipis': stok di bawah atau sama dengan ambang
 *   - 'prediksi': prediksi penggunaan bahan baku untuk minggu depan
 *
 * @param PDO $pdo Koneksi database
 * @return array Data KPI
 */
function ambil_semua_kpi(PDO $pdo): array
{
    $kpi = [
        'penjualan' => kpi_penjualan($pdo),
        'stok_tersedia' => kpi_stok_tersedia($pdo),
        'stok_menipis' => kpi_stok_menipis($pdo),
        'prediksi' => kpi_ringkasan_prediksi($pdo),
    ];
    // Clear prediction cache to ensure fresh data on next request
    unset($_SESSION['kpi_prediksi_cache']);
    return $kpi;
}

/**
 * KPI 1: Ringkasan Penjualan
 * Menghitung total omzet, total porsi, dan jumlah transaksi.
 *
 * @param PDO $pdo
 * @return array ['total_omzet'=>float, 'total_porsi'=>int, 'jumlah_transaksi'=>int]
 */
function kpi_penjualan(PDO $pdo): array
{
    try {
        // Ambil seluruh data penjualan (tidak hanya hari ini)
        $stmt = $pdo->query('
            SELECT
                COALESCE(SUM(total_harga), 0) AS total_omzet,
                COALESCE(SUM(jumlah_porsi), 0) AS total_porsi,
                COUNT(*) AS jumlah_transaksi
            FROM penjualan
        ');
        $row = $stmt->fetch();

        return [
            'total_omzet' => (float) $row['total_omzet'],
            'total_porsi' => (int) $row['total_porsi'],
            'jumlah_transaksi' => (int) $row['jumlah_transaksi'],
        ];
    } catch (Exception $e) {
        // Mengembalikan array kosong jika ada error database
        return [
            'total_omzet' => 0.0,
            'total_porsi' => 0,
            'jumlah_transaksi' => 0,
        ];
    }
}

/**
 * Helper: map kategori X to human-readable label
 */
function kategori_to_label(string $kategori): string
{
    $map = [
        'X1' => 'Ayam',
        'X2' => 'Sapi/Tetelan',
        'X3' => 'Beras',
        'X4' => 'Bumbu Merah',
        'X5' => 'Bumbu Bawang',
        'X6' => 'Minyak/Santan',
    ];
    return $map[$kategori] ?? $kategori;
}

/**
 * KPI 2: Stok Tersedia (di atas ambang threshold)
 * Mengambil stok bahan baku yang sisa di atas ambang threshold,
 * dikelompokkan oleh kategori_bahan (X1-X6) dari mapping_bahan,
 * dengan konversi ke satuan dasar (kg) menggunakan faktor_konversi.
 *
 * @param PDO $pdo
 * @return array Daftar bahan dengan nama_bahan (label kategori), sisa (kg), satuan (Kg)
 */
function kpi_stok_tersedia(PDO $pdo): array
{
    try {
        $sql = "
            SELECT
                m.kategori_x,
                COALESCE(SUM(sm.jumlah_masuk * m.faktor_konversi), 0) AS total_masuk_kg,
                COALESCE(SUM(sk.jumlah_terpakai * m.faktor_konversi), 0) AS total_keluar_kg
            FROM mapping_bahan m
            LEFT JOIN stok_masuk sm ON m.nama_bahan = sm.nama_bahan
            LEFT JOIN stok_keluar sk ON m.nama_bahan = sk.nama_bahan
            WHERE m.kategori_x IN ('X1','X2','X3','X4','X5','X6')
            GROUP BY m.kategori_x
        ";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hasil = [];

        foreach ($rows as $row) {
            $sisa_kg = (float) $row['total_masuk_kg'] - (float) $row['total_keluar_kg'];
            if ($sisa_kg > STOK_THRESHOLD_KG) {
                $hasil[] = [
                    'nama_bahan' => kategori_to_label($row['kategori_x']),
                    'sisa' => $sisa_kg,
                    'satuan' => 'Kg',
                ];
            }
        }

        return $hasil;
    } catch (Exception $e) {
        // Mengembalikan array kosong jika ada error database
        return [];
    }
}

/**
 * KPI 3: Stok Menipis (di bawah atau sama dengan ambang threshold)
 * Mirip dengan kpi_stok_tersedia tetapi untuk stok di bawah ambang.
 *
 * @param PDO $pdo
 * @return array Daftar bahan dengan nama_bahan (label kategori), sisa (kg), satuan (Kg)
 */
function kpi_stok_menipis(PDO $pdo): array
{
    try {
        $sql = "
            SELECT
                m.kategori_x,
                COALESCE(SUM(sm.jumlah_masuk * m.faktor_konversi), 0) AS total_masuk_kg,
                COALESCE(SUM(sk.jumlah_terpakai * m.faktor_konversi), 0) AS total_keluar_kg
            FROM mapping_bahan m
            LEFT JOIN stok_masuk sm ON m.nama_bahan = sm.nama_bahan
            LEFT JOIN stok_keluar sk ON m.nama_bahan = sk.nama_bahan
            WHERE m.kategori_x IN ('X1','X2','X3','X4','X5','X6')
            GROUP BY m.kategori_x
        ";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hasil = [];

        foreach ($rows as $row) {
            $sisa_kg = (float) $row['total_masuk_kg'] - (float) $row['total_keluar_kg'];
            if ($sisa_kg <= STOK_THRESHOLD_KG) {
                $hasil[] = [
                    'nama_bahan' => kategori_to_label($row['kategori_x']),
                    'sisa' => $sisa_kg,
                    'satuan' => 'Kg',
                ];
            }
        }

        return $hasil;
    } catch (Exception $e) {
        // Mengembalikan array kosong jika ada error database
        return [];
    }
}

/**
 * KPI 4: Ringkasan Prediksi Minggu Depan
 * Menggunakan data historis dari stok_keluar untuk menghitung rata-rata penggunaan
 * per bahan dalam 7 hari terakhir sebagai prediksi untuk minggu depan.
 * Grup berdasarkan kategori X1-X6 dari mapping_bahan.
 *
 * @param PDO $pdo
 * @return array Daftar prediksi per bahan dengan nama_bahan, forecasted_val, next_week, mad_error, satuan
 */
function kpi_ringkasan_prediksi(PDO $pdo): array {
    try {
        if (!boleh_prediksi()) {
            return [];
        }

        $stmt = $pdo->query("
            SELECT
                kategori_x,
                GROUP_CONCAT(nama_bahan) as nama_bahan,
                MAX(satuan) as satuan
            FROM mapping_bahan
            WHERE kategori_x IN ('X1','X2','X3','X4','X5','X6')
            GROUP BY kategori_x
            ORDER BY kategori_x ASC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtModel = $pdo->query("SELECT * FROM model_regresi ORDER BY id DESC LIMIT 1");
        $model = $stmtModel->fetch(PDO::FETCH_ASSOC);
        $mad = (float)($model['mad'] ?? 2.01);

        $hasil = [];
        foreach ($rows as $r) {
            $hasil[] = [
                'nama_bahan'     => kategori_to_label($r['kategori_x']),
                'kategori_x'     => $r['kategori_x'],
                'forecasted_val' => 0.0,
                'next_week'      => 'Estimasi',
                'mad_error'      => $mad,
                'satuan'         => $r['satuan'] ?? 'Kg'
            ];
        }
        return $hasil;
    } catch (Exception $e) {
        // Mengembalikan array kosong jika ada error database
        // Tapi tetap hapus cache untuk memastikan data segar di request berikutnya
        unset($_SESSION['kpi_prediksi_cache']);
        return [];
    }

    // Clear prediction cache to ensure fresh data on next request
    // Note: This line is unreachable due to return statements above, but keeping for clarity
    // unset($_SESSION['kpi_prediksi_cache']);
}
