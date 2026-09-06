-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2025 at 08:01 AM
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
-- Database: `road_fuel`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `branch_code` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(50) NOT NULL,
  `province` varchar(50) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `branch_name`, `branch_code`, `address`, `city`, `province`, `latitude`, `longitude`, `contact_number`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Tupi Seaoil', '234324', 'tupi', 'poblacion', 'south cotabato', 6.34101300, 124.94326000, '7898797812', 'active', '2025-10-14 11:32:28', '2025-10-14 12:14:57'),
(2, 'Flying V', '1231', 'General Santos-Koronadal Highway, Purok 2, Tupi, South Cotabato, Soccsksargen, 9505, Philippines', 'poblacion', 'south cotabato', 6.34714795, 124.93757218, '7898797812', 'active', '2025-10-14 13:00:39', '2025-10-14 13:00:39');

-- --------------------------------------------------------

--
-- Table structure for table `branch_stock`
--

CREATE TABLE `branch_stock` (
  `stock_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_liters` decimal(10,2) NOT NULL DEFAULT 0.00,
  `minimum_stock` decimal(10,2) NOT NULL DEFAULT 100.00,
  `last_updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branch_stock`
--

INSERT INTO `branch_stock` (`stock_id`, `branch_id`, `product_id`, `quantity_liters`, `minimum_stock`, `last_updated_by`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 42.00, 1.00, 2, '2025-10-14 11:36:13', '2025-10-14 13:03:35');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `driver_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`driver_id`, `username`, `password`, `full_name`, `email`, `phone`, `license_number`, `address`, `profile_image`, `status`, `created_at`, `updated_at`) VALUES
(2, 'mark', '$2y$10$Nq2XLlVyaWaVdaCvWQCr7ugVRCQsR6Ejt6e6zJgaBzRbTZTQzvMu2', 'Mark Villar', 'mark@gmail.com', '78987978', '1231212', 'tupi', NULL, 'active', '2025-10-14 08:54:29', '2025-10-14 08:54:29');

-- --------------------------------------------------------

--
-- Table structure for table `driver_locations`
--

CREATE TABLE `driver_locations` (
  `location_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `address_description` text DEFAULT NULL,
  `landmark` text DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `assigned_staff_id` int(11) DEFAULT NULL,
  `delivery_latitude` decimal(10,8) NOT NULL,
  `delivery_longitude` decimal(11,8) NOT NULL,
  `delivery_address` text NOT NULL,
  `landmark` text DEFAULT NULL,
  `vehicle_type` enum('motorcycle','sedan','suv','truck','van','bus') NOT NULL,
  `vehicle_plate_number` varchar(20) NOT NULL,
  `vehicle_brand` varchar(50) DEFAULT NULL,
  `vehicle_model` varchar(50) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_liters` decimal(10,2) NOT NULL,
  `price_per_liter` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) DEFAULT 50.00,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cod','gcash') NOT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `gcash_reference_number` varchar(100) DEFAULT NULL,
  `gcash_payment_proof` varchar(255) DEFAULT NULL,
  `order_status` enum('pending','confirmed','preparing','on_delivery','completed','cancelled') DEFAULT 'pending',
  `cancellation_reason` text DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `estimated_delivery_time` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_number`, `driver_id`, `branch_id`, `assigned_staff_id`, `delivery_latitude`, `delivery_longitude`, `delivery_address`, `landmark`, `vehicle_type`, `vehicle_plate_number`, `vehicle_brand`, `vehicle_model`, `product_id`, `quantity_liters`, `price_per_liter`, `subtotal`, `delivery_fee`, `total_amount`, `payment_method`, `payment_status`, `gcash_reference_number`, `gcash_payment_proof`, `order_status`, `cancellation_reason`, `special_instructions`, `estimated_delivery_time`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 'ORD-20251014202852-404', 2, 1, NULL, 6.34101800, 124.94303100, 'General Santos-Koronadal Highway, Purok 2, Tupi, South Cotabato, Soccsksargen, 9505, Philippines', NULL, 'motorcycle', '234234', NULL, NULL, 3, 1.00, 58.00, 58.00, 50.00, 108.00, 'cod', 'pending', NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL, '2025-10-14 12:28:52', '2025-10-14 12:48:53'),
