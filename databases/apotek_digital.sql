-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 07, 2025 at 03:17 AM
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
-- Database: `apotek_digital`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `status` enum('Active','Non-Active') NOT NULL,
  `level` enum('Admin','Kasir') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `email`, `username`, `password`, `image`, `reset_token`, `reset_expiry`, `status`, `level`) VALUES
(7, 'rennard95@gmail.com', 'rennard', '$2y$10$EWO4jwZQG4HpVnkJiCkIseDXFRklxD0leehxFcOMzpRFJL5WriBJ.', '6818427ea3710.jpeg', NULL, '2025-07-17 03:34:43', 'Active', 'Admin'),
(11, 'rennardadit@gmail.com', 'ridho', '$2y$10$iNDDr2JkBvNHqhuN/wtZhu4mODTXRtMwnTK7wLWM5tYyeHouZhfFO', '6818642987032.jpeg', NULL, '2025-07-17 03:17:51', 'Active', 'Kasir'),
(13, 'rido@email.com', 'batok', '$2y$10$RTJrweZE4wPgkSIFcrY47.UJS//4e/XkiWvirUV2ClvuqU7VN6NTa', 'admin_682a9d52c45d90.49758496.jpeg', NULL, NULL, 'Active', 'Admin'),
(15, 'biasalah860@gmail.com', 'renn', '$2y$10$mr9B1eWH1q97Ypjefl2/hehGpPBc6HjWnmfMOzOLVf6IsjtBzkJF.', 'admin_682b3f45366ed9.71029453.jpeg', NULL, NULL, 'Active', 'Kasir');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `category`, `image`) VALUES
(18, 'Obat jamu', '30345668.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `member`
--

CREATE TABLE `member` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `point` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `status` enum('active','non-active') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `barcode` varchar(255) NOT NULL,
  `fid_kategori` int(11) NOT NULL,
  `harga_awal` double NOT NULL,
  `harga_jual` double NOT NULL,
  `margin` double NOT NULL,
  `stok` int(11) NOT NULL,
  `expired_at` date DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `barcode`, `fid_kategori`, `harga_awal`, `harga_jual`, `margin`, `stok`, `expired_at`, `image`, `description`, `created_at`, `updated_at`) VALUES
(35, 'ww', '66789426', 18, 9000, 12000, 3000, 5, '2025-08-09', '980089.jpeg', 'enak', '2025-08-04 11:42:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `fid_admin` int(11) NOT NULL,
  `fid_member` int(11) DEFAULT NULL,
  `detail` varchar(255) NOT NULL,
  `total_price` decimal(10,0) DEFAULT NULL,
  `payment_method` enum('Cash','Qris','Transfer') NOT NULL,
  `margin_total` decimal(10,0) NOT NULL,
  `amount_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `change_amount` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions_details`
--

CREATE TABLE `transactions_details` (
  `id` int(11) NOT NULL,
  `fid_transaction` int(11) NOT NULL,
  `fid_product` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_produk_kategori` (`fid_kategori`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fid_admin` (`fid_admin`),
  ADD KEY `fid_member` (`fid_member`);

--
-- Indexes for table `transactions_details`
--
ALTER TABLE `transactions_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fid_transaction` (`fid_transaction`),
  ADD KEY `transactions_details_ibfk_2` (`fid_product`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `transactions_details`
--
ALTER TABLE `transactions_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_produk_kategori` FOREIGN KEY (`fid_kategori`) REFERENCES `category` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`fid_admin`) REFERENCES `admin` (`id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`fid_member`) REFERENCES `member` (`id`);

--
-- Constraints for table `transactions_details`
--
ALTER TABLE `transactions_details`
  ADD CONSTRAINT `transactions_details_ibfk_1` FOREIGN KEY (`fid_transaction`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_details_ibfk_2` FOREIGN KEY (`fid_product`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
