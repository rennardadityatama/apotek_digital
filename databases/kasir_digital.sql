-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 05, 2025 at 07:59 AM
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
-- Database: `kasir_digital`
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
(11, 'rennardadit@gmail.com', 'adit', '$2y$10$E70c8dlTuAFZLNO/4LCkO.KZ2dNjceypgxrT9WqLNBYh2GnxWQE9G', '6818642987032.jpeg', NULL, '2025-06-03 06:43:46', 'Active', 'Kasir'),
(13, 'rido@email.com', 'batok', '$2y$10$RTJrweZE4wPgkSIFcrY47.UJS//4e/XkiWvirUV2ClvuqU7VN6NTa', 'admin_682a9d52c45d90.49758496.jpeg', NULL, NULL, 'Active', 'Admin'),
(15, 'biasalah860@gmail.com', 'renn', '$2y$10$mr9B1eWH1q97Ypjefl2/hehGpPBc6HjWnmfMOzOLVf6IsjtBzkJF.', 'admin_682b3f45366ed9.71029453.jpeg', NULL, NULL, 'Active', 'Kasir'),
(16, 'renalod@gmail.com', 'batook', '$2y$10$sxhed8R003me5RuvS576rOvYdbh2l3siI9I/8Gq.XySmIvGszLWAa', '', NULL, NULL, 'Active', 'Kasir');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `category`) VALUES
(3, 'Baju'),
(9, 'Makanan'),
(10, 'Snack'),
(11, 'Lauk'),
(14, 'Ciki');

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
(6, 'ucucp', '1212', 324, '2025-04-19 13:17:35', 'non-active'),
(7, 'reyhan', '244343', 3, '2025-05-05 09:09:31', 'non-active'),
(9, 'Rennard Adityatama', '09657328', 19, '2025-05-07 08:33:56', 'non-active'),
(10, 'Rennard Adityatama', '09999', 0, '2025-05-19 22:16:11', 'non-active'),
(11, 'ayam', '9999', 4, '2025-05-19 22:35:56', 'non-active'),
(14, 'Rennard', '082213521461', 697, '2025-06-04 09:00:02', 'non-active');

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
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `barcode`, `fid_kategori`, `harga_awal`, `harga_jual`, `margin`, `stok`, `expired_at`, `image`, `description`) VALUES
(9, 'Bubur', '90909090', 3, 0, 10000, 0, 38, '2025-05-24', '682a876052e1f_salad.jpeg', 'enak'),
(10, 'Ikan', '49714047', 11, 0, 0, 0, 12, '0000-00-00', '682b2beab2c83_WhatsApp Image 2025-05-19 at 08.30.58 (1).jpeg', 'enak'),
(16, 'Air', '29188292', 9, 0, 0, 0, 10, '0000-00-00', '682b660bc56da_WhatsApp Image 2025-05-19 at 08.30.57 (2).jpeg', 'aghfkg'),
(21, 'Yoguhrt', '91503113', 10, 9000, 13000, 4000, 3, '0000-00-00', '586950.png', 'enak'),
(22, 'Botol', '69989276', 11, 21000, 25000, 4000, 2, '0000-00-00', '627408.png', 'wenak'),
(23, 'Matcha', '29081846', 3, 0, 0, 0, 12, '0000-00-00', '218827.png', 'I LOVE MATCHA'),
(24, 'Baju', '69540520', 10, 190000, 250000, 60000, 11, '0000-00-00', '433410.png', 'mantap');

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

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `date`, `fid_admin`, `fid_member`, `detail`, `total_price`, `payment_method`, `margin_total`, `amount_paid`, `change_amount`) VALUES
(4, '2025-05-18 16:26:35', 11, 6, 'krakers x 1, Air x 1, Ayam x 1', 141222, 'Cash', 0, 0.00, 0.00),
(5, '2025-05-18 16:30:10', 11, 9, 'krakers x 1, Air x 1', 19000, 'Qris', 0, 0.00, 0.00),
(17, '2025-05-18 17:27:14', 11, NULL, 'Air x 2', 20000, 'Cash', 0, 0.00, 0.00),
(18, '2025-05-18 17:32:10', 11, NULL, 'krakers x 1', 9000, 'Cash', 0, 0.00, 0.00),
(19, '2025-05-18 17:32:40', 11, NULL, 'krakers x 2', 18000, 'Cash', 0, 0.00, 0.00),
(20, '2025-05-18 17:33:38', 11, NULL, 'krakers x 1', 9000, 'Cash', 0, 0.00, 0.00),
(21, '2025-05-18 17:42:32', 11, NULL, 'Air x 2', 20000, 'Cash', 0, 0.00, 0.00),
(22, '2025-05-19 00:52:04', 11, NULL, 'Ayam x 2, Air x 1', 254444, 'Cash', 0, 0.00, 0.00),
(23, '2025-05-19 00:58:20', 11, NULL, 'krakers x 2', 18000, 'Cash', 0, 0.00, 0.00),
(24, '2025-05-19 01:24:52', 11, 6, 'Ayam x 1, Air x 1, krakers x 1', 141222, 'Cash', 0, 0.00, 0.00),
(25, '2025-05-19 12:44:03', 11, NULL, '', 0, 'Cash', 0, 0.00, 0.00),
(26, '2025-05-19 15:33:09', 11, 6, 'Ikan x 1', 12000, 'Cash', 0, 0.00, 0.00),
(27, '2025-05-19 15:42:25', 11, 11, 'Ikan x 1, Bubur x 1', 22000, 'Cash', 0, 0.00, 0.00),
(28, '2025-05-19 16:26:15', 11, NULL, 'Ikan x 1', 12000, 'Cash', 0, 0.00, 0.00),
(29, '2025-05-19 16:33:04', 11, 6, 'Ayam x 1', 122222, 'Cash', 0, 0.00, 0.00),
(30, '2025-05-19 17:24:22', 11, NULL, 'Air x 1, Kentang x 1, Ayam x 1', 20000, 'Cash', 0, 0.00, 0.00),
(31, '2025-05-19 17:37:08', 11, NULL, 'Yoghurt x 1', 10000, 'Cash', 0, 0.00, 0.00),
(32, '2025-05-19 17:59:21', 11, NULL, 'Kentang x 1', 10000, 'Cash', 0, 0.00, 0.00),
(33, '2025-05-19 18:27:14', 11, NULL, 'Air x 3, Kentang x 2', 23000, 'Cash', 0, 0.00, 0.00),
(34, '2025-06-03 03:57:44', 11, 6, 'Bubur x 1', 10000, 'Cash', 0, 10000.00, 0.00),
(35, '2025-06-03 04:01:38', 11, NULL, 'Ikan x 1, Bubur x 2', 32000, 'Cash', 0, 50000.00, 18000.00),
(36, '2025-06-03 04:09:38', 11, 6, 'Bubur x 1', 10000, 'Cash', 0, 12000.00, 2000.00),
(37, '2025-06-03 04:11:01', 11, NULL, 'Bubur x 1', 10000, 'Cash', 0, 12000.00, 2000.00),
(38, '2025-06-03 04:11:58', 11, NULL, 'Bubur x 1', 10000, 'Cash', 0, 12000.00, 2000.00),
(39, '2025-06-03 04:19:33', 11, NULL, 'Bubur x 1', 10000, 'Cash', 0, 12000.00, 2000.00),
(40, '2025-06-03 16:10:59', 11, 6, 'Bubur x 1', 10000, 'Qris', 0, 12000.00, 2000.00),
(41, '2025-06-04 00:52:17', 11, NULL, 'Bubur x 3', 30000, 'Cash', 0, 30000.00, 0.00),
(42, '2025-06-04 00:53:01', 11, NULL, 'Bubur x 1, nagor x 3', 61000, 'Cash', 0, 70000.00, 9000.00),
(43, '2025-06-04 02:15:36', 11, 14, 'Air x 1, Ikan x 1', 1012, 'Cash', 0, 2000.00, 988.00),
(44, '2025-06-04 02:23:10', 11, NULL, 'Ikan x 1, Air x 1, Bubur x 2', 21012, 'Cash', 0, 22000.00, 988.00),
(45, '2025-06-04 02:23:52', 11, NULL, 'Air x 1, Bubur x 1', 11000, 'Cash', 0, 12000.00, 1000.00),
(46, '2025-06-04 02:24:21', 11, NULL, 'Bubur x 1, Air x 1', 11000, 'Cash', 0, 12000.00, 1000.00),
(47, '2025-06-04 02:24:54', 11, 14, 'Bubur x 2', 20000, 'Cash', 0, 21000.00, 1000.00),
(48, '2025-06-04 02:28:58', 11, 14, 'Bubur x 1', 10000, 'Cash', 0, 11000.00, 1000.00),
(49, '2025-06-04 02:30:27', 11, 14, 'Ikan x 1', 12, 'Cash', 0, 15.00, 3.00),
(50, '2025-06-04 02:36:30', 11, 14, 'Bubur x 1', 10000, 'Cash', 0, 15000.00, 5000.00),
(51, '2025-06-04 02:57:53', 11, 14, '', 0, 'Cash', 0, 250000.00, 250000.00),
(52, '2025-06-04 03:03:46', 11, 14, '', 0, 'Cash', 0, 250000.00, 250000.00),
(53, '2025-06-04 03:21:09', 11, 14, '', 0, 'Cash', 0, 250000.00, 250000.00),
(54, '2025-06-04 03:23:51', 11, 14, 'Bubur x 21', 210000, 'Cash', 0, 250000.00, 40000.00),
(55, '2025-06-04 03:26:51', 11, 14, 'Botol x 1, Yoguhrt x 1, Bubur x 10', 138000, 'Cash', 0, 150000.00, 12000.00),
(56, '2025-06-04 03:28:42', 11, 14, 'Matcha x 1', 300000, 'Cash', 0, 300000.00, 0.00),
(57, '2025-06-04 03:34:22', 11, 14, 'Bubur x 1', 9336, 'Cash', 0, 10000.00, 664.00),
(58, '2025-06-04 07:43:05', 11, 14, 'Baju x 1, Botol x 1', 24327, 'Cash', 0, 25000.00, 673.00),
(59, '2025-06-04 07:45:19', 11, NULL, 'Bubur x 1', 10000, 'Cash', 0, 12000.00, 2000.00);

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
(22, 33, 16, 3, 1000.00, 3000.00),
(24, 34, 9, 1, 10000.00, 10000.00),
(25, 35, 10, 1, 12000.00, 12000.00),
(26, 35, 9, 2, 10000.00, 20000.00),
(27, 36, 9, 1, 10000.00, 10000.00),
(28, 37, 9, 1, 10000.00, 10000.00),
(29, 38, 9, 1, 10000.00, 10000.00),
(30, 39, 9, 1, 10000.00, 10000.00),
(31, 40, 9, 1, 10000.00, 10000.00),
(32, 41, 9, 3, 10000.00, 30000.00),
(33, 42, 9, 1, 10000.00, 10000.00),
(35, 43, 16, 1, 1000.00, 1000.00),
(36, 43, 10, 1, 12.00, 12.00),
(37, 44, 10, 1, 12.00, 12.00),
(38, 44, 16, 1, 1000.00, 1000.00),
(39, 44, 9, 2, 10000.00, 20000.00),
(40, 45, 16, 1, 1000.00, 1000.00),
(41, 45, 9, 1, 10000.00, 10000.00),
(42, 46, 9, 1, 10000.00, 10000.00),
(43, 46, 16, 1, 1000.00, 1000.00),
(44, 47, 9, 2, 10000.00, 20000.00),
(45, 48, 9, 1, 10000.00, 10000.00),
(46, 49, 10, 1, 12.00, 12.00),
(47, 50, 9, 1, 10000.00, 10000.00),
(48, 54, 9, 21, 10000.00, 210000.00),
(49, 55, 22, 1, 25000.00, 25000.00),
(50, 55, 21, 1, 13000.00, 13000.00),
(51, 55, 9, 10, 10000.00, 100000.00),
(52, 56, 23, 1, 300000.00, 300000.00),
(53, 57, 9, 1, 10000.00, 10000.00),
(54, 58, 24, 1, 0.00, 0.00),
(55, 58, 22, 1, 25000.00, 25000.00),
(56, 59, 9, 1, 10000.00, 10000.00);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `transactions_details`
--
ALTER TABLE `transactions_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

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
