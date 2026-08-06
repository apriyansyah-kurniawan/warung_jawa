CREATE TABLE IF NOT EXISTS dataset_regresi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    x1_ayam DECIMAL(10,2) DEFAULT 0,
    x2_sapi DECIMAL(10,2) DEFAULT 0,
    x3_beras DECIMAL(10,2) DEFAULT 0,
    x4_bumbu_merah DECIMAL(10,2) DEFAULT 0,
    x5_bumbu_bawang DECIMAL(10,2) DEFAULT 0,
    x6_minyak DECIMAL(10,2) DEFAULT 0,
    jumlah_porsi INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unik_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;