-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 15, 2026 at 10:46 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `warung_jawa`
--

-- --------------------------------------------------------

--
-- Table structure for table `penjualan`
--

CREATE TABLE `penjualan` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `nama_menu` varchar(100) NOT NULL,
  `jumlah_porsi` int(11) NOT NULL,
  `total_harga` decimal(12,2) NOT NULL,
  `id_user` int(11) NOT NULL COMMENT 'Kasir yang mencatat penjualan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_aktivitas`
--

CREATE TABLE `riwayat_aktivitas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `action_description` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `riwayat_aktivitas`
--

INSERT INTO `riwayat_aktivitas` (`id`, `user_id`, `username`, `action_description`, `created_at`) VALUES
(36, 3, 'owner', 'Logout dari sistem', '2026-07-14 07:49:05'),
(37, 1, 'admin', 'Login berhasil sebagai Admin', '2026-07-14 07:49:09'),
(38, 1, 'admin', 'Logout dari sistem', '2026-07-14 07:50:06'),
(39, 2, 'kasir', 'Login berhasil sebagai Kasir', '2026-07-14 07:50:11'),
(40, 2, 'kasir', 'Logout dari sistem', '2026-07-14 07:50:18'),
(41, 1, 'admin', 'Login berhasil sebagai Admin', '2026-07-14 07:50:22'),
(42, 1, 'admin', 'Logout dari sistem', '2026-07-14 07:50:27'),
(43, 3, 'owner', 'Login berhasil sebagai Owner', '2026-07-14 07:50:32'),
(44, 3, 'owner', 'Stok masuk: Ayam 10.00 Kg', '2026-07-14 07:51:13'),
(45, 3, 'owner', 'Stok masuk: Daun Ubi 10.00 Ikat', '2026-07-14 07:51:32'),
(46, 3, 'owner', 'Stok masuk: Daun Ubi 10.00 Ikat', '2026-07-14 07:51:52'),
(47, 3, 'owner', 'Stok masuk: Daging 10.00 Kg', '2026-07-14 07:52:04'),
(48, 3, 'owner', 'Stok masuk: Daging 5.00 Kg', '2026-07-14 07:52:27'),
(49, 3, 'owner', 'Stok masuk: Ayam 10.00 Kg', '2026-07-14 07:52:42'),
(50, 3, 'owner', 'Stok masuk: Daun Ubi 10.00 Ikat', '2026-07-14 07:53:24'),
(51, 3, 'owner', 'Stok masuk: Ayam 10.00 Kg', '2026-07-14 07:53:37'),
(52, 3, 'owner', 'Stok masuk: Daging 5.00 Kg', '2026-07-14 07:53:48'),
(53, 3, 'owner', 'Stok masuk: Ayam 20.00 Kg', '2026-07-14 07:54:30'),
(54, 3, 'owner', 'Logout dari sistem', '2026-07-14 07:54:34'),
(55, 3, 'owner', 'Login berhasil sebagai Owner', '2026-07-15 07:50:37'),
(56, 3, 'owner', 'Logout dari sistem', '2026-07-15 07:51:19'),
(57, 1, 'admin', 'Login berhasil sebagai Admin', '2026-07-15 07:51:27'),
(58, 1, 'admin', 'Logout dari sistem', '2026-07-15 07:51:37'),
(59, 2, 'kasir', 'Login berhasil sebagai Kasir', '2026-07-15 07:51:41'),
(60, 2, 'kasir', 'Logout dari sistem', '2026-07-15 08:39:34');

-- --------------------------------------------------------

--
-- Table structure for table `stok_keluar`
--

CREATE TABLE `stok_keluar` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `nama_bahan` varchar(50) NOT NULL,
  `jumlah_terpakai` decimal(8,2) NOT NULL,
  `satuan` varchar(20) NOT NULL DEFAULT 'Kg' COMMENT 'Kg, Ons, Ikat, Liter, Pcs',
  `id_user` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stok_masuk`
--

CREATE TABLE `stok_masuk` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `nama_bahan` varchar(50) NOT NULL,
  `jumlah_masuk` decimal(8,2) NOT NULL,
  `satuan` varchar(20) NOT NULL DEFAULT 'Kg',
  `keterangan` varchar(255) DEFAULT NULL,
  `id_user` int(11) NOT NULL COMMENT 'Owner yang mencatat stok masuk',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stok_masuk`
--

INSERT INTO `stok_masuk` (`id`, `tanggal`, `nama_bahan`, `jumlah_masuk`, `satuan`, `keterangan`, `id_user`, `created_at`) VALUES
(3, '2026-07-01', 'Ayam', 10.00, 'Kg', '-', 3, '2026-07-14 07:51:13'),
(4, '2026-07-14', 'Daun Ubi', 10.00, 'Ikat', '-', 3, '2026-07-14 07:51:32'),
(5, '2026-07-01', 'Daun Ubi', 10.00, 'Ikat', '-', 3, '2026-07-14 07:51:52'),
(6, '2026-07-01', 'Daging', 10.00, 'Kg', '-', 3, '2026-07-14 07:52:04'),
(7, '2026-07-02', 'Daging', 5.00, 'Kg', 'tambahan', 3, '2026-07-14 07:52:27'),
(8, '2026-07-03', 'Ayam', 10.00, 'Kg', 'tambahan', 3, '2026-07-14 07:52:42'),
(9, '2026-07-02', 'Daun Ubi', 10.00, 'Ikat', 'tambahan', 3, '2026-07-14 07:53:24'),
(10, '2026-07-04', 'Ayam', 10.00, 'Kg', 'Tambahan', 3, '2026-07-14 07:53:37'),
(11, '2026-07-05', 'Daging', 5.00, 'Kg', '-', 3, '2026-07-14 07:53:48'),
(12, '2026-07-05', 'Ayam', 20.00, 'Kg', 'T', 3, '2026-07-14 07:54:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Disimpan dengan password_hash() PHP',
  `role` enum('Admin','Kasir','Owner') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$1I.mFoDuqY3Hdjp5RggCtOcUzzqCsz2gx1l.LXdIYXfSgzgaZuXZq', 'Admin', '2026-07-12 10:52:12'),
(2, 'kasir', '$2y$10$xsPFSbL9WQ/AG4dgm2XL5uYnMePcLpr/MpSxGUHCj9nj1f9woX7sC', 'Kasir', '2026-07-12 10:52:12'),
(3, 'owner', '$2y$10$k5506b3INpRgJ88otTQ17.pGWROP6vi1aRjcdV3rpqC24f8IHmCe.', 'Owner', '2026-07-12 10:52:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_nama_menu` (`nama_menu`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `riwayat_aktivitas`
--
ALTER TABLE `riwayat_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `stok_keluar`
--
ALTER TABLE `stok_keluar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nama_bahan` (`nama_bahan`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `stok_masuk`
--
ALTER TABLE `stok_masuk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nama_bahan` (`nama_bahan`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `riwayat_aktivitas`
--
ALTER TABLE `riwayat_aktivitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `stok_keluar`
--
ALTER TABLE `stok_keluar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `stok_masuk`
--
ALTER TABLE `stok_masuk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`);

--
-- Constraints for table `riwayat_aktivitas`
--
ALTER TABLE `riwayat_aktivitas`
  ADD CONSTRAINT `riwayat_aktivitas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stok_keluar`
--
ALTER TABLE `stok_keluar`
  ADD CONSTRAINT `stok_keluar_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`);

--
-- Constraints for table `stok_masuk`
--
ALTER TABLE `stok_masuk`
  ADD CONSTRAINT `stok_masuk_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
