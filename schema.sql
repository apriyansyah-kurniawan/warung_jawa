CREATE DATABASE IF NOT EXISTS warung_jawa
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE warung_jawa;

-- ---------------------------------------------------------------------
-- 1. Tabel users (autentikasi multi-role)
-- ---------------------------------------------------------------------
-- Role yang tersedia:
--   Admin  = kelola sistem, user, dan pemantauan teknis
--   Kasir  = input penjualan menu (otomatis kurangi stok bahan)
--   Owner  = analitik, penjualan, stok masuk, prediksi
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS riwayat_aktivitas;
DROP TABLE IF EXISTS penjualan;
DROP TABLE IF EXISTS stok_masuk;
DROP TABLE IF EXISTS stok_keluar;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'Disimpan dengan password_hash() PHP',
    role ENUM('Admin', 'Kasir', 'Owner') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Akun contoh (password asli: admin123, kasir123, owner123)
INSERT INTO users (username, password, role) VALUES
('admin', '$2y$10$1I.mFoDuqY3Hdjp5RggCtOcUzzqCsz2gx1l.LXdIYXfSgzgaZuXZq', 'Admin'),
('kasir', '$2y$10$xsPFSbL9WQ/AG4dgm2XL5uYnMePcLpr/MpSxGUHCj9nj1f9woX7sC', 'Kasir'),
('owner', '$2y$10$k5506b3INpRgJ88otTQ17.pGWROP6vi1aRjcdV3rpqC24f8IHmCe.', 'Owner');

-- ---------------------------------------------------------------------
-- 2. Tabel stok_keluar (catatan pemakaian bahan harian)

-- ---------------------------------------------------------------------
-- 1.5. Tabel mapping_bahan (mapping bahan baku ke kategori X1-X6)
-- ---------------------------------------------------------------------
CREATE TABLE mapping_bahan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_bahan VARCHAR(50) NOT NULL UNIQUE,
    kategori_x ENUM('X1','X2','X3','X4','X5','X6') NOT NULL,
    satuan VARCHAR(20) NOT NULL COMMENT 'Kg, Ons, Ikat, Liter, Pcs',
    faktor_konversi DECIMAL(8,4) NOT NULL DEFAULT 1.0 COMMENT 'Faktor konversi ke satuan dasar',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data contoh untuk mapping_bahan
