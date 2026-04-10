-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 09:05 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `toko_buku`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `nama`, `password`) VALUES
(1, 'admin', '123');

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) DEFAULT NULL,
  `penulis` varchar(255) DEFAULT NULL,
  `penerbit` varchar(255) DEFAULT NULL,
  `harga` int(11) DEFAULT NULL,
  `stok` int(11) DEFAULT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `kategori` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id`, `judul`, `penulis`, `penerbit`, `harga`, `stok`, `kategori_id`, `gambar`, `kategori`) VALUES
(13, 'Harry Potter and the Philosopher\'s Stone', 'J.K. Rowling', NULL, 145000, 15, 3, 'buku_1775791501_972.jpg', NULL),
(14, 'Harry Potter and the Chamber of Secrets', 'J.K. Rowling', NULL, 150000, 10, 3, 'buku_1775791552_551.jpg', NULL),
(15, 'Harry Potter and the Prisoner of Azkaban', 'J.K. Rowling', NULL, 100000, 10, 3, 'buku_1775791601_663.jpg', NULL),
(16, 'Harry Potter and the Goblet of Fire', 'J.K. Rowling', NULL, 175000, 5, 3, 'buku_1775791633_381.jpg', NULL),
(17, 'Harry Potter and the Order of the Phoenix', 'J.K. Rowling', NULL, 180000, 11, 3, 'buku_1775791681_207.jpg', NULL),
(18, 'Harry Potter and the Half-Blood Prince', 'J.K. Rowling', NULL, 160000, 7, 3, 'buku_1775791719_232.jpg', NULL),
(19, 'Harry Potter and the Deathly Hallows', 'J.K. Rowling', NULL, 210000, 4, 3, 'buku_1775791753_406.jpg', NULL),
(20, 'Filosofi Teras', 'Henry Manampiring', NULL, 100000, 13, 4, 'buku_1775792580_924.jfif', NULL),
(21, 'Atomic Habits', 'James Clear:', NULL, 120000, 8, 4, 'buku_1775792632_460.jfif', NULL),
(22, 'Sapiens: Riwayat Singkat Umat Manusia', 'Yuval Noah Harari', NULL, 145000, 9, 4, 'buku_1775792695_406.jfif', NULL),
(23, 'Sebuah Seni untuk Bersikap Bodo Amat', 'Mark Manson', NULL, 130000, 17, 4, 'buku_1775792896_923.png', NULL),
(24, 'Catatan Seorang Demonstran', 'Soe Hok Gie', NULL, 189000, 6, 4, 'buku_1775792982_941.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chat`
--

CREATE TABLE `chat` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `pesan` text NOT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `cart_data` text DEFAULT NULL,
  `pengirim` enum('user','admin') NOT NULL DEFAULT 'user',
  `status` enum('dibaca','belum_dibaca') DEFAULT 'belum_dibaca',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat`
--

INSERT INTO `chat` (`id`, `user_id`, `nama`, `email`, `pesan`, `lampiran`, `cart_data`, `pengirim`, `status`, `created_at`) VALUES
(1, 4, 'gendut', '', 'halo', '', NULL, 'user', 'dibaca', '2026-04-10 09:46:37'),
(2, 4, 'gendut', '', 'p', '', NULL, 'user', 'dibaca', '2026-04-10 09:47:07'),
(3, 4, 'gendut', '', 'halok', '', NULL, 'user', 'dibaca', '2026-04-10 10:05:20'),
(4, 4, 'gendut', '', 'p', '', NULL, 'user', 'dibaca', '2026-04-10 10:16:14'),
(5, 4, 'gendut', '', 'p', '', NULL, 'user', 'dibaca', '2026-04-10 10:17:30'),
(6, 4, 'gendut', '', 'p', '', NULL, 'user', 'dibaca', '2026-04-10 10:18:17'),
(7, 4, 'gendut', '', 'p', '', NULL, 'user', 'dibaca', '2026-04-10 10:19:01'),
(8, 4, 'gendut', '', '', '', '[{\"id\":\"17\",\"judul\":\"Harry Potter and the Order of the Phoenix\",\"harga\":\"180000\",\"qty\":1,\"subtotal\":180000}]', 'user', 'dibaca', '2026-04-10 10:30:41'),
(9, 4, 'gendut', '', 'oke', NULL, NULL, 'admin', 'belum_dibaca', '2026-04-10 10:32:01'),
(10, 4, 'gendut', '', 'halo', '', NULL, 'user', 'dibaca', '2026-04-10 10:32:11'),
(11, 4, 'gendut', '', 'oke', NULL, NULL, 'admin', 'belum_dibaca', '2026-04-10 10:32:18');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`, `created_at`) VALUES
(3, 'fiksi', '2026-04-10 03:22:59'),
(4, 'non fiksi', '2026-04-10 03:39:29');

