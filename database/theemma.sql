-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 06, 2025 lúc 06:34 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `theemma`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `customers`
--

INSERT INTO `customers` (`name`, `phone`, `address`) VALUES
('Khách hàng 1', '0123456785', '123 Đường ABC, Quận 1, TP.HCM'),
('Khách hàng 2', '0123456786', '456 Đường XYZ, Quận 2, TP.HCM'),
('Khách hàng 3', '0123456787', '789 Đường DEF, Quận 3, TP.HCM'),
('Khách hàng 4', '0123456788', '321 Đường GHI, Quận 4, TP.HCM'),
('Khách hàng 5', '0123456789', '654 Đường KLM, Quận 5, TP.HCM');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `phone` varchar(20) DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT 0.00,
  `isDeleted` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `hourly_rate`, `isDeleted`) VALUES
(1, 'Admin', 'admin@theemma.com', '$2y$10$N.VdieKrRFTjQ0kBes3RJ.2ei1sUzA8qnbLn9qMwXM7QWsYwOemQO', 'admin', '0123456789', 0.00, 0),
(2, 'Nhân viên 1', 'nv1@theemma.com', '$2y$10$N.VdieKrRFTjQ0kBes3RJ.2ei1sUzA8qnbLn9qMwXM7QWsYwOemQO', 'user', '0123456781', 50000.00, 0),
(3, 'Nhân viên 2', 'nv2@theemma.com', '$2y$10$N.VdieKrRFTjQ0kBes3RJ.2ei1sUzA8qnbLn9qMwXM7QWsYwOemQO', 'user', '0123456782', 45000.00, 0),
(4, 'Nhân viên 3', 'nv3@theemma.com', '$2y$10$N.VdieKrRFTjQ0kBes3RJ.2ei1sUzA8qnbLn9qMwXM7QWsYwOemQO', 'user', '0123456783', 48000.00, 0),
(5, 'Nhân viên 4', 'nv4@theemma.com', '$2y$10$N.VdieKrRFTjQ0kBes3RJ.2ei1sUzA8qnbLn9qMwXM7QWsYwOemQO', 'user', '0123456784', 52000.00, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `user_tokens`
--

INSERT INTO `user_tokens` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 1, '7e507a5637513d312631335c42c1072ab9b93aa01b26950c962353441bc191ff', '2025-06-07 04:32:58', '2025-06-06 04:32:58');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `description` TEXT,
  `image` VARCHAR(255),
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `category_id` INT,
  `isDeleted` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `description`, `image`, `status`, `created_at`, `updated_at`, `category_id`, `isDeleted`) VALUES
(1, 'Bánh kem socola', 350000, 'Bánh kem socola thơm ngon', 'banhkem1.jpg', 'active', NOW(), NOW(), 1, 0),
(2, 'Bánh kem dâu tây', 355000, 'Bánh kem dâu tây tươi', 'banhkem2.jpg', 'active', NOW(), NOW(), 1, 0),
(3, 'Bánh kem matcha', 340000, 'Bánh kem vị matcha Nhật Bản', 'banhkem3.jpg', 'active', NOW(), NOW(), 2, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `order_code` VARCHAR(50),
  `payment_method` VARCHAR(50),
  `total_amount` DECIMAL(10,2) NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `shipping_address` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isDeleted` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `order_code`, `payment_method`, `total_amount`, `status`, `notes`, `shipping_address`, `created_at`, `updated_at`, `isDeleted`) VALUES
(1, 1, 'DH0001', 'cod', 700000, 'pending', 'Giao hàng trong giờ hành chính', '123 Đường ABC, Quận 1, TP.HCM', NOW(), NOW(), 0),
(2, 2, 'DH0002', 'cod', 355000, 'completed', 'Khách nhận sau 18h', '456 Đường XYZ, Quận 2, TP.HCM', NOW(), NOW(), 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 350000, '', NOW(), NOW()),
(2, 1, 2, 1, 350000, '', NOW(), NOW()),
(3, 2, 2, 1, 355000, '', NOW(), NOW());

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isDeleted` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`, `updated_at`, `isDeleted`) VALUES
(1, 'Bánh kem truyền thống', NOW(), NOW(), 0),
(2, 'Bánh kem hiện đại', NOW(), NOW(), 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_sizes`
--

CREATE TABLE IF NOT EXISTS `product_sizes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `size` VARCHAR(20) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `product_sizes`
--

INSERT INTO `product_sizes` (`product_id`, `size`, `price`) VALUES
(1, '16cm', 350000),
(1, '18cm', 400000),
(1, '20cm', 450000),
(2, '16cm', 355000),
(2, '18cm', 410000),
(2, '20cm', 465000),
(3, '16cm', 340000),
(3, '18cm', 390000),
(3, '20cm', 440000);

-- --------------------------------------------------------

-- Cấu trúc bảng cho bảng `inventory_in`

CREATE TABLE `inventory_in` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `item_name` VARCHAR(100) NOT NULL,
    `quantity` DECIMAL(10,2) NOT NULL,
    `unit` VARCHAR(50) DEFAULT NULL,
    `price` DECIMAL(15,2) DEFAULT 0,
    `item_type` VARCHAR(50) DEFAULT NULL,
    `notes` TEXT,
    `created_by` INT,
    `isDeleted` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Đang đổ dữ liệu cho bảng `inventory_in`

INSERT INTO `inventory_in` (`id`, `item_name`, `quantity`, `unit`, `price`, `item_type`, `notes`, `created_by`, `created_at`, `isDeleted`) VALUES
(1, 'Bột mì', 50, 'kg', 100000, 'ingredient', 'Nhập nguyên liệu làm bánh', 1, NOW(), 0),
(2, 'Đường', 30, 'kg', 50000, 'ingredient', 'Nguyên liệu cơ bản', 1, NOW(), 0),
(3, 'Hộp giấy', 100, 'cái', 20000, 'packaging', 'Hộp đựng bánh size lớn', 1, NOW(), 0),
(4, 'Túi nilon', 200, 'cái', 10000, 'packaging', 'Túi đựng bánh nhỏ', 1, NOW(), 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_check`
--

CREATE TABLE `inventory_check` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `before_quantity` DECIMAL(10,2) DEFAULT NULL,
    `actual_quantity` DECIMAL(10,2) NOT NULL,
    `note` TEXT,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`item_id`) REFERENCES `inventory_in`(`id`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Cấu trúc bảng cho bảng `attendance`
--
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `checked_at` DATETIME NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_date` (`user_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Cấu trúc bảng cho bảng `attendance_details`
--
CREATE TABLE IF NOT EXISTS `attendance_details` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `weekday` TINYINT NOT NULL, -- 1=Thứ 2, ..., 7=Chủ nhật
  `time_from` TIME NOT NULL,
  `time_to` TIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;