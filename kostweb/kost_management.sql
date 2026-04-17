-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2025 at 07:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kost_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `request_type` enum('plumbing','electrical','furniture','cleaning','other') NOT NULL,
  `description` text NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','in_progress','completed','rejected') NOT NULL DEFAULT 'pending',
  `resolved_date` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`id`, `resident_id`, `room_id`, `request_type`, `description`, `request_date`, `status`, `resolved_date`, `notes`) VALUES
(1, 1, 1, 'furniture', 'AC bocor', '2025-04-15 10:08:53', 'completed', '2025-04-26 09:21:06', '');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','transfer','other') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `resident_id`, `room_id`, `amount`, `payment_date`, `payment_method`, `description`, `status`, `created_at`) VALUES
(1, 1, 1, 1000000.00, '2025-04-15', 'transfer', 'Monthly rent for Room 101', 'confirmed', '2025-04-15 10:46:37'),
(2, 1, 1, 1000000.00, '2025-04-26', 'cash', 'Monthly rent for Room 101', 'confirmed', '2025-04-26 14:30:18'),
(3, 1, 1, 1000000.00, '2025-05-06', 'transfer', 'Monthly rent for Room 101', 'pending', '2025-05-06 13:07:57'),
(4, 1, 1, 1000000.00, '2025-05-06', 'transfer', 'Monthly rent for Room 101', 'pending', '2025-05-06 13:08:03');

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `id_card_number` varchar(50) NOT NULL,
  `check_in_date` date DEFAULT NULL,
  `check_out_date` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`id`, `user_id`, `room_id`, `full_name`, `phone`, `emergency_contact`, `id_card_number`, `check_in_date`, `check_out_date`, `status`) VALUES
(1, 3, 1, 'Shanty', '081285672878', '', '0012300', NULL, NULL, 'active'),
(2, 8, 6, 'Kale', '081211111', '', '10101010', NULL, NULL, 'active'),
(3, 9, 4, 'Johnny', '081222222', '', '121212', NULL, NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `floor` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `facilities` text DEFAULT NULL,
  `status` enum('available','occupied','maintenance') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `floor`, `price`, `facilities`, `status`) VALUES
(1, '101', 1, 1000000.00, 'Private room, Bed, Air Conditioner', 'occupied'),
(2, '102', 1, 1200000.00, 'AC, Bed', 'available'),
(3, '105', 1, 1000000.00, 'AC', 'available'),
(4, '103', 1, 1300000.00, 'Bed, TV, AC', 'occupied'),
(5, '104', 1, 1000000.00, 'AC', 'available'),
(6, '110', 1, 1500000.00, 'AC, Bed', 'occupied'),
(7, '201', 2, 1000000.00, 'AC', 'available');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','resident') NOT NULL DEFAULT 'resident',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$IEzWVGZn8i2/iEeTc9U2AOGMAZrxXZ3ARPpSFVx46Hq0.9S/JI45K', 'admin@kost.com', 'admin', '2025-04-15 08:15:39'),
(3, 'shan00', '$2y$10$P.ETp8XvSJsFC3wxNSJ9jONTixiYelnRf4aHxDhAHlvWgPj2VILSq', 'shanty.kh2@gmail.com', 'resident', '2025-04-15 08:27:45'),
(5, 'admin1', '80a19f669b02edfbc208a5386ab5036b', '', 'admin', '2025-04-15 09:54:40'),
(7, 'admin2', '$2y$10$xDrLSw4pI.bUEOdbSCl6CegCOf4s/1dSVHx5WnG6PWZfILqrdI5fe', 'admin@gmail.com', 'admin', '2025-04-15 10:00:20'),
(8, 'kale', '$2y$10$zXG4fXQhFEQmATgaDeZsb.yJF9weFpLMM5jNcPEYm6aKHcJUDk5Wa', 'kale@gmail.com', 'resident', '2025-05-06 16:49:27'),
(9, 'john', '$2y$10$ZIqcjpszwY.fgSteu3pPGOUnDQJZGwo.62dGkhYCKYodSa7yJdQMu', 'john@gmail.com', 'resident', '2025-05-06 16:50:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resident_id` (`resident_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resident_id` (`resident_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`),
  ADD CONSTRAINT `maintenance_requests_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);

--
-- Constraints for table `residents`
--
ALTER TABLE `residents`
  ADD CONSTRAINT `residents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `residents_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
