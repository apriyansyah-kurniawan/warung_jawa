<?php
$dsn = 'mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=warung_jawa;charset=utf8mb4';
$opsi_pdo = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
$pdo = new PDO($dsn, 'root', '', $opsi_pdo);
$sql = "
SELECT
    m.nama_bahan,
    m.kategori_x,
    m.satuan AS satuan_bahan,
    COALESCE(SUM(sm.jumlah_masuk), 0) AS total_masuk,
    COALESCE(SUM(sk.jumlah_terpakai), 0) AS total_keluar
FROM mapping_bahan m
LEFT JOIN stok_masuk sm ON m.nama_bahan = sm.nama_bahan
LEFT JOIN stok_keluar sk ON m.nama_bahan = sk.nama_bahan
WHERE m.kategori_x IN ('X1','X2','X3','X4','X5','X6')
GROUP BY m.nama_bahan, m.kategori_x, m.satuan
";
$stmt = $pdo->query($sql);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>