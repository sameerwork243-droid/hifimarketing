-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: shareddb-m.hosting.stackcp.net
-- Generation Time: Jul 21, 2026 at 09:32 AM
-- Server version: 10.11.18-MariaDB-log
-- PHP Version: 8.3.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Hifiwebsite-313031aed2`
--
CREATE DATABASE IF NOT EXISTS `Hifiwebsite-313031aed2` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `Hifiwebsite-313031aed2`;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `addons`
--

CREATE TABLE `addons` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'In Progress',
  `progress` int(11) DEFAULT 0,
  `metrics` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `addons`
--

INSERT INTO `addons` (`id`, `client_id`, `name`, `type`, `price`, `status`, `progress`, `metrics`, `created_at`) VALUES
(2, 1, 'Elite Video Production (3 4K reels)', 'Video Production', 30000.00, 'In Progress', 100, '3 / 3 videos delivered', '2026-07-02 01:31:45'),
(3, 1, 'Ad Account Expansion Setup', 'Ads Setup', 12000.00, 'Approved & Scheduled', 0, 'Awaiting launch call', '2026-07-02 01:31:45'),
(4, 5, 'Branding Booster (10 custom posts)', NULL, 0.00, 'Pending', 0, 'Requested', '2026-07-07 13:53:16'),
(5, 5, 'Branding Booster (10 custom posts)', NULL, 0.00, 'Pending', 0, 'Requested', '2026-07-07 14:02:05'),
(6, 5, 'Branding Booster (10 custom posts)', NULL, 0.00, 'Pending', 0, 'Requested', '2026-07-09 13:26:46');

-- --------------------------------------------------------

--
-- Table structure for table `ad_campaigns`
--

CREATE TABLE `ad_campaigns` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `campaign_name` varchar(255) NOT NULL,
  `platform` varchar(50) DEFAULT 'Meta',
  `status` varchar(50) DEFAULT 'Active',
  `budget` decimal(10,2) DEFAULT 0.00,
  `spent` decimal(10,2) DEFAULT 0.00,
  `impressions` int(11) DEFAULT 0,
  `reach` int(11) DEFAULT 0,
  `engagement` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `cpc` decimal(10,2) DEFAULT 0.00,
  `cpm` decimal(10,2) DEFAULT 0.00,
  `cpa` decimal(10,2) DEFAULT 0.00,
  `ctr` decimal(5,2) DEFAULT 0.00,
  `roi` decimal(5,2) DEFAULT 0.00,
  `conversions` int(11) DEFAULT 0,
  `leads` int(11) DEFAULT 0,
  `sales` int(11) DEFAULT 0,
  `revenue` decimal(10,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `target_audience` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ad_campaigns`
--

INSERT INTO `ad_campaigns` (`id`, `client_id`, `campaign_name`, `platform`, `status`, `budget`, `spent`, `impressions`, `reach`, `engagement`, `clicks`, `cpc`, `cpm`, `cpa`, `ctr`, `roi`, `conversions`, `leads`, `sales`, `revenue`, `start_date`, `end_date`, `target_audience`, `notes`, `updated_at`, `created_at`) VALUES
(1, 2, 'Ghaza Compaign', 'Facebook Ads', 'Draft', 23.00, 0.00, 0, 0, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0.00, '0000-00-00', '0000-00-00', '18-35, Pakistan', '', '2026-07-08 19:17:57', '2026-07-08 19:17:57'),
(2, 10, 'Auto MOT Campaign', 'Meta Ads', 'Active', 2000.00, 0.00, 0, 0, 0, 0, 1200.00, 1300.00, 12.00, 0.00, 0.00, 0, 0, 0, 0.00, '2026-09-07', '2026-12-07', '18-35', '', '2026-07-09 09:50:33', '2026-07-09 09:50:33'),
(4, 3, 'Summer Sales', 'Meta Ads', 'Draft', 189101.00, 90000.00, 18000, 0, 0, 1191101, 188110.00, 177110.00, 167718.00, 999.99, 999.99, 190101, 0, 0, 0.00, '0000-00-00', '0000-00-00', '', '', '2026-07-14 17:03:03', '2026-07-14 17:01:29');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `resume` varchar(255) NOT NULL,
  `status` enum('pending','reviewed','shortlisted','rejected') DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `job_id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `resume`, `status`, `applied_at`) VALUES
(7, 2, 3, 'Faizan', 'hg', 'gunb07912@gmail.com', '+92hg', 'gh', 'uploads/resumes/1782496319_6a3ebc3f3b306.pdf', 'pending', '2026-06-26 17:51:59'),
(8, 14, 5, 'Jaweria', 'Boss', 'jaweria@gmail.com', '+92312343483', 'sf 234', 'uploads/resumes/1782538223_6a3f5fef327e8.pdf', 'pending', '2026-06-27 05:30:23'),
(9, 2, 5, 'Jaweria', 'Boss', 'jaweria@gmail.com', '+92312343483', 'sf 234', 'uploads/resumes/1782542307_6a3f6fe3f07f3.pdf', 'pending', '2026-06-27 06:38:27'),
(10, 10, 5, 'Jaweria', 'Faizan', 'jaweria@gmail.com', '+92312343483', 'sf 234', 'uploads/resumes/1782654647_6a4126b74b537.docx', 'pending', '2026-06-28 13:50:47'),
(11, 9, 5, 'Faizan', 'Boss', 'mehmoodulfat184@gmail.com', '+92312343483', 'chakri road rwp', 'uploads/resumes/1782656645_6a412e85bdd41.docx', 'pending', '2026-06-28 14:24:05'),
(12, 8, 5, 'Faizan', 'Expert', 'mehmoodulfat184@gmail.com', '+92312343483', 'chakri road rwp', 'uploads/resumes/1782659288_6a4138d8cd33f.docx', 'reviewed', '2026-06-28 15:08:08'),
(13, 4, 5, 'Hasnain', 'Sajid', 'gunb07912@gmail.com', '+9203115227363', 'chakri road rwp', 'uploads/resumes/1782714851_6a4211e3c0239.pdf', 'reviewed', '2026-06-29 06:34:11'),
(14, 14, 45, 'hifimarketing.co@gmailcom', 'Boss', 'hifimarketing.co@gmail.com', '+92312343483', 'sf 234', 'uploads/resumes/1783264136_6a4a73886df83.pdf', 'pending', '2026-07-05 15:08:56'),
(15, 4, 45, 'Asad', 'Zaman', 'asadbzaman@gmail.com', '+92+92 324 9837880', 'chakri road rwp', 'uploads/resumes/1783264476_6a4a74dc41ebf.pdf', 'pending', '2026-07-05 15:14:36'),
(16, 16, 64, 'Umair', 'Qayyum', 'kitsoldier55@gmail.com', '+923145302737', 'Rawalpindi', 'uploads/resumes/1784254951_6a5991e73fed5.pdf', 'pending', '2026-07-17 02:22:31'),
(17, 15, 70, 'Laiba', 'Hussain', 'laibahussain5567@gmail.com', '+923355604429', 'Rawalpindi', 'uploads/resumes/1784282262_6a59fc9700594.pdf', 'pending', '2026-07-17 09:57:43'),
(18, 16, 66, 'Faizan', 'Boss', 'gunb07912@gmail.com', '+92312343483', 'sf 234', 'uploads/resumes/1784358097_6a5b24d1eba8e.pdf', 'pending', '2026-07-18 07:01:37'),
(19, 16, 27, 'Khushhal Flour Mill', 'jkn', 'millkhushhalflour@gmail.com', '+920998099090', 'io', 'uploads/resumes/1784360170_6a5b2cea43150.pdf', 'pending', '2026-07-18 07:36:10');

-- --------------------------------------------------------

--
-- Table structure for table `bookmarks`
--

CREATE TABLE `bookmarks` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sender_type` enum('admin','client') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message_type` enum('text','file','voice') DEFAULT 'text',
  `message` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_for` varchar(255) DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `ticket_id`, `sender_type`, `sender_id`, `message_type`, `message`, `file_path`, `file_name`, `file_size`, `mime_type`, `is_deleted`, `deleted_for`, `deleted_by`, `deleted_at`, `created_at`) VALUES
