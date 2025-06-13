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
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `created_at`) VALUES
(1, 'Admin', 'admin@theemma.com', '$2y$10$JW3kZlnK8MushDeG.MkHSuk29kKfDLtnkKPR1tQvX7Z4Wic2MlX9a', 'admin', '0123456789', '2025-06-06 04:22:22');

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
  `category_id` INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `description`, `image`, `status`, `created_at`, `updated_at`, `category_id`) VALUES
(1, 'Bánh kem socola', 350000, 'Bánh kem socola thơm ngon', 'banhkem1.jpg', 'active', NOW(), NOW(), 1),
(2, 'Bánh kem dâu tây', 355000, 'Bánh kem dâu tây tươi', 'banhkem2.jpg', 'active', NOW(), NOW(), 1),
(3, 'Bánh kem matcha', 340000, 'Bánh kem vị matcha Nhật Bản', 'banhkem3.jpg', 'active', NOW(), NOW(), 2);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `order_code` VARCHAR(50),
  `customer_name` VARCHAR(100),
  `customer_phone` VARCHAR(20),
  `customer_email` VARCHAR(100),
  `payment_method` VARCHAR(50),
  `total_amount` DECIMAL(10,2) NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `shipping_address` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_code`, `customer_name`, `customer_phone`, `customer_email`, `payment_method`, `total_amount`, `status`, `notes`, `shipping_address`, `created_at`, `updated_at`) VALUES
(1, 1, 'DH0001', 'Admin', '0123456789', 'admin@theemma.com', 'cod', 700000, 'pending', 'Giao hàng trong giờ hành chính', '123 Đường ABC, Quận 1, TP.HCM', NOW(), NOW()),
(2, 1, 'DH0002', 'Admin', '0123456789', 'admin@theemma.com', 'cod', 355000, 'completed', 'Khách nhận sau 18h', '456 Đường XYZ, Quận 3, TP.HCM', NOW(), NOW());

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
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Bánh kem truyền thống', NOW(), NOW()),
(2, 'Bánh kem hiện đại', NOW(), NOW());

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
    `item_type` VARCHAR(50) DEFAULT NULL,
    `notes` TEXT,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Đang đổ dữ liệu cho bảng `inventory_in`

INSERT INTO `inventory_in` (`id`, `item_name`, `quantity`, `item_type`, `notes`, `created_by`, `created_at`) VALUES
(1, 'Bột mì', 50, 'ingredient', 'Nhập nguyên liệu làm bánh', 1, NOW()),
(2, 'Đường', 30, 'ingredient', 'Nguyên liệu cơ bản', 1, NOW()),
(3, 'Hộp giấy', 100, 'packaging', 'Hộp đựng bánh size lớn', 1, NOW()),
(4, 'Túi nilon', 200, 'packaging', 'Túi đựng bánh nhỏ', 1, NOW());

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
