-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 28, 2025 at 04:06 AM
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
-- Database: `kasir1`
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
(7, 'rennard95@gmail.com', 'rennard', '$2y$10$s2o3JO3OfOal9gOuKeVDjuqYMaHHJHq4DS7lEkHbNsmavyyJ4DMTe', '6818427ea3710.jpeg', NULL, '2025-05-21 10:28:56', 'Active', 'Admin'),
(11, 'rennardadit@gmail.com', 'adit', '$2y$10$u7ZVasDvU5QrKKiUekhOQuBQ4rNzFvbYSVLISBmxVM3TQKJRM3z2i', '6818642987032.jpeg', NULL, '2025-05-07 04:55:08', 'Active', 'Kasir'),
(13, 'rido@email.com', 'batok', '$2y$10$RTJrweZE4wPgkSIFcrY47.UJS//4e/XkiWvirUV2ClvuqU7VN6NTa', 'admin_682a9d52c45d90.49758496.jpeg', NULL, NULL, 'Active', 'Admin'),
(15, 'biasalah860@gmail.com', 'renn', '$2y$10$mr9B1eWH1q97Ypjefl2/hehGpPBc6HjWnmfMOzOLVf6IsjtBzkJF.', 'admin_682b3f45366ed9.71029453.jpeg', NULL, NULL, 'Active', 'Kasir'),
(16, 'renalod@gmail.com', 'batook', '$2y$10$sxhed8R003me5RuvS576rOvYdbh2l3siI9I/8Gq.XySmIvGszLWAa', '', NULL, NULL, 'Active', 'Kasir');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `category`, `image`) VALUES
(3, 'Makanan', '68181e8c2de70_bread.jpeg'),
(9, 'Makana', '681b70e1f1afb_salad.jpeg'),
(10, 'Snack', '681b70ed79923_creackers.jpeg'),
(11, 'Lauk', '682b2b4a5e4c0_WhatsApp Image 2025-05-19 at 08.30.01 (2).jpeg');

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

--
-- Dumping data for table `member`
--

INSERT INTO `member` (`id`, `name`, `phone`, `point`, `created_at`, `status`) VALUES
(6, 'ucucp', '1212', 318, '2025-04-19 13:17:35', 'non-active'),
(7, 'reyhan', '244343', 3, '2025-05-05 09:09:31', 'non-active'),
(9, 'Rennard Adityatama', '09657328', 19, '2025-05-07 08:33:56', 'non-active'),
(10, 'Rennard Adityatama', '09999', 0, '2025-05-19 22:16:11', 'non-active'),
(11, 'ayam', '9999', 4, '2025-05-19 22:35:56', 'non-active');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `barcode` varchar(255) NOT NULL,
  `fid_kategori` int(11) NOT NULL,
  `diskon` decimal(10,2) NOT NULL,
  `harga` double NOT NULL,
  `stok` int(11) NOT NULL,
  `expired_at` date NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `barcode`, `fid_kategori`, `diskon`, `harga`, `stok`, `expired_at`, `image`, `description`) VALUES
(9, 'Bubur', '90909090', 3, 0.00, 10000, 1, '2025-05-24', '682a876052e1f_salad.jpeg', 'enak'),
(10, 'Ikan', '49714047', 11, 12.00, 12000, 4, '2025-05-30', '682b2beab2c83_WhatsApp Image 2025-05-19 at 08.30.58 (1).jpeg', 'enak'),
(16, 'Air', '29188292', 9, 0.00, 1000, 0, '2025-05-23', '682b660bc56da_WhatsApp Image 2025-05-19 at 08.30.57 (2).jpeg', 'aghfkg');

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
  `margin_total` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `date`, `fid_admin`, `fid_member`, `detail`, `total_price`, `payment_method`, `margin_total`) VALUES
(4, '2025-05-18 16:26:35', 11, 6, 'krakers x 1, Air x 1, Ayam x 1', 141222, 'Cash', 0),
(5, '2025-05-18 16:30:10', 11, 9, 'krakers x 1, Air x 1', 19000, 'Qris', 0),
(17, '2025-05-18 17:27:14', 11, NULL, 'Air x 2', 20000, 'Cash', 0),
(18, '2025-05-18 17:32:10', 11, NULL, 'krakers x 1', 9000, 'Cash', 0),
(19, '2025-05-18 17:32:40', 11, NULL, 'krakers x 2', 18000, 'Cash', 0),
(20, '2025-05-18 17:33:38', 11, NULL, 'krakers x 1', 9000, 'Cash', 0),
(21, '2025-05-18 17:42:32', 11, NULL, 'Air x 2', 20000, 'Cash', 0),
(22, '2025-05-19 00:52:04', 11, NULL, 'Ayam x 2, Air x 1', 254444, 'Cash', 0),
(23, '2025-05-19 00:58:20', 11, NULL, 'krakers x 2', 18000, 'Cash', 0),
(24, '2025-05-19 01:24:52', 11, 6, 'Ayam x 1, Air x 1, krakers x 1', 141222, 'Cash', 0),
(25, '2025-05-19 12:44:03', 11, NULL, '', 0, 'Cash', 0),
(26, '2025-05-19 15:33:09', 11, 6, 'Ikan x 1', 12000, 'Cash', 0),
(27, '2025-05-19 15:42:25', 11, 11, 'Ikan x 1, Bubur x 1', 22000, 'Cash', 0),
(28, '2025-05-19 16:26:15', 11, NULL, 'Ikan x 1', 12000, 'Cash', 0),
(29, '2025-05-19 16:33:04', 11, 6, 'Ayam x 1', 122222, 'Cash', 0),
(30, '2025-05-19 17:24:22', 11, NULL, 'Air x 1, Kentang x 1, Ayam x 1', 20000, 'Cash', 0),
(31, '2025-05-19 17:37:08', 11, NULL, 'Yoghurt x 1', 10000, 'Cash', 0),
(32, '2025-05-19 17:59:21', 11, NULL, 'Kentang x 1', 10000, 'Cash', 0),
(33, '2025-05-19 18:27:14', 11, NULL, 'Air x 3, Kentang x 2', 23000, 'Cash', 0);

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
-- Dumping data for table `transactions_details`
--

INSERT INTO `transactions_details` (`id`, `fid_transaction`, `fid_product`, `quantity`, `price`, `subtotal`) VALUES
(12, 26, 10, 1, 12000.00, 12000.00),
(13, 27, 10, 1, 12000.00, 12000.00),
(14, 27, 9, 1, 10000.00, 10000.00),
(15, 28, 10, 1, 12000.00, 12000.00),
(17, 30, 16, 1, 1000.00, 1000.00),
(22, 33, 16, 3, 1000.00, 3000.00);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `transactions_details`
--
ALTER TABLE `transactions_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_produk_kategori` FOREIGN KEY (`fid_kategori`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
