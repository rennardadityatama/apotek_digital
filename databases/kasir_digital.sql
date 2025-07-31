-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 31, 2025 at 03:19 AM
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
  `category` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `category`) VALUES
(3, 'Minuman'),
(9, 'Makanan'),
(11, 'Cemilan'),
(15, 'Perlengkapan');

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
(14, 'Rennard', '082213521461', 991, '2025-06-04 09:00:02', 'non-active'),
(15, 'laisofhh', '088888', 0, '2025-07-18 13:33:00', 'non-active');

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
(28, 'Mouse', '98668349', 15, 190000, 210000, 20000, 3, '2025-07-28', '125802.jpeg', 'kualitas', '2025-07-28 10:21:51', '2025-07-28 10:44:01'),
(29, 'Panadol', '36088945', 15, 9000, 12000, 3000, 2, '2025-07-29', '444234.jpg', 'anjayy', '2025-07-28 10:23:02', NULL),
(30, 'Floridina', '87699485', 3, 4500, 6000, 1500, 2, '2025-07-31', '513766.jpg', 'enak', '2025-07-28 10:23:38', NULL),
(31, 'Fried Chicken', '54849370', 9, 4500, 7000, 2500, 3, '2025-07-29', '815673.jpg', 'dww', '2025-07-28 10:24:43', NULL),
(32, 'Hansaplast', '69767989', 15, 2000, 3500, 1500, 2, '2025-07-29', '866810.jpg', 'aghfkg', '2025-07-28 10:25:20', NULL),
(33, 'Momogi', '13544650', 11, 1500, 2500, 1000, 2, '2025-07-31', '241503.jpg', 'aghfkg', '2025-07-28 10:25:54', NULL),
(34, 'Oasis', '88662795', 3, 2000, 2500, 500, 4, '2025-07-31', '905878.jpeg', 'vwuieyfiyfief', '2025-07-28 10:26:24', NULL);

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
(59, '2025-06-04 07:45:19', 11, NULL, 'Bubur x 1', 10000, 'Cash', 0, 12000.00, 2000.00),
(60, '2025-07-18 06:37:49', 11, 14, 'Bubur x 1, Baju x 1, Kunci x 2', 280000, 'Cash', 0, 300000.00, 20000.00),
(61, '2025-07-28 01:15:05', 11, 14, 'few x 1, Yoguhrt x 1', 14222, 'Cash', 5210, 15000.00, 778.00);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `transactions_details`
--
ALTER TABLE `transactions_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

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
