-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 20 Okt 2026 pada 13.49
-- Versi server: 8.0.30
-- Versi PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `keuangan_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `catatan_keuangan`
--

CREATE TABLE `catatan_keuangan` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `jenis` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `deskripsi` text,
  `tanggal` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `dari_tabungan` tinyint(1) DEFAULT '0',
  `target_id` int DEFAULT NULL,
  `jenis_tabungan` enum('ditabung','diambil') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `catatan_keuangan`
--

INSERT INTO `catatan_keuangan` (`id`, `user_id`, `jenis`, `jumlah`, `kategori`, `deskripsi`, `tanggal`, `created_at`, `dari_tabungan`, `target_id`, `jenis_tabungan`) VALUES
(5, 2, 'pemasukan', 20000.00, 'Bonus', 'uang jajan sekolah', '2026-06-17', '2026-06-17 14:43:49', 0, NULL, NULL),
(6, 2, 'pemasukan', 50000.00, 'Bonus', 'uang jajan sekolah', '2026-06-17', '2026-06-17 14:44:11', 0, NULL, NULL),
(7, 2, 'pengeluaran', 20000.00, 'Tabungan', 'dari uang jajan', '2026-06-17', '2026-06-17 14:47:47', 1, 2, 'ditabung'),
(8, 2, 'pengeluaran', 10000.00, 'Makanan', 'beli ayam krispi + nasi', '2026-06-17', '2026-06-17 14:49:02', 0, NULL, NULL),
(9, 2, 'pemasukan', 10000.00, 'Tabungan', 'buat beli minuman', '2026-06-17', '2026-06-17 15:24:28', 1, 2, 'diambil'),
(11, 2, 'pemasukan', 20000.00, 'Bonus', 'uang jajan lagi', '2026-06-18', '2026-06-18 15:42:05', 0, NULL, NULL),
(12, 2, 'pengeluaran', 10000.00, 'Tabungan', 'dari uang jajan', '2026-06-18', '2026-06-18 15:42:42', 1, 2, 'ditabung'),
(14, 2, 'pengeluaran', 10000.00, 'Tabungan', 'dari uang jajan', '2026-06-18', '2026-06-18 15:48:04', 1, 2, 'ditabung'),
(15, 7, 'pemasukan', 100000.00, 'Bonus', '', '2026-06-20', '2026-06-20 04:29:51', 0, NULL, NULL),
(16, 7, 'pengeluaran', 49000.00, 'Makanan', 'gacoan', '2026-06-20', '2026-06-20 04:30:23', 0, NULL, NULL),
(17, 7, 'pengeluaran', 7500.00, 'Makanan', 'beli minum', '2026-06-20', '2026-06-20 04:31:06', 0, NULL, NULL),
(18, 7, 'pengeluaran', 2000.00, 'Transportasi', 'parkir', '2026-06-20', '2026-06-20 04:31:27', 0, NULL, NULL),
(19, 7, 'pengeluaran', 20000.00, 'Tabungan', 'dari bonus', '2026-06-20', '2026-06-20 04:35:35', 1, 3, 'ditabung');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id` int NOT NULL,
  `nama` varchar(50) NOT NULL,
  `jenis` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id`, `nama`, `jenis`) VALUES
(1, 'Gaji', 'pemasukan'),
(2, 'Bonus', 'pemasukan'),
(3, 'Investasi', 'pemasukan'),
(4, 'Makanan', 'pengeluaran'),
(5, 'Transportasi', 'pengeluaran'),
(6, 'Belanja', 'pengeluaran'),
(7, 'Tagihan', 'pengeluaran'),
(8, 'Hiburan', 'pengeluaran'),
(9, 'Kesehatan', 'pengeluaran');

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_tabungan`
--

