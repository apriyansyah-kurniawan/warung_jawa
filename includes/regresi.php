<?php
/**
 * regresi.php — Dataset, prediksi, dan pelatihan OLS (PHP native + opsional Python).
 */

function kolom_x_regresi(): array
{
    return [
        'x1_ayam',
        'x2_sapi',
        'x3_beras',
        'x4_bumbu_merah',
        'x5_bumbu_bawang',
        'x6_minyak',
        'x7_telur',
        'x8_seafood',
        'x9_jeroan',
        'x10_jengkol',
        'x11_tahu_tempe',
    ];
}

function prediksi_dari_model(array $model, array $x): float
{
    $pred = (float) ($model['beta0'] ?? 0);
    $i = 1;
    foreach (kolom_x_regresi() as $kolom) {
        $pred += (float) ($model['beta' . $i] ?? 0) * (float) ($x[$kolom] ?? 0);
        $i++;
    }
    return max(0, $pred);
}

function pastikan_skema_regresi(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS dataset_regresi (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tanggal DATE NOT NULL,
            jumlah_porsi INT DEFAULT 0,
            x1_ayam DECIMAL(12,4) DEFAULT 0,
            x2_sapi DECIMAL(12,4) DEFAULT 0,
            x3_beras DECIMAL(12,4) DEFAULT 0,
            x4_bumbu_merah DECIMAL(12,4) DEFAULT 0,
            x5_bumbu_bawang DECIMAL(12,4) DEFAULT 0,
            x6_minyak DECIMAL(12,4) DEFAULT 0,
            x7_telur DECIMAL(12,4) DEFAULT 0,
            x8_seafood DECIMAL(12,4) DEFAULT 0,
            x9_jeroan DECIMAL(12,4) DEFAULT 0,
            x10_jengkol DECIMAL(12,4) DEFAULT 0,
            x11_tahu_tempe DECIMAL(12,4) DEFAULT 0,
            UNIQUE KEY unik_tanggal (tanggal)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS model_regresi (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tanggal_training DATETIME DEFAULT CURRENT_TIMESTAMP,
            beta0 DECIMAL(16,6) DEFAULT 0,
            beta1 DECIMAL(16,6) DEFAULT 0,
            beta2 DECIMAL(16,6) DEFAULT 0,
            beta3 DECIMAL(16,6) DEFAULT 0,
            beta4 DECIMAL(16,6) DEFAULT 0,
            beta5 DECIMAL(16,6) DEFAULT 0,
            beta6 DECIMAL(16,6) DEFAULT 0,
            beta7 DECIMAL(16,6) DEFAULT 0,
            beta8 DECIMAL(16,6) DEFAULT 0,
            beta9 DECIMAL(16,6) DEFAULT 0,
            beta10 DECIMAL(16,6) DEFAULT 0,
            beta11 DECIMAL(16,6) DEFAULT 0,
            r_square DECIMAL(12,6) DEFAULT 0,
            mad DECIMAL(12,6) DEFAULT 0,
            jumlah_data_training INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $kolomDataset = [
        'x7_telur', 'x8_seafood', 'x9_jeroan', 'x10_jengkol', 'x11_tahu_tempe',
    ];
    $adaDataset = $pdo->query('SHOW COLUMNS FROM dataset_regresi')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($kolomDataset as $kolom) {
        if (!in_array($kolom, $adaDataset, true)) {
            $pdo->exec("ALTER TABLE dataset_regresi ADD COLUMN {$kolom} DECIMAL(12,4) DEFAULT 0");
        }
    }

    $kolomModel = ['beta7', 'beta8', 'beta9', 'beta10', 'beta11'];
    $adaModel = $pdo->query('SHOW COLUMNS FROM model_regresi')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($kolomModel as $kolom) {
        if (!in_array($kolom, $adaModel, true)) {
            $pdo->exec("ALTER TABLE model_regresi ADD COLUMN {$kolom} DECIMAL(16,6) DEFAULT 0");
        }
    }
}

function bangun_dataset_regresi(PDO $pdo): int
{
    pastikan_skema_regresi($pdo);

    $sqlStok = "
        SELECT
            DATE(s.tanggal) AS tanggal,
            SUM(CASE WHEN m.kategori_x = 'X1' THEN s.jumlah_terpakai * COALESCE(m.faktor_konversi, 1) ELSE 0 END) AS x1_ayam,
            SUM(CASE WHEN m.kategori_x = 'X2' THEN s.jumlah_terpakai * COALESCE(m.faktor_konversi, 1) ELSE 0 END) AS x2_sapi,
            SUM(CASE WHEN m.kategori_x = 'X3' THEN s.jumlah_terpakai * COALESCE(m.faktor_konversi, 1) ELSE 0 END) AS x3_beras,
            SUM(CASE WHEN m.kategori_x = 'X4' THEN s.jumlah_terpakai * COALESCE(m.faktor_konversi, 1) ELSE 0 END) AS x4_bumbu_merah,
            SUM(CASE WHEN m.kategori_x = 'X5' THEN s.jumlah_terpakai * COALESCE(m.faktor_konversi, 1) ELSE 0 END) AS x5_bumbu_bawang,
            SUM(CASE WHEN m.kategori_x = 'X6' THEN s.jumlah_terpakai * COALESCE(m.faktor_konversi, 1) ELSE 0 END) AS x6_minyak,
            SUM(CASE WHEN m.kategori_x = 'X7' THEN s.jumlah_terpakai * COALESCE(m.faktor_konversi, 1) ELSE 0 END) AS x7_telur,
            SUM(CASE WHEN m.kategori_x = 'X8' THEN s.jumlah_terpakai * COALESCE(m.faktor_konversi, 1) ELSE 0 END) AS x8_seafood,
            SUM(CASE WHEN m.kategori_x = 'X9' THEN s.jumlah_terpakai * COALESCE(m.faktor_konversi, 1) ELSE 0 END) AS x9_jeroan,
            SUM(CASE WHEN m.kategori_x = 'X10' THEN s.jumlah_terpakai * COALESCE(m.faktor_konversi, 1) ELSE 0 END) AS x10_jengkol,
            SUM(CASE WHEN m.kategori_x = 'X11' THEN s.jumlah_terpakai * COALESCE(m.faktor_konversi, 1) ELSE 0 END) AS x11_tahu_tempe
        FROM stok_keluar s
        LEFT JOIN mapping_bahan m ON s.nama_bahan = m.nama_bahan
        GROUP BY DATE(s.tanggal)
    ";

    $stokMap = [];
    try {
        foreach ($pdo->query($sqlStok)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $stokMap[$row['tanggal']] = $row;
        }
    } catch (Throwable) {
        $stokMap = [];
    }

    $porsiMap = [];
    try {
        foreach ($pdo->query('SELECT DATE(tanggal) AS tanggal, SUM(jumlah_porsi) AS jumlah_porsi FROM penjualan GROUP BY DATE(tanggal)') as $row) {
            $porsiMap[$row['tanggal']] = (int) $row['jumlah_porsi'];
        }
    } catch (Throwable) {
        $porsiMap = [];
    }

    $tanggalSemua = array_unique(array_merge(array_keys($stokMap), array_keys($porsiMap)));
    sort($tanggalSemua);

    $insert = $pdo->prepare('
        INSERT INTO dataset_regresi
            (tanggal, jumlah_porsi, x1_ayam, x2_sapi, x3_beras, x4_bumbu_merah, x5_bumbu_bawang, x6_minyak,
             x7_telur, x8_seafood, x9_jeroan, x10_jengkol, x11_tahu_tempe)
        VALUES
            (:tanggal, :jumlah_porsi, :x1_ayam, :x2_sapi, :x3_beras, :x4_bumbu_merah, :x5_bumbu_bawang, :x6_minyak,
             :x7_telur, :x8_seafood, :x9_jeroan, :x10_jengkol, :x11_tahu_tempe)
        ON DUPLICATE KEY UPDATE
            jumlah_porsi = VALUES(jumlah_porsi),
            x1_ayam = VALUES(x1_ayam),
            x2_sapi = VALUES(x2_sapi),
            x3_beras = VALUES(x3_beras),
            x4_bumbu_merah = VALUES(x4_bumbu_merah),
            x5_bumbu_bawang = VALUES(x5_bumbu_bawang),
            x6_minyak = VALUES(x6_minyak),
            x7_telur = VALUES(x7_telur),
            x8_seafood = VALUES(x8_seafood),
            x9_jeroan = VALUES(x9_jeroan),
            x10_jengkol = VALUES(x10_jengkol),
            x11_tahu_tempe = VALUES(x11_tahu_tempe)
    ');

    $jumlah = 0;
    foreach ($tanggalSemua as $tanggal) {
        $stok = $stokMap[$tanggal] ?? [];
        $porsi = $porsiMap[$tanggal] ?? 0;
        $nilai = ['tanggal' => $tanggal, 'jumlah_porsi' => $porsi];
        foreach (kolom_x_regresi() as $kolom) {
            $nilai[$kolom] = (float) ($stok[$kolom] ?? 0);
        }
        $adaX = false;
        foreach (kolom_x_regresi() as $kolom) {
            if ($nilai[$kolom] > 0) {
                $adaX = true;
                break;
            }
        }
        if (!$adaX && $porsi <= 0) {
            continue;
        }
        $insert->execute($nilai);
        $jumlah++;
    }

    return $jumlah;
}

function regresi_selesaikan(array $A, array $b): array
{
    $n = count($b);
    $M = [];
    for ($i = 0; $i < $n; $i++) {
        $M[$i] = $A[$i];
        $M[$i][] = $b[$i];
    }

    for ($k = 0; $k < $n; $k++) {
        $pivot = $k;
        $max = abs($M[$k][$k]);
        for ($i = $k + 1; $i < $n; $i++) {
            if (abs($M[$i][$k]) > $max) {
                $max = abs($M[$i][$k]);
                $pivot = $i;
            }
        }
        if ($max < 1e-12) {
            continue;
        }
        if ($pivot !== $k) {
            [$M[$k], $M[$pivot]] = [$M[$pivot], $M[$k]];
        }
        $div = $M[$k][$k];
        for ($j = $k; $j <= $n; $j++) {
            $M[$k][$j] /= $div;
        }
        for ($i = 0; $i < $n; $i++) {
            if ($i === $k) {
                continue;
            }
            $faktor = $M[$i][$k];
            for ($j = $k; $j <= $n; $j++) {
                $M[$i][$j] -= $faktor * $M[$k][$j];
            }
        }
    }

    $x = array_fill(0, $n, 0.0);
    for ($i = 0; $i < $n; $i++) {
        $x[$i] = $M[$i][$n] ?? 0.0;
        if (!is_finite($x[$i])) {
            $x[$i] = 0.0;
        }
    }
    return $x;
}

function latih_model_php(PDO $pdo): array
{
    pastikan_skema_regresi($pdo);
    $rows = $pdo->query('SELECT * FROM dataset_regresi ORDER BY tanggal ASC')->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) < 2) {
        throw new RuntimeException('Data regresi kurang dari 2 baris. Isi penjualan/stok keluar lalu bangun dataset.');
    }

    $fitur = kolom_x_regresi();
    $p = count($fitur) + 1;
    $X = [];
    $y = [];
    foreach ($rows as $row) {
        $baris = [1.0];
        foreach ($fitur as $kolom) {
            $baris[] = (float) ($row[$kolom] ?? 0);
        }
        $X[] = $baris;
        $y[] = (float) ($row['jumlah_porsi'] ?? 0);
    }

    $n = count($y);
    $XtX = array_fill(0, $p, array_fill(0, $p, 0.0));
    $Xty = array_fill(0, $p, 0.0);
    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $p; $j++) {
            $Xty[$j] += $X[$i][$j] * $y[$i];
            for ($k = 0; $k < $p; $k++) {
                $XtX[$j][$k] += $X[$i][$j] * $X[$i][$k];
            }
        }
    }
    $lambda = 1e-4;
    for ($j = 0; $j < $p; $j++) {
        $XtX[$j][$j] += $lambda;
    }

    $beta = regresi_selesaikan($XtX, $Xty);
    $pred = [];
    $ssRes = 0.0;
    $ssTot = 0.0;
    $mad = 0.0;
    $meanY = array_sum($y) / $n;
    for ($i = 0; $i < $n; $i++) {
        $yhat = 0.0;
        for ($j = 0; $j < $p; $j++) {
            $yhat += $beta[$j] * $X[$i][$j];
        }
        $pred[] = $yhat;
        $ssRes += ($y[$i] - $yhat) ** 2;
        $ssTot += ($y[$i] - $meanY) ** 2;
        $mad += abs($y[$i] - $yhat);
    }
    $mad /= $n;
    $r2 = $ssTot > 0 ? 1 - ($ssRes / $ssTot) : 0.0;

    $pdo->exec('DELETE FROM model_regresi');
    $stmt = $pdo->prepare('
        INSERT INTO model_regresi
            (beta0, beta1, beta2, beta3, beta4, beta5, beta6, beta7, beta8, beta9, beta10, beta11, mad, r_square, jumlah_data_training)
        VALUES
            (:beta0, :beta1, :beta2, :beta3, :beta4, :beta5, :beta6, :beta7, :beta8, :beta9, :beta10, :beta11, :mad, :r_square, :n)
    ');
    $params = [
        'mad' => $mad,
        'r_square' => $r2,
        'n' => $n,
    ];
    for ($i = 0; $i < 12; $i++) {
        $params['beta' . $i] = (float) ($beta[$i] ?? 0);
    }
    $stmt->execute($params);

    $hasil = [
        'success' => true,
        'engine' => 'php',
        'mad' => $mad,
        'r_square' => $r2,
        'jumlah_data_training' => $n,
    ];
    for ($i = 0; $i < 12; $i++) {
        $hasil['beta' . $i] = (float) ($beta[$i] ?? 0);
    }
    return $hasil;
}