-- --------------------------------------------------------

--
-- Table structure for table `komplain`
--

CREATE TABLE `komplain` (
  `id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `pesan` text NOT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `pengirim` enum('user','admin') NOT NULL,
  `status` enum('dibaca','belum_dibaca') DEFAULT 'belum_dibaca',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `komplain`
--

INSERT INTO `komplain` (`id`, `transaksi_id`, `user_id`, `admin_id`, `pesan`, `lampiran`, `pengirim`, `status`, `created_at`) VALUES
(1, 17, 4, NULL, 'halo', '', 'user', 'dibaca', '2026-04-10 08:25:51'),
(2, 17, 4, NULL, 'nih', 'komplain_17_1775785152.jpg', 'user', 'dibaca', '2026-04-10 08:39:12'),
(3, 17, 4, NULL, 'nih', '', 'user', 'dibaca', '2026-04-10 08:41:04'),
(4, 17, 4, NULL, 'nih', 'komplain_17_1775785399.jpg', 'user', 'dibaca', '2026-04-10 08:43:19'),
(5, 17, 4, 1, 'yauda sabar', '', 'admin', 'belum_dibaca', '2026-04-10 09:06:59'),
(6, 17, 4, 1, 'iya', '', 'admin', 'belum_dibaca', '2026-04-10 09:40:21'),
(7, 18, 4, NULL, 'oke', 'komplain_18_1775791806_731.jpg', 'user', 'dibaca', '2026-04-10 10:30:06'),
(8, 18, 4, 1, 'oke', '', 'admin', 'belum_dibaca', '2026-04-10 10:59:13');

-- --------------------------------------------------------

--
-- Table structure for table `rating`
--

CREATE TABLE `rating` (
  `id` int(11) NOT NULL,
  `id_buku` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `komentar` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rating`
--

INSERT INTO `rating` (`id`, `id_buku`, `rating`, `komentar`, `created_at`) VALUES
(1, 1, 5, 'Bagus banget!', '2026-04-06 12:37:46'),
(2, 1, 4, 'Lumayan seru', '2026-04-06 12:37:46'),
(3, 2, 5, 'Keren!', '2026-04-06 12:37:46');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `metode_bayar` varchar(20) DEFAULT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `user_id`, `total`, `status`, `created_at`, `metode_bayar`, `bukti_pembayaran`) VALUES
(19, 6, 210000, 'Diproses', '2026-04-10 06:54:01', 'cod', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_detail`
--

CREATE TABLE `transaksi_detail` (
  `id` int(11) NOT NULL,
  `transaksi_id` int(11) DEFAULT NULL,
  `buku_id` int(11) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `harga` int(11) DEFAULT NULL,
  `subtotal` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi_detail`
--

INSERT INTO `transaksi_detail` (`id`, `transaksi_id`, `buku_id`, `qty`, `harga`, `subtotal`, `created_at`) VALUES
(1, 2, 2, 2, 85000, 170000, '2026-04-06 12:47:36'),
(2, 3, 2, 1, 85000, 85000, '2026-04-07 01:11:12'),
(3, 4, 2, 1, 85000, 85000, '2026-04-07 04:16:27'),
(4, 5, 8, 1, 175000, 175000, '2026-04-07 05:41:04'),
(5, 6, 4, 14, 50000, 700000, '2026-04-07 09:10:57'),
(6, 7, 4, 1, 50000, 50000, '2026-04-07 23:52:37'),
(7, 8, 4, 1, 50000, 50000, '2026-04-07 23:58:08'),
(8, 9, 8, 9, 175000, 1575000, '2026-04-08 03:31:26'),
(9, 10, 8, 1, 175000, 175000, '2026-04-08 03:33:22'),
(10, 11, 4, 1, 50000, 50000, '2026-04-08 13:56:04'),
(11, 11, 7, 2, 100000, 200000, '2026-04-08 13:56:04'),
(12, 12, 8, 1, 175000, 175000, '2026-04-09 05:12:50'),
(13, 13, 4, 1, 50000, 50000, '2026-04-09 05:23:39'),
(14, 14, 4, 1, 50000, 50000, '2026-04-09 05:29:54'),
(15, 15, 7, 1, 100000, 100000, '2026-04-09 05:30:38'),
(16, 16, 6, 1, 200000, 200000, '2026-04-09 05:31:55'),
(17, 17, 10, 1, 200000, 200000, '2026-04-09 06:50:27'),
(18, 18, 19, 1, 210000, 210000, '2026-04-10 03:29:45'),
(19, 19, 19, 1, 210000, 210000, '2026-04-10 06:54:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '0192023a7bbd73250516f069df18b500', 'admin', '2026-04-06 12:39:11'),
(2, 'fatir', 'fatir@gmail.com', '$2y$10$IjiJKK2bjS82Y5mQWY1mceeFaQBZe1G2Q/49S.QL5fSFnIDMvZt2W', 'user', '2026-04-06 12:39:15'),
(3, 'gita', 'gita@gmail.com', '$2y$10$ckqEAMWH9tuusF5Qw2VDsOLknXmArOujKvTgatmavgAhh6AXydFQe', 'user', '2026-04-07 04:15:10'),
(4, 'gendut', 'gendut@gmail.com', '$2y$10$0ksx3F4O4fHTWWhFCqdmqe3077kqOFT/fKnZGyvrfPj1oP6tEgZgW', 'user', '2026-04-09 06:47:58'),
(5, 'maul@gmail.com', 'maul@gmail.com', '$2y$10$ALQWU3ypi2UqN/ym0yhgJ.2S.KGeVk9xMXUYWsXrLv5LxMMhOMq8G', 'user', '2026-04-10 06:13:46'),
(6, 'lana', 'lana@gmail.com', '$2y$10$eYQYufunxtYeTjrZXIoAS.zm5UTnTPQ3pcb1gt7isLOM36TR6WuTm', 'user', '2026-04-10 06:50:11');

-- --------------------------------------------------------

--
-- Table structure for table `user_alamat`
--

CREATE TABLE `user_alamat` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nama_penerima` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_alamat`
--

INSERT INTO `user_alamat` (`id`, `user_id`, `nama_penerima`, `no_hp`, `alamat`, `provinsi`, `kota`, `kode_pos`, `created_at`, `is_default`) VALUES
(1, 2, 'fatir', '08', 'tebet', 'DKI JAKARTA', 'KOTA JAKARTA SELATAN - TEBET', '12830', '2026-04-06 12:46:16', 1),
(2, 3, 'gita', '08', 'jkt', 'DKI JAKARTA', 'KOTA JAKARTA TIMUR - CAKUNG', '12356', '2026-04-07 04:16:20', 1),
(3, 4, 'gendut', '08', 'jkt', 'DKI JAKARTA', 'KOTA JAKARTA SELATAN - TEBET', '12830', '2026-04-09 06:50:24', 1),
(4, 6, 'lana', '123456', 'bandung', 'Jawa Barat', 'Bandung - Dago', '12345', '2026-04-10 06:52:14', 1);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `buku_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `buku_id`, `created_at`) VALUES
(2, 4, 24, '2026-04-10 11:00:40'),
(4, 4, 21, '2026-04-10 11:05:08'),
(5, 6, 19, '2026-04-10 13:50:31'),
(6, 6, 16, '2026-04-10 13:50:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_buku_kategori` (`kategori_id`);

--
-- Indexes for table `chat`
--
ALTER TABLE `chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `komplain`
--
ALTER TABLE `komplain`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_id` (`transaksi_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rating`
--
ALTER TABLE `rating`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_alamat`
--
ALTER TABLE `user_alamat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `buku_id` (`buku_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `chat`
--
ALTER TABLE `chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `komplain`
--
ALTER TABLE `komplain`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `rating`
--
ALTER TABLE `rating`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_alamat`
--
ALTER TABLE `user_alamat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buku`
--
ALTER TABLE `buku`
  ADD CONSTRAINT `fk_buku_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