CREATE TABLE `riwayat_tabungan` (
  `id` int NOT NULL,
  `target_id` int NOT NULL,
  `user_id` int NOT NULL,
  `jenis` enum('tambah','kurang') NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `riwayat_tabungan`
--

INSERT INTO `riwayat_tabungan` (`id`, `target_id`, `user_id`, `jenis`, `jumlah`, `keterangan`, `created_at`) VALUES
(2, 2, 2, 'tambah', 20000.00, 'dari uang jajan', '2026-06-17 14:47:47'),
(3, 2, 2, 'kurang', 10000.00, 'buat beli minuman', '2026-06-17 15:24:28'),
(4, 2, 2, 'tambah', 10000.00, 'dari uang jajan', '2026-06-18 15:42:42'),
(5, 2, 2, 'tambah', 10000.00, 'dari uang jajan', '2026-06-18 15:48:04'),
(6, 3, 7, 'tambah', 20000.00, 'dari bonus', '2026-06-20 04:35:35');

-- --------------------------------------------------------

--
-- Struktur dari tabel `target_tabungan`
--

CREATE TABLE `target_tabungan` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `harga_target` decimal(15,2) NOT NULL,
  `jumlah_terkumpul` decimal(15,2) DEFAULT '0.00',
  `gambar` varchar(255) DEFAULT NULL,
  `deskripsi` text,
  `target_tanggal` date DEFAULT NULL,
  `status` enum('aktif','tercapai','gagal') DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `target_tabungan`
--

INSERT INTO `target_tabungan` (`id`, `user_id`, `nama_barang`, `harga_target`, `jumlah_terkumpul`, `gambar`, `deskripsi`, `target_tanggal`, `status`, `created_at`, `updated_at`) VALUES
(2, 2, 'laptop', 9000000.00, 30000.00, '1781707478_6a32b2d637817.jpg', 'laptop buat ngoding', NULL, 'aktif', '2026-06-17 14:44:38', '2026-06-18 15:48:04'),
(3, 7, 'hosting web', 300000.00, 20000.00, '1781930062_6a36184e2cab0.jpg', 'hosting + domain', NULL, 'aktif', '2026-06-20 04:34:22', '2026-06-20 04:35:35');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `prefered_language` varchar(10) DEFAULT 'en',
  `profile_image` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `prefered_language`, `profile_image`, `full_name`, `updated_at`) VALUES
(2, 'Darys', 'darys@gmail.com', '$2y$10$MbJDi8myWEZzK4vr/KzxOueYeNZQFICiQDcYflFDYVPc1W7yk7cla', '2026-06-17 14:40:01', 'id', 'profile_2_1781796858.jpeg', 'Darys Luhur Wijaya', '2026-06-19 14:13:41'),
(6, 'nagisa', 'nagis@gmail.com', '$2y$10$kBwvfmxG4kad2UhBqyfNseFABAYvJromHeerv2l/du1YYb21AUvPK', '2026-06-19 03:55:40', 'vi', NULL, NULL, '2026-06-19 03:59:20'),
(7, 'haidar', 'haidar@gmail.com', '$2y$10$VJ/0BZ93sjL3BwpFz3A1buKgBJrCuh46VDjcuudEEhzXL4dZlv.xW', '2026-06-20 04:28:42', 'id', 'profile_7_1781929761.jpeg', NULL, '2026-06-20 04:29:22');

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `catatan_keuangan`
--
ALTER TABLE `catatan_keuangan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `riwayat_tabungan`
--
ALTER TABLE `riwayat_tabungan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `target_id` (`target_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `target_tabungan`
--
ALTER TABLE `target_tabungan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `catatan_keuangan`
--
ALTER TABLE `catatan_keuangan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `riwayat_tabungan`
--
ALTER TABLE `riwayat_tabungan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `target_tabungan`
--
ALTER TABLE `target_tabungan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `catatan_keuangan`
--
ALTER TABLE `catatan_keuangan`
  ADD CONSTRAINT `catatan_keuangan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `riwayat_tabungan`
--
ALTER TABLE `riwayat_tabungan`
  ADD CONSTRAINT `riwayat_tabungan_ibfk_1` FOREIGN KEY (`target_id`) REFERENCES `target_tabungan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `riwayat_tabungan_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `target_tabungan`
--
ALTER TABLE `target_tabungan`
  ADD CONSTRAINT `target_tabungan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
