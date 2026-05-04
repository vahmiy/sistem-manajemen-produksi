-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2026 at 08:07 PM
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
-- Database: `flowerindo`
--

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id_client` int(11) NOT NULL,
  `nama_client` varchar(100) NOT NULL,
  `email_client` varchar(50) DEFAULT NULL,
  `no_hp_client` int(15) DEFAULT NULL,
  `tgl_dibuat` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id_order` int(11) NOT NULL,
  `id_po_unik` varchar(50) NOT NULL,
  `tgl_po_dibuat` date NOT NULL,
  `id_client` int(11) NOT NULL,
  `nama_kain` varchar(255) NOT NULL,
  `nama_file_desain` varchar(255) NOT NULL,
  `kebutuhan_panjang` decimal(10,2) NOT NULL,
  `satuan_panjang` enum('Yard','Meter') NOT NULL,
  `metode_print` varchar(50) DEFAULT NULL,
  `foto_desain` varchar(255) DEFAULT NULL,
  `keterangan_po` text DEFAULT NULL,
  `nama_editor` varchar(100) DEFAULT NULL,
  `operator_print` varchar(100) DEFAULT NULL,
  `operator_press` varchar(100) DEFAULT NULL,
  `jenis_mesin` varchar(100) DEFAULT NULL,
  `keterangan_tambahan` text DEFAULT NULL,
  `status_order` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stok_kertas_master`
--

CREATE TABLE `stok_kertas_master` (
  `id_stok_master` int(11) NOT NULL,
  `nama_kertas` varchar(100) NOT NULL,
  `stok_saat_ini` decimal(15,2) DEFAULT 0.00,
  `satuan` enum('LBR','M') NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stok_master`
--

CREATE TABLE `stok_master` (
  `id_stok` int(11) NOT NULL,
  `nama_kain` varchar(100) NOT NULL,
  `stok_saat_ini` decimal(10,2) DEFAULT 0.00,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_keluar`
--

CREATE TABLE `transaksi_keluar` (
  `id_transaksi_keluar` int(11) NOT NULL,
  `tgl_keluar` date NOT NULL,
  `tujuan_produksi` varchar(100) NOT NULL,
  `nama_kain` varchar(100) NOT NULL,
  `qty_keluar` decimal(15,2) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_kertas`
--

CREATE TABLE `transaksi_kertas` (
  `id_transaksi` int(11) NOT NULL,
  `nama_kertas` varchar(100) NOT NULL,
  `tgl_transaksi` date NOT NULL,
  `lebar_kertas` decimal(10,2) DEFAULT 0.00,
  `gramasi` int(11) DEFAULT 0,
  `jumlah` decimal(15,2) NOT NULL,
  `satuan` enum('LBR','M') NOT NULL,
  `jenis_transaksi` enum('masuk','keluar') NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_masuk`
--

CREATE TABLE `transaksi_masuk` (
  `id_roll` int(11) NOT NULL,
  `nama_kain` varchar(100) NOT NULL,
  `tgl_diterima` date NOT NULL,
  `lebar_kain` decimal(10,2) DEFAULT 0.00,
  `panjang_yard_awal` decimal(10,2) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `level` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `level`) VALUES
(1, 'admin', 'admin123', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id_client`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id_order`),
  ADD UNIQUE KEY `id_po_unik` (`id_po_unik`),
  ADD KEY `fk_client_order` (`id_client`);

--
-- Indexes for table `stok_kertas_master`
--
ALTER TABLE `stok_kertas_master`
  ADD PRIMARY KEY (`id_stok_master`),
  ADD UNIQUE KEY `nama_kertas` (`nama_kertas`);

--
-- Indexes for table `stok_master`
--
ALTER TABLE `stok_master`
  ADD PRIMARY KEY (`id_stok`),
  ADD UNIQUE KEY `nama_kain` (`nama_kain`);

--
-- Indexes for table `transaksi_keluar`
--
ALTER TABLE `transaksi_keluar`
  ADD PRIMARY KEY (`id_transaksi_keluar`),
  ADD KEY `nama_kain` (`nama_kain`);

--
-- Indexes for table `transaksi_kertas`
--
ALTER TABLE `transaksi_kertas`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `nama_kertas` (`nama_kertas`);

--
-- Indexes for table `transaksi_masuk`
--
ALTER TABLE `transaksi_masuk`
  ADD PRIMARY KEY (`id_roll`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id_client` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id_order` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stok_kertas_master`
--
ALTER TABLE `stok_kertas_master`
  MODIFY `id_stok_master` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stok_master`
--
ALTER TABLE `stok_master`
  MODIFY `id_stok` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaksi_keluar`
--
ALTER TABLE `transaksi_keluar`
  MODIFY `id_transaksi_keluar` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaksi_kertas`
--
ALTER TABLE `transaksi_kertas`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaksi_masuk`
--
ALTER TABLE `transaksi_masuk`
  MODIFY `id_roll` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_client_order` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