(2, 'ORD-20251014204444-832', 2, 1, 4, 6.34103600, 124.94319000, 'tupi', NULL, 'motorcycle', '234234', NULL, NULL, 3, 1.00, 58.00, 58.00, 50.00, 108.00, 'cod', 'pending', NULL, NULL, 'pending', NULL, NULL, NULL, NULL, '2025-10-14 12:44:44', '2025-10-14 12:44:44'),
(3, 'ORD-20251014205344-428', 2, 1, 4, 6.34102600, 124.94300700, 'General Santos-Koronadal Highway, Purok 2, Tupi, South Cotabato, Soccsksargen, 9505, Philippines', NULL, 'sedan', '234234', NULL, NULL, 3, 1.00, 58.00, 58.00, 50.00, 108.00, 'gcash', 'pending', NULL, NULL, 'pending', NULL, NULL, NULL, NULL, '2025-10-14 12:53:44', '2025-10-14 12:53:44'),
(4, 'ORD-20251014210335-963', 2, 1, 4, 6.34100200, 124.94307700, 'General Santos-Koronadal Highway, Purok 2, Tupi, South Cotabato, Soccsksargen, 9505, Philippines', NULL, 'motorcycle', '234234', NULL, NULL, 3, 1.00, 58.00, 58.00, 50.00, 108.00, 'gcash', 'pending', NULL, NULL, 'confirmed', NULL, NULL, NULL, NULL, '2025-10-14 13:03:35', '2025-10-16 05:54:49');

-- --------------------------------------------------------

--
-- Table structure for table `order_tracking`
--