function jalankan_python_latih(): ?array
{
    $script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'train_model.py';
    if (!is_file($script) || !is_readable($script)) {
        return null;
    }
    $python = defined('PYTHON_PATH') ? PYTHON_PATH : 'python3';
    if (!is_file($python) && $python !== 'python3') {
        foreach (['/usr/local/bin/python3', '/opt/homebrew/bin/python3', '/usr/bin/python3'] as $kandidat) {
            if (is_file($kandidat)) {
                $python = $kandidat;
                break;
            }
        }
    }
    if (!function_exists('exec')) {
        return null;
    }
    $cmd = escapeshellarg($python) . ' ' . escapeshellarg($script) . ' 2>&1';
    $output = [];
    $code = 0;
    exec($cmd, $output, $code);
    $raw = implode("\n", $output);
    $jsonMulai = strrpos($raw, '{');
    if ($jsonMulai === false) {
        return null;
    }
    $decoded = json_decode(substr($raw, $jsonMulai), true);
    if (!is_array($decoded) || empty($decoded['success'])) {
        return null;
    }
    $decoded['engine'] = 'python';
    return $decoded;
}

function latih_model_regresi(PDO $pdo): array
{
    pastikan_skema_regresi($pdo);
    $jumlah = (int) $pdo->query('SELECT COUNT(*) FROM dataset_regresi')->fetchColumn();
    if ($jumlah < 2) {
        bangun_dataset_regresi($pdo);
    }
    $python = jalankan_python_latih();
    if ($python) {
        return $python;
    }
    return latih_model_php($pdo);
}

