-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 22, 2026 at 01:19 AM
-- Server version: 8.0.17
-- PHP Version: 7.3.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `it_ticketing`
--

-- --------------------------------------------------------

--
-- Table structure for table `branch`
--

CREATE TABLE `branch` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `branch`
--

INSERT INTO `branch` (`id`, `name`) VALUES
(1, 'Bansalan'),
(2, 'Calinan'),
(3, 'Digos'),
(4, 'Glan'),
(5, 'Kapatagan'),
(6, 'Kiblawan'),
(7, 'Malandag'),
(8, 'Matanao'),
(9, 'Sulop'),
(10, 'Hagonoy'),
(11, 'Toril'),
(12, 'JAS'),
(13, 'Gensan'),
(14, 'Polomolok'),
(15, 'Sta. Cruz'),
(16, 'Kidapawan'),
(17, 'Surallah'),
(18, 'Malungon'),
(19, 'Head Office'),
(20, 'Koronadal');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `default_priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `dept_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `default_priority`, `dept_id`) VALUES
(1, 'Network/Internet Outage', 'High', 1),
(2, 'Software Installation', 'Medium', 1),
(3, 'Hardware Malfunction', 'High', 1),
(4, 'Email/Account Locked', 'High', 1),
(5, 'Printer/Scanner Issue', 'Medium', 1),
(6, 'VPN/Remote Access', 'Medium', 1),
(7, 'Payroll Discrepancy', 'High', 2),
(8, 'Leave/Time Off Request', 'Low', 2),
(9, 'Employee Onboarding', 'Medium', 2),
(10, 'Benefits Inquiry', 'Low', 2),
(11, 'Policy Clarification', 'Low', 2),
(12, 'Invoice Processing', 'Medium', 3),
(13, 'Budget Approval', 'High', 3),
(14, 'Expense Reimbursement', 'Medium', 3),
(15, 'Tax Document Request', 'Low', 3),
(16, 'Air Conditioning/HVAC', 'High', 4),
(17, 'Plumbing Leak', 'High', 4),
(18, 'Electrical Issue', 'High', 4),
(19, 'Furniture Repair', 'Low', 4),
(20, 'Office Cleaning', 'Low', 4);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `created_at`) VALUES
(1, 'IT Department', '2026-01-19 07:48:55'),
(2, 'Human Resources', '2026-01-19 07:48:55'),
(3, 'Finance', '2026-01-19 07:48:55'),
(4, 'Admin Department', '2026-01-19 07:48:55');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('Low','Medium','High') DEFAULT 'Low',
  `category` varchar(50) NOT NULL,
  `status` enum('Open','In Progress','On Hold','Resolved','Closed') DEFAULT 'Open',
  `attachment` varchar(255) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `feedback` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `resolution_remarks` text,
  `dept_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `assigned_to`, `subject`, `description`, `priority`, `category`, `status`, `attachment`, `rating`, `feedback`, `created_at`, `resolved_at`, `confirmed_at`, `resolution_remarks`, `dept_id`) VALUES
(1, 9, NULL, 'Email/Account Locked Support Request', 'test Email for Manager', 'High', 'Email/Account Locked', 'Open', NULL, NULL, NULL, '2026-01-19 08:20:26', NULL, NULL, NULL, 1),
(2, 9, 2, 'Printer/Scanner Issue Support Request', 'Test to Notify', 'Medium', 'Printer/Scanner Issue', 'Closed', NULL, 1, 'asd', '2026-01-19 08:24:28', '2026-01-20 03:33:23', '2026-01-20 03:39:27', 'tasd', 1),
(3, 9, 8, 'VPN/Remote Access Support Request', 'Last Test 1/19/2026', 'Medium', 'VPN/Remote Access', 'In Progress', NULL, NULL, NULL, '2026-01-19 08:32:01', NULL, NULL, '', 1),
(4, 9, 8, 'Hardware Malfunction Support Request', 'test Email', 'High', 'Hardware Malfunction', 'Closed', NULL, 3, 'rwsda', '2026-01-19 08:38:46', '2026-01-19 08:50:40', '2026-01-20 03:39:19', 'done', 1),
(5, 8, 8, 'Network/Internet Outage Support Request', 'testttt', 'High', 'Network/Internet Outage', 'Resolved', NULL, NULL, NULL, '2026-01-19 08:42:31', '2026-01-19 08:43:54', NULL, 'test', 1),
(6, 9, 8, 'Software Installation Support Request', 'testd', 'Medium', 'Software Installation', 'Closed', NULL, 3, 'fasdsad', '2026-01-19 08:45:01', '2026-01-19 08:46:19', '2026-01-20 03:38:56', 'done', 1),
(7, 10, NULL, 'Hardware Malfunction Support Request', 'test', 'High', 'Hardware Malfunction', 'Open', NULL, NULL, NULL, '2026-01-20 04:55:53', NULL, NULL, NULL, 1),
(8, 9, NULL, 'Network/Internet Outage Support Request', 'test help', 'High', 'Network/Internet Outage', 'Open', NULL, NULL, NULL, '2026-01-22 00:25:42', NULL, NULL, NULL, 1),
(9, 9, NULL, 'Software Installation Support Request', 'test', 'Medium', 'Software Installation', 'Open', NULL, NULL, NULL, '2026-01-22 00:32:31', NULL, NULL, NULL, 1),
(10, 9, NULL, 'Printer/Scanner Issue Support Request', 'testtt', 'Medium', 'Printer/Scanner Issue', 'Open', NULL, NULL, NULL, '2026-01-22 00:42:29', NULL, NULL, NULL, 1),
(11, 9, NULL, 'Network/Internet Outage Support Request', 'dddd', 'High', 'Network/Internet Outage', 'Open', NULL, NULL, NULL, '2026-01-22 00:47:41', NULL, NULL, NULL, 1),
(12, 9, NULL, 'Hardware Malfunction Support Request', 'ddddd', 'High', 'Hardware Malfunction', 'Open', NULL, NULL, NULL, '2026-01-22 00:51:27', NULL, NULL, NULL, 1),
(13, 9, NULL, 'Network/Internet Outage Support Request', 'ddddd', 'High', 'Network/Internet Outage', 'Open', NULL, NULL, NULL, '2026-01-22 00:52:13', NULL, NULL, NULL, 1),
(14, 9, NULL, 'Network/Internet Outage Support Request', 'test', 'High', 'Network/Internet Outage', 'Open', NULL, NULL, NULL, '2026-01-22 00:52:58', NULL, NULL, NULL, 1),
(15, 9, NULL, 'Network/Internet Outage Support Request', 'ffff', 'High', 'Network/Internet Outage', 'Open', NULL, NULL, NULL, '2026-01-22 00:58:14', NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `ticket_logs`
--

CREATE TABLE `ticket_logs` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` enum('comment','status_change','log','file') DEFAULT 'log',
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ticket_logs`
--

INSERT INTO `ticket_logs` (`id`, `ticket_id`, `user_id`, `action_type`, `message`, `created_at`) VALUES
(1, NULL, 8, '', 'User logged in successfully.', '2026-01-19 08:42:18'),
(2, NULL, 8, '', 'User logged in successfully.', '2026-01-19 08:42:50'),
(3, 5, 8, 'status_change', 'Changed status to In Progress. Remarks: ', '2026-01-19 08:42:56'),
(4, 5, 8, 'status_change', 'Changed status to Resolved. Remarks: test', '2026-01-19 08:43:54'),
(5, NULL, 9, '', 'User logged in successfully.', '2026-01-19 08:44:48'),
(6, NULL, 8, '', 'User logged in successfully.', '2026-01-19 08:45:36'),
(7, 6, 8, 'status_change', 'Changed status to In Progress. Remarks: ', '2026-01-19 08:45:44'),
(8, 6, 8, 'status_change', 'Changed status to Resolved. Remarks: done', '2026-01-19 08:46:19'),
(9, 4, 8, 'status_change', 'Changed status to In Progress. Remarks: ', '2026-01-19 08:50:13'),
(10, 4, 8, 'status_change', 'Changed status to Resolved. Remarks: done', '2026-01-19 08:50:40'),
(11, NULL, 2, '', 'User logged in successfully.', '2026-01-20 03:14:25'),
(12, NULL, 2, '', 'User logged in successfully.', '2026-01-20 03:25:38'),
(13, NULL, 10, '', 'User logged in successfully.', '2026-01-20 03:26:04'),
(14, NULL, 2, '', 'User logged in successfully.', '2026-01-20 03:28:14'),
(15, 2, 2, 'status_change', 'Changed status to In Progress. Remarks: ', '2026-01-20 03:33:12'),
(16, 2, 2, 'status_change', 'Changed status to Resolved. Remarks: tasd', '2026-01-20 03:33:23'),
(17, NULL, 9, '', 'User logged in successfully.', '2026-01-20 03:38:49'),
(18, 6, 9, 'log', 'User confirmed resolution.', '2026-01-20 03:38:56'),
(19, 4, 9, 'log', 'User confirmed resolution.', '2026-01-20 03:39:19'),
(20, 2, 9, 'log', 'User confirmed resolution.', '2026-01-20 03:39:27'),
(21, NULL, 2, '', 'User logged in successfully.', '2026-01-20 03:39:34'),
(22, NULL, 10, '', 'User logged in successfully.', '2026-01-20 04:55:45'),
(23, NULL, 2, '', 'User logged in successfully.', '2026-01-20 04:58:37'),
(24, NULL, 2, '', 'User logged in successfully.', '2026-01-20 05:36:04'),
(25, NULL, 2, '', 'User logged in successfully.', '2026-01-22 00:24:41'),
(26, NULL, 9, '', 'User logged in successfully.', '2026-01-22 00:25:22'),
(27, NULL, 2, '', 'User logged in successfully.', '2026-01-22 00:41:59'),
(28, NULL, 9, '', 'User logged in successfully.', '2026-01-22 00:42:20'),
(29, NULL, 9, '', 'User logged in successfully.', '2026-01-22 00:47:33');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'employee',
  `is_active` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `branch_id` int(11) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `reset_token` varchar(10) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `role`, `is_active`, `created_at`, `branch_id`, `profile_image`, `dept_id`, `reset_token`, `reset_expires`) VALUES
(2, 'Super Admin', 'admin@gmail.com', NULL, '$2y$10$NdWQv7bbI6zZYKRuiaI1y.G/Eyl3cbpZX9quIoAeyo7Xs8Y4cPDli', 'super_admin', 1, '2026-01-13 02:05:21', NULL, 'profile_2_1768797697.png', NULL, NULL, NULL),
(8, 'test user', 'lejapdepro@gmail.com', '09467353789', '$2y$10$2phu3zjXor0MWtZPUgFOgOIBZubrFahe/zldgZZ9aVYq/JzmtsfDS', 'manager', 1, '2026-01-19 08:17:59', 1, NULL, 1, NULL, NULL),
(9, 'Test User2', 'kuruchin06@gmail.com', '094665656', '$2y$10$dDpRGYmezhYxRl/g1exDLORpMnPe5MNYwROI6Dk4qHvOgr1nGopra', 'employee', 1, '2026-01-19 08:19:29', 1, 'profile_9_1769041816.png', NULL, NULL, NULL),
(10, 'test user', 'test@gmail.com', '09467353789', '$2y$10$zG3bZ86TIGNRv1JjWbKszO.oSlzcseiGbYlapaBATTaQ0Th.efTN.', 'employee', 1, '2026-01-20 03:25:19', 1, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branch`
--
ALTER TABLE `branch`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `ticket_logs`
--
ALTER TABLE `ticket_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `dept_id` (`dept_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branch`
--
ALTER TABLE `branch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `ticket_logs`
--
ALTER TABLE `ticket_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ticket_logs`
--
ALTER TABLE `ticket_logs`
  ADD CONSTRAINT `ticket_logs_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