CREATE TABLE `order_tracking` (
  `tracking_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','preparing','on_delivery','completed','cancelled') NOT NULL,
  `notes` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_tracking`
--

INSERT INTO `order_tracking` (`tracking_id`, `order_id`, `status`, `notes`, `updated_by`, `created_at`) VALUES
(1, 1, 'pending', 'Order placed via quick form', NULL, '2025-10-14 12:28:52'),
(2, 2, 'pending', 'Order placed via quick form', NULL, '2025-10-14 12:44:45'),
(3, 1, 'cancelled', '', 2, '2025-10-14 12:48:53'),
(4, 3, 'pending', 'Order placed via quick form', NULL, '2025-10-14 12:53:45'),
(5, 4, 'pending', 'Order placed via quick form', NULL, '2025-10-14 13:03:35'),
(6, 4, 'preparing', '', 4, '2025-10-16 05:42:20'),
(7, 4, 'completed', '', 4, '2025-10-16 05:54:42'),
(8, 4, 'confirmed', '', 4, '2025-10-16 05:54:49'),
(9, 4, 'confirmed', '', 4, '2025-10-16 05:54:49');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `fuel_type` enum('unleaded','premium','diesel','kerosene','special') NOT NULL,
  `description` text DEFAULT NULL,
  `price_per_liter` decimal(10,2) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `fuel_type`, `description`, `price_per_liter`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Unleaded Gasoline', 'unleaded', 'Regular unleaded gasoline', 60.50, 'active', '2025-10-14 08:35:09', '2025-10-14 08:35:09'),
(2, 'Premium Gasoline 97', 'premium', 'Premium high-octane gasoline', 72.50, 'active', '2025-10-14 08:35:09', '2025-10-14 08:35:09'),
(3, 'Diesel Fuel', 'diesel', 'Standard diesel fuel', 58.00, 'active', '2025-10-14 08:35:09', '2025-10-14 08:35:09'),
(4, 'Premium Diesel', 'diesel', 'High-quality diesel fuel', 65.00, 'active', '2025-10-14 08:35:09', '2025-10-14 08:35:09');

-- --------------------------------------------------------

--
-- Table structure for table `stock_history`
--

CREATE TABLE `stock_history` (
  `history_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `transaction_type` enum('add','deduct','order') NOT NULL,
  `quantity_change` decimal(10,2) NOT NULL,
  `previous_quantity` decimal(10,2) NOT NULL,
  `new_quantity` decimal(10,2) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_history`
--

INSERT INTO `stock_history` (`history_id`, `branch_id`, `product_id`, `transaction_type`, `quantity_change`, `previous_quantity`, `new_quantity`, `reference_id`, `notes`, `updated_by`, `created_at`) VALUES
(1, 1, 3, 'order', -1.00, 45.00, 44.00, 2, 'Auto-deduct for order', 2, '2025-10-14 12:44:44'),
(2, 1, 3, 'order', -1.00, 44.00, 43.00, 3, 'Auto-deduct for order', 2, '2025-10-14 12:53:45'),
(3, 1, 3, 'order', -1.00, 43.00, 42.00, 4, 'Auto-deduct for order', 2, '2025-10-14 13:03:35');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'delivery_fee', '50.00', 'Base delivery fee in PHP', '2025-10-14 08:35:09'),
(2, 'service_radius_km', '20', 'Maximum service radius from branch in kilometers', '2025-10-14 08:35:09'),
(3, 'gcash_merchant_id', '', 'GCash merchant ID', '2025-10-14 08:35:09'),
(4, 'enable_sms_notifications', '1', 'Enable SMS notifications', '2025-10-14 08:35:09'),
(5, 'enable_email_notifications', '1', 'Enable email notifications', '2025-10-14 08:35:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `role` enum('admin','staff') NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `branch_id`, `status`, `created_at`, `updated_at`) VALUES
(2, 'admin', '$2y$10$kxVdmrk7gtsWKblRHiG8FuMKf1mah33U88mG35/eH89PkysI9u49a', 'Admin User', 'admin@example.com', '09171234567', 'admin', NULL, 'active', '2025-10-14 10:44:12', '2025-10-14 10:44:12'),
(4, 'james', '$2y$10$gkuuNvRUufjy.eNMGIIfl.d9c48xI.2VQYLAlcuzIC.9uT.ycQqzi', 'james', 'james@gmail.com', '7898797812', 'staff', 1, 'active', '2025-10-14 11:35:48', '2025-10-14 11:35:48'),
(5, 'jam', '$2y$10$7/A1P5gawfD9KbwXa92sP.PHal1pMSXCx2o39dM9flRXgPNE7e/1a', 'jamjamn', 'jam@gmail.com', '78987978121', 'staff', 2, 'active', '2025-10-14 13:01:57', '2025-10-14 13:01:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`branch_id`),
  ADD UNIQUE KEY `branch_code` (`branch_code`);

--
-- Indexes for table `branch_stock`
--
ALTER TABLE `branch_stock`
  ADD PRIMARY KEY (`stock_id`),
  ADD UNIQUE KEY `unique_branch_product` (`branch_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `last_updated_by` (`last_updated_by`),
  ADD KEY `idx_branch_stock` (`branch_id`,`product_id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`driver_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `driver_locations`
--
ALTER TABLE `driver_locations`
  ADD PRIMARY KEY (`location_id`),
  ADD KEY `idx_driver_location` (`driver_id`,`is_current`),
  ADD KEY `idx_location_coords` (`latitude`,`longitude`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `assigned_staff_id` (`assigned_staff_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_driver_orders` (`driver_id`,`order_status`),
  ADD KEY `idx_branch_orders` (`branch_id`,`order_status`),
  ADD KEY `idx_order_date` (`created_at`);

--
-- Indexes for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD PRIMARY KEY (`tracking_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_branch` (`branch_id`,`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `branch_stock`
--
ALTER TABLE `branch_stock`
  MODIFY `stock_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `driver_locations`
--
ALTER TABLE `driver_locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_tracking`
--
ALTER TABLE `order_tracking`
  MODIFY `tracking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `branch_stock`
--
ALTER TABLE `branch_stock`
  ADD CONSTRAINT `branch_stock_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `branch_stock_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `branch_stock_ibfk_3` FOREIGN KEY (`last_updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `driver_locations`
--
ALTER TABLE `driver_locations`
  ADD CONSTRAINT `driver_locations_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD CONSTRAINT `order_tracking_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_tracking_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD CONSTRAINT `stock_history_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_history_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_history_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