function ambil_kpi_dashboard(PDO $pdo): array
{
    pastikan_skema_regresi($pdo);
    $error = '';
    $totalStokKg = 0.0;
    $penjualanHariIni = 0;
    $penjualanSub = 'Transaksi hari ini';
    $prediksiBesok = 0.0;
    $bahanStokRendah = 0;
    $chart = [
        'labels' => [],
        'actual' => [],
        'predicted' => [],
        'x' => array_fill_keys(kolom_x_regresi(), []),
    ];

    try {
        $totalMasuk = (float) $pdo->query('SELECT COALESCE(SUM(jumlah_masuk), 0) FROM stok_masuk')->fetchColumn();
        $totalKeluar = (float) $pdo->query('SELECT COALESCE(SUM(jumlah_terpakai), 0) FROM stok_keluar')->fetchColumn();
        $totalStokKg = $totalMasuk - $totalKeluar;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }

    try {
        $penjualanHariIni = (int) $pdo->query('SELECT COALESCE(SUM(jumlah_porsi), 0) FROM penjualan WHERE tanggal = CURDATE()')->fetchColumn();
        if ($penjualanHariIni === 0) {
            $terakhir = $pdo->query('SELECT tanggal, SUM(jumlah_porsi) AS total FROM penjualan GROUP BY tanggal ORDER BY tanggal DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
            if ($terakhir) {
                $penjualanHariIni = (int) $terakhir['total'];
                $penjualanSub = 'Data terakhir ' . date('d M Y', strtotime((string) $terakhir['tanggal']));
            }
        }
    } catch (Throwable $e) {
        $error = $error ?: $e->getMessage();
    }

    try {
        $jumlahDataset = (int) $pdo->query('SELECT COUNT(*) FROM dataset_regresi')->fetchColumn();
        if ($jumlahDataset === 0) {
            bangun_dataset_regresi($pdo);
        }
        $model = $pdo->query('SELECT * FROM model_regresi ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        $xVals = $pdo->query('SELECT * FROM dataset_regresi ORDER BY tanggal DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        if (!$model && $xVals) {
            try {
                $model = latih_model_php($pdo);
            } catch (Throwable) {
                $model = null;
            }
        }
        if ($model && $xVals) {
            $prediksiBesok = prediksi_dari_model($model, $xVals);
        }
    } catch (Throwable $e) {
        $error = $error ?: $e->getMessage();
    }

    try {
        $sqlLow = "
            SELECT COUNT(*) FROM (
                SELECT
                    m.nama_bahan,
                    COALESCE((SELECT SUM(sm.jumlah_masuk) FROM stok_masuk sm WHERE sm.nama_bahan = m.nama_bahan), 0) AS masuk,
                    COALESCE((SELECT SUM(sk.jumlah_terpakai) FROM stok_keluar sk WHERE sk.nama_bahan = m.nama_bahan), 0) AS keluar
                FROM mapping_bahan m
            ) t
            WHERE (masuk + keluar) > 0
              AND (masuk - keluar) <= :ambang
        ";
        $stmtLow = $pdo->prepare($sqlLow);
        $stmtLow->bindValue(':ambang', defined('STOK_THRESHOLD_KG') ? STOK_THRESHOLD_KG : 5);
        $stmtLow->execute();
        $bahanStokRendah = (int) $stmtLow->fetchColumn();
    } catch (Throwable $e) {
        try {
            $bahanStokRendah = (int) $pdo->query('
                SELECT COUNT(*) FROM (
                    SELECT nama_bahan,
                        COALESCE(SUM(jumlah_masuk), 0) - (
                            SELECT COALESCE(SUM(sk.jumlah_terpakai), 0)
                            FROM stok_keluar sk WHERE sk.nama_bahan = sm.nama_bahan
                        ) AS sisa
                    FROM stok_masuk sm
                    GROUP BY nama_bahan
                    HAVING sisa <= 5
                ) x
            ')->fetchColumn();
        } catch (Throwable $e2) {
            $error = $error ?: $e2->getMessage();
        }
    }

    try {
        $modelChart = $pdo->query('SELECT * FROM model_regresi ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: [];
        $barisChart = $pdo->query("
            SELECT *
            FROM dataset_regresi
            WHERE tanggal >= DATE_SUB((SELECT MAX(tanggal) FROM dataset_regresi), INTERVAL 30 DAY)
            ORDER BY tanggal ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        if (!$barisChart) {
            $barisChart = $pdo->query('SELECT * FROM dataset_regresi ORDER BY tanggal DESC LIMIT 30')->fetchAll(PDO::FETCH_ASSOC);
            $barisChart = array_reverse($barisChart);
        }
        foreach ($barisChart as $row) {
            $chart['labels'][] = $row['tanggal'];
            $chart['actual'][] = (float) ($row['jumlah_porsi'] ?? 0);
            $chart['predicted'][] = round(prediksi_dari_model($modelChart, $row), 2);
            foreach (kolom_x_regresi() as $kolom) {
                $chart['x'][$kolom][] = (float) ($row[$kolom] ?? 0);
            }
        }
    } catch (Throwable $e) {
        $error = $error ?: $e->getMessage();
    }

    return [
        'totalStokKg' => $totalStokKg,
        'penjualanHariIni' => $penjualanHariIni,
        'penjualanSub' => $penjualanSub,
        'prediksiBesok' => $prediksiBesok,
        'bahanStokRendah' => $bahanStokRendah,
        'chart' => $chart,
        'error' => $error,
    ];
}