INSERT INTO mapping_bahan (nama_bahan, kategori_x, satuan, faktor_konversi) VALUES
('Ayam', 'X1', 'Kg', 1.0),
('Daging', 'X2', 'Kg', 1.0),
('Daun Ubi', 'X3', 'Ikat', 0.5);
-- ---------------------------------------------------------------------
-- Setiap baris = satu transaksi pemakaian bahan pada tanggal tertentu.
-- id_user mencatat siapa yang menginput data (Kasir atau Admin).
-- satuan: Kg untuk daging/ayam, Ikat untuk sayuran seperti Daun Ubi.
-- ---------------------------------------------------------------------
CREATE TABLE stok_keluar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    nama_bahan VARCHAR(50) NOT NULL,
    jumlah_terpakai DECIMAL(8,2) NOT NULL,
    satuan VARCHAR(20) NOT NULL DEFAULT 'Kg' COMMENT 'Kg, Ons, Ikat, Liter, Pcs',
    id_user INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nama_bahan (nama_bahan),
    INDEX idx_tanggal (tanggal),
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 3. Data sampel 6 minggu (Senin, Rabu, Sabtu) untuk 3 bahan baku
-- ---------------------------------------------------------------------
-- Minggu 1-6: 4 Mei 2026 s.d. 13 Juni 2026
-- id_user 2 = kasir, id_user 1 = admin
-- ---------------------------------------------------------------------
INSERT INTO stok_keluar (tanggal, nama_bahan, jumlah_terpakai, satuan, id_user) VALUES
-- MINGGU 1
('2026-05-04', 'Ayam', 7.30, 'Kg', 2),
('2026-05-06', 'Ayam', 7.10, 'Kg', 2),
('2026-05-09', 'Ayam', 7.70, 'Kg', 2),
('2026-05-04', 'Daging', 4.50, 'Kg', 2),
('2026-05-06', 'Daging', 4.50, 'Kg', 2),
('2026-05-09', 'Daging', 4.60, 'Kg', 2),
('2026-05-04', 'Daun Ubi', 2.90, 'Ikat', 2),
('2026-05-06', 'Daun Ubi', 2.70, 'Ikat', 2),
('2026-05-09', 'Daun Ubi', 3.10, 'Ikat', 2),
-- MINGGU 2
('2026-05-11', 'Ayam', 7.30, 'Kg', 2),
('2026-05-13', 'Ayam', 7.90, 'Kg', 2),
('2026-05-16', 'Ayam', 7.70, 'Kg', 2),
('2026-05-11', 'Daging', 5.30, 'Kg', 2),
('2026-05-13', 'Daging', 4.80, 'Kg', 2),
('2026-05-16', 'Daging', 5.10, 'Kg', 2),
('2026-05-11', 'Daun Ubi', 2.70, 'Ikat', 2),
('2026-05-13', 'Daun Ubi', 2.90, 'Ikat', 2),
('2026-05-16', 'Daun Ubi', 3.10, 'Ikat', 2),
-- MINGGU 3
('2026-05-18', 'Ayam', 7.70, 'Kg', 2),
('2026-05-20', 'Ayam', 8.20, 'Kg', 2),
('2026-05-23', 'Ayam', 7.60, 'Kg', 2),
('2026-05-18', 'Daging', 5.40, 'Kg', 2),
('2026-05-20', 'Daging', 5.10, 'Kg', 2),
('2026-05-23', 'Daging', 5.30, 'Kg', 2),
('2026-05-18', 'Daun Ubi', 2.80, 'Ikat', 2),
('2026-05-20', 'Daun Ubi', 3.00, 'Ikat', 2),
('2026-05-23', 'Daun Ubi', 2.70, 'Ikat', 2),
-- MINGGU 4
('2026-05-25', 'Ayam', 8.50, 'Kg', 1),
('2026-05-27', 'Ayam', 8.00, 'Kg', 1),
('2026-05-30', 'Ayam', 8.10, 'Kg', 1),
('2026-05-25', 'Daging', 5.10, 'Kg', 1),
('2026-05-27', 'Daging', 5.10, 'Kg', 1),
('2026-05-30', 'Daging', 5.20, 'Kg', 1),
('2026-05-25', 'Daun Ubi', 3.20, 'Ikat', 1),
('2026-05-27', 'Daun Ubi', 3.30, 'Ikat', 1),
('2026-05-30', 'Daun Ubi', 3.10, 'Ikat', 1),
-- MINGGU 5
('2026-06-01', 'Ayam', 8.80, 'Kg', 1),
('2026-06-03', 'Ayam', 9.30, 'Kg', 1),
('2026-06-06', 'Ayam', 8.40, 'Kg', 1),
('2026-06-01', 'Daging', 5.80, 'Kg', 1),
('2026-06-03', 'Daging', 5.60, 'Kg', 1),
('2026-06-06', 'Daging', 5.50, 'Kg', 1),
('2026-06-01', 'Daun Ubi', 3.40, 'Ikat', 1),
('2026-06-03', 'Daun Ubi', 3.10, 'Ikat', 1),
('2026-06-06', 'Daun Ubi', 3.30, 'Ikat', 1),
-- MINGGU 6
('2026-06-08', 'Ayam', 8.90, 'Kg', 1),
('2026-06-10', 'Ayam', 9.40, 'Kg', 1),
('2026-06-13', 'Ayam', 9.80, 'Kg', 1),
('2026-06-08', 'Daging', 6.00, 'Kg', 1),
('2026-06-10', 'Daging', 5.90, 'Kg', 1),
('2026-06-13', 'Daging', 5.70, 'Kg', 1),
('2026-06-08', 'Daun Ubi', 3.40, 'Ikat', 1),
('2026-06-10', 'Daun Ubi', 3.30, 'Ikat', 1),
('2026-06-13', 'Daun Ubi', 3.30, 'Ikat', 1);

-- Total: 54 baris (6 minggu × 3 hari/minggu × 3 bahan)

-- ---------------------------------------------------------------------
-- 4. Tabel penjualan (transaksi penjualan menu oleh Kasir)
-- ---------------------------------------------------------------------
CREATE TABLE penjualan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    nama_menu VARCHAR(100) NOT NULL,
    jumlah_porsi INT NOT NULL,
    total_harga DECIMAL(12,2) NOT NULL,
    id_user INT NOT NULL COMMENT 'Kasir yang mencatat penjualan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tanggal (tanggal),
    INDEX idx_nama_menu (nama_menu),
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 5. Tabel stok_masuk (penyimpanan/refill bahan oleh Owner)
-- ---------------------------------------------------------------------
-- Catatan stok masuk TIDAK mempengaruhi predict.py.
-- predict.py hanya membaca tabel stok_keluar sebagai data historis.
-- ---------------------------------------------------------------------
CREATE TABLE stok_masuk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    nama_bahan VARCHAR(50) NOT NULL,
    jumlah_masuk DECIMAL(8,2) NOT NULL,
    satuan VARCHAR(20) NOT NULL DEFAULT 'Kg',
    keterangan VARCHAR(255) DEFAULT NULL,
    id_user INT NOT NULL COMMENT 'Owner yang mencatat stok masuk',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nama_bahan (nama_bahan),
    INDEX idx_tanggal (tanggal),
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data sampel penjualan (opsional, untuk demo dashboard Owner)
INSERT INTO penjualan (tanggal, nama_menu, jumlah_porsi, total_harga, id_user) VALUES
('2026-06-08', 'Nasi Ayam Jawa', 10, 250000.00, 2),
('2026-06-08', 'Gulai Daging', 5, 175000.00, 2),
('2026-06-09', 'Pecel Daun Ubi', 8, 120000.00, 2),
('2026-06-10', 'Nasi Ayam Jawa', 12, 300000.00, 2),
('2026-06-11', 'Gulai Daging', 6, 210000.00, 2);

-- ---------------------------------------------------------------------
-- 6. Tabel riwayat_aktivitas (audit log keamanan)
-- ---------------------------------------------------------------------
CREATE TABLE riwayat_aktivitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    action_description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

