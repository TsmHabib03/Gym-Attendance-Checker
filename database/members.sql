-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 27, 2026 at 11:00 AM
-- Server version: 8.0.41
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gym_attendance`
--

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int UNSIGNED NOT NULL,
  `member_code` varchar(30) NOT NULL,
  `qr_token` varchar(64) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(160) DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_say') NOT NULL DEFAULT 'prefer_not_say',
  `photo_path` varchar(255) DEFAULT NULL,
  `qr_payload` longtext,
  `membership_end_date` date NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `member_code`, `qr_token`, `full_name`, `email`, `gender`, `photo_path`, `qr_payload`, `membership_end_date`, `created_at`, `updated_at`) VALUES
(1, 'REP-000001', '7a3f24c89e4a6341db1fce863118a00f3f96a7150b542417', 'Juan Dela Cruz', 'juan@example.com', 'male', NULL, '{\"v\": 1, \"type\": \"gym_member\", \"email\": \"juan@example.com\", \"gender\": \"male\", \"qr_token\": \"7a3f24c89e4a6341db1fce863118a00f3f96a7150b542417\", \"full_name\": \"Juan Dela Cruz\", \"photo_path\": null, \"member_code\": \"REP-000001\"}', '2026-04-29', '2026-04-19 13:56:15', '2026-04-27 18:55:19'),
(3, 'REP-000002', 'f15ad3a7df0131d9bdb30aaf8cd9f1f8cb14859cd2e6759b', 'Pedro Reyes', 'pedro@example.com', 'male', NULL, '{\"v\": 1, \"type\": \"gym_member\", \"email\": \"pedro@example.com\", \"gender\": \"male\", \"qr_token\": \"f15ad3a7df0131d9bdb30aaf8cd9f1f8cb14859cd2e6759b\", \"full_name\": \"Pedro Reyes\", \"photo_path\": null, \"member_code\": \"REP-000002\"}', '2026-04-14', '2026-04-19 13:56:15', '2026-04-27 18:55:19'),
(25, 'REP-000003', '705c1ad7b09e7813d94fab6e6cecfff7813a71b7d18184bc', 'Habib Jaudian', 'jaudianhabib879@gmail.com', 'male', '/uploads/member_photos/member_5754c75ff60b4a8d0669.jpg', '{\"v\": 1, \"type\": \"gym_member\", \"email\": \"jaudianhabib879@gmail.com\", \"gender\": \"male\", \"qr_token\": \"705c1ad7b09e7813d94fab6e6cecfff7813a71b7d18184bc\", \"full_name\": \"Habib Jaudian\", \"photo_path\": \"/uploads/member_photos/member_5754c75ff60b4a8d0669.jpg\", \"member_code\": \"REP-000003\", \"generated_at\": \"2026-04-26 19:50:17\", \"membership_end_date\": \"2026-04-29\"}', '2026-04-29', '2026-04-26 19:50:17', '2026-04-27 18:55:19'),
(26, 'REP-000004', '22d85e5f6e8e519634c804bd1daa04120a1c8ac173265b91', 'Rep Core', NULL, 'male', '/uploads/member_photos/member_0739a3ccb236eb9508d0.png', '{\"v\": 1, \"type\": \"gym_member\", \"email\": null, \"gender\": \"male\", \"qr_token\": \"22d85e5f6e8e519634c804bd1daa04120a1c8ac173265b91\", \"full_name\": \"Rep Core\", \"photo_path\": \"/uploads/member_photos/member_0739a3ccb236eb9508d0.png\", \"member_code\": \"REP-000004\", \"generated_at\": \"2026-04-26 20:03:11\", \"membership_end_date\": \"2026-05-26\"}', '2026-05-26', '2026-04-26 20:03:11', '2026-04-27 18:55:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `member_code` (`member_code`),
  ADD UNIQUE KEY `qr_token` (`qr_token`),
  ADD KEY `idx_membership_end_date` (`membership_end_date`),
  ADD KEY `idx_full_name` (`full_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