(1, 11, 'client', 3, 'text', 'Here is the chat system', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-16 11:01:11'),
(2, 11, 'admin', 59, 'text', 'hi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-16 11:01:34'),
(3, 11, 'client', 3, 'text', 'how are you?', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-16 11:01:55'),
(4, 11, 'admin', 59, 'text', 'hi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-16 11:02:28'),
(12, 11, 'admin', 59, 'file', 'File uploaded: 9b1d47e0-b2e8-48e2-8315-1f17b0bb5c95.jpeg', 'uploads/chat_files/1784201704_9b1d47e0-b2e8-48e2-8315-1f17b0bb5c95.jpeg', '1784201704_9b1d47e0-b2e8-48e2-8315-1f17b0bb5c95.jpeg', 210082, 'image/jpeg', 0, NULL, NULL, NULL, '2026-07-16 12:35:04'),
(13, 11, 'client', 3, 'file', 'File uploaded: _Enjoy a clean welco.mp3', 'uploads/chat_files/1784201723__Enjoyacleanwelco.mp3', '1784201723__Enjoyacleanwelco.mp3', 228717, 'audio/mpeg', 0, NULL, NULL, NULL, '2026-07-16 12:35:23'),
(14, 13, 'client', 3, 'text', 'Instagram business page for my business', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-16 12:49:27'),
(20, 7, 'client', 5, 'text', 'hi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-16 18:06:59'),
(22, 13, 'admin', 59, 'voice', 'This message was deleted', 'admin-portal/voice_messages/1784312695_voice_voice_message.webm', '1784312695_voice_voice_message.webm', 42383, 'audio/webm', 1, NULL, 59, '2026-07-17 19:25:06', '2026-07-17 19:24:55'),
(24, 13, 'client', 3, 'text', 'hi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-17 19:34:13'),
(25, 13, 'admin', 59, 'text', 'yes', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-17 19:38:25'),
(26, 13, 'admin', 59, 'text', 'whats wrong with you', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-17 19:38:32'),
(27, 13, 'admin', 59, 'text', '?', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-17 19:38:54'),
(28, 13, 'admin', 59, 'voice', 'Voice message', 'admin-portal/voice_messages/1784313563_voice_voice_message.webm', '1784313563_voice_voice_message.webm', 64779, 'audio/webm', 0, NULL, NULL, NULL, '2026-07-17 19:39:23'),
(29, 13, 'client', 3, 'voice', 'Voice message', 'admin-portal/voice_messages/1784313601_voice_voice_message.webm', '1784313601_voice_voice_message.webm', 32707, 'audio/webm', 0, NULL, NULL, NULL, '2026-07-17 19:40:01'),
(30, 13, 'client', 3, 'text', 'kindly behave your language', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-17 19:40:14'),
(31, 13, 'client', 3, 'text', 'hi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-18 04:10:16'),
(32, 13, 'client', 3, 'text', 'hi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-18 04:10:18'),
(33, 13, 'client', 3, 'text', 'hi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-18 04:10:29'),
(34, 13, 'client', 3, 'text', 'hi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-18 04:14:12'),
(35, 13, 'client', 3, 'text', 'hi', NULL, NULL, NULL, NULL, 0, '59,,', 59, '2026-07-18 07:12:13', '2026-07-18 04:14:13'),
(36, 13, 'client', 3, 'text', 'hi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-18 04:14:16'),
(37, 13, 'admin', 59, 'text', 'kia hai bar bar message q kr rhy ho', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-18 04:15:22'),
(38, 13, 'client', 3, 'text', 'sir ap gussa q ho rhy ho? mein to Demo client hu na', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-07-18 04:16:55'),
(39, 13, 'client', 3, 'text', 'This message was deleted', NULL, NULL, NULL, NULL, 1, NULL, 3, '2026-07-19 16:26:17', '2026-07-18 04:24:17'),
(40, 13, 'client', 3, 'text', 'kia', NULL, NULL, NULL, NULL, 0, '59,,', 59, '2026-07-18 06:36:04', '2026-07-18 04:24:21'),
(41, 13, 'client', 3, 'text', 'This message was deleted', NULL, NULL, NULL, NULL, 1, NULL, 3, '2026-07-19 16:26:22', '2026-07-18 04:24:34'),
(42, 13, 'client', 3, 'text', 'This message was deleted', NULL, NULL, NULL, NULL, 1, NULL, 3, '2026-07-18 04:24:55', '2026-07-18 04:24:37'),
(43, 13, 'client', 3, 'text', '😎', NULL, NULL, NULL, NULL, 0, '3,,', 3, '2026-07-18 04:24:49', '2026-07-18 04:24:43'),
(44, 11, 'client', 3, 'voice', 'Voice message', 'admin-portal/voice_messages/1784534087_voice_voice_message.webm', '1784534087_voice_voice_message.webm', 146155, 'audio/webm', 0, NULL, NULL, NULL, '2026-07-20 08:54:47');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `client_code` varchar(20) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `active_package_id` int(11) DEFAULT 1,
  `posts_completed` int(11) DEFAULT 0,
  `stories_completed` int(11) DEFAULT 0,
  `reels_completed` int(11) DEFAULT 0,
  `followers_gained` int(11) DEFAULT 0,
  `total_likes` int(11) DEFAULT 0,
  `brand_mentions` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `fb_ads_completed` int(11) DEFAULT 0,
  `ig_ads_completed` int(11) DEFAULT 0,
  `google_ads_completed` int(11) DEFAULT 0,
  `youtube_ads_completed` int(11) DEFAULT 0,
  `tiktok_ads_completed` int(11) DEFAULT 0,
  `linkedin_ads_completed` int(11) DEFAULT 0,
  `pinterest_ads_completed` int(11) DEFAULT 0,
  `ads_completed` int(11) DEFAULT 0,
  `ads_platforms` text DEFAULT NULL,
  `posts_platforms` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `client_code`, `user_id`, `name`, `company`, `phone`, `address`, `active_package_id`, `posts_completed`, `stories_completed`, `reels_completed`, `followers_gained`, `total_likes`, `brand_mentions`, `created_at`, `fb_ads_completed`, `ig_ads_completed`, `google_ads_completed`, `youtube_ads_completed`, `tiktok_ads_completed`, `linkedin_ads_completed`, `pinterest_ads_completed`, `ads_completed`, `ads_platforms`, `posts_platforms`) VALUES
(1, 'CLI-64841', 8, 'Client Owner', 'SMMA Agency', NULL, NULL, 2, 0, 0, 5, 1420, 8740, 112, '2026-07-02 01:31:45', 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL),
(2, '#001', 27, 'millkhushhalflour@gmail.com', NULL, NULL, NULL, 20, 6, 0, 1, 0, 0, 0, '2026-07-04 23:00:00', 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL),
(3, 'CLI-96437', 25, 'client@hifi.com', NULL, NULL, NULL, 23, 14, 10, 4, 8126, 45392, 0, '2026-07-05 15:30:33', 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL),
(4, 'CLI-07612', 46, 'Project Manager', NULL, NULL, NULL, 1, 0, 0, 0, 0, 0, 0, '2026-07-07 10:09:58', 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL),
(5, '#002', 50, 'buildersexpert', NULL, NULL, NULL, 21, 0, 0, 7, 0, 0, 0, '2026-07-06 23:00:00', 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL),
(6, '#003', 51, 'The Billionaire Affair', NULL, NULL, NULL, 23, 11, 20, 5, 0, 0, 0, '2026-07-07 23:00:00', 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL),
(7, '#004', 53, 'Skyway Fire Safety', NULL, NULL, NULL, 1, 0, 0, 0, 0, 0, 0, '2026-07-09 08:18:54', 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL),
(8, NULL, 55, 'Skyway Fire Safety', NULL, NULL, NULL, 24, 9, 9, 0, 0, 0, 0, '2026-07-09 08:22:33', 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL),
(9, '#005', 56, 'HWF', NULL, NULL, NULL, 1, 0, 0, 0, 0, 0, 0, '2026-07-09 08:26:18', 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL),
(10, '#006', 57, 'Saks Auto World', NULL, NULL, NULL, 1, 0, 0, 0, 0, 0, 0, '2026-07-09 08:28:04', 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL),
(11, '#007', 58, 'ERP BizTrack', NULL, NULL, NULL, 1, 0, 0, 0, 0, 0, 0, '2026-07-09 11:47:31', 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL),
(12, NULL, 59, 'Super Admin', NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, '2026-07-14 09:12:03', 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `client_packages`
--

CREATE TABLE `client_packages` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `assigned_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `client_packages`
--

INSERT INTO `client_packages` (`id`, `client_id`, `package_id`, `assigned_at`) VALUES
(17, 2, 20, '2026-07-07 10:32:44'),
(22, 6, 23, '2026-07-08 13:39:03'),
(23, 7, 1, '2026-07-09 08:18:54'),
(24, 9, 1, '2026-07-09 08:26:18'),
(25, 10, 1, '2026-07-09 08:28:04'),
(26, 11, 1, '2026-07-09 11:47:31'),
(27, 8, 24, '2026-07-09 12:30:36'),
(28, 3, 23, '2026-07-14 15:54:27'),
(29, 5, 21, '2026-07-15 09:36:02');

-- --------------------------------------------------------

--
-- Table structure for table `client_plan_assignments`
--

CREATE TABLE `client_plan_assignments` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_progress_history`
--

CREATE TABLE `client_progress_history` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `posts_completed` int(11) DEFAULT 0,
  `stories_completed` int(11) DEFAULT 0,
  `reels_completed` int(11) DEFAULT 0,
  `ads_completed` int(11) DEFAULT 0,
  `total_likes` int(11) DEFAULT 0,
  `followers_gained` int(11) DEFAULT 0,
  `snapshot_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_tasks`
--

CREATE TABLE `custom_tasks` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'Awaiting Quote',
  `progress` int(11) DEFAULT 0,
  `assigned_to` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deliverables`
--

CREATE TABLE `deliverables` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'To Do',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `deliverables`
--

INSERT INTO `deliverables` (`id`, `client_id`, `name`, `type`, `description`, `assigned_to`, `status`, `due_date`, `created_at`) VALUES
(13, 10, 'Delivery of BizTrack MOT Mobile Application', 'Development', 'Successfully developed and delivered the BizTrack MOT Mobile Application, designed to streamline MOT inspection and management processes. The application enables users to efficiently manage MOT-related tasks through a user-friendly mobile interface, offering features such as vehicle records management, inspection tracking, appointment handling, real-time status updates, and secure data synchronization. The project was completed with a focus on performance, usability, and reliability, ensuring a smooth experience for both businesses and end users.', 'Abdul Rehman', 'To Do', '2026-07-09', '2026-07-09 11:15:18'),
(14, 9, 'Quotation for 50 to 100 TrustPilot Reviews', 'Elegant Design', 'Quotation for 50 to 100 reviews', 'Shagufta Munir', 'To Do', '2026-07-10', '2026-07-09 11:24:09'),
(15, 9, 'Removal of Stripe from HWF Website', 'Elegant Design', '', 'Dev- Ahmed', 'To Do', '2026-07-10', '2026-07-09 11:28:25'),
(16, 9, 'Removal of Religious Content and Mosque Campaign from Donation Form(HWF Website)', 'Elegant Design', '', 'Dev- Ahmed', 'To Do', '2026-07-10', '2026-07-09 11:30:40'),
(17, 9, 'Campaign for Water Well in Sindh (2x Wells)', 'Setup', '', 'Qaiser Swadi', 'To Do', '2026-07-10', '2026-07-09 11:38:49'),
(18, 9, 'Converted Muslim Influencer Onboarding Campaign at Tiktok', 'Elegant Design', '', 'Hussnain', 'To Do', '2026-07-10', '2026-07-09 11:40:37'),
(19, 8, 'Domain and Hosting Server Renewel', 'Elegant Design', '', 'Dev- Team', 'To Do', '2026-07-10', '2026-07-09 12:17:49'),
(20, 11, 'Paypal Integration at landing page', 'Elegant Design', '', 'Abdul Rehman', 'To Do', '2026-07-10', '2026-07-09 12:22:01'),
(21, 11, 'Free Trial Access at BizTrack', 'Elegant Design', '', 'Abdul Rehman', 'To Do', '2026-07-10', '2026-07-09 12:22:47'),
(22, 3, 'Website Development for ZDJAR', 'Development', 'Demo', 'Susan Marry', 'To Do', '2026-07-23', '2026-07-09 15:20:49'),
(23, 3, '4x Media Campaigns', 'Engagement', 'Demo', 'Marrie', 'To Do', '2026-07-22', '2026-07-09 15:22:08'),
(24, 3, 'Software Development for ZDJAR', 'Development', 'Demo', 'Henry', 'To Do', '2026-07-28', '2026-07-09 15:22:56');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uploaded_by` varchar(50) DEFAULT 'PM',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `education`
--

CREATE TABLE `education` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `institution` varchar(255) DEFAULT NULL,
  `year` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `status` enum('sent','failed','pending') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`id`, `to_email`, `subject`, `type`, `status`, `error_message`, `created_at`) VALUES
(1, 'gunb07912@gmail.com', 'User Confirmation: Trainee Functional Consultant', 'user_confirmation', 'sent', NULL, '2026-06-29 07:34:13'),
(2, 'expertfaizan932@gmail.com', 'Admin Notification: Trainee Functional Consultant', 'admin_notification', 'sent', NULL, '2026-06-29 07:34:13');

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `max_attempts` int(11) DEFAULT 3,
  `created_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `experience`
--

CREATE TABLE `experience` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `year` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `issue_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Due','Paid','Partially Paid') DEFAULT 'Due',
  `lps` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `paid_date` datetime DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `stripe_payment_status` varchar(50) DEFAULT NULL,
  `stripe_session_id` varchar(255) DEFAULT NULL,
  `stripe_payment_intent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `client_id`, `invoice_number`, `amount`, `issue_date`, `due_date`, `status`, `lps`, `paid_amount`, `paid_date`, `attachment`, `note`, `created_at`, `updated_at`, `stripe_payment_status`, `stripe_session_id`, `stripe_payment_intent`) VALUES
(1, 1, 'INV-TEST-001', 25000.00, '2026-07-10', '2026-07-29', 'Due', 0.03, 0.00, NULL, NULL, 'Test', '2026-07-10 09:10:15', '2026-07-10 09:10:15', NULL, NULL, NULL),
(3, 2, 'INV-2026-001', 25000.00, '0000-00-00', '2026-07-24', 'Paid', 10000.00, 25000.00, '2026-07-14 13:28:48', '1783772810_1783772495_BizTrack___Project_Management_System.pdf', 'i am a invoice', '2026-07-10 14:15:04', '2026-07-14 13:28:48', NULL, NULL, NULL),
(4, 3, 'INV-2026-001', 3422.00, NULL, '2026-07-18', 'Due', 0.01, 3422.00, '2026-07-14 13:17:42', '1784019376_1783772810_1783772495_BizTrack___Project_Management_System.pdf', 'sdcx', '2026-07-11 09:49:12', '2026-07-16 12:57:03', NULL, NULL, NULL),
(5, 2, 'INV-20919', 10000.00, '0000-00-00', '2026-07-15', 'Due', 0.00, 0.00, NULL, '1784031736_Invoice_23929_protected.pdf', '', '2026-07-14 13:21:59', '2026-07-14 13:22:16', NULL, NULL, NULL),
(6, 8, 'INV-2026-001', 11000.00, '0000-00-00', '2026-07-17', 'Due', 0.00, 0.00, NULL, NULL, 'Domain and Hosting Server Renewal Yearly', '2026-07-16 13:11:17', '2026-07-16 13:11:17', NULL, NULL, NULL),
(7, 8, 'INV-2026-002', 12000.00, '0000-00-00', '2026-07-07', 'Due', 0.00, 0.00, NULL, NULL, 'Monthly Retainer 5th July till 5th August', '2026-07-16 13:22:05', '2026-07-16 13:22:05', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `type` enum('Full-time','Part-time','Contract','Internship') DEFAULT 'Full-time',
  `workplace` enum('On-site','Remote','Hybrid') DEFAULT 'On-site',
  `posted_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` text DEFAULT NULL,
  `responsibilities` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `title`, `department`, `location`, `type`, `workplace`, `posted_date`, `description`, `responsibilities`, `requirements`, `is_active`) VALUES
(2, 'Senior Python Developer', 'Cluster Head', 'Lahore, Punjab, Pakistan', 'Full-time', 'On-site', '2026-06-25 14:09:07', 'Experienced Python developer needed for enterprise projects.', NULL, NULL, 1),
(3, 'Senior Graphic Designer', 'Global Marketing and Business Development', 'Lahore, Punjab, Pakistan', 'Full-time', 'On-site', '2026-06-25 14:09:07', 'Creative graphic designer with 5+ years experience.', NULL, NULL, 1),
(4, 'Trainee Functional Consultant', 'Cluster Head', 'Lahore, Punjab, Pakistan', 'Full-time', 'On-site', '2026-06-25 14:09:07', 'Fresh graduates with strong analytical skills.', NULL, NULL, 1),
(5, 'Software Engineer - Python', 'Development', 'Lahore, Punjab, Pakistan', 'Full-time', 'On-site', '2026-06-25 14:09:07', 'Python developer with Django experience.', NULL, NULL, 1),
(6, 'NLP Engineer - Arabic Language Focus', 'Human Assets', 'Riyadh, Saudi Arabia', 'Full-time', 'On-site', '2026-06-25 14:09:07', 'NLP specialist with Arabic language expertise.', NULL, NULL, 1),
(7, 'Senior Data Scientist', 'Data Science', 'Riyadh, Saudi Arabia', 'Full-time', 'On-site', '2026-06-25 14:09:07', 'Senior Data Scientist with 5+ years experience.', NULL, NULL, 1),
(8, 'Software Engineer - MERN', 'Cluster Head', 'Lahore, Punjab, Pakistan', 'Full-time', 'On-site', '2026-06-25 14:09:07', 'MERN stack developer for web applications.', NULL, NULL, 1),
(9, 'Software Engineer - Ruby on Rails', 'Development', 'Lahore, Punjab, Pakistan', 'Full-time', 'On-site', '2026-06-25 14:09:07', 'Ruby on Rails developer with 3+ years experience.', NULL, NULL, 1),
(10, 'Associate Software Engineer - ROR', 'Cluster Head', 'Lahore, Punjab, Pakistan', 'Full-time', 'On-site', '2026-06-25 14:09:07', 'Entry level Ruby on Rails developer.', NULL, NULL, 1),
(14, 'Paid Ads Campaign Expert (Meta, Google and TikTok)', 'Digital Marketing', 'Rawalpindi, Pakistan', 'Full-time', 'On-site', '2026-06-26 17:52:17', 'Hifi Marketing and Technologies is looking for a data-driven and results-oriented Paid Ads Campaign Expert to join our growing team in Rawalpindi. In this role, you will be responsible for planning, executing, and optimizing high-performing paid media campaigns across Meta, Google, TikTok, and Snapchat.\r\n\r\nThe ideal candidate possesses a deep understanding of media buying, audience targeting, A/B testing, and conversion rate optimization (CRO). You will manage performance marketing budgets to maximize ROI/ROAS, generate high-quality leads, and scale our digital footprint. If you live and breathe analytics, performance metrics, and creative ad strategies, we want you on board!', 'Plan, set up, and launch paid ad campaigns across Meta (Facebook &amp;amp; Instagram), Google, TikTok, and Snapchat.\r\n\r\nConduct continuous A/B testing of ad creatives, copy, bidding strategies, and target audiences to maximize ROAS.\r\n\r\nPerform thorough audience research and build highly targeted custom and lookalike audiences.\r\n\r\nTrack, analyze, and report on campaign performance metrics (KPIs like CPC, CPA, CTR, and ROI) daily.\r\n\r\nWork closely with the creative and content teams to brainstorm and develop high-converting ad copy and visual assets.\r\n\r\nManage ad budgets effectively, scaling winning campaigns while cutting underperforming ones.\r\n\r\nStay up to date with the latest trends, algorithm changes, and best practices across all major paid advertising platforms.', '* *Experience:* Proven experience managing successful paid ad campaigns on Meta, Google, TikTok, and Snapchat (agency experience is a big plus).\r\n* *Technical Skills:* Strong proficiency with Meta Ads Manager, Google Ads (Search, Performance Max, Display), TikTok Ads Manager, and Snapchat Ads Manager.\r\n* *Analytical Mindset:* Expert at interpreting data dashboards, setting up tracking pixels, and translating numbers into actionable marketing strategies.\r\n* *Communication:* Excellent written and verbal communication skills for crafting compelling ad copies and communicating insights.\r\n* *Education:* Bachelor\'s/Master’s degree in Marketing, Media Sciences, Business, or a related field is preferred (relevant certifications from Google/Meta are highly valued).', 1),
(15, 'Graphic Designer Intern', 'Creative Designing', 'Rawalpindi', 'Full-time', 'Remote', '2026-07-17 02:05:32', 'We are looking for a creative, detail-oriented, and enthusiastic Graphic Designer Intern to join our growing team. If you have a passion for visual storytelling, a sharp eye for aesthetics, and want to build a stellar portfolio with real-world projects, this is the perfect opportunity for you! As a remote intern, you will collaborate closely with our team to create engaging visual content while refining your design skills under professional mentorship.📋 Role Overview Attribute Details Position Graphic Designer Intern Location100% Remote (Work from Home)Duration3 Months Timings10:00 AM to 5:30 PM Stipend 10,000PKR per month', 'During this 3-month internship, you will:\r\n\r\nCreate Visual Content: Design eye-catching social media posts, stories, banners, and digital marketing materials.\r\n\r\nSupport Branding: Assist in maintaining and executing brand guidelines across various digital platforms.\r\n\r\nCollaborate: Work closely with the marketing and content teams to brainstorm and bring creative concepts to life.\r\n\r\nEdit &amp; Refine: Perform basic photo editing, resizing, and retouching as needed.\r\n\r\nIncorporate Feedback: Adapt and iterate on designs based on constructive feedback from senior team members.', 'Software Skills: Proficiency in Adobe Photoshop and Adobe Illustrator (knowledge of Canva, Figma, or Premiere Pro is a plus!).\r\n\r\nPortfolio: A basic portfolio or sample drive demonstrating your design style and creativity.\r\n\r\nAttention to Detail: A strong sense of layout, color theory, typography, and visual balance.\r\n\r\nTech Readiness: Access to a reliable laptop/computer and a stable high-speed internet connection.\r\n\r\nCommunication: Good communication skills and the ability to meet deadlines in a remote work environment.', 1),
(16, 'Video Editing Expert', 'Editing', 'Rawalpindi', 'Full-time', 'On-site', '2026-07-17 02:09:16', 'We are seeking a highly creative, skilled, and detail-oriented Professional Video Editor to join our creative team on-site. If you have a passion for visual storytelling, a deep understanding of pacing and rhythm, and the ability to turn raw footage into high-converting, engaging cinematic content, we want you!\r\n\r\nIn this role, you will work closely with our production and marketing teams to produce high-quality videos for social media, advertising campaigns, and brand initiatives.\r\n\r\nAttribute	Details\r\nPosition	Professional Video Editor (Full-Time)\r\nLocation	In-House (On-site)\r\nTimings	10:00 AM to 6:00 PM\r\nCompensation	Competitive Salary (Based on Experience) + Performance Bonuses\r\nBenefits	Paid Leaves &amp;amp; Punctuality Allowance\r\n\r\n💎 Perks &amp;amp; Benefits\r\nFinancial Growth: A highly competitive salary package supplemented by Performance Bonuses to reward your hard work and creativity.\r\n\r\nTime Off: Generous allocation of Paid Leaves to ensure a healthy work-life balance.\r\n\r\nReliability Reward: A dedicated Punctuality Allowance for team members who consistently respect timing and scheduling.\r\n\r\nState-of-the-Art Gear: Access to a high-end, in-house editing setup designed to streamline your workflow.', 'Assemble &amp;amp; Edit: Seamlessly edit raw footage into polished, high-quality videos (commercials, social media content, promos, and corporate videos).\r\n\r\nAudio &amp;amp; Sound Design: Enhance videos with sound design, sound effects, voiceovers, and appropriate background music.\r\n\r\nColor Grading &amp;amp; Effects: Apply professional color correction, grading, transitions, and basic motion graphics/VFX to elevate production value.\r\n\r\nStorytelling: Structure narrative flow and pacing to ensure the video aligns with the brand’s voice and campaign objectives.\r\n\r\nCollaboration &amp;amp; Delivery: Work closely with directors, content creators, and marketing teams to meet deadlines and export videos in various formats optimized for different platforms.', 'Proven Experience: 2+ years of professional video editing experience with a strong, diverse portfolio/showreel.\r\n\r\nSoftware Mastery: Expert-level skills in industry-standard software (e.g., Adobe Premiere Pro, After Effects, or DaVinci Resolve).\r\n\r\nTechnical Knowledge: Deep understanding of video formats, codecs, aspect ratios, and export settings for different digital platforms.\r\n\r\nCreativity &amp;amp; Speed: Ability to work efficiently under tight deadlines without compromising on visual quality.\r\n\r\nPunctuality &amp;amp; Discipline: High professionalism, respect for studio timings, and strong organizational skills.', 1);

-- --------------------------------------------------------

--
-- Table structure for table `ledger`
--

CREATE TABLE `ledger` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ledger`
--

INSERT INTO `ledger` (`id`, `client_id`, `type`, `amount`, `balance`, `description`, `created_at`) VALUES
(1, 1, 'Invoice Settled (INV-2026-006)', 55000.00, 110000.00, NULL, '2026-06-29 06:07:05'),
(2, 1, 'Invoice Settled (INV-2026-005)', 55000.00, 55000.00, NULL, '2026-06-29 06:07:05'),
(3, 1, 'Starting Retainer Deposit', 45000.00, 0.00, NULL, '2026-06-29 06:07:05');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `name`, `email`, `subject`, `message`, `status`, `created_at`) VALUES
(1, NULL, 'Faizan', 'gunb07912@gmail.com', 'SALLARY', 'PLEASE INCREASE MY SALLARY TO MINIMUM 30K', 'read', '2026-06-26 00:08:17'),
(2, NULL, 'Jaweria', 'jaweria@gmail.com', 'SALLARY', 'dsf', 'unread', '2026-06-26 00:40:40'),
(4, NULL, 'Faizan', 'gunb07912@gmail.com', 'SALLARY', 'iiiiiiiiiiii', 'unread', '2026-06-26 17:52:40');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'PKR',
  `billing_type` varchar(50) DEFAULT 'Per Month',
  `posts_limit` int(11) DEFAULT 20,
  `stories_limit` int(11) DEFAULT 25,
  `reels_limit` int(11) DEFAULT 7,
  `ads_limit` int(11) DEFAULT 0,
  `features` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `content_calendar` tinyint(1) DEFAULT 1,
  `hashtag_research` tinyint(1) DEFAULT 1,
  `daily_engagement` tinyint(1) DEFAULT 1,
  `graphic_designs` tinyint(1) DEFAULT 1,
  `monthly_report` tinyint(1) DEFAULT 1,
  `youtube_seo` tinyint(1) DEFAULT 0,
  `fb_ig_ads` tinyint(1) DEFAULT 0,
  `google_ads` tinyint(1) DEFAULT 0,
  `website_store` tinyint(1) DEFAULT 0,
  `pinterest_management` tinyint(1) DEFAULT 0,
  `ugc_blogs` tinyint(1) DEFAULT 0,
  `profile_creation` tinyint(1) DEFAULT 0,
  `fb_ads_limit` int(11) DEFAULT 0,
  `ig_ads_limit` int(11) DEFAULT 0,
  `google_ads_limit` int(11) DEFAULT 0,
  `youtube_ads_limit` int(11) DEFAULT 0,
  `tiktok_ads_limit` int(11) DEFAULT 0,
  `linkedin_ads_limit` int(11) DEFAULT 0,
  `pinterest_ads_limit` int(11) DEFAULT 0,
  `ads_platforms` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `name`, `description`, `price`, `currency`, `billing_type`, `posts_limit`, `stories_limit`, `reels_limit`, `ads_limit`, `features`, `status`, `created_at`, `content_calendar`, `hashtag_research`, `daily_engagement`, `graphic_designs`, `monthly_report`, `youtube_seo`, `fb_ig_ads`, `google_ads`, `website_store`, `pinterest_management`, `ugc_blogs`, `profile_creation`, `fb_ads_limit`, `ig_ads_limit`, `google_ads_limit`, `youtube_ads_limit`, `tiktok_ads_limit`, `linkedin_ads_limit`, `pinterest_ads_limit`, `ads_platforms`) VALUES
(20, 'Package 1: Platinum Omnipresence', 'sjlkdj', 65000.00, 'PKR', 'Per Month', 23, 0, 43, 5, NULL, 'active', '2026-07-07 10:30:38', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, NULL),
(21, 'Production Growth Plan', '16 Short Reel Videos\r\n4 Long Podcast Videos', 40000.00, 'PKR', 'Per Month', 0, 0, 16, 4, NULL, 'active', '2026-07-07 12:27:14', 1, 1, 0, 0, 1, 1, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, NULL),
(23, 'Basic Package SMM', '', 400.00, 'USD', 'Per Month', 25, 30, 10, 2, NULL, 'active', '2026-07-08 13:38:31', 1, 1, 1, 1, 1, 0, 1, 0, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, NULL),
(24, 'Omnipresence Marketing Plan', '', 12000.00, 'PKR', 'Per Month', 12, 16, 4, 2, NULL, 'active', '2026-07-09 12:30:08', 0, 1, 0, 1, 1, 0, 1, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, NULL),
(25, 'Basic SMM', '', 15000.00, 'PKR', 'Per Month', 20, 25, 7, 0, NULL, 'active', '2026-07-20 05:51:27', 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `package_services`
--

CREATE TABLE `package_services` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `service_name` varchar(200) NOT NULL,
  `service_value` varchar(100) DEFAULT NULL,
  `service_unit` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan_progress`
--

CREATE TABLE `plan_progress` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `completed` int(11) NOT NULL DEFAULT 0,
  `total` int(11) NOT NULL DEFAULT 0,
  `updated_by` int(11) NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan_services`
--

CREATE TABLE `plan_services` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `service_type` varchar(50) NOT NULL DEFAULT 'standard',
  `quantity` int(11) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plan_services`
--

INSERT INTO `plan_services` (`id`, `plan_id`, `service_name`, `service_type`, `quantity`, `unit`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Feed Posts', 'standard', 15, 'posts', 1, 1, '2026-07-03 13:20:29', NULL),
(2, 1, 'Stories', 'standard', 20, 'stories', 2, 1, '2026-07-03 13:20:29', NULL),
(3, 1, 'Reels/Videos', 'standard', 5, 'reels', 3, 1, '2026-07-03 13:20:29', NULL),
(4, 1, 'Content Calendar', 'standard', NULL, NULL, 4, 1, '2026-07-03 13:20:29', NULL),
(5, 1, 'Hashtag Research', 'standard', NULL, NULL, 5, 1, '2026-07-03 13:20:29', NULL),
(6, 1, 'Daily Engagement', 'standard', 1, 'hours', 6, 1, '2026-07-03 13:20:29', NULL),
(7, 1, 'Monthly Report', 'standard', NULL, NULL, 7, 1, '2026-07-03 13:20:29', NULL),
(8, 2, 'Feed Posts', 'standard', 25, 'posts', 1, 1, '2026-07-03 13:20:29', NULL),
(9, 2, 'Stories', 'standard', 30, 'stories', 2, 1, '2026-07-03 13:20:29', NULL),
(10, 2, 'Reels/Videos', 'standard', 10, 'reels', 3, 1, '2026-07-03 13:20:29', NULL),
(11, 2, 'Content Calendar', 'standard', NULL, NULL, 4, 1, '2026-07-03 13:20:29', NULL),
(12, 2, 'Hashtag Research', 'standard', NULL, NULL, 5, 1, '2026-07-03 13:20:29', NULL),
(13, 2, 'Daily Engagement', 'standard', 2, 'hours', 6, 1, '2026-07-03 13:20:29', NULL),
(14, 2, 'Elegant Catchy Graphic Designs', 'standard', NULL, NULL, 7, 1, '2026-07-03 13:20:29', NULL),
(15, 2, 'Monthly Report', 'standard', NULL, NULL, 8, 1, '2026-07-03 13:20:29', NULL),
(16, 2, 'YouTube SEO', 'standard', NULL, NULL, 9, 1, '2026-07-03 13:20:29', NULL),
(17, 2, 'Facebook & Instagram Targeted Ads', 'standard', NULL, NULL, 10, 1, '2026-07-03 13:20:29', NULL),
(18, 3, 'Feed Posts', 'standard', 50, 'posts', 1, 1, '2026-07-03 13:20:29', NULL),
(19, 3, 'Stories', 'standard', 60, 'stories', 2, 1, '2026-07-03 13:20:29', NULL),
(20, 3, 'Reels/Videos', 'standard', 20, 'reels', 3, 1, '2026-07-03 13:20:29', NULL),
(21, 3, 'Content Calendar', 'standard', NULL, NULL, 4, 1, '2026-07-03 13:20:29', NULL),
(22, 3, 'Hashtag Research', 'standard', NULL, NULL, 5, 1, '2026-07-03 13:20:29', NULL),
(23, 3, 'Daily Engagement', 'standard', 3, 'hours', 6, 1, '2026-07-03 13:20:29', NULL),
(24, 3, 'Elegant Catchy Graphic Designs', 'standard', NULL, NULL, 7, 1, '2026-07-03 13:20:29', NULL),
(25, 3, 'Monthly Report', 'standard', NULL, NULL, 8, 1, '2026-07-03 13:20:29', NULL),
(26, 3, 'YouTube SEO', 'standard', NULL, NULL, 9, 1, '2026-07-03 13:20:29', NULL),
(27, 3, 'Facebook & Instagram Targeted Ads', 'standard', NULL, NULL, 10, 1, '2026-07-03 13:20:29', NULL),
(28, 3, 'Google Ads', 'standard', NULL, NULL, 11, 1, '2026-07-03 13:20:29', NULL),
(29, 3, 'Website/Store Management', 'standard', NULL, NULL, 12, 1, '2026-07-03 13:20:29', NULL),
(30, 3, 'Pinterest Account Setup & Management', 'standard', NULL, NULL, 13, 1, '2026-07-03 13:20:29', NULL),
(31, 3, 'UGC Blogs for SEO', 'standard', 4, 'blogs', 14, 1, '2026-07-03 13:20:29', NULL),
(32, 3, 'All Social Media Platform Profile Creation', 'standard', NULL, NULL, 15, 1, '2026-07-03 13:20:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pm_billing`
--

CREATE TABLE `pm_billing` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `pm_id` int(11) NOT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','paid') DEFAULT 'pending',
  `date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('planning','active','review','completed') DEFAULT 'planning',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reel_analytics`
--

CREATE TABLE `reel_analytics` (
  `id` int(11) NOT NULL,
  `reel_url` varchar(500) NOT NULL,
  `platform` enum('instagram','facebook','tiktok','youtube') NOT NULL,
  `video_id` varchar(100) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `profile_name` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(500) DEFAULT NULL,
  `followers` int(11) DEFAULT 0,
  `caption` text DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `likes` int(11) DEFAULT 0,
  `comments` int(11) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `shares` int(11) DEFAULT 0,
  `duration` varchar(20) DEFAULT NULL,
  `fetch_date` datetime DEFAULT NULL,
  `status` enum('success','failed','pending') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `api_response` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reel_analytics`
--

INSERT INTO `reel_analytics` (`id`, `reel_url`, `platform`, `video_id`, `username`, `profile_name`, `profile_picture`, `followers`, `caption`, `thumbnail_url`, `likes`, `comments`, `views`, `shares`, `duration`, `fetch_date`, `status`, `error_message`, `api_response`, `created_at`, `updated_at`) VALUES
(15, 'https://www.instagram.com/p/DbA1WNHiMJz/', 'instagram', 'DbA1WNHiMJz', 'demo_user', 'Demo User', 'https://i.pravatar.cc/150?img=66', 3209, 'Sample reel data (API temporarily unavailable)', 'https://picsum.photos/seed/900/400/225', 4480, 46, 4080, 37, '0', '2026-07-20 18:06:25', 'success', NULL, NULL, '2026-07-20 18:06:25', '2026-07-20 18:06:25'),
(16, 'https://youtu.be/FlDxVg4NCTQ?si=ELcjfbzoGwt4bdRl', 'youtube', 'FlDxVg4NCTQ', 'FZCUBE TECH', 'FZCUBE TECH', 'https://i.ytimg.com/vi/FlDxVg4NCTQ/hqdefault.jpg?sqp=-oaymwEcCNACELwBSFXyq4qpAw4IARUAAIhCGAFwAcABBg==&rs=AOn4CLBeygF6p75S5RNFVgWyhB7Sf9ncSg', 0, 'Kali Linux Basics', 'https://i.ytimg.com/vi/FlDxVg4NCTQ/hqdefault.jpg?sqp=-oaymwEcCNACELwBSFXyq4qpAw4IARUAAIhCGAFwAcABBg==&rs=AOn4CLBeygF6p75S5RNFVgWyhB7Sf9ncSg', 7, 0, 109, 0, '0', '2026-07-20 18:08:59', 'success', NULL, NULL, '2026-07-20 18:06:42', '2026-07-20 18:08:59'),
(17, 'https://youtu.be/DcgtvfTReqs?si=GhiJi3jT-1nsyuA_', 'youtube', 'DcgtvfTReqs', 'Expert Marketing and Developers', 'Expert Marketing and Developers', 'https://i.ytimg.com/vi_webp/DcgtvfTReqs/maxresdefault.webp', 0, 'Are you tired of watching your hard-earned money lose', 'https://i.ytimg.com/vi_webp/DcgtvfTReqs/maxresdefault.webp', 0, 0, 6, 0, '0', '2026-07-20 18:07:55', 'success', NULL, NULL, '2026-07-20 18:07:55', '2026-07-20 18:07:55'),
(18, 'https://www.tiktok.com/@asma.khan0095/video/7662193228703190285?is_from_webapp=1&sender_device=pc', 'tiktok', '7662193228703190285', 'asma.khan0095', '@asma.khan0095', 'https://p19-common-sign.tiktokcdn-us.com/tos-useast5-p-0068-tx/osu1AfdJDDRqFldETVElZHEDVBSIafsgt9RVOA~tplv-tiktokx-origin.image?dr=9636&x-expires=1784739600&x-signature=bEHDI0SqbcDcnr6YBXAfSvgs4Us%3D&t=4d5b0474&ps=13740610&shp=81f88b70&shcp=43f4a2f9&idc=useast5', 0, '', 'https://p19-common-sign.tiktokcdn-us.com/tos-useast5-p-0068-tx/osu1AfdJDDRqFldETVElZHEDVBSIafsgt9RVOA~tplv-tiktokx-origin.image?dr=9636&x-expires=1784739600&x-signature=bEHDI0SqbcDcnr6YBXAfSvgs4Us%3D&t=4d5b0474&ps=13740610&shp=81f88b70&shcp=43f4a2f9&idc=useast5', 446500, 13900, 7400000, 6724, '0', '2026-07-20 18:08:35', 'success', NULL, NULL, '2026-07-20 18:08:34', '2026-07-20 18:08:35'),
(19, 'https://www.tiktok.com/@expertmarketingdev/video/7662782206162685192', 'tiktok', '7662782206162685192', 'expertmarketingdev', '@expertmarketingdev', 'https://p19-common-sign.tiktokcdn-us.com/tos-alisg-p-4863-sg/oA0hEbrVgl69EIV9qEQlGcF9vBBXc6fqBjF4Df~tplv-tiktokx-origin.image?dr=9636&x-expires=1784739600&x-signature=aByiqYQLDmm3lfSYmUqmKJXGYHY%3D&t=4d5b0474&ps=13740610&shp=81f88b70&shcp=43f4a2f9&idc=useast5', 0, 'Beautiful 5-Marla Double Story House for Sale! 🏡 Looking for your dream home or a smart investment? This stunning 5-Marla Double Story House is located in the highly accessible and peaceful Pir Mehr ', 'https://p19-common-sign.tiktokcdn-us.com/tos-alisg-p-4863-sg/oA0hEbrVgl69EIV9qEQlGcF9vBBXc6fqBjF4Df~tplv-tiktokx-origin.image?dr=9636&x-expires=1784739600&x-signature=aByiqYQLDmm3lfSYmUqmKJXGYHY%3D&t=4d5b0474&ps=13740610&shp=81f88b70&shcp=43f4a2f9&idc=useast5', 21, 1, 550, 0, '0', '2026-07-20 18:10:24', 'success', NULL, NULL, '2026-07-20 18:10:24', '2026-07-20 18:10:24');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `total_spend` decimal(10,2) DEFAULT 0.00,
  `impressions` int(11) DEFAULT 0,
  `conversions` int(11) DEFAULT 0,
  `roi` decimal(5,2) DEFAULT 0.00,
  `type` varchar(50) DEFAULT 'campaign',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `client_id`, `platform`, `total_spend`, `impressions`, `conversions`, `roi`, `type`, `created_at`) VALUES
(1, 1, 'Meta Ads', 18500.00, 112450, 245, 3.20, 'campaign', '2026-07-02 01:31:45'),
(2, 1, 'Google Ads', 12800.00, 78320, 187, 4.10, 'campaign', '2026-07-02 01:31:45');

-- --------------------------------------------------------

--
-- Table structure for table `report_settings`
--

CREATE TABLE `report_settings` (
  `id` int(11) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT 'HIFI Marketing & Technologies',
  `report_title` varchar(255) DEFAULT 'Application Report',
  `primary_color` varchar(7) DEFAULT '#4a5cf5',
  `secondary_color` varchar(7) DEFAULT '#1a1c26',
  `footer_text` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report_settings`
--

INSERT INTO `report_settings` (`id`, `logo_path`, `company_name`, `report_title`, `primary_color`, `secondary_color`, `footer_text`, `updated_at`) VALUES
(1, 'uploads/logos/report_logo.png', 'HIFI Marketing &amp;amp; Technologies', 'Application Report', '#f64c4c', '#fa0079', 'This report is generated automatically. For any queries, contact support@hifimarketing.com', '2026-06-26 13:42:01');

-- --------------------------------------------------------

--
-- Table structure for table `service_plans`
--

CREATE TABLE `service_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'AED',
  `billing_type` varchar(20) NOT NULL DEFAULT 'monthly',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_plans`
--

INSERT INTO `service_plans` (`id`, `name`, `price`, `currency`, `billing_type`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Starter Plan', 499.00, 'AED', 'monthly', 'Perfect for small businesses starting their digital journey', 1, '2026-07-03 13:20:29', NULL),
(2, 'Professional Plan', 999.00, 'AED', 'monthly', 'Complete solution for growing businesses', 1, '2026-07-03 13:20:29', NULL),
(3, 'Enterprise Plan', 1999.00, 'AED', 'monthly', 'Full-scale digital transformation for enterprises', 1, '2026-07-03 13:20:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `primary_color` varchar(20) DEFAULT '#4f46e5',
  `primary_hover` varchar(20) DEFAULT '#4338ca',
  `secondary_color` varchar(20) DEFAULT '#f59e0b',
  `secondary_hover` varchar(20) DEFAULT '#d97706',
  `sidebar_bg` varchar(20) DEFAULT '#0f172a',
  `sidebar_text` varchar(20) DEFAULT '#94a3b8',
  `sidebar_active` varchar(20) DEFAULT '#818cf8',
  `header_bg` varchar(20) DEFAULT '#ffffff',
  `header_text` varchar(20) DEFAULT '#1e293b',
  `card_bg` varchar(20) DEFAULT '#ffffff',
  `card_border` varchar(20) DEFAULT '#e2e8f0',
  `body_bg` varchar(20) DEFAULT '#f8fafc',
  `body_text` varchar(20) DEFAULT '#1e293b',
  `success_color` varchar(20) DEFAULT '#22c55e',
  `warning_color` varchar(20) DEFAULT '#f59e0b',
  `danger_color` varchar(20) DEFAULT '#ef4444',
  `info_color` varchar(20) DEFAULT '#3b82f6',
  `btn_radius` varchar(10) DEFAULT '8px',
  `card_radius` varchar(10) DEFAULT '12px',
  `font_family` varchar(100) DEFAULT 'Plus Jakarta Sans',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `primary_color`, `primary_hover`, `secondary_color`, `secondary_hover`, `sidebar_bg`, `sidebar_text`, `sidebar_active`, `header_bg`, `header_text`, `card_bg`, `card_border`, `body_bg`, `body_text`, `success_color`, `warning_color`, `danger_color`, `info_color`, `btn_radius`, `card_radius`, `font_family`, `created_at`, `updated_at`) VALUES
(1, '#4f46e5', '#4338ca', '#4f46e5', '#d97706', '#0f172a', '#94a3b8', '#818cf8', '#ffffff', '#1e293b', '#ffffff', '#e2e8f0', '#f8fafc', '#1e293b', '#22c55e', '#4f46e5', '#ef4444', '#3b82f6', '8px', '12px', 'Plus Jakarta Sans', '2026-07-02 02:29:51', '2026-07-02 02:31:10');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT 'General',
  `priority` varchar(20) DEFAULT 'Medium',
  `status` varchar(20) DEFAULT 'Open',
  `description` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `client_reply` text DEFAULT NULL,
  `client_reply_date` datetime DEFAULT NULL,
  `pm_reply` text DEFAULT NULL,
  `pm_reply_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `client_id`, `title`, `category`, `priority`, `status`, `description`, `admin_notes`, `client_reply`, `client_reply_date`, `pm_reply`, `pm_reply_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'Ad copy revision for Eid Campaign', 'Task Assignment', 'Medium', 'Resolved', 'Please tweak the Hook variations for the FB story ads to focus more on the flat 20% discount offer.', NULL, NULL, NULL, NULL, NULL, '2026-07-02 01:31:45', '2026-07-04 04:11:05'),
(2, 1, 'INSTA story upload', 'Task Assignment', 'Medium', 'Resolved', 'No details provided.', NULL, NULL, NULL, NULL, NULL, '2026-07-02 01:31:45', '2026-07-02 01:31:45'),
(3, 1, 'Billing Issue: Charge on 15th was double-routed', 'Support Ticket', 'Medium', 'Open', 'The payment system debited twice. Please review the payment gateway log and refund the surplus.', 'Verified duplicate payment reference. Gateway refund initiated.', NULL, NULL, NULL, NULL, '2026-07-02 01:31:45', '2026-07-02 01:31:45'),
(4, 5, 'Podcast Scripts', 'Task Assignment', 'Medium', 'Open', 'Need podcast scripts for my videos of houses in university homes', 'ok working on it can you share samples', 'i am faizan', '2026-07-09 13:58:27', NULL, NULL, '2026-07-07 14:06:18', '2026-07-09 12:58:27'),
(5, 5, 'Podcast Scripts', 'Task Assignment', 'Medium', 'In Progress', 'i need scripts for podcast', NULL, NULL, NULL, '\n\n--- PM Reply (2026-07-09 14:13) ---\nhi', '2026-07-09 14:13:02', '2026-07-08 12:18:59', '2026-07-09 13:13:02'),
(6, 5, 'Need Design for Damac', 'Task Assignment', 'Medium', 'Open', 'asas', NULL, 'My name is faizan', '2026-07-09 14:08:03', '\n\n--- PM Reply (2026-07-09 14:06) ---\ni am faizan website developer', '2026-07-09 14:06:23', '2026-07-08 12:20:33', '2026-07-09 13:08:03'),
(7, 5, 'nnvmn', 'Task Assignment', 'Medium', 'In Progress', 'bg', NULL, NULL, NULL, '\n\n--- PM Reply (2026-07-15 17:39) ---\nwhat?', '2026-07-15 17:39:04', '2026-07-11 03:48:26', '2026-07-15 16:39:04'),
(11, 3, 'Chat system', 'Task Assignment', 'Medium', 'In Progress', 'Here is the chat system', NULL, NULL, NULL, NULL, NULL, '2026-07-16 10:01:11', '2026-07-16 10:01:34'),
(13, 3, 'Create an instagram page for my business', 'Task Assignment', 'Medium', 'In Progress', 'Instagram business page for my business', NULL, NULL, NULL, NULL, NULL, '2026-07-16 11:49:27', '2026-07-17 18:24:55'),
(16, 3, 'I want to create a website', 'Task Assignment', 'Medium', 'In Progress', 'Required a website for my Business', NULL, NULL, NULL, NULL, NULL, '2026-07-20 07:56:00', '2026-07-20 07:56:34');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `task_title` varchar(255) NOT NULL,
  `task_description` text DEFAULT NULL,
  `status` enum('todo','in_progress','review','done') DEFAULT 'todo',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `assigned_to` varchar(100) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `theme_settings`
--

CREATE TABLE `theme_settings` (
  `id` int(11) NOT NULL,
  `dashboard_bg` varchar(20) DEFAULT '#f8fafc',
  `dashboard_card_bg` varchar(20) DEFAULT '#ffffff',
  `dashboard_card_border` varchar(20) DEFAULT '#e2e8f0',
  `dashboard_heading` varchar(20) DEFAULT '#1e293b',
  `dashboard_text` varchar(20) DEFAULT '#475569',
  `dashboard_metric_bg` varchar(20) DEFAULT '#ffffff',
  `dashboard_metric_text` varchar(20) DEFAULT '#1e293b',
  `dashboard_metric_icon` varchar(20) DEFAULT '#4f46e5',
  `dashboard_metric_value` varchar(20) DEFAULT '#1e293b',
  `dashboard_progress_bg` varchar(20) DEFAULT '#e2e8f0',
  `dashboard_progress_fill` varchar(20) DEFAULT '#4f46e5',
  `plan_bg` varchar(20) DEFAULT '#ffffff',
  `plan_card_bg` varchar(20) DEFAULT '#ffffff',
  `plan_card_border` varchar(20) DEFAULT '#e2e8f0',
  `plan_heading` varchar(20) DEFAULT '#1e293b',
  `plan_text` varchar(20) DEFAULT '#475569',
  `plan_price` varchar(20) DEFAULT '#1e293b',
  `plan_active_border` varchar(20) DEFAULT '#4f46e5',
  `plan_active_bg` varchar(20) DEFAULT '#eef2ff',
  `plan_button_bg` varchar(20) DEFAULT '#4f46e5',
  `plan_button_text` varchar(20) DEFAULT '#ffffff',
  `addons_bg` varchar(20) DEFAULT '#ffffff',
  `addons_card_bg` varchar(20) DEFAULT '#ffffff',
  `addons_card_border` varchar(20) DEFAULT '#e2e8f0',
  `addons_heading` varchar(20) DEFAULT '#1e293b',
  `addons_text` varchar(20) DEFAULT '#475569',
  `addons_price` varchar(20) DEFAULT '#4f46e5',
  `addons_button_bg` varchar(20) DEFAULT '#4f46e5',
  `addons_button_text` varchar(20) DEFAULT '#ffffff',
  `deliverables_bg` varchar(20) DEFAULT '#ffffff',
  `deliverables_card_bg` varchar(20) DEFAULT '#ffffff',
  `deliverables_card_border` varchar(20) DEFAULT '#e2e8f0',
  `deliverables_heading` varchar(20) DEFAULT '#1e293b',
  `deliverables_text` varchar(20) DEFAULT '#475569',
  `deliverables_status_todo` varchar(20) DEFAULT '#94a3b8',
  `deliverables_status_progress` varchar(20) DEFAULT '#f59e0b',
  `deliverables_status_done` varchar(20) DEFAULT '#22c55e',
  `tickets_bg` varchar(20) DEFAULT '#ffffff',
  `tickets_card_bg` varchar(20) DEFAULT '#ffffff',
  `tickets_card_border` varchar(20) DEFAULT '#e2e8f0',
  `tickets_heading` varchar(20) DEFAULT '#1e293b',
  `tickets_text` varchar(20) DEFAULT '#475569',
  `tickets_status_open` varchar(20) DEFAULT '#ef4444',
  `tickets_status_resolved` varchar(20) DEFAULT '#22c55e',
  `tickets_button_bg` varchar(20) DEFAULT '#4f46e5',
  `tickets_button_text` varchar(20) DEFAULT '#ffffff',
  `billing_bg` varchar(20) DEFAULT '#ffffff',
  `billing_table_header` varchar(20) DEFAULT '#f1f5f9',
  `billing_table_border` varchar(20) DEFAULT '#e2e8f0',
  `billing_heading` varchar(20) DEFAULT '#1e293b',
  `billing_text` varchar(20) DEFAULT '#475569',
  `billing_paid` varchar(20) DEFAULT '#22c55e',
  `billing_pending` varchar(20) DEFAULT '#f59e0b',
  `billing_button_bg` varchar(20) DEFAULT '#4f46e5',
  `billing_button_text` varchar(20) DEFAULT '#ffffff',
  `reports_bg` varchar(20) DEFAULT '#ffffff',
  `reports_card_bg` varchar(20) DEFAULT '#ffffff',
  `reports_card_border` varchar(20) DEFAULT '#e2e8f0',
  `reports_heading` varchar(20) DEFAULT '#1e293b',
  `reports_text` varchar(20) DEFAULT '#475569',
  `reports_button_bg` varchar(20) DEFAULT '#4f46e5',
  `reports_button_text` varchar(20) DEFAULT '#ffffff',
  `pm_operations_bg` varchar(20) DEFAULT '#ffffff',
  `pm_operations_card_bg` varchar(20) DEFAULT '#ffffff',
  `pm_operations_card_border` varchar(20) DEFAULT '#e2e8f0',
  `pm_operations_heading` varchar(20) DEFAULT '#1e293b',
  `pm_operations_text` varchar(20) DEFAULT '#475569',
  `pm_operations_status` varchar(20) DEFAULT '#4f46e5',
  `pm_operations_button_bg` varchar(20) DEFAULT '#4f46e5',
  `pm_operations_button_text` varchar(20) DEFAULT '#ffffff',
  `pm_deliverables_bg` varchar(20) DEFAULT '#ffffff',
  `pm_deliverables_card_bg` varchar(20) DEFAULT '#ffffff',
  `pm_deliverables_card_border` varchar(20) DEFAULT '#e2e8f0',
  `pm_deliverables_heading` varchar(20) DEFAULT '#1e293b',
  `pm_deliverables_text` varchar(20) DEFAULT '#475569',
  `pm_deliverables_status_todo` varchar(20) DEFAULT '#94a3b8',
  `pm_deliverables_status_progress` varchar(20) DEFAULT '#f59e0b',
  `pm_deliverables_status_done` varchar(20) DEFAULT '#22c55e',
  `pm_deliverables_button_bg` varchar(20) DEFAULT '#4f46e5',
  `pm_deliverables_button_text` varchar(20) DEFAULT '#ffffff',
  `pm_tickets_bg` varchar(20) DEFAULT '#ffffff',
  `pm_tickets_card_bg` varchar(20) DEFAULT '#ffffff',
  `pm_tickets_card_border` varchar(20) DEFAULT '#e2e8f0',
  `pm_tickets_heading` varchar(20) DEFAULT '#1e293b',
  `pm_tickets_text` varchar(20) DEFAULT '#475569',
  `pm_tickets_status_open` varchar(20) DEFAULT '#ef4444',
  `pm_tickets_status_resolved` varchar(20) DEFAULT '#22c55e',
  `pm_tickets_button_bg` varchar(20) DEFAULT '#4f46e5',
  `pm_tickets_button_text` varchar(20) DEFAULT '#ffffff',
  `pm_verbal_bg` varchar(20) DEFAULT '#ffffff',
  `pm_verbal_card_bg` varchar(20) DEFAULT '#ffffff',
  `pm_verbal_card_border` varchar(20) DEFAULT '#e2e8f0',
  `pm_verbal_heading` varchar(20) DEFAULT '#1e293b',
  `pm_verbal_text` varchar(20) DEFAULT '#475569',
  `pm_verbal_button_bg` varchar(20) DEFAULT '#4f46e5',
  `pm_verbal_button_text` varchar(20) DEFAULT '#ffffff',
  `pm_sync_bg` varchar(20) DEFAULT '#ffffff',
  `pm_sync_card_bg` varchar(20) DEFAULT '#ffffff',
  `pm_sync_card_border` varchar(20) DEFAULT '#e2e8f0',
  `pm_sync_heading` varchar(20) DEFAULT '#1e293b',
  `pm_sync_text` varchar(20) DEFAULT '#475569',
  `pm_sync_slider` varchar(20) DEFAULT '#4f46e5',
  `pm_sync_button_bg` varchar(20) DEFAULT '#4f46e5',
  `pm_sync_button_text` varchar(20) DEFAULT '#ffffff'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `theme_settings`
--

INSERT INTO `theme_settings` (`id`, `dashboard_bg`, `dashboard_card_bg`, `dashboard_card_border`, `dashboard_heading`, `dashboard_text`, `dashboard_metric_bg`, `dashboard_metric_text`, `dashboard_metric_icon`, `dashboard_metric_value`, `dashboard_progress_bg`, `dashboard_progress_fill`, `plan_bg`, `plan_card_bg`, `plan_card_border`, `plan_heading`, `plan_text`, `plan_price`, `plan_active_border`, `plan_active_bg`, `plan_button_bg`, `plan_button_text`, `addons_bg`, `addons_card_bg`, `addons_card_border`, `addons_heading`, `addons_text`, `addons_price`, `addons_button_bg`, `addons_button_text`, `deliverables_bg`, `deliverables_card_bg`, `deliverables_card_border`, `deliverables_heading`, `deliverables_text`, `deliverables_status_todo`, `deliverables_status_progress`, `deliverables_status_done`, `tickets_bg`, `tickets_card_bg`, `tickets_card_border`, `tickets_heading`, `tickets_text`, `tickets_status_open`, `tickets_status_resolved`, `tickets_button_bg`, `tickets_button_text`, `billing_bg`, `billing_table_header`, `billing_table_border`, `billing_heading`, `billing_text`, `billing_paid`, `billing_pending`, `billing_button_bg`, `billing_button_text`, `reports_bg`, `reports_card_bg`, `reports_card_border`, `reports_heading`, `reports_text`, `reports_button_bg`, `reports_button_text`, `pm_operations_bg`, `pm_operations_card_bg`, `pm_operations_card_border`, `pm_operations_heading`, `pm_operations_text`, `pm_operations_status`, `pm_operations_button_bg`, `pm_operations_button_text`, `pm_deliverables_bg`, `pm_deliverables_card_bg`, `pm_deliverables_card_border`, `pm_deliverables_heading`, `pm_deliverables_text`, `pm_deliverables_status_todo`, `pm_deliverables_status_progress`, `pm_deliverables_status_done`, `pm_deliverables_button_bg`, `pm_deliverables_button_text`, `pm_tickets_bg`, `pm_tickets_card_bg`, `pm_tickets_card_border`, `pm_tickets_heading`, `pm_tickets_text`, `pm_tickets_status_open`, `pm_tickets_status_resolved`, `pm_tickets_button_bg`, `pm_tickets_button_text`, `pm_verbal_bg`, `pm_verbal_card_bg`, `pm_verbal_card_border`, `pm_verbal_heading`, `pm_verbal_text`, `pm_verbal_button_bg`, `pm_verbal_button_text`, `pm_sync_bg`, `pm_sync_card_bg`, `pm_sync_card_border`, `pm_sync_heading`, `pm_sync_text`, `pm_sync_slider`, `pm_sync_button_bg`, `pm_sync_button_text`) VALUES
(1, '#f8fafc', '#ffffff', '#e2e8f0', '#1e293b', '#475569', '#ffffff', '#1e293b', '#4f46e5', '#1e293b', '#e2e8f0', '#4f46e5', '#ffffff', '#ffffff', '#e2e8f0', '#1e293b', '#475569', '#1e293b', '#4f46e5', '#eef2ff', '#4f46e5', '#ffffff', '#ffffff', '#ffffff', '#e2e8f0', '#1e293b', '#475569', '#4f46e5', '#4f46e5', '#ffffff', '#ffffff', '#ffffff', '#e2e8f0', '#1e293b', '#475569', '#f43f5e', '#4f46e5', '#22c55e', '#ffffff', '#ffffff', '#e2e8f0', '#1e293b', '#475569', '#ef4444', '#22c55e', '#4f46e5', '#ffffff', '#ffffff', '#f1f5f9', '#e2e8f0', '#1e293b', '#475569', '#22c55e', '#f59e0b', '#4f46e5', '#ffffff', '#ffffff', '#ffffff', '#e2e8f0', '#1e293b', '#475569', '#4f46e5', '#ffffff', '#ffffff', '#ffffff', '#e2e8f0', '#1e293b', '#475569', '#4f46e5', '#4f46e5', '#ffffff', '#ffffff', '#ffffff', '#e2e8f0', '#1e293b', '#475569', '#94a3b8', '#f59e0b', '#22c55e', '#4f46e5', '#ffffff', '#ffffff', '#ffffff', '#e2e8f0', '#1e293b', '#475569', '#ef4444', '#22c55e', '#4f46e5', '#ffffff', '#ffffff', '#ffffff', '#e2e8f0', '#1e293b', '#475569', '#4f46e5', '#ffffff', '#ffffff', '#ffffff', '#e2e8f0', '#1e293b', '#475569', '#4f46e5', '#4f46e5', '#ffffff');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user','client','pm','super_admin') DEFAULT 'client',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_role` enum('client','admin','pm','super_admin') DEFAULT 'client',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive','suspended','pending','super_admin') DEFAULT 'active',
  `avatar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `user_role`, `updated_at`, `status`, `avatar`) VALUES
(25, 'Demo client', 'client@hifimarketing.co', 'client123', 'user', '2026-07-03 07:18:05', 'client', '2026-07-16 12:38:03', 'active', NULL),
(27, 'Khushhal Flour Mill', 'millkhushhalflour@gmail.com', 'khushhalflour', 'user', '2026-07-03 07:22:44', 'client', '2026-07-09 09:43:37', 'active', NULL),
(46, 'Project Manager', 'pm@hifimarketing.co', '#@FWE#', 'pm', '2026-07-06 16:11:03', 'pm', '2026-07-14 11:57:05', 'active', NULL),
(49, 'Careers Admin', 'careers@hifimarketing.co', 'E@WRT$#%WGTRE', 'admin', '2026-07-07 10:15:13', 'admin', '2026-07-16 12:39:30', 'active', NULL),
(50, 'buildersexpert', 'Buildersexpert42@gmail.com', 'expert$%^', 'user', '2026-07-07 10:22:33', 'client', '2026-07-07 11:23:57', 'active', NULL),
(51, 'The Billionaire Affair', 'vip@thebillionaireaffair.com', 'vip$%^tba', 'user', '2026-07-08 13:09:56', 'client', '2026-07-08 14:10:41', 'active', NULL),
(55, 'Skyway Fire Safety', 'aafriaz@gmail.com', 'aafriaz&*^', 'user', '2026-07-09 08:21:14', 'client', '2026-07-09 09:22:28', 'active', NULL),
(56, 'Hopeful Welfare Foundation', 'hwf.org.uk@gmail.com', 'hwf&*#', 'user', '2026-07-09 08:26:18', 'client', '2026-07-09 09:26:37', 'active', NULL),
(57, 'Saks Auto World', 'repair@saksautoworld.com', 'saks@#$@', 'user', '2026-07-09 08:28:04', 'client', '2026-07-09 09:28:04', 'active', NULL),
(58, 'ERP BizTrack', 'erpbiztrack@gmail.com', '@#$RR$$RER', 'user', '2026-07-09 11:47:31', 'client', '2026-07-09 12:47:31', 'active', NULL),
(59, 'Super Admin', 'admin@hifimarketing.co', 'Super$%$#', 'super_admin', '2026-07-10 14:09:40', 'super_admin', '2026-07-16 12:39:36', 'active', NULL),
(64, 'UmairQayyum', 'kitsoldier55@gmail.com', '$2y$10$4RrgNg26XIfhWFZxLhaMJO17/wEWXTX3AJSXPvYUkynBhw45wmSuS', 'user', '2026-07-17 02:20:44', 'client', '2026-07-17 03:20:44', 'active', NULL),
(65, 'Zubair Sultan', 'zubairjawed6666@gmail.com', '$2y$10$leA3xbmHbb1BUM2TIGd3PeeuBnKsJvd9sjcFj6uJMQc.ue1KRF4lS', 'user', '2026-07-17 02:56:50', 'client', '2026-07-17 03:56:50', 'active', NULL),
(66, 'Faizan', 'gunb07912@gmail.com', '$2y$10$NuToic5JEbCg4RKyNG7j2ur89cv7jk/0Unp0O5G6UOCB8psGMhbrm', 'user', '2026-07-17 05:29:26', 'client', '2026-07-17 06:29:26', 'active', NULL),
(67, 'Aleena Aziz', 'alinaaziz476@gmail.com', '$2y$10$MYNbz0pFokXob0lSncQUfOW8kbAxe3lFEImFoEY8I05MuTNp0nX5y', 'user', '2026-07-17 05:58:09', 'client', '2026-07-17 06:58:09', 'active', NULL),
(68, 'Umme Roman empire', 'romanume982@gmail.com', '$2y$10$I6bpOOS1sTacQfNxQ2Jgh.DTvclwF6/XGSO5NLnTNkGJTa0hkHQXq', 'user', '2026-07-17 07:41:20', 'client', '2026-07-17 08:41:20', 'active', NULL),
(69, 'Hifsa Nishat', 'nishatdesigner1@gmail.com', '$2y$10$ndLWRGJcv8OxAOR0qI4PGOQHgK00dPr5EajUkFKFpPquqWDKDCZF.', 'user', '2026-07-17 09:09:49', 'client', '2026-07-17 10:09:49', 'active', NULL),
(70, 'Laibahussain', 'laibahussain5567@gmail.com', '$2y$10$B1SnqK4QAs4FE7sFrY858uLGzEzJSiOSIvelcOrGVKVMCpAKjVRtq', 'user', '2026-07-17 09:53:06', 'client', '2026-07-17 10:53:06', 'active', NULL),
(71, 'Aisha', 'ayeshasyeda535@gmail.com', '$2y$10$4Gt9mRl2bDmnoalfBYMsyeYw46PhhgEYK6VGB2TyoBIVbuHT..sXO', 'user', '2026-07-18 13:28:44', 'client', '2026-07-18 14:28:44', 'active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `verbal_tasks`
--

CREATE TABLE `verbal_tasks` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'In Progress',
  `invoice_generated` tinyint(1) DEFAULT 0,
  `invoice_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_client_id` (`client_id`),
  ADD KEY `idx_activity_logs_user_id` (`user_id`);

--
-- Indexes for table `addons`
--
ALTER TABLE `addons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ad_campaigns`
--
ALTER TABLE `ad_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_bookmark` (`job_id`,`user_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_ticket_sender` (`ticket_id`,`sender_type`),
  ADD KEY `idx_created_at_desc` (`created_at` DESC),
  ADD KEY `idx_is_deleted` (`is_deleted`),
  ADD KEY `idx_deleted_for` (`deleted_for`),
  ADD KEY `idx_deleted_by` (`deleted_by`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_code` (`client_code`);

--
-- Indexes for table `client_packages`
--
ALTER TABLE `client_packages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_client_package` (`client_id`,`package_id`),
  ADD KEY `idx_client_packages_client` (`client_id`),
  ADD KEY `idx_client_packages_package` (`package_id`);

--
-- Indexes for table `client_plan_assignments`
--
ALTER TABLE `client_plan_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `client_progress_history`
--
ALTER TABLE `client_progress_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client_id` (`client_id`),
  ADD KEY `idx_snapshot_date` (`snapshot_date`);

--
-- Indexes for table `custom_tasks`
--
ALTER TABLE `custom_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deliverables`
--
ALTER TABLE `deliverables`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `education`
--
ALTER TABLE `education`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_logs_to_email` (`to_email`),
  ADD KEY `idx_email_logs_status` (`status`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_queue_status` (`status`);

--
-- Indexes for table `experience`
--
ALTER TABLE `experience`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ledger`
--
ALTER TABLE `ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ledger_client_id` (`client_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_id` (`user_id`),
  ADD KEY `idx_notifications_is_read` (`is_read`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_services`
--
ALTER TABLE `package_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `plan_progress`
--
ALTER TABLE `plan_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `plan_services`
--
ALTER TABLE `plan_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `pm_billing`
--
ALTER TABLE `pm_billing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_projects_client_id` (`client_id`),
  ADD KEY `idx_projects_status` (`status`);

--
-- Indexes for table `reel_analytics`
--
ALTER TABLE `reel_analytics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_platform` (`platform`),
  ADD KEY `idx_video_id` (`video_id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_fetch_date` (`fetch_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `report_settings`
--
ALTER TABLE `report_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_plans`
--
ALTER TABLE `service_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tasks_client_id` (`client_id`),
  ADD KEY `idx_tasks_project_id` (`project_id`),
  ADD KEY `idx_tasks_status` (`status`);

--
-- Indexes for table `theme_settings`
--
ALTER TABLE `theme_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_user_role` (`user_role`);

--
-- Indexes for table `verbal_tasks`
--
ALTER TABLE `verbal_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `addons`
--
ALTER TABLE `addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ad_campaigns`
--
ALTER TABLE `ad_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `bookmarks`
--
ALTER TABLE `bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `client_packages`
--
ALTER TABLE `client_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `client_plan_assignments`
--
ALTER TABLE `client_plan_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client_progress_history`
--
ALTER TABLE `client_progress_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custom_tasks`
--
ALTER TABLE `custom_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `deliverables`
--
ALTER TABLE `deliverables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `education`
--
ALTER TABLE `education`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `experience`
--
ALTER TABLE `experience`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `ledger`
--
ALTER TABLE `ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `package_services`
--
ALTER TABLE `package_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `plan_progress`
--
ALTER TABLE `plan_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plan_services`
--
ALTER TABLE `plan_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `pm_billing`
--
ALTER TABLE `pm_billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reel_analytics`
--
ALTER TABLE `reel_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `report_settings`
--
ALTER TABLE `report_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `service_plans`
--
ALTER TABLE `service_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `theme_settings`
--
ALTER TABLE `theme_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `verbal_tasks`
--
ALTER TABLE `verbal_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_plan_assignments`
--
ALTER TABLE `client_plan_assignments`
  ADD CONSTRAINT `client_plan_assignments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_plan_assignments_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `service_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_plan_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_progress_history`
--
ALTER TABLE `client_progress_history`
  ADD CONSTRAINT `client_progress_history_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `education`
--
ALTER TABLE `education`
  ADD CONSTRAINT `education_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `experience`
--
ALTER TABLE `experience`
  ADD CONSTRAINT `experience_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_services`
--
ALTER TABLE `package_services`
  ADD CONSTRAINT `package_services_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_services_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `plan_progress`
--
ALTER TABLE `plan_progress`
  ADD CONSTRAINT `plan_progress_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plan_progress_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `service_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plan_progress_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `plan_services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `plan_services`
--
ALTER TABLE `plan_services`
  ADD CONSTRAINT `plan_services_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `service_plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pm_billing`
--
ALTER TABLE `pm_billing`
  ADD CONSTRAINT `pm_billing_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `verbal_tasks`
--
ALTER TABLE `verbal_tasks`
  ADD CONSTRAINT `verbal_tasks_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
