-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 12, 2025 at 03:18 AM
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
(7, 'rennard95@gmail.com', 'reffan', '$2y$10$EWO4jwZQG4HpVnkJiCkIseDXFRklxD0leehxFcOMzpRFJL5WriBJ.', '6818427ea3710.jpeg', NULL, '2025-07-17 03:34:43', 'Active', 'Admin'),
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
(18, 'Obat jamu', '69425038.jpg'),
(19, 'Obat Keras', '12408574.jpg'),
(20, 'Obat Bebas', '11290405.jpg'),
(21, 'Obat Bebas Terbatas', '40338970.jpg');

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
(19, 'reifan', '082213521461', 88, '2025-08-11 08:51:21', 'non-active'),
(21, 'Rennard', '08568795015', 3, '2025-08-11 09:35:31', 'active');

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
(35, 'Panadol Biru', '66789426', 20, 9000, 12000, 3000, 1, '2025-08-20', '399695.jpeg', 'enak', '2025-08-04 11:42:43', '2025-08-11 11:51:06'),
(36, 'Extra Joss Hijau', '60717102', 18, 3500, 4000, 500, 3, '2025-08-14', '738685.jpeg', 'enak', '2025-08-11 11:01:10', '2025-08-11 11:54:33'),
(37, 'Amoxicillin', '23355486', 21, 2500, 3500, 1000, 8, '2025-08-13', '965371.jpeg', 'enak', '2025-08-11 11:01:40', '2025-08-12 08:02:55'),
(38, 'Panadol Merah', '32280019', 20, 5000, 6500, 1500, 10, '2025-08-19', '369781.jpeg', 'enak', '2025-08-11 11:02:25', '2025-08-11 11:59:22'),
(39, 'Panadol Hijau', '43257273', 20, 1500, 3500, 2000, 5, '2025-08-18', '126498.jpeg', 'enak', '2025-08-11 11:03:05', '2025-08-11 11:59:27'),
(40, 'Cefixime', '98491750', 21, 34000, 37000, 3000, 8, '2025-08-15', '993348.jpeg', 'enak', '2025-08-11 11:03:33', '2025-08-11 11:59:31'),
(41, 'Panadol Ga Liat', '49249597', 20, 1000, 2000, 1000, 2, '2025-08-12', '121511.jpeg', 'enak', '2025-08-11 11:04:36', '2025-08-11 11:56:37'),
(42, 'Dextromethorphan HBr', '41658779', 19, 4500, 5000, 500, 43, '2025-08-13', '476111.jpeg', 'enak', '2025-08-11 11:05:27', '2025-08-11 12:00:28'),
(45, 'Extra Joss Ungu', '19225545', 18, 5000, 6500, 1500, 3, '2025-08-12', '134455.jpeg', 'enak', '2025-08-11 11:07:35', '2025-08-11 11:51:14'),
(46, 'Guaifenesin', '32826851', 19, 4890, 5000, 110, 3, '2025-08-12', '935233.jpeg', 'enak', '2025-08-11 11:08:30', NULL),
(47, 'Pseudoephedrine', '90688027', 19, 400, 5000, 4600, 4, '2025-08-13', '173975.jpeg', 'enak', '2025-08-11 11:09:13', NULL),
(48, 'Loperamide', '40320120', 19, 800, 6000, 5200, 2, '2025-08-13', '741645.jpeg', 'enak', '2025-08-11 11:09:35', '2025-08-11 12:00:10'),
(51, 'Kuku Bima Hijau', '65894103', 18, 900, 8000, 7100, 7, '2025-08-12', '998833.jpeg', 'enak', '2025-08-11 11:12:04', '2025-08-11 11:15:09'),
(52, 'Kuku Bima Kuning', '27804467', 18, 900, 8000, 7100, 9, '2025-08-12', '851886.jpeg', 'enak', '2025-08-11 11:12:52', NULL),
(53, 'Kuku Bima Ungu', '39012920', 18, 5600, 9000, 3400, 21, '2025-08-12', '523947.jpeg', 'erer', '2025-08-11 11:13:25', '2025-08-11 11:59:38'),
(54, 'Tolak Angin Biru', '65372403', 18, 900, 9000, 8100, 11, '2025-08-12', '688072.png', 'enak', '2025-08-11 11:13:56', '2025-08-11 12:00:04'),
(55, 'Tolak Angin Coklat', '91732182', 20, 3434, 5000, 1566, 23, '2025-08-19', '259635.png', 'enak', '2025-08-11 11:14:59', NULL);

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
(62, '2025-08-11 04:32:17', 11, 19, 'Panadol Biru x 1', 12000, 'Cash', 3000, 13000.00, 1000.00),
(63, '2025-08-11 04:41:52', 11, 19, 'Panadol Biru x 1', 12000, 'Cash', 3000, 13000.00, 1000.00),
(64, '2025-08-11 04:46:12', 11, 19, 'Panadol Ga Liat x 1', 2000, 'Cash', 1000, 10000.00, 8000.00),
(65, '2025-08-11 04:51:34', 11, 19, 'Panadol Biru x 2, Extra Joss Hijau x 2, Panadol Merah x 2, Panadol Ga Liat x 2, Extra Joss Ungu x 2', 62000, 'Cash', 15000, 100000.00, 38000.00),
(66, '2025-08-11 04:56:55', 11, NULL, 'Panadol Ga Liat x 1', 2000, 'Cash', 1000, 3000.00, 1000.00),
(67, '2025-08-12 01:03:23', 11, 21, 'Amoxicillin x 1', 3500, 'Cash', 1000, 4000.00, 500.00);

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
(1, 62, 35, 1, 12000.00, 12000.00),
(2, 63, 35, 1, 12000.00, 12000.00),
(3, 64, 41, 1, 2000.00, 2000.00),
(4, 65, 35, 2, 12000.00, 24000.00),
(5, 65, 36, 2, 4000.00, 8000.00),
(6, 65, 38, 2, 6500.00, 13000.00),
(7, 65, 41, 2, 2000.00, 4000.00),
(8, 65, 45, 2, 6500.00, 13000.00),
(9, 66, 41, 1, 2000.00, 2000.00),
(10, 67, 37, 1, 3500.00, 3500.00);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `transactions_details`
--
ALTER TABLE `transactions_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
