-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 29, 2026 at 03:19 AM
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
-- Database: `wmsu_voting_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int(11) NOT NULL,
  `year_label` varchar(20) DEFAULT NULL,
  `semester` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` text NOT NULL DEFAULT 'Ongoing',
  `custom_voter_option` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `year_label`, `semester`, `start_date`, `end_date`, `status`, `custom_voter_option`) VALUES
(54, '2026 - 2027', '1st Semester', '2026-03-27', '2026-05-31', 'Archived', 0),
(55, '2026 - 2027', '2nd Semester', '2026-03-27', '2026-04-30', 'Archived', 0),
(56, '2025 - 2026', '1st Semester', '2026-03-27', '2026-06-30', 'Archived', 0),
(57, '2026 - 2027', '1st Semester', '2026-03-29', '2026-05-31', 'Ongoing', 0);

-- --------------------------------------------------------

--
-- Table structure for table `actual_year_levels`
--

CREATE TABLE `actual_year_levels` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `major_id` int(11) DEFAULT NULL,
  `year_level` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `actual_year_levels`
--

INSERT INTO `actual_year_levels` (`id`, `course_id`, `major_id`, `year_level`) VALUES
(24, 17, NULL, 1),
(25, 17, NULL, 2),
(26, 17, NULL, 3),
(27, 17, NULL, 4),
(28, 17, NULL, 5),
(29, 17, 7, 1),
(30, 17, 7, 2),
(31, 17, 7, 3),
(32, 17, 7, 4),
(33, 17, 7, 5),
(34, 14, NULL, 1),
(35, 14, NULL, 2),
(36, 14, NULL, 3),
(37, 14, NULL, 4),
(38, 14, NULL, 5),
(39, 29, NULL, 1),
(40, 29, NULL, 2),
(41, 29, NULL, 3),
(42, 29, NULL, 4),
(43, 29, NULL, 5),
(44, 18, NULL, 1),
(45, 18, NULL, 2),
(46, 18, NULL, 3),
(47, 18, NULL, 4),
(48, 18, NULL, 5);

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `user_id`, `full_name`, `phone_number`) VALUES
(1, 1, 'WMSU Admin', '+63123456789');

-- --------------------------------------------------------

--
-- Table structure for table `advisers`
--

CREATE TABLE `advisers` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `college_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `major_id` int(11) DEFAULT NULL,
  `wmsu_campus_id` int(11) NOT NULL,
  `external_campus_id` int(11) DEFAULT NULL,
  `year_level` int(20) UNSIGNED DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `has_changed` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `advisers`
--

INSERT INTO `advisers` (`id`, `first_name`, `middle_name`, `last_name`, `full_name`, `email`, `password`, `college_id`, `department_id`, `major_id`, `wmsu_campus_id`, `external_campus_id`, `year_level`, `semester`, `school_year`, `has_changed`, `created_at`, `updated_at`) VALUES
(17, 'ZXC', 'Guy', 'Adviser', 'ZXC \nGuy\nAdviser', 'zxcguyadviser@gmail.com', '$2y$10$HVWaTZch2y3l9SXIuMx1Le3DQQfExbVUPmLuJxsVD50/vVJ9b3KY6', 22, 158, NULL, 8, NULL, 29, '1st Semester', '2025-2026', 0, '2026-03-12 06:35:36', NULL),
(18, 'New ESU', 'WMSU', 'Adviser', 'New ESU WMSU Adviser', 'wmsuadvisermadah@gmail.com', '$2y$10$BqDeibX92rF8o8AXxB6vEuWeUB6gpJRZyj5l/PvR24EsIvC7xxFCO', 22, 158, NULL, 10, 12, 24, '1st Semester', '2025-2026', 0, '2026-03-13 19:23:41', NULL),
(20, 'BSCS', '3rd Year', 'Adviser', 'BSCS 3rd Year Adviser', 'csadviser@wmsu.edu.ph', '$2y$10$/3nZvXNOkFpGZPZ.ZL3NtuK0BYXIHzF7AFr/lS5Dg82HRa/A4YPuu', 22, 158, NULL, 8, NULL, 26, '2nd Semester', '2025-2026', 0, '2026-03-15 09:08:11', NULL),
(21, 'CCS Adviser', 'IT', '1st Year', 'CCS Adviser IT 1st Year', 'ccsadviser1st@wmsu.edu.ph', '$2y$10$GJ215bgif1s6kU2J5qG58uUDwCcoPDY/CCBxDe2jHGo6Tvx.s2ZeK', 22, 159, NULL, 8, NULL, 44, '1st Semester', '2025-2026', 0, '2026-03-16 15:51:02', NULL),
(22, 'CE Adviser', 'Engineering', '1st Year', 'CE Adviser Engineering 1st Year', 'ceadviserengineering1st@gmail.com', '$2y$10$oZEVA07DZfxvKoVNgzVwAeSKGtWBwici5rvfzdoovCBOlTEPv.C8.', 28, 170, NULL, 8, NULL, 39, '1st Semester', '2025-2026', 0, '2026-03-16 15:53:17', NULL),
(23, 'CCS Adviser', 'CS', '1st Year', 'CCS Adviser CS 1st Year', 'ccsadviser1stmain@wmsu.edu.ph', '$2y$10$ELZZylAXLzuJgoMaLRWBS.uoBAShEdgFTFA4aNqOlessOBh.8sQLC', 22, 158, NULL, 8, NULL, 24, '1st Semester', '2025-2026', 0, '2026-03-16 16:09:38', NULL),
(24, 'Adviser', 'CCS Software Engineering', '1st Year', 'Adviser CCS Software Engineering 1st Year', 'ccsadviser1stsofteng@gmail.com', '$2y$10$QnvpSJnobsHTPyj5QYp6Ju6FloG/VSB2RR8DUU9CJydrjUUND2pmy', 22, 158, 7, 10, 12, 29, '1st Semester', '2025-2026', 0, '2026-03-17 05:55:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `adviser_import_details`
--

CREATE TABLE `adviser_import_details` (
  `id` int(11) NOT NULL,
  `file` text NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp(),
  `status` text NOT NULL,
  `voters_added` int(11) NOT NULL,
  `emails_sent` int(11) NOT NULL,
  `adviser_email` text NOT NULL,
  `mismatches` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adviser_import_details`
--

INSERT INTO `adviser_import_details` (`id`, `file`, `date`, `status`, `voters_added`, `emails_sent`, `adviser_email`, `mismatches`) VALUES
(76, '1752470433_Submission-of-Student-Details-for-Voting-Copy.csv', '2025-07-14', 'completed', 0, 0, 'sharislavilla@gmail.com', '[]'),
(77, '1752470691_Submission-of-Student-Details-for-Voting-Copy.csv', '2025-07-14', 'completed', 0, 0, 'sharislavilla@gmail.com', '[]'),
(78, '1752566141_Submission of Student Details for Voting - Copy.csv', '2025-07-15', 'completed', 1, 1, 'sharislavilla@gmail.com', '[]'),
(79, '1752566313_Submission of Student Details for Voting - Copy.csv', '2025-07-15', 'completed', 1, 1, 'sharislavilla@gmail.com', '[]'),
(80, '1752567142_Submission of Student Details for Voting - Copy.csv', '2025-07-15', 'completed', 0, 0, 'sharislavilla@gmail.com', '[]'),
(81, '1752567156_Submission of Student Details for Voting - Copy.csv', '2025-07-15', 'completed', 0, 0, 'sharislavilla@gmail.com', '[]'),
(82, '1752567173_Submission of Student Details for Voting - Copy.csv', '2025-07-15', 'completed', 0, 0, 'sharislavilla@gmail.com', '[]'),
(83, '1752567222_Submission of Student Details for Voting - Copy.csv', '2025-07-15', 'completed', 0, 0, 'sharislavilla@gmail.com', '[]'),
(84, '1752567290_bago Submission of Student Details for Voting.csv.xlsx', '2025-07-15', 'completed', 0, 0, 'sharislavilla@gmail.com', '[]'),
(85, '1752567321_bago Submission of Student Details for Voting.csv.xlsx', '2025-07-15', 'completed', 0, 0, 'sharislavilla@gmail.com', '[]'),
(86, '1752567341_bago Submission of Student Details for Voting.csv.xlsx', '2025-07-15', 'completed', 0, 0, 'sharislavilla@gmail.com', '[]'),
(87, '1752567365_Submission of Student Details for Voting - Copy.csv', '2025-07-15', 'completed', 1, 1, 'sharislavilla@gmail.com', '[]'),
(88, '1752567404_bago Submission of Student Details for Voting.csv.xlsx', '2025-07-15', 'completed', 0, 0, 'sharislavilla@gmail.com', '[]'),
(89, '1752567436_bago Submission of Student Details for Voting.csv.xlsx', '2025-07-15', 'completed', 0, 0, 'sharislavilla@gmail.com', '[]'),
(90, '1752567578_Submission of Student Details for Voting.csv', '2025-07-15', 'completed', 1, 1, 'sharislavilla@gmail.com', '[]'),
(91, '1752567646_Submission of Student Details for Voting.csv', '2025-07-15', 'completed', 2, 2, 'sharislavilla@gmail.com', '[]'),
(92, '1752567916_Submission of Student Details for Voting.csv', '2025-07-15', 'completed', 2, 2, 'sharislavilla@gmail.com', '[]'),
(93, '1753112068_Submission of Student Details for Voting.csv', '2025-07-21', 'completed', 1, 1, 'cs_adviser@wmsu.edu.ph', '[]'),
(94, '1753112180_1Submission of Student Details for Voting.csv.xlsx', '2025-07-21', 'completed', 1, 1, 'cs_adviser@wmsu.edu.ph', '[]'),
(95, '1753113918_1Submission of Student Details for Voting.csv.xlsx', '2025-07-21', 'completed', 1, 1, 'cs_adviser@wmsu.edu.ph', '[]'),
(96, '1753159327_bago Submission of Student Details for Voting.csv.xlsx', '2025-07-22', 'completed', 1, 1, 'cs_adviser@wmsu.edu.ph', '[]'),
(97, '1753159954_Submission of Student Details for Voting.csv', '2025-07-22', 'completed', 1, 1, 'cs_adviser@wmsu.edu.ph', '[]'),
(98, '1753159996_Submission of Student Details for Voting.csv', '2025-07-22', 'completed', 1, 1, 'cs_adviser@wmsu.edu.ph', '[]'),
(99, '1753160040_Submission of Student Details for Voting.csv', '2025-07-22', 'completed', 1, 1, 'cs_adviser@wmsu.edu.ph', '[]'),
(100, '1753160119_bago Submission of Student Details for Voting.csv.xlsx', '2025-07-22', 'completed', 1, 1, 'cs_adviser@wmsu.edu.ph', '[]'),
(101, '1753160207_bago Submission of Student Details for Voting.csv.xlsx', '2025-07-22', 'completed', 1, 1, 'cs_adviser@wmsu.edu.ph', '[]'),
(102, '1753160240_bago Submission of Student Details for Voting.csv.xlsx', '2025-07-22', 'completed', 2, 2, 'cs_adviser@wmsu.edu.ph', '[]'),
(103, '1753160898_bago Submission of Student Details for Voting.csv.xlsx', '2025-07-22', 'completed', 2, 2, 'cs_adviser@wmsu.edu.ph', '[]'),
(104, '1753160910_bago Submission of Student Details for Voting.csv.xlsx', '2025-07-22', 'completed', 2, 2, 'cs_adviser@wmsu.edu.ph', '[]'),
(105, '1753160971_bago Submission of Student Details for Voting.csv.xlsx', '2025-07-22', 'completed', 2, 2, 'cs_adviser@wmsu.edu.ph', '[]'),
(106, '1753160995_bago Submission of Student Details for Voting.csv.xlsx', '2025-07-22', 'completed', 2, 2, 'cs_adviser@wmsu.edu.ph', '[]'),
(107, '1753168582_Submission of Student Details for Voting.csv', '2025-07-22', 'completed', 2, 2, 'cs_adviser@wmsu.edu.ph', '[]');

-- --------------------------------------------------------

--
-- Table structure for table `archived_details_short`
--

CREATE TABLE `archived_details_short` (
  `id` int(11) NOT NULL,
  `election_id` int(10) NOT NULL,
  `candidates` int(11) NOT NULL,
  `precincts` int(11) NOT NULL,
  `voters` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `archived_details_short`
--

INSERT INTO `archived_details_short` (`id`, `election_id`, `candidates`, `precincts`, `voters`) VALUES
(8, 137, 4, 4, 13),
(9, 138, 4, 4, 13),
(10, 141, 4, 1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `archived_precincts`
--

CREATE TABLE `archived_precincts` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `longitude` int(11) NOT NULL,
  `latitude` int(11) NOT NULL,
  `location` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp(),
  `assignment_status` enum('assigned','unassigned') NOT NULL,
  `occupied_status` enum('occupied','unoccupied','') NOT NULL,
  `college` text NOT NULL,
  `department` text NOT NULL,
  `current_capacity` int(11) NOT NULL DEFAULT 0,
  `type` text NOT NULL,
  `status` text NOT NULL DEFAULT 'active',
  `college_external` text DEFAULT NULL,
  `election` text NOT NULL,
  `archived_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campuses`
--

CREATE TABLE `campuses` (
  `campus_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `campus_name` varchar(150) NOT NULL,
  `campus_location` varchar(255) DEFAULT NULL,
  `campus_type` varchar(255) NOT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `campuses`
--

INSERT INTO `campuses` (`campus_id`, `parent_id`, `campus_name`, `campus_location`, `campus_type`, `latitude`, `longitude`, `created_at`, `updated_at`) VALUES
(8, NULL, 'Main Campus', 'Main Campus', '', '6.913199', '122.062221', '2026-03-03 08:25:29', '2026-03-11 01:01:52'),
(10, NULL, 'WMSU ESU', 'WMSU ESU', '', '6.916287', '122.052187', '2026-03-03 08:26:12', '2026-03-09 16:32:24'),
(12, 10, 'ESU - Alicia', 'Zamboanga Sibugay', '', '6.911047', '122.058414', '2026-03-03 08:27:31', '2026-03-09 16:35:34'),
(13, 10, 'ESU - Aurora', 'Zamboanga del Sur', '', '6.913305', '122.062965', '2026-03-03 08:27:31', '2026-03-09 16:40:27'),
(14, 10, 'ESU - Curuan', 'Zamboanga City', '', '6.91339', '122.063695', '2026-03-03 08:27:31', '2026-03-09 16:40:30'),
(15, 10, 'ESU - Diplahan', 'Zamboanga Sibugay', '', '6.914072', '122.058929', '2026-03-03 08:27:31', '2026-03-09 16:35:37'),
(16, 10, 'ESU - Imelda', 'Zamboanga Sibugay', '', '6.915691', '122.05996', '2026-03-03 08:27:31', '2026-03-09 16:40:16'),
(17, 10, 'ESU - Ipil', 'Zamboanga Sibugay', '', '6.914967', '122.060432', '2026-03-03 08:27:31', '2026-03-09 16:40:35'),
(18, 10, 'ESU - Mabuhay', 'Zamboanga Sibugay', '', '6.913646', '122.060775', '2026-03-03 08:27:31', '2026-03-09 16:40:40'),
(19, 10, 'ESU - Malangas', 'Zamboanga Sibugay', '', '6.914413', '122.060818', '2026-03-03 08:27:31', '2026-03-09 16:40:43'),
(20, 10, 'ESU - Molave', 'Zamboanga del Sur', '', '6.913476', '122.063352', '2026-03-03 08:27:31', '2026-03-09 16:40:50'),
(21, 10, 'ESU - Naga', 'Zamboanga Sibugay', '', '6.913305', '122.06069', '2026-03-03 08:27:31', '2026-03-09 16:40:55'),
(22, 10, 'ESU - Olutanga', 'Zamboanga Sibugay', '', '6.914072', '122.06172', '2026-03-03 08:27:31', '2026-03-09 16:41:00'),
(23, 10, 'ESU - Pagadian City', 'Zamboanga del Sur', '', '6.913007', '122.062665', '2026-03-03 08:27:31', '2026-03-09 16:41:05'),
(24, 10, 'ESU - Siay', 'Zamboanga Sibugay', '', '6.915734', '122.061591', '2026-03-03 08:27:31', '2026-03-09 16:35:39'),
(25, 10, 'ESU - Tungawan', 'Zamboanga Sibugay', '', '6.913902', '122.062536', '2026-03-03 08:27:31', '2026-03-09 16:41:11');

-- --------------------------------------------------------

--
-- Table structure for table `candidacy`
--

CREATE TABLE `candidacy` (
  `id` int(11) NOT NULL,
  `election_id` int(11) NOT NULL,
  `start_period` datetime NOT NULL,
  `end_period` datetime NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidacy`
--

INSERT INTO `candidacy` (`id`, `election_id`, `start_period`, `end_period`, `status`, `created_at`, `updated_at`) VALUES
(80, 137, '2026-04-03 11:37:00', '2026-04-10 11:37:00', 'Published', '2026-03-27 03:39:05', '2026-03-27 11:39:05'),
(81, 138, '2026-04-03 14:06:00', '2026-04-10 14:06:00', 'Published', '2026-03-27 06:07:03', '2026-03-27 14:07:03'),
(82, 141, '2026-03-28 07:00:00', '2026-03-28 16:00:00', 'Published', '2026-03-27 21:20:53', '2026-03-28 05:20:53'),
(83, 142, '2026-04-05 09:14:00', '2026-04-12 09:14:00', 'Ongoing', '2026-03-29 01:16:42', '2026-03-29 09:16:42');

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `id` int(11) NOT NULL,
  `form_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `status` text NOT NULL DEFAULT 'pending',
  `admin_config` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`id`, `form_id`, `created_at`, `status`, `admin_config`) VALUES
(413, 90, '2026-03-27 13:27:50', 'accepted', 1),
(414, 90, '2026-03-27 13:29:15', 'accepted', 1),
(415, 90, '2026-03-27 13:29:48', 'accepted', 1),
(416, 90, '2026-03-27 13:30:14', 'accepted', 1),
(417, 91, '2026-03-27 14:07:45', 'accepted', 1),
(418, 91, '2026-03-27 14:08:59', 'accepted', 1),
(419, 91, '2026-03-27 14:10:24', 'accepted', 1),
(420, 91, '2026-03-27 14:10:35', 'accepted', 1),
(421, 92, '2026-03-28 05:22:36', 'accepted', 1),
(422, 92, '2026-03-28 05:23:26', 'accepted', 1),
(423, 92, '2026-03-28 05:33:59', 'accepted', 1),
(424, 92, '2026-03-28 05:34:16', 'accepted', 1);

-- --------------------------------------------------------

--
-- Table structure for table `candidate_files`
--

CREATE TABLE `candidate_files` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) DEFAULT NULL,
  `field_id` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidate_files`
--

INSERT INTO `candidate_files` (`id`, `candidate_id`, `field_id`, `file_path`, `created_at`) VALUES
(611, 413, 474, 'candidate_69c61556d606f0.91663103.jpg', '2026-03-27 13:27:50'),
(612, 414, 474, 'candidate_69c615ab781842.81116239.jpg', '2026-03-27 13:29:15'),
(613, 415, 474, 'candidate_69c615cc8ae5b7.35492217.jpg', '2026-03-27 13:29:48'),
(614, 416, 474, 'candidate_69c615e6887989.20950809.jpg', '2026-03-27 13:30:14'),
(615, 417, 479, 'candidate_69c61eb1b36fd4.37477656.jpg', '2026-03-27 14:07:45'),
(616, 418, 479, 'candidate_69c61efbddc0c2.43545176.jpg', '2026-03-27 14:08:59'),
(617, 419, 479, 'candidate_69c61f50c61c25.41664633.jpg', '2026-03-27 14:10:24'),
(618, 420, 479, 'candidate_69c61f5b1f4e56.26639346.jpg', '2026-03-27 14:10:35'),
(619, 421, 484, 'candidate_69c6f51ca5b1a9.41740238.png', '2026-03-28 05:22:36'),
(620, 422, 484, 'candidate_69c6f54ef38b65.51792088.jpg', '2026-03-28 05:23:26'),
(621, 423, 484, 'candidate_69c6f7c7682da3.57143375.jpg', '2026-03-28 05:33:59'),
(622, 424, 484, 'candidate_69c6f7d8ac5316.18057821.jpg', '2026-03-28 05:34:16');

-- --------------------------------------------------------

--
-- Table structure for table `candidate_responses`
--

CREATE TABLE `candidate_responses` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) DEFAULT NULL,
  `field_id` int(11) DEFAULT NULL,
  `value` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidate_responses`
--

INSERT INTO `candidate_responses` (`id`, `candidate_id`, `field_id`, `value`, `created_at`, `updated_at`) VALUES
(1615, 413, 470, '[TESTER] Test [TESTER] from [TESTER] Scratch -1', '2026-03-27 13:27:50', '2026-03-27 13:27:50'),
(1616, 413, 471, '2020-01520', '2026-03-27 13:27:50', '2026-03-27 13:27:50'),
(1617, 413, 472, 'Party A', '2026-03-27 13:27:50', '2026-03-27 13:27:50'),
(1618, 413, 473, 'President', '2026-03-27 13:27:50', '2026-03-27 13:27:50'),
(1619, 414, 470, 'TESTER TESTER TESTER', '2026-03-27 13:29:15', '2026-03-27 13:29:15'),
(1620, 414, 471, '2020-01524', '2026-03-27 13:29:15', '2026-03-27 13:29:15'),
(1621, 414, 472, 'Party A', '2026-03-27 13:29:15', '2026-03-27 13:29:15'),
(1622, 414, 473, 'Mayor', '2026-03-27 13:29:15', '2026-03-27 13:29:15'),
(1623, 415, 470, '[TESTER] Test [TESTER] from [TESTER] Scratch 1', '2026-03-27 13:29:48', '2026-03-27 13:29:48'),
(1624, 415, 471, '2020-01521', '2026-03-27 13:29:48', '2026-03-27 13:29:48'),
(1625, 415, 472, 'Party B', '2026-03-27 13:29:48', '2026-03-27 13:29:48'),
(1626, 415, 473, 'President', '2026-03-27 13:29:48', '2026-03-27 13:29:48'),
(1627, 416, 470, '[TESTER] Test [TESTER] from [TESTER] Scratch 2', '2026-03-27 13:30:14', '2026-03-27 13:30:14'),
(1628, 416, 471, '2020-01525', '2026-03-27 13:30:14', '2026-03-27 13:30:14'),
(1629, 416, 472, 'Party B', '2026-03-27 13:30:14', '2026-03-27 13:30:14'),
(1630, 416, 473, 'Mayor', '2026-03-27 13:30:14', '2026-03-27 13:30:14'),
(1631, 417, 475, '[TESTER] Test [TESTER] from [TESTER] Scratch -1', '2026-03-27 14:07:45', '2026-03-27 14:07:45'),
(1632, 417, 476, '2020-01520', '2026-03-27 14:07:45', '2026-03-27 14:07:45'),
(1633, 417, 477, 'A', '2026-03-27 14:07:45', '2026-03-27 14:07:45'),
(1634, 417, 478, 'President', '2026-03-27 14:07:45', '2026-03-27 14:07:45'),
(1635, 418, 475, 'TESTER TESTER TESTER', '2026-03-27 14:08:59', '2026-03-27 14:08:59'),
(1636, 418, 476, '2020-01524', '2026-03-27 14:08:59', '2026-03-27 14:08:59'),
(1637, 418, 477, 'A', '2026-03-27 14:08:59', '2026-03-27 14:08:59'),
(1638, 418, 478, 'Mayor', '2026-03-27 14:08:59', '2026-03-27 14:08:59'),
(1639, 419, 475, '[TESTER] Test [TESTER] from [TESTER] Scratch 1', '2026-03-27 14:10:24', '2026-03-27 14:10:24'),
(1640, 419, 476, '2020-01521', '2026-03-27 14:10:24', '2026-03-27 14:10:24'),
(1641, 419, 477, 'B', '2026-03-27 14:10:24', '2026-03-27 14:10:24'),
(1642, 419, 478, 'President', '2026-03-27 14:10:24', '2026-03-27 14:10:24'),
(1643, 420, 475, '[TESTER] Test [TESTER] from [TESTER] Scratch 2', '2026-03-27 14:10:35', '2026-03-27 14:10:35'),
(1644, 420, 476, '2020-01525', '2026-03-27 14:10:35', '2026-03-27 14:10:35'),
(1645, 420, 477, 'B', '2026-03-27 14:10:35', '2026-03-27 14:10:35'),
(1646, 420, 478, 'Mayor', '2026-03-27 14:10:35', '2026-03-27 14:10:35'),
(1647, 421, 480, 'TESTER TESTER TESTER', '2026-03-28 05:22:36', '2026-03-28 05:22:36'),
(1648, 421, 481, '2020-01524', '2026-03-28 05:22:36', '2026-03-28 05:22:36'),
(1649, 421, 482, 'Party A', '2026-03-28 05:22:36', '2026-03-28 05:22:36'),
(1650, 421, 483, 'President', '2026-03-28 05:22:36', '2026-03-28 05:22:36'),
(1651, 422, 480, '[TESTER] Test [TESTER] from [TESTER] Scratch -1', '2026-03-28 05:23:26', '2026-03-28 05:23:26'),
(1652, 422, 481, '2020-01520', '2026-03-28 05:23:26', '2026-03-28 05:23:26'),
(1653, 422, 482, 'Party B', '2026-03-28 05:23:26', '2026-03-28 05:23:26'),
(1654, 422, 483, 'President', '2026-03-28 05:23:26', '2026-03-28 05:23:26'),
(1655, 423, 480, '[TESTER] Test [TESTER] from [TESTER] Scratch 1', '2026-03-28 05:33:59', '2026-03-28 05:33:59'),
(1656, 423, 481, '2020-01521', '2026-03-28 05:33:59', '2026-03-28 05:33:59'),
(1657, 423, 482, 'Party A', '2026-03-28 05:33:59', '2026-03-28 05:33:59'),
(1658, 423, 483, 'Mayor', '2026-03-28 05:33:59', '2026-03-28 05:33:59'),
(1659, 424, 480, '[TESTER] Test [TESTER] from [TESTER] Scratch 2', '2026-03-28 05:34:16', '2026-03-28 05:34:16'),
(1660, 424, 481, '2020-01525', '2026-03-28 05:34:16', '2026-03-28 05:34:16'),
(1661, 424, 482, 'Party B', '2026-03-28 05:34:16', '2026-03-28 05:34:16'),
(1662, 424, 483, 'Mayor', '2026-03-28 05:34:16', '2026-03-28 05:34:16');

-- --------------------------------------------------------

--
-- Table structure for table `colleges`
--

CREATE TABLE `colleges` (
  `college_id` int(11) NOT NULL,
  `college_name` varchar(255) NOT NULL,
  `college_abbreviation` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_on` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `colleges`
--

INSERT INTO `colleges` (`college_id`, `college_name`, `college_abbreviation`, `created_at`, `archived_on`) VALUES
(19, 'College of Law', 'CL', '2026-03-03 08:55:53', '0000-00-00 00:00:00'),
(20, 'College of Liberal Arts', 'CLA', '2026-03-03 08:55:53', '0000-00-00 00:00:00'),
(21, 'College of Agriculture', 'CA', '2026-03-03 08:55:53', '0000-00-00 00:00:00'),
(22, 'College of Computing Studies', 'CCS', '2026-03-03 08:55:53', '0000-00-00 00:00:00'),
(23, 'College of Architecture', 'CArch', '2026-03-03 08:55:53', '0000-00-00 00:00:00'),
(24, 'College of Nursing', 'CN', '2026-03-03 08:55:53', '0000-00-00 00:00:00'),
(25, 'College of Asian & Islamic Studies', 'CAIS', '2026-03-03 08:55:53', '0000-00-00 00:00:00'),
(26, 'College of Home Economics', 'CHE', '2026-03-03 08:55:53', '0000-00-00 00:00:00'),
(27, 'College of Public Administration & Development Studies', 'CPADS', '2026-03-03 08:55:53', '0000-00-00 00:00:00'),
(28, 'College of Engineering', 'CE', '2026-03-03 08:55:53', '0000-00-00 00:00:00'),
(29, 'College of Medicine', 'CM', '2026-03-03 08:55:53', '0000-00-00 00:00:00'),
(30, 'College of Criminology', 'CCrim', '2026-03-03 08:55:53', '0000-00-00 00:00:00'),
(31, 'College of Sports Science & Physical Education', 'CCSPE', '2026-03-03 08:55:53', '0000-00-00 00:00:00'),
(32, 'College of Science & Mathematics', 'CSM', '2026-03-03 08:55:53', '0000-00-00 00:00:00'),
(33, 'College of Social Work & Community Development', 'CSWCD', '2026-03-03 08:55:53', '0000-00-00 00:00:00'),
(34, 'College of Teacher Education', 'CTE', '2026-03-03 08:55:53', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `college_coordinates`
--

CREATE TABLE `college_coordinates` (
  `coordinate_id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `campus_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `college_coordinates`
--

INSERT INTO `college_coordinates` (`coordinate_id`, `college_id`, `campus_id`, `latitude`, `longitude`, `created_at`) VALUES
(11, 21, 12, 6.91130300, 122.06621900, '2026-03-09 19:37:44'),
(20, 21, 8, 6.91319900, 122.06222100, '2026-03-11 05:36:11');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(10) UNSIGNED NOT NULL,
  `college_id` int(11) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `course_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `college_id`, `course_name`, `course_code`, `created_at`, `updated_at`) VALUES
(3, 19, 'Bachelor of Science in Law', 'Law', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(4, 20, 'Bachelor of Science in Accountancy', 'Accountancy', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(5, 20, 'Bachelor of Arts in History', 'History', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(6, 20, 'Bachelor of Arts in English', 'English', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(7, 20, 'Bachelor of Arts in Political Science', 'Political Science', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(8, 20, 'Bachelor of Arts in Mass Communication', 'Mass Communication', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(9, 20, 'Bachelor of Science in Economics', 'Economics', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(10, 20, 'Bachelor of Science in Psychology', 'Psychology', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(11, 21, 'Bachelor of Science in Crop Science', 'Crop Science', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(12, 21, 'Bachelor of Science in Animal Science', 'Animal Science', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(13, 21, 'Bachelor of Science in Food Technology', 'Food Technology', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(14, 21, 'Bachelor of Science in Agribusiness', 'Agribusiness', '2026-03-03 22:43:45', '2026-03-10 17:24:24'),
(15, 21, 'Bachelor of Science in Agricultural Technology', 'Agricultural Technology', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(16, 21, 'Bachelor of Science in Agronomy', 'Agronomy', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(17, 22, 'Bachelor of Science in Computer Science', 'Computer Science', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(18, 22, 'Bachelor of Science in Information Technology', 'Information Technology', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(19, 22, 'Associate in Computer Technology', 'Computer Technology', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(20, 23, 'Bachelor of Science in Architecture', 'Architecture', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(21, 24, 'Bachelor of Science in Nursing', 'Nursing', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(22, 25, 'Bachelor of Science in Asian Studies', 'Asian Studies', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(23, 25, 'Bachelor of Science in Islamic Studies', 'Islamic Studies', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(24, 26, 'Bachelor of Science in Home Economics', 'Home Economics', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(25, 26, 'Bachelor of Science in Nutrition and Dietetics', 'Nutrition and Dietetics', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(26, 26, 'Bachelor of Science in Hospitality Management', 'Hospitality Management', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(27, 27, 'Bachelor of Science in Public Administration', 'Public Administration', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(28, 28, 'Bachelor of Science in Civil Engineering', 'Civil Engineering', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(29, 28, 'Bachelor of Science in Computer Engineering', 'Computer Engineering', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(30, 28, 'Bachelor of Science in Electrical Engineering', 'Electrical Engineering', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(31, 28, 'Bachelor of Science in Environmental Engineering', 'Environmental Engineering', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(32, 28, 'Bachelor of Science in Geodetic Engineering', 'Geodetic Engineering', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(33, 28, 'Bachelor of Science in Industrial Engineering', 'Industrial Engineering', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(34, 28, 'Bachelor of Science in Mechanical Engineering', 'Mechanical Engineering', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(35, 28, 'Bachelor of Science in Sanitary Engineering', 'Sanitary Engineering', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(36, 29, 'Bachelor of Science in Medicine', 'Medicine', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(37, 30, 'Bachelor of Science in Criminology', 'Criminology', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(38, 31, 'Bachelor of Science in Physical Education', 'Physical Education', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(39, 32, 'Bachelor of Science in Biology', 'Biology', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(40, 32, 'Bachelor of Science in Chemistry', 'Chemistry', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(41, 32, 'Bachelor of Science in Mathematics', 'Mathematics', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(42, 32, 'Bachelor of Science in Physics', 'Physics', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(43, 32, 'Bachelor of Science in Statistics', 'Statistics', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(44, 33, 'Bachelor of Science in Social Work', 'Social Work', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(45, 33, 'Bachelor of Science in Community Development', 'Community Development', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(46, 34, 'Bachelor of Science Culture and Arts Education', 'Culture and Arts Education', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(47, 34, 'Bachelor of Science in Early Childhood Education', 'Early Childhood Education', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(48, 34, 'Bachelor of Science in Elementary Education', 'Elementary Education', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(49, 34, 'Bachelor of Science in Secondary Education', 'Secondary Education', '2026-03-03 22:43:45', '2026-03-03 22:43:45'),
(72, 25, 'Bachelor of Science in Testing', 'Testing', '2026-03-10 16:53:38', '2026-03-10 16:53:38');

-- --------------------------------------------------------

--
-- Table structure for table `course_year_levels`
--

CREATE TABLE `course_year_levels` (
  `id` int(11) NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `year_level_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `course_year_levels`
--

INSERT INTO `course_year_levels` (`id`, `course_id`, `year_level_id`) VALUES
(1, 14, 1),
(2, 14, 2),
(3, 14, 3),
(4, 14, 4),
(5, 14, 5),
(6, 17, 1),
(7, 17, 2),
(8, 17, 3),
(9, 17, 4);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `college_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `college_id`, `created_at`) VALUES
(144, 'Law', 19, '2026-03-03 09:06:25'),
(145, 'Accountancy', 20, '2026-03-03 09:06:25'),
(146, 'History', 20, '2026-03-03 09:06:25'),
(147, 'English', 20, '2026-03-03 09:06:25'),
(148, 'Political Science', 20, '2026-03-03 09:06:25'),
(149, 'Mass Communication', 20, '2026-03-03 09:06:25'),
(150, 'Economics', 20, '2026-03-03 09:06:25'),
(151, 'Psychology', 20, '2026-03-03 09:06:25'),
(152, 'Crop Science', 21, '2026-03-03 09:06:25'),
(153, 'Animal Science', 21, '2026-03-03 09:06:25'),
(154, 'Food Technology', 21, '2026-03-03 09:06:25'),
(155, 'Agribusiness', 21, '2026-03-03 09:06:25'),
(156, 'Agricultural Technology', 21, '2026-03-03 09:06:25'),
(157, 'Agronomy', 21, '2026-03-03 09:06:25'),
(158, 'Computer Science', 22, '2026-03-03 09:06:25'),
(159, 'Information Technology', 22, '2026-03-03 09:06:25'),
(160, 'Computer Technology', 22, '2026-03-03 09:06:25'),
(161, 'Architecture', 23, '2026-03-03 09:06:25'),
(162, 'Nursing', 24, '2026-03-03 09:06:25'),
(163, 'Asian Studies', 25, '2026-03-03 09:06:25'),
(164, 'Islamic Studies', 25, '2026-03-03 09:06:25'),
(165, 'Home Economics', 26, '2026-03-03 09:06:25'),
(166, 'Nutrition and Dietetics', 26, '2026-03-03 09:06:25'),
(167, 'Hospitality Management', 26, '2026-03-03 09:06:25'),
(168, 'Public Administration', 27, '2026-03-03 09:06:25'),
(169, 'Civil Engineering', 28, '2026-03-03 09:06:25'),
(170, 'Computer Engineering', 28, '2026-03-03 09:06:25'),
(171, 'Electrical Engineering', 28, '2026-03-03 09:06:25'),
(172, 'Environmental Engineering', 28, '2026-03-03 09:06:25'),
(173, 'Geodetic Engineering', 28, '2026-03-03 09:06:25'),
(174, 'Industrial Engineering', 28, '2026-03-03 09:06:25'),
(175, 'Mechanical Engineering', 28, '2026-03-03 09:06:25'),
(176, 'Sanitary Engineering', 28, '2026-03-03 09:06:25'),
(177, 'Medicine', 29, '2026-03-03 09:06:25'),
(178, 'Criminology', 30, '2026-03-03 09:06:25'),
(179, 'Physical Education', 31, '2026-03-03 09:06:25'),
(180, 'Biology', 32, '2026-03-03 09:06:25'),
(181, 'Chemistry', 32, '2026-03-03 09:06:25'),
(182, 'Mathematics', 32, '2026-03-03 09:06:25'),
(183, 'Physics', 32, '2026-03-03 09:06:25'),
(184, 'Statistics', 32, '2026-03-03 09:06:25'),
(185, 'Social Work', 33, '2026-03-03 09:06:25'),
(186, 'Community Development', 33, '2026-03-03 09:06:25'),
(187, 'Culture and Arts Education', 34, '2026-03-03 09:06:25'),
(188, 'Early Childhood Education', 34, '2026-03-03 09:06:25'),
(189, 'Elementary Education', 34, '2026-03-03 09:06:25'),
(190, 'Secondary Education', 34, '2026-03-03 09:06:25');

-- --------------------------------------------------------

--
-- Table structure for table `elections`
--

CREATE TABLE `elections` (
  `id` int(11) NOT NULL,
  `election_name` varchar(255) NOT NULL,
  `academic_year_id` int(100) NOT NULL,
  `start_period` datetime NOT NULL,
  `end_period` datetime NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elections`
--

INSERT INTO `elections` (`id`, `election_name`, `academic_year_id`, `start_period`, `end_period`, `status`, `created_at`, `updated_at`) VALUES
(137, 'Test', 54, '2026-03-27 11:37:00', '2026-05-31 11:37:00', 'Published', '2026-03-27 03:37:56', '2026-03-27 11:37:56'),
(138, 'New', 55, '2026-03-27 14:06:00', '2026-04-30 14:06:00', 'Published', '2026-03-27 06:06:24', '2026-03-27 14:06:24'),
(141, 'Test', 56, '2026-03-27 07:00:00', '2026-06-30 16:00:00', 'Published', '2026-03-27 21:16:15', '2026-03-28 05:16:15'),
(142, 'Newest', 57, '2026-03-29 09:14:00', '2026-05-31 09:14:00', 'Ongoing', '2026-03-29 01:14:54', '2026-03-29 09:14:54');

-- --------------------------------------------------------

--
-- Table structure for table `email`
--

CREATE TABLE `email` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `app_password` varchar(100) NOT NULL,
  `capacity` varchar(50) NOT NULL DEFAULT '0',
  `adviser_id` int(11) DEFAULT NULL,
  `status` text NOT NULL DEFAULT 'available',
  `date_added` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email`
--

INSERT INTO `email` (`id`, `email`, `app_password`, `capacity`, `adviser_id`, `status`, `date_added`) VALUES
(20, 'sharislavilla@gmail.com', 'dqyn yaqq icbi geej', '0', 22, 'taken', '2026-03-29'),
(22, 'ninilunagranger@gmail.com', 'pfib zpnz tynl tfui', '0', 21, 'taken', '2026-03-29'),
(23, 'sarajanetoledo4@gmail.com', 'dmfs qpht xowt opgc', '0', 20, 'taken', '2026-03-29'),
(24, 'hermosakristine408@gmail.com', 'vnfv qgmq ndcu irjy', '0', 18, 'taken', '2026-03-29'),
(25, 'mrronronweasley@gmail.com', 'zevm vqit hdtg yugi', '0', NULL, 'available', '2026-03-29'),
(26, 'joes66170@gmail.com', 'rppt nhzr brtu pyje', '0', 23, 'taken', '2026-03-29'),
(27, 'mistytantelope@gmail.com', 'cyka sqhp ifrk yyfr', '0', NULL, 'taken', '2026-03-29'),
(28, 'jd5502546@gmail.com', 'dgxl odhm sgka swjv', '0', 24, 'taken', '2026-03-29'),
(29, 'dracomalfoy1234566@gmail.com', 'eluz kwve txip vcul', '0', NULL, 'taken', '2026-03-29'),
(33, 'antonetqt3.14@gmail.com', 'rbwe uhtl bwlt trey', '0', NULL, 'taken', '2026-03-29'),
(34, 'aleviackermannn@gmail.com', 'ntpa aoeb kfsz qahm', '0', NULL, 'taken', '2026-03-29');

-- --------------------------------------------------------

--
-- Table structure for table `email_errors`
--

CREATE TABLE `email_errors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `adviser_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `error_message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_role_log`
--

CREATE TABLE `email_role_log` (
  `id` int(11) NOT NULL,
  `student_id` text NOT NULL,
  `status` text NOT NULL,
  `count` int(11) NOT NULL DEFAULT 0,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  `voting_period_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `email_role_log`
--

INSERT INTO `email_role_log` (`id`, `student_id`, `status`, `count`, `sent_at`, `voting_period_id`) VALUES
(1, '2020-01524', 'sent', 11, '2026-03-16 12:51:59', 0),
(2, '2020-01525', 'sent', 5, '2026-03-16 12:51:59', 0),
(3, '2020-01526', 'sent', 1, '2026-03-16 12:51:59', 0),
(4, '2020-01528', 'sent', 1, '2026-03-16 12:51:59', 0),
(5, '2020-01520', 'sent', 1, '2026-03-16 12:51:59', 0),
(6, '2020-01521', 'sent', 1, '2026-03-16 12:51:59', 0),
(7, '2021-00168', 'sent', 1, '2026-03-16 12:51:59', 0),
(8, '2021-01252', 'sent', 1, '2026-03-16 12:51:59', 0),
(9, '1997-12345', 'sent', 1, '2026-03-17 13:16:57', 0),
(10, '1999-54321', 'sent', 1, '2026-03-17 13:17:07', 0),
(11, '1999-12134', 'sent', 1, '2026-03-17 13:56:19', 0);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `event_title` varchar(255) NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `event_details` text NOT NULL,
  `registration_enabled` tinyint(1) DEFAULT 0,
  `registration_start` datetime DEFAULT NULL,
  `registration_deadline` datetime DEFAULT NULL,
  `status` text DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `views` int(11) NOT NULL DEFAULT 0,
  `author` text DEFAULT '\'Western Mindanao State University\'',
  `candidacy` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `event_title`, `cover_image`, `event_details`, `registration_enabled`, `registration_start`, `registration_deadline`, `status`, `created_at`, `views`, `author`, `candidacy`) VALUES
(131, 'Test', 'cover_69c5fbe905dd3_test.png', '<p>Test</p>', 0, '2026-04-03 11:37:00', '2026-04-10 11:37:00', 'published', '2026-03-27 03:39:21', 3, NULL, 137),
(132, 'New', 'cover_69c61ea35324b_images.jpg', '<p>zxc</p>', 0, '2026-04-03 14:06:00', '2026-04-10 14:06:00', 'published', '2026-03-27 06:07:31', 0, NULL, 138),
(133, 'Event Title New', 'cover_69c6f4cc6fa07_02843bf1df0668a0219a5b388911f56b.jpg', '<p>Event</p>', 0, '2026-03-28 07:00:00', '2026-03-28 16:00:00', 'published', '2026-03-27 21:21:16', 0, NULL, 141),
(134, 'Eventer', 'cover_69c87d91eb04f_student affairs.png', '<p>eventer</p>', 1, '2026-04-05 09:14:00', '2026-04-12 09:14:00', 'published', '2026-03-29 01:17:05', 0, NULL, 142);

-- --------------------------------------------------------

--
-- Table structure for table `form_fields`
--

CREATE TABLE `form_fields` (
  `id` int(11) NOT NULL,
  `form_id` int(11) DEFAULT NULL,
  `field_name` varchar(255) DEFAULT NULL,
  `field_type` text DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `is_default` tinyint(1) DEFAULT 0,
  `options` text DEFAULT NULL,
  `template_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_fields`
--

INSERT INTO `form_fields` (`id`, `form_id`, `field_name`, `field_type`, `is_required`, `is_default`, `options`, `template_path`, `created_at`) VALUES
(470, 90, 'full_name', 'text', 0, 1, NULL, NULL, '2026-03-27 11:39:21'),
(471, 90, 'student_id', 'text', 0, 1, NULL, NULL, '2026-03-27 11:39:21'),
(472, 90, 'party', 'dropdown', 0, 1, NULL, NULL, '2026-03-27 11:39:21'),
(473, 90, 'position', 'dropdown', 0, 1, NULL, NULL, '2026-03-27 11:39:21'),
(474, 90, 'picture', 'file', 0, 1, NULL, NULL, '2026-03-27 11:39:21'),
(475, 91, 'full_name', 'text', 0, 1, NULL, NULL, '2026-03-27 14:07:31'),
(476, 91, 'student_id', 'text', 0, 1, NULL, NULL, '2026-03-27 14:07:31'),
(477, 91, 'party', 'dropdown', 0, 1, NULL, NULL, '2026-03-27 14:07:31'),
(478, 91, 'position', 'dropdown', 0, 1, NULL, NULL, '2026-03-27 14:07:31'),
(479, 91, 'picture', 'file', 0, 1, NULL, NULL, '2026-03-27 14:07:31'),
(480, 92, 'full_name', 'text', 0, 1, NULL, NULL, '2026-03-28 05:21:16'),
(481, 92, 'student_id', 'text', 0, 1, NULL, NULL, '2026-03-28 05:21:16'),
(482, 92, 'party', 'dropdown', 0, 1, NULL, NULL, '2026-03-28 05:21:16'),
(483, 92, 'position', 'dropdown', 0, 1, NULL, NULL, '2026-03-28 05:21:16'),
(484, 92, 'picture', 'file', 0, 1, NULL, NULL, '2026-03-28 05:21:16'),
(485, 93, 'full_name', 'text', 0, 1, NULL, NULL, '2026-03-29 09:17:05'),
(486, 93, 'student_id', 'text', 0, 1, NULL, NULL, '2026-03-29 09:17:05'),
(487, 93, 'party', 'dropdown', 0, 1, NULL, NULL, '2026-03-29 09:17:05'),
(488, 93, 'position', 'dropdown', 0, 1, NULL, NULL, '2026-03-29 09:17:05'),
(489, 93, 'picture', 'file', 0, 1, NULL, NULL, '2026-03-29 09:17:05');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `majors`
--

CREATE TABLE `majors` (
  `major_id` int(11) NOT NULL,
  `major_name` varchar(255) NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `majors`
--

INSERT INTO `majors` (`major_id`, `major_name`, `course_id`, `created_at`) VALUES
(7, 'Software Engineer', 17, '2026-03-11 05:52:58');

-- --------------------------------------------------------

--
-- Table structure for table `major_year_levels`
--

CREATE TABLE `major_year_levels` (
  `id` int(11) NOT NULL,
  `major_id` int(11) NOT NULL,
  `year_level_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `major_year_levels`
--

INSERT INTO `major_year_levels` (`id`, `major_id`, `year_level_id`) VALUES
(67, 7, 1),
(68, 7, 2),
(69, 7, 3),
(70, 7, 4),
(76, 8, 1),
(77, 8, 2),
(78, 8, 3),
(79, 8, 4),
(80, 8, 5);

-- --------------------------------------------------------

--
-- Table structure for table `moderators`
--

CREATE TABLE `moderators` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `college` int(11) DEFAULT NULL,
  `department` int(11) DEFAULT NULL,
  `major` int(11) DEFAULT NULL,
  `precinct` int(11) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `moderators`
--

INSERT INTO `moderators` (`id`, `name`, `email`, `password`, `gender`, `college`, `department`, `major`, `precinct`, `status`, `created_at`, `updated_at`) VALUES
(148, 'wmsu ccs modzxczxc', 'wmsuccsmod@gmail.com', '$2y$10$alXxS1Y.kSFa.ACmWGDXe.FCtvLW4O88F8aqYtEeJwKQ2YMd9jeSK', 'Male', 22, 159, NULL, 218, 'active', '2026-03-18 06:33:52', '2026-03-27 13:57:31'),
(149, 'wmsu it mod', 'wmsuitmod@gmail.com', '$2y$10$Qc.5Yapl1RqLYwNxiM2gyud1419Im63Q01rj7f/8IbZ93YionsG..', 'Male', 22, 158, NULL, 217, 'active', '2026-03-18 06:35:37', '2026-03-28 05:37:24'),
(150, 'Wmsu Moderator New', 'wmsumoderatornew@gmail.com', '$2y$10$Q0R7oxe8hcM1Rupmku8njemoUadv7DH/r8smrvZFmpbeyIfI9RZja', 'Male', 28, 170, NULL, 220, 'active', '2026-03-27 13:58:09', '2026-03-27 13:58:09'),
(151, 'wmsu csmod newer', 'wmsumodmod@gmail.com', '$2y$10$eixVbTj2xcJgqgqkhEdMpeaWEVWTVc7fAA4wCyz6Wr8OP5FcrTztC', 'Male', 22, 158, NULL, 219, 'active', '2026-03-27 13:58:48', '2026-03-27 13:58:48');

-- --------------------------------------------------------

--
-- Table structure for table `parties`
--

CREATE TABLE `parties` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `election_id` int(255) NOT NULL,
  `party_image` text NOT NULL,
  `platforms` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parties`
--

INSERT INTO `parties` (`id`, `name`, `election_id`, `party_image`, `platforms`, `created_at`, `updated_at`, `status`) VALUES
(190, 'Party A', 137, 'party_69c5fbabdc8ae0.44215529.png', '<p>Test</p>', '2026-03-27 03:38:19', '2026-03-27 03:38:19', 'Approved'),
(191, 'Party B', 137, 'party_69c5fbc205fa57.73521270.png', '<p>Test</p>', '2026-03-27 03:38:42', '2026-03-27 03:38:42', 'Approved'),
(192, 'A', 138, 'party_69c61e6cc45ab1.56310947.png', '<p>hey</p>', '2026-03-27 06:06:36', '2026-03-27 06:06:36', 'Approved'),
(193, 'B', 138, 'party_69c61e74314578.31956546.png', '<p>b</p>', '2026-03-27 06:06:44', '2026-03-27 06:06:44', 'Approved'),
(196, 'Party A', 141, 'party_69c6f3abed5c79.02099543.png', '<p>A</p>', '2026-03-27 21:16:27', '2026-03-27 21:16:27', 'Approved'),
(197, 'Party B', 141, 'party_69c6f3b7c2c8f5.72343981.png', '<p>Party Platforms</p>', '2026-03-27 21:16:39', '2026-03-27 21:16:39', 'Approved'),
(198, 'A', 142, 'party_69c87d2795d011.00024524.png', '<p>A</p>', '2026-03-29 01:15:19', '2026-03-29 01:15:19', 'Approved'),
(199, 'B', 142, 'party_69c87d2f7035c3.05231159.png', '<p>B</p>', '2026-03-29 01:15:27', '2026-03-29 01:15:27', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(11, 'xt202001524@wmsu.edu.ph', 'b7b0aded1a357ea2ae1083363fad987d40fce00581112fee1099a16ebcc33815', '2026-02-16 00:09:24', '2025-07-25 03:55:18'),
(12, 'ahmadaquino.2002@gmail.com', '78a9bd6da0df854739426cb698021a5f14bf7f043151af33223606322f6776ea', '2025-07-25 06:56:21', '2025-07-25 03:56:21');

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `party` varchar(100) NOT NULL,
  `level` varchar(50) NOT NULL,
  `election_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `allowed_colleges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_colleges`)),
  `allowed_departments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_departments`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`id`, `name`, `party`, `level`, `election_id`, `created_at`, `allowed_colleges`, `allowed_departments`) VALUES
(275, 'President', 'A', 'Central', 138, '2026-03-27 14:06:49', NULL, NULL),
(276, 'President', 'B', 'Central', 138, '2026-03-27 14:06:49', NULL, NULL),
(277, 'Mayor', 'A', 'Local', 138, '2026-03-27 14:06:56', NULL, NULL),
(278, 'Mayor', 'B', 'Local', 138, '2026-03-27 14:06:56', NULL, NULL),
(279, 'President', 'Party A', 'Central', 141, '2026-03-28 05:16:48', NULL, NULL),
(280, 'President', 'Party B', 'Central', 141, '2026-03-28 05:16:48', NULL, NULL),
(281, 'Mayor', 'Party A', 'Local', 141, '2026-03-28 05:33:00', NULL, NULL),
(282, 'Mayor', 'Party B', 'Local', 141, '2026-03-28 05:33:00', NULL, NULL),
(283, 'President', 'A', 'Central', 142, '2026-03-29 09:15:31', NULL, NULL),
(284, 'President', 'B', 'Central', 142, '2026-03-29 09:15:31', NULL, NULL),
(285, 'Mayor', 'A', 'Local', 142, '2026-03-29 09:15:42', NULL, NULL),
(286, 'Mayor', 'B', 'Local', 142, '2026-03-29 09:15:42', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `precincts`
--

CREATE TABLE `precincts` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `longitude` int(11) NOT NULL,
  `latitude` int(11) NOT NULL,
  `location` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp(),
  `assignment_status` enum('assigned','unassigned') NOT NULL,
  `occupied_status` enum('occupied','unoccupied','') NOT NULL,
  `college` int(11) DEFAULT NULL,
  `department` int(11) DEFAULT NULL,
  `major_id` int(11) DEFAULT NULL,
  `current_capacity` int(255) NOT NULL DEFAULT 0,
  `max_capacity` int(255) NOT NULL DEFAULT 0,
  `type` int(11) DEFAULT NULL,
  `status` text NOT NULL DEFAULT 'active',
  `college_external` int(11) DEFAULT NULL,
  `election` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `precincts`
--

INSERT INTO `precincts` (`id`, `name`, `longitude`, `latitude`, `location`, `created_at`, `updated_at`, `assignment_status`, `occupied_status`, `college`, `department`, `major_id`, `current_capacity`, `max_capacity`, `type`, `status`, `college_external`, `election`) VALUES
(217, '2026-2026 1st_CCS_Newest_Location-1', 122, 7, 'Location', '2026-03-27 04:40:05', '2026-03-29 09:17:22', 'unassigned', 'unoccupied', 22, 158, NULL, 0, 25, 8, 'active', NULL, 142),
(218, '2026-2026 2nd_CCS_New_wmsumainbuilding-1', 122, 7, 'wmsu main building', '2026-03-27 04:40:35', '2026-03-27 14:22:31', 'unassigned', 'unoccupied', 22, 159, NULL, 0, 25, 8, 'archived', NULL, NULL),
(219, '2026-2026 2nd_CCS_New_ZamboangaZamboangadelSurZamboangaPeninsulaPHL-1', 122, 7, 'Zamboanga, Zamboanga del Sur, Zamboanga Peninsula, PHL', '2026-03-27 06:35:01', '2026-03-27 14:22:31', 'unassigned', 'unoccupied', 22, 158, NULL, 0, 25, 10, 'archived', 12, NULL),
(220, '2026-2026 2nd_CE_New_WMSUMainBuilding-1', 122, 7, 'WMSU Main Building', '2026-03-27 06:35:26', '2026-03-27 14:22:31', 'unassigned', 'unoccupied', 28, 170, NULL, 0, 25, 8, 'archived', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `precinct_elections`
--

CREATE TABLE `precinct_elections` (
  `id` int(11) NOT NULL,
  `precinct_id` int(11) NOT NULL,
  `precinct_name` varchar(255) NOT NULL,
  `election_name` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `precinct_elections`
--

INSERT INTO `precinct_elections` (`id`, `precinct_id`, `precinct_name`, `election_name`, `assigned_at`, `archived`, `archived_at`) VALUES
(260, 217, '2026-2026 1st_CCS_Test_Location-1', 137, '2026-03-27 03:40:05', 1, '2026-03-27 14:02:49'),
(261, 218, '2026-2026 1st_CCS_Test_NA-1', 137, '2026-03-27 03:40:35', 1, '2026-03-27 14:02:49'),
(262, 219, '2026-2026 1st_CCS_Test_ZamboangaZamboangadelSurZamboangaPeninsulaPHL-1', 137, '2026-03-27 05:35:01', 1, '2026-03-27 14:02:49'),
(263, 220, '2026-2026 1st_CE_Test_WMSUMainBuilding-1', 137, '2026-03-27 05:35:26', 1, '2026-03-27 14:02:49'),
(264, 217, '2026-2026 2nd_CCS_New_Location-1', 138, '2026-03-27 06:10:51', 1, '2026-03-27 14:22:31'),
(265, 218, '2026-2026 2nd_CCS_New_wmsumainbuilding-1', 138, '2026-03-27 06:10:58', 1, '2026-03-27 14:22:31'),
(266, 219, '2026-2026 2nd_CCS_New_ZamboangaZamboangadelSurZamboangaPeninsulaPHL-1', 138, '2026-03-27 06:11:05', 1, '2026-03-27 14:22:31'),
(267, 220, '2026-2026 2nd_CE_New_WMSUMainBuilding-1', 138, '2026-03-27 06:11:13', 1, '2026-03-27 14:22:31'),
(268, 217, '2026-2026 1st_CCS_Test_Location-1', 141, '2026-03-27 21:35:06', 1, '2026-03-28 06:08:52'),
(269, 217, '2026-2026 1st_CCS_Newest_Location-1', 142, '2026-03-29 01:17:22', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `precinct_voters`
--

CREATE TABLE `precinct_voters` (
  `id` int(11) NOT NULL,
  `precinct` varchar(100) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cor` text DEFAULT NULL,
  `status` text NOT NULL DEFAULT 'unverified'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qr_sending_log`
--

CREATE TABLE `qr_sending_log` (
  `id` int(11) NOT NULL,
  `email` text NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `election_id` int(11) NOT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'sent',
  `notes` text DEFAULT NULL,
  `qr_img` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_sending_log`
--

INSERT INTO `qr_sending_log` (`id`, `email`, `student_id`, `election_id`, `sent_at`, `status`, `notes`, `qr_img`) VALUES
(93, 'ahmadaquino.2002@gmail.com', '2020-01524', 62, '2026-03-13 14:57:26', 'sent', 'QR sent successfully', '49f43f7001a911e34a8a27b06d81efd5.png'),
(94, 'ahmadaquino.2002@gmail.com', '2020-01524', 62, '2026-03-13 14:58:47', 'sent', 'QR sent successfully', '49f43f7001a911e34a8a27b06d81efd5.png'),
(95, 'ahmadaquino.2002@gmail.com', '2020-01524', 62, '2026-03-13 15:00:15', 'sent', 'QR sent successfully', '49f43f7001a911e34a8a27b06d81efd5.png'),
(96, 'ahmadaquino.2002@gmail.com', '2020-01524', 62, '2026-03-13 15:03:14', 'sent', 'QR sent successfully', '49f43f7001a911e34a8a27b06d81efd5.png'),
(97, 'ahmadaquino.2002@gmail.com', '2020-01524', 62, '2026-03-13 15:05:10', 'sent', 'QR sent successfully', '49f43f7001a911e34a8a27b06d81efd5.png'),
(98, 'ahmadaquino.2002@gmail.com', '2020-01520', 62, '2026-03-13 15:09:10', 'sent', 'QR sent successfully', '195b71d3c320925e55db60117deb65b8.png'),
(99, 'ahmadaquino.2002@gmail.com', '2020-01524', 62, '2026-03-13 15:09:17', 'sent', 'QR sent successfully', '7d196271568f814a6ec77692c3ca8a17.png'),
(100, 'ahmadaquino.2002@gmail.com', '2020-01521', 62, '2026-03-13 15:09:25', 'sent', 'QR sent successfully', 'e1df608853ffa2b266fee1772835f351.png'),
(101, 'ahmadaquino.2002@gmail.com', '2020-01520', 62, '2026-03-13 15:13:03', 'sent', 'QR sent successfully', '195b71d3c320925e55db60117deb65b8.png'),
(102, 'ahmadaquino.2002@gmail.com', '2020-01524', 62, '2026-03-13 15:13:10', 'sent', 'QR sent successfully', '7d196271568f814a6ec77692c3ca8a17.png'),
(103, 'ahmadaquino.2002@gmail.com', '2020-01521', 62, '2026-03-13 15:13:17', 'sent', 'QR sent successfully', 'e1df608853ffa2b266fee1772835f351.png'),
(104, 'csadviser@wmsu.edu.ph', '2021-00168', 65, '2026-03-15 17:59:36', 'sent', 'QR sent successfully', '153ba1d853553bdd73077e0dbb6a4b4c.png'),
(105, 'csadviser@wmsu.edu.ph', '2021-01252', 65, '2026-03-15 17:59:44', 'sent', 'QR sent successfully', '6894eee00ee0143582615e472b80ac2e.png'),
(106, 'csadviser@wmsu.edu.ph', '2021-00274', 65, '2026-03-15 17:59:51', 'sent', 'QR sent successfully', '6ace42879cbc1c4b3e1330be6b862276.png'),
(107, 'csadviser@wmsu.edu.ph', '2021-00168', 65, '2026-03-15 18:00:08', 'sent', 'QR sent successfully', 'dd0e4a21f9ad455d015503160f9f8ee9.png'),
(108, 'csadviser@wmsu.edu.ph', '2021-01252', 65, '2026-03-15 18:00:53', 'sent', 'QR sent successfully', 'c75a2678b004c3791a64ef3fb8fc29e4.png'),
(109, 'ceadviserengineering1st@gmail.com', '1997-12345', 66, '2026-03-17 13:18:10', 'sent', 'QR sent successfully', '5a9f9ae1723e824466e0f8b9dea24a96.png'),
(110, 'ceadviserengineering1st@gmail.com', '1999-54321', 66, '2026-03-17 13:18:23', 'sent', 'QR sent successfully', '82e5ee01adfa3d91f30c87d04f241c94.png'),
(111, 'ceadviserengineering1st@gmail.com', '1997-12345', 66, '2026-03-17 13:22:02', 'sent', 'QR sent successfully', '0fd8f13a2d8d6abb6a55ecc62433cd07.png'),
(112, 'ccsadviser1stmain@wmsu.edu.ph', '2020-01524', 68, '2026-03-18 05:56:24', 'sent', 'QR sent successfully', 'df9cd833d64fbffd3777ce0914bcae94.png'),
(113, 'ccsadviser1stmain@wmsu.edu.ph', '2020-01524', 70, '2026-03-23 05:27:21', 'sent', 'QR sent successfully', '7c8731da9f7c871853339d9c02a454b3.png'),
(114, 'ccsadviser1stmain@wmsu.edu.ph', '2020-01524', 77, '2026-03-28 05:36:05', 'sent', 'QR sent successfully', '39f9b036ef193799b14639b4c7a26c9c.png');

-- --------------------------------------------------------

--
-- Table structure for table `registration_attempts`
--

CREATE TABLE `registration_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `registration_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registration_forms`
--

CREATE TABLE `registration_forms` (
  `id` int(11) NOT NULL,
  `form_name` varchar(255) DEFAULT NULL,
  `election_name` int(10) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `status` text NOT NULL DEFAULT '\'active\''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration_forms`
--

INSERT INTO `registration_forms` (`id`, `form_name`, `election_name`, `created_at`, `status`) VALUES
(90, 'Filing of Candidacy Form', 137, '2026-03-27 11:39:21', 'inactive'),
(91, 'Filing of Candidacy Form', 138, '2026-03-27 14:07:31', 'inactive'),
(92, 'Filing of Candidacy Form', 141, '2026-03-28 05:21:16', 'inactive'),
(93, 'Filing of Candidacy Form', 142, '2026-03-29 09:17:05', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `tied_candidates`
--

CREATE TABLE `tied_candidates` (
  `id` int(11) NOT NULL,
  `form_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `status` text NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tied_candidates`
--

INSERT INTO `tied_candidates` (`id`, `form_id`, `created_at`, `status`) VALUES
(421, 92, '2026-03-28 05:22:36', 'accepted'),
(422, 92, '2026-03-28 05:23:26', 'accepted'),
(423, 92, '2026-03-28 05:33:59', 'accepted'),
(424, 92, '2026-03-28 05:34:16', 'accepted');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `created_at`, `is_active`) VALUES
(1, 'wmsu_admin@wmsu.edu.ph', '$2y$10$Vtl4NA1Wwe89mjVzocZ0CuTHN2.oHt4JxP3pfatTtvwb0TJpxjC5q', 'admin', '2025-02-14 20:20:03', 1),
(19372, 'cs_adviser@wmsu.edu.ph', '$2y$10$qoTlhTXVLJ5u32aGh.KFTOa.8kaESXgZXKf0FNt./rE6dMdLuS5JC', 'adviser', '2025-07-21 07:04:51', 1),
(19405, 'it_adviser@wmsu.edu.ph', '$2y$10$qoTlhTXVLJ5u32aGh.KFTOa.8kaESXgZXKf0FNt./rE6dMdLuS5JC', 'adviser', '2025-07-24 22:45:55', 1),
(19419, 'cla_accountancy@wmsu.edu.ph', '$2y$10$qoTlhTXVLJ5u32aGh.KFTOa.8kaESXgZXKf0FNt./rE6dMdLuS5JC', 'adviser', '2025-07-27 09:40:50', 1),
(19420, 'cla_history@wmsu.edu.ph', '$2y$10$qoTlhTXVLJ5u32aGh.KFTOa.8kaESXgZXKf0FNt./rE6dMdLuS5JC', 'adviser', '2025-07-27 15:37:13', 1),
(19421, 'cla_english@wmsu.edu.ph', '$2y$10$qoTlhTXVLJ5u32aGh.KFTOa.8kaESXgZXKf0FNt./rE6dMdLuS5JC', 'adviser', '2025-07-27 15:40:17', 1),
(19422, 'cla_polsci@wmsu.edu.ph', '$2y$10$qoTlhTXVLJ5u32aGh.KFTOa.8kaESXgZXKf0FNt./rE6dMdLuS5JC', 'adviser', '2025-07-27 15:41:07', 1),
(19423, 'ca_adviser@wmsu.edu.ph', '$2y$10$qoTlhTXVLJ5u32aGh.KFTOa.8kaESXgZXKf0FNt./rE6dMdLuS5JC', 'adviser', '2025-07-27 15:42:16', 1),
(19424, 'cn_adviser@wmsu.edu.ph', '$2y$10$qoTlhTXVLJ5u32aGh.KFTOa.8kaESXgZXKf0FNt./rE6dMdLuS5JC', 'adviser', '2025-07-27 15:42:47', 1),
(19425, 'cs_adviser_4th@wmsu.edu.ph', '$2y$10$qoTlhTXVLJ5u32aGh.KFTOa.8kaESXgZXKf0FNt./rE6dMdLuS5JC', 'adviser', '2025-07-27 15:43:51', 1),
(19426, 'cais_islamicadviser@gmail.com', '$2y$10$qoTlhTXVLJ5u32aGh.KFTOa.8kaESXgZXKf0FNt./rE6dMdLuS5JC', 'adviser', '2025-07-27 15:44:43', 1),
(19457, 'chem_eng_adviser@wmsu.edu.ph', '$2y$10$qoTlhTXVLJ5u32aGh.KFTOa.8kaESXgZXKf0FNt./rE6dMdLuS5JC', 'adviser', '2026-02-04 11:18:35', 1),
(19493, 'xt202001520@wmsu.edu.ph', '$2y$10$TErwTpAodOWBN7QaXwNlJOy0YfXt76p4OLoXSYyAolc574kztUUwO', 'voter', '2026-03-12 05:54:38', 1),
(19495, 'xt202001524@wmsu.edu.ph', '$2y$10$2ToO9mK.2b5bg36rQsK/7OCrd7B8FrS7UiaFVPErx8cLnh94.r6Xy', 'voter', '2026-03-13 03:03:07', 1),
(19496, 'xt202001521@wmsu.edu.ph', '$2y$10$TErwTpAodOWBN7QaXwNlJOy0YfXt76p4OLoXSYyAolc574kztUUwO', 'voter', '2026-03-13 03:27:10', 1),
(19498, 'xt202001525@wmsu.edu.ph', '$2y$10$TErwTpAodOWBN7QaXwNlJOy0YfXt76p4OLoXSYyAolc574kztUUwO', 'voter', '2026-03-13 03:03:07', 1),
(19501, 'wmsuadvisermadah@gmail.com', '$2y$10$BqDeibX92rF8o8AXxB6vEuWeUB6gpJRZyj5l/PvR24EsIvC7xxFCO', 'adviser', '2026-03-13 19:23:41', 1),
(19502, 'xt202001526@wmsu.edu.ph', '$2y$10$TErwTpAodOWBN7QaXwNlJOy0YfXt76p4OLoXSYyAolc574kztUUwO', 'voter', '2026-03-13 03:03:07', 1),
(19505, 'csadviser@wmsu.edu.ph', '$2y$10$/3nZvXNOkFpGZPZ.ZL3NtuK0BYXIHzF7AFr/lS5Dg82HRa/A4YPuu', 'adviser', '2026-03-15 09:08:11', 1),
(19506, 'qb202100168@wmsu.edu.ph', '$2y$10$TErwTpAodOWBN7QaXwNlJOy0YfXt76p4OLoXSYyAolc574kztUUwO', 'voter', '2026-03-15 09:26:54', 1),
(19507, 'qb202101252@wmsu.edu.ph', '$2y$10$TErwTpAodOWBN7QaXwNlJOy0YfXt76p4OLoXSYyAolc574kztUUwO', 'voter', '2026-03-15 09:28:29', 1),
(19508, 'qb202100274@wmsu.edu.ph', '$2y$10$TErwTpAodOWBN7QaXwNlJOy0YfXt76p4OLoXSYyAolc574kztUUwO', 'voter', '2026-03-15 09:30:39', 1),
(19510, 'xt199912345@wmsu.edu.ph', '$2y$10$TErwTpAodOWBN7QaXwNlJOy0YfXt76p4OLoXSYyAolc574kztUUwO', 'voter', '2026-03-16 15:49:28', 1),
(19511, 'ccsadviser1st@wmsu.edu.ph', '$2y$10$GJ215bgif1s6kU2J5qG58uUDwCcoPDY/CCBxDe2jHGo6Tvx.s2ZeK', 'adviser', '2026-03-16 15:51:02', 1),
(19512, 'ceadviserengineering1st@gmail.com', '$2y$10$oZEVA07DZfxvKoVNgzVwAeSKGtWBwici5rvfzdoovCBOlTEPv.C8.', 'adviser', '2026-03-16 15:53:17', 1),
(19513, 'ccsadviser1stmain@wmsu.edu.ph', '$2y$10$ELZZylAXLzuJgoMaLRWBS.uoBAShEdgFTFA4aNqOlessOBh.8sQLC', 'adviser', '2026-03-16 16:09:38', 1),
(19514, 'zxcguyadviser@gmail.com', '$2y$10$GJ215bgif1s6kU2J5qG58uUDwCcoPDY/CCBxDe2jHGo6Tvx.s2ZeK', 'adviser', '2026-03-16 15:51:02', 1),
(19519, 'xt199812345@wmsu.edu.ph', '$2y$10$TErwTpAodOWBN7QaXwNlJOy0YfXt76p4OLoXSYyAolc574kztUUwO', 'voter', '2026-03-13 03:03:07', 1),
(19520, 'ce199712345@wmsu.edu.ph', '$2y$10$TErwTpAodOWBN7QaXwNlJOy0YfXt76p4OLoXSYyAolc574kztUUwO', 'voter', '2026-03-17 05:02:41', 1),
(19521, 'ce199954321@wmsu.edu.ph', '$2y$10$TErwTpAodOWBN7QaXwNlJOy0YfXt76p4OLoXSYyAolc574kztUUwO', 'voter', '2026-03-17 05:03:46', 1),
(19522, 'xt199912134@wmsu.edu.ph', '$2y$10$TErwTpAodOWBN7QaXwNlJOy0YfXt76p4OLoXSYyAolc574kztUUwO', 'voter', '2026-03-17 05:05:03', 1),
(19523, 'ccsadviser1stsofteng@gmail.com', '$2y$10$QnvpSJnobsHTPyj5QYp6Ju6FloG/VSB2RR8DUU9CJydrjUUND2pmy', 'adviser', '2026-03-17 05:55:21', 1),
(19529, 'wmsuccsmod@gmail.com', '$2y$10$alXxS1Y.kSFa.ACmWGDXe.FCtvLW4O88F8aqYtEeJwKQ2YMd9jeSK', 'moderator', '2026-03-17 22:33:52', 1),
(19530, 'wmsuitmod@gmail.com', '$2y$10$Qc.5Yapl1RqLYwNxiM2gyud1419Im63Q01rj7f/8IbZ93YionsG..', 'moderator', '2026-03-17 22:35:37', 1),
(19531, 'wmsumoderatornew@gmail.com', '$2y$10$Q0R7oxe8hcM1Rupmku8njemoUadv7DH/r8smrvZFmpbeyIfI9RZja', 'moderator', '2026-03-27 05:58:09', 1),
(19532, 'wmsumodmod@gmail.com', '$2y$10$eixVbTj2xcJgqgqkhEdMpeaWEVWTVc7fAA4wCyz6Wr8OP5FcrTztC', 'moderator', '2026-03-27 05:58:48', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_activities`
--

CREATE TABLE `user_activities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `timestamp` datetime NOT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `behavior_patterns` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activities`
--

INSERT INTO `user_activities` (`id`, `user_id`, `action`, `timestamp`, `device_info`, `ip_address`, `location`, `behavior_patterns`) VALUES
(663, 1, 'LOGIN', '2025-07-11 14:43:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '131.226.114.118', 'N/A', 'Successful login'),
(665, 1, 'LOGIN', '2025-07-13 23:27:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.87.56', 'N/A', 'Successful login'),
(666, 1, 'LOGIN', '2025-07-14 11:47:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.81.212', 'N/A', 'Successful login'),
(667, 1, 'ADD_ELECTION', '2025-07-14 04:21:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.81.212', 'N/A', 'Added election: Name: USC Election 2025-2026, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(668, 1, 'UPDATE_ELECTION', '2025-07-14 04:22:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.81.212', 'N/A', 'Updated election ID=55, Name=USC Election 2025-2026, Status=Ongoing'),
(669, 1, 'UPDATE_ELECTION', '2025-07-14 04:22:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.81.212', 'N/A', 'Updated election ID=55, Name=USC Election 2025-2026, Status=Ongoing'),
(670, 1, 'UPDATE_ELECTION', '2025-07-14 04:23:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.81.212', 'N/A', 'Updated election ID=55, Name=USC Election 2025-2026, Status=Ongoing'),
(671, 1, 'UPDATE_ELECTION', '2025-07-14 04:23:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.81.212', 'N/A', 'Updated election ID=55, Name=USC Election 2025-2026, Status=Ongoing'),
(672, 1, 'ADD_ELECTION', '2025-07-14 04:24:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.81.212', 'N/A', 'Added election: Name: BSCS 4B Class Election S.Y. 2025-2026, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(673, 1, 'ADD_ELECTION', '2025-07-14 04:25:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.81.212', 'N/A', 'Added election: Name: Test Election, School Year: 2025-2026, Semester: 2nd Semester, Status: Upcoming, The period clashes with an existing Ongoing election. Status set to Upcoming.'),
(674, 1, 'UPDATE_ELECTION', '2025-07-14 04:27:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.81.212', 'N/A', 'Updated election ID=57, Name=Test Election, Status=Ended'),
(675, 1, 'UPDATE_ELECTION', '2025-07-14 04:28:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.81.212', 'N/A', 'Updated election ID=56, Name=BSCS 4B Class Election S.Y. 2025-2026, Status=Upcoming'),
(676, 1, 'UPDATE_ELECTION', '2025-07-14 04:28:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.81.212', 'N/A', 'Updated election ID=55, Name=USC Election 2025-2026, Status=Upcoming'),
(677, 1, 'UPDATE_ELECTION', '2025-07-14 04:28:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.81.212', 'N/A', 'Updated election ID=56, Name=BSCS 4B Class Election S.Y. 2025-2026, Status=Ended'),
(678, 1, 'UPDATE_ELECTION', '2025-07-14 04:31:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.81.212', 'N/A', 'Updated election ID=57, Name=Test Election, Status=Ongoing'),
(680, 1, 'LOGIN', '2025-07-14 13:16:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.81.212', 'N/A', 'Successful login'),
(683, 1, 'LOGIN', '2025-07-14 13:25:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '175.176.81.212', 'N/A', 'Successful login'),
(684, 1, 'LOGIN', '2025-07-14 15:07:20', 'Mozilla/5.0 (Linux; Android 15; CPH2591 Build/AP3A.240617.008; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/138.0.7204.45 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/515.0.0.51.108;]', '158.62.64.193', 'N/A', 'Successful login'),
(685, 1, 'LOGIN', '2025-07-15 15:51:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2001:4455:6ba:2400:5d13:381:4327:bf51', 'N/A', 'Successful login'),
(687, 1, 'UPDATE_ELECTION', '2025-07-15 08:52:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2001:4455:6ba:2400:5d13:381:4327:bf51', 'N/A', 'Updated election ID=57, Name=Test Election, Status=Ended'),
(688, 1, 'DELETE_ELECTION', '2025-07-15 08:52:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2001:4455:6ba:2400:5d13:381:4327:bf51', 'N/A', 'Deleted election: BSCS 4B Class Election S.Y. 2025-2026'),
(689, 1, 'DELETE_ELECTION', '2025-07-15 08:52:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2001:4455:6ba:2400:5d13:381:4327:bf51', 'N/A', 'Deleted election: Test Election'),
(690, 1, 'DELETE_ELECTION', '2025-07-15 08:52:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2001:4455:6ba:2400:5d13:381:4327:bf51', 'N/A', 'Deleted election: USC Election 2025-2026'),
(691, 1, 'LOGIN', '2025-07-21 22:53:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '158.62.66.83', 'N/A', 'Successful login'),
(692, 1, 'ADD_ELECTION', '2025-07-21 14:57:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '158.62.66.83', 'N/A', 'Added election: Name: WMSU Senatorial, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(693, 1, 'LOGIN', '2025-07-21 23:00:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:139.0) Gecko/20100101 Firefox/139.0', '158.62.66.83', 'N/A', 'Successful login'),
(694, 1, 'LOGOUT', '2025-07-21 23:05:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:139.0) Gecko/20100101 Firefox/139.0', '158.62.66.83', 'N/A', 'Successful logout'),
(696, 1, 'UPDATE_ELECTION', '2025-07-21 15:37:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '158.62.66.83', 'N/A', 'Updated election ID=58, Name=WMSU Senatorial, Status=Upcoming'),
(697, 1, 'UPDATE_ELECTION', '2025-07-21 15:40:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '158.62.66.83', 'N/A', 'Updated election ID=58, Name=WMSU Senatorial, Status=Ongoing'),
(698, 1, 'LOGIN', '2025-07-21 23:48:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:139.0) Gecko/20100101 Firefox/139.0', '158.62.66.83', 'N/A', 'Successful login'),
(700, 1, 'LOGIN', '2025-07-21 23:58:54', 'Mozilla/5.0 (Linux; Android 15; CPH2591 Build/AP3A.240617.008; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/138.0.7204.67 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/516.0.0.57.110;]', '158.62.66.83', 'N/A', 'Successful login'),
(701, 1, 'ADD_MODERATOR', '2025-07-21 16:02:21', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '158.62.66.83', 'N/A', 'Added moderator CS Moderator Email csmode123wmsueduph Precinct 20252026-CCS-WMSU Senatorial-1'),
(703, 1, 'LOGIN', '2025-07-22 00:37:55', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15', '180.190.18.51', 'N/A', 'Successful login'),
(704, 1, 'ADD_ELECTION', '2025-07-21 16:38:49', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15', '180.190.18.51', 'N/A', 'Added election: Name: UCS, School Year: 2025-2026, Semester: 1st Semester, Status: Upcoming, The period clashes with an existing Ongoing election. Status set to Upcoming.'),
(705, 1, 'DELETE_ELECTION', '2025-07-21 16:48:08', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15', '180.190.18.51', 'N/A', 'Deleted election: UCS'),
(706, 1, 'ADD_ELECTION', '2025-07-21 16:49:22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15', '180.190.18.51', 'N/A', 'Added election: Name: USC, School Year: 2025-2026, Semester: 1st Semester, Status: Upcoming, The period clashes with an existing Ongoing election. Status set to Upcoming.'),
(707, 1, 'LOGIN', '2025-07-22 08:38:39', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15', '180.190.19.241', 'N/A', 'Successful login'),
(708, 1, 'UPDATE_ELECTION', '2025-07-22 00:40:36', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15', '180.190.19.241', 'N/A', 'Updated election ID=58, Name=WMSU Senatorial, Status=Ended'),
(709, 1, 'UPDATE_ELECTION', '2025-07-22 00:40:50', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15', '180.190.19.241', 'N/A', 'Updated election ID=60, Name=USC, Status=Upcoming'),
(710, 1, 'LOGIN', '2025-07-22 09:19:46', 'Mozilla/5.0 (Linux; Android 15; CPH2591 Build/AP3A.240617.008; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/138.0.7204.67 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/516.0.0.57.110;]', '158.62.66.15', 'N/A', 'Successful login'),
(711, 1, 'LOGIN', '2025-07-22 11:52:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '49.149.100.229', 'N/A', 'Successful login'),
(712, 1, 'UPDATE_ELECTION', '2025-07-22 03:52:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '49.149.100.229', 'N/A', 'Updated election ID=60, Name=USC, Status=Ongoing'),
(713, 1, 'LOGOUT', '2025-07-22 11:53:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '49.149.100.229', 'N/A', 'Successful logout'),
(714, 1, 'LOGIN', '2025-07-22 11:53:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '49.149.100.229', 'N/A', 'Successful login'),
(715, 1, 'LOGIN', '2025-07-22 11:55:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '49.149.100.229', 'N/A', 'Successful login'),
(716, 1, 'DELETE_CANDIDACY', '2025-07-22 04:18:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '49.149.100.229', 'N/A', 'Deleted candidacy period for election: WMSU Senatorial'),
(717, NULL, 'Submitted candidacy application', '2025-07-22 04:28:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '49.149.100.229', NULL, '{\"form_submission_time\":\"2025-07-22 12:28:27\",\"field_count\":4,\"has_files\":true}'),
(718, NULL, 'Submitted candidacy application', '2025-07-22 04:29:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '49.149.100.229', NULL, '{\"form_submission_time\":\"2025-07-22 12:29:52\",\"field_count\":4,\"has_files\":true}'),
(719, 1, 'VIEW_CANDIDATES', '2025-07-22 04:30:03', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(720, 1, 'VIEW_CANDIDATES', '2025-07-22 04:30:08', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(721, 1, 'VIEW_CANDIDATES', '2025-07-22 04:30:22', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(722, 1, 'VIEW_CANDIDATES', '2025-07-22 04:38:36', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(723, 1, 'VIEW_CANDIDATES', '2025-07-22 04:38:49', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(724, 1, 'Submitted candidacy application', '2025-07-22 04:39:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '49.149.100.229', NULL, '{\"form_submission_time\":\"2025-07-22 12:39:05\",\"field_count\":4,\"has_files\":true}'),
(725, 1, 'VIEW_CANDIDATES', '2025-07-22 04:39:06', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(726, 1, 'VIEW_CANDIDATES', '2025-07-22 04:39:13', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(730, 1, 'VIEW_CANDIDATES', '2025-07-22 05:34:17', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(731, 1, 'Submitted candidacy application', '2025-07-22 05:34:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '49.149.100.229', NULL, '{\"form_submission_time\":\"2025-07-22 13:34:39\",\"field_count\":4,\"has_files\":true}'),
(732, 1, 'VIEW_CANDIDATES', '2025-07-22 05:34:39', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(733, 1, 'Submitted candidacy application', '2025-07-22 05:34:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '49.149.100.229', NULL, '{\"form_submission_time\":\"2025-07-22 13:34:59\",\"field_count\":4,\"has_files\":true}'),
(734, 1, 'VIEW_CANDIDATES', '2025-07-22 05:34:59', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(735, 1, 'Submitted candidacy application', '2025-07-22 05:35:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '49.149.100.229', NULL, '{\"form_submission_time\":\"2025-07-22 13:35:15\",\"field_count\":4,\"has_files\":true}'),
(736, 1, 'VIEW_CANDIDATES', '2025-07-22 05:35:16', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(737, 1, 'Submitted candidacy application', '2025-07-22 05:35:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '49.149.100.229', NULL, '{\"form_submission_time\":\"2025-07-22 13:35:32\",\"field_count\":4,\"has_files\":true}'),
(738, 1, 'VIEW_CANDIDATES', '2025-07-22 05:35:32', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(739, 1, 'VIEW_CANDIDATES', '2025-07-22 05:36:00', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(740, 1, 'Submitted candidacy application', '2025-07-22 05:36:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '49.149.100.229', NULL, '{\"form_submission_time\":\"2025-07-22 13:36:17\",\"field_count\":4,\"has_files\":true}'),
(741, 1, 'VIEW_CANDIDATES', '2025-07-22 05:36:17', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(742, 1, 'Submitted candidacy application', '2025-07-22 05:36:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '49.149.100.229', NULL, '{\"form_submission_time\":\"2025-07-22 13:36:32\",\"field_count\":4,\"has_files\":true}'),
(743, 1, 'VIEW_CANDIDATES', '2025-07-22 05:36:32', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(744, 1, 'VIEW_CANDIDATES', '2025-07-22 05:38:04', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '49.149.100.229', 'N/A', 'Viewed candidates for event ID 76 Candidacy USC'),
(746, 1, 'LOGIN', '2025-07-22 13:55:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '8.5295104,126.0650496', 'Successful login'),
(747, 1, 'ADD_ELECTION', '2025-07-22 08:54:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: USC, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(748, NULL, 'Submitted candidacy application', '2025-07-22 15:00:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-07-22 15:00:28\",\"field_count\":4,\"has_files\":true}'),
(749, NULL, 'Submitted candidacy application', '2025-07-22 15:00:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-07-22 15:00:55\",\"field_count\":4,\"has_files\":true}'),
(750, NULL, 'Submitted candidacy application', '2025-07-22 15:01:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-07-22 15:01:21\",\"field_count\":4,\"has_files\":true}'),
(751, 1, 'VIEW_CANDIDATES', '2025-07-22 09:02:18', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 77 Candidacy USC'),
(752, 1, 'VIEW_CANDIDATES', '2025-07-22 09:04:20', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 77 Candidacy USC'),
(753, NULL, 'Submitted candidacy application', '2025-07-22 15:05:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-07-22 15:05:03\",\"field_count\":4,\"has_files\":true}'),
(754, NULL, 'Submitted candidacy application', '2025-07-22 15:05:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-07-22 15:05:21\",\"field_count\":4,\"has_files\":true}'),
(755, NULL, 'Submitted candidacy application', '2025-07-22 15:05:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-07-22 15:05:33\",\"field_count\":4,\"has_files\":true}'),
(756, NULL, 'Submitted candidacy application', '2025-07-22 15:05:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-07-22 15:05:48\",\"field_count\":4,\"has_files\":true}'),
(757, NULL, 'Submitted candidacy application', '2025-07-22 15:06:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-07-22 15:06:07\",\"field_count\":4,\"has_files\":true}'),
(758, 1, 'VIEW_CANDIDATES', '2025-07-22 09:06:13', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 77 Candidacy USC'),
(759, NULL, 'Submitted candidacy application', '2025-07-22 15:06:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-07-22 15:06:33\",\"field_count\":4,\"has_files\":true}'),
(760, NULL, 'Submitted candidacy application', '2025-07-22 15:06:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-07-22 15:06:56\",\"field_count\":4,\"has_files\":true}'),
(761, 1, 'VIEW_CANDIDATES', '2025-07-22 09:06:59', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 77 Candidacy USC'),
(762, 1, 'VIEW_CANDIDATES', '2025-07-22 09:07:08', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 77 Candidacy USC'),
(764, 1, 'VIEW_CANDIDATES', '2025-07-22 09:16:44', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 77 Candidacy USC'),
(766, 1, 'LOGIN', '2025-07-24 13:43:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '6.9365873,122.0842299', 'Successful login'),
(767, 1, 'LOGIN', '2025-07-24 14:39:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '6.931734,122.0950952', 'Successful login'),
(770, 1, 'LOGIN', '2025-07-25 07:04:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '7.110656,125.5145472', 'Successful login'),
(771, 1, 'ADD_ELECTION', '2025-07-25 03:37:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(772, 1, 'ADD_MODERATOR', '2025-07-25 03:37:46', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Added moderator test Email testergmailcom Precinct 20252026-CSM-Test-1'),
(774, 1, 'LOGIN', '2025-07-25 12:02:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '7.110656,125.5145472', 'Successful login'),
(777, 1, 'LOGIN', '2025-07-25 14:45:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(780, 1, 'LOGIN', '2025-07-25 15:03:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '7.110656,125.5145472', 'Successful login'),
(781, 1, 'LOGOUT', '2025-07-25 15:53:42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful logout'),
(782, 1, 'LOGIN', '2025-07-25 16:40:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '::1', '7.110656,125.5145472', 'Successful login'),
(783, 1, 'LOGIN', '2025-07-25 16:40:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '::1', '7.110656,125.5145472', 'Successful login'),
(784, 1, 'VIEW_CANDIDATES', '2025-07-25 10:49:29', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(785, 1, 'VIEW_CANDIDATES', '2025-07-25 10:52:34', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(786, 1, 'ADD_MODERATOR', '2025-07-25 10:53:14', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Added moderator modcs Email modcswmsucom Precinct 20252026-CCS-Test-1'),
(787, 1, 'ADD_MODERATOR', '2025-07-25 10:53:31', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Added moderator modit Email moditwmsucom Precinct 20252026-CCS-Test-2'),
(788, 1, 'ADD_MODERATOR', '2025-07-25 10:53:55', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Added moderator modac Email modacwmsucom Precinct 20252026-CLA-Test-1'),
(789, 1, 'VIEW_CANDIDATES', '2025-07-25 10:54:17', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(790, 1, 'Submitted candidacy application', '2025-07-25 16:54:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-25 16:54:28\",\"field_count\":4,\"has_files\":true}'),
(791, 1, 'VIEW_CANDIDATES', '2025-07-25 10:54:28', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(792, 1, 'Submitted candidacy application', '2025-07-25 16:54:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-25 16:54:37\",\"field_count\":4,\"has_files\":true}'),
(793, 1, 'VIEW_CANDIDATES', '2025-07-25 10:54:37', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(794, 1, 'VIEW_CANDIDATES', '2025-07-25 10:54:46', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(795, 1, 'Submitted candidacy application', '2025-07-25 16:54:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-25 16:54:57\",\"field_count\":4,\"has_files\":true}'),
(796, 1, 'VIEW_CANDIDATES', '2025-07-25 10:54:57', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(797, 1, 'Submitted candidacy application', '2025-07-25 16:55:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-25 16:55:11\",\"field_count\":4,\"has_files\":true}'),
(798, 1, 'VIEW_CANDIDATES', '2025-07-25 10:55:11', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(799, 1, 'Submitted candidacy application', '2025-07-25 16:55:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-25 16:55:24\",\"field_count\":4,\"has_files\":true}'),
(800, 1, 'VIEW_CANDIDATES', '2025-07-25 10:55:24', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(801, 1, 'VIEW_CANDIDATES', '2025-07-25 11:03:13', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(802, 1, 'Submitted candidacy application', '2025-07-25 17:03:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-25 17:03:26\",\"field_count\":4,\"has_files\":true}'),
(803, 1, 'VIEW_CANDIDATES', '2025-07-25 11:03:26', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(804, 1, 'VIEW_CANDIDATES', '2025-07-25 11:04:37', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(805, 1, 'Submitted candidacy application', '2025-07-25 17:04:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-25 17:04:49\",\"field_count\":4,\"has_files\":true}'),
(806, 1, 'VIEW_CANDIDATES', '2025-07-25 11:04:49', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(807, 1, 'VIEW_CANDIDATES', '2025-07-25 11:04:59', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(808, 1, 'Submitted candidacy application', '2025-07-25 17:05:14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-25 17:05:14\",\"field_count\":4,\"has_files\":true}'),
(809, 1, 'VIEW_CANDIDATES', '2025-07-25 11:05:14', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(810, 1, 'Submitted candidacy application', '2025-07-25 17:05:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-25 17:05:29\",\"field_count\":4,\"has_files\":true}'),
(811, 1, 'VIEW_CANDIDATES', '2025-07-25 11:05:29', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(812, 1, 'Submitted candidacy application', '2025-07-25 17:05:42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-25 17:05:42\",\"field_count\":4,\"has_files\":true}'),
(813, 1, 'VIEW_CANDIDATES', '2025-07-25 11:05:42', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(814, 1, 'VIEW_CANDIDATES', '2025-07-25 11:05:56', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(815, 1, 'VIEW_CANDIDATES', '2025-07-25 11:05:59', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(816, 1, 'LOGOUT', '2025-07-25 17:06:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful logout'),
(819, 1, 'LOGIN', '2025-07-25 17:11:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '7.110656,125.5145472', 'Successful login'),
(820, 1, 'LOGOUT', '2025-07-25 17:11:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful logout'),
(823, 1, 'LOGIN', '2025-07-25 17:22:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '6.9365885,122.0842295', 'Successful login'),
(827, 1, 'LOGIN', '2025-07-26 21:00:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '6.9365867,122.0842282', 'Successful login'),
(830, 1, 'LOGIN', '2025-07-27 08:00:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '6.9365862,122.0842284', 'Successful login'),
(840, 1, 'VIEW_CANDIDATES', '2025-07-27 10:47:13', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(841, 1, 'VIEW_CANDIDATES', '2025-07-27 10:48:02', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(842, 1, 'VIEW_CANDIDATES', '2025-07-27 10:49:47', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(843, 1, 'VIEW_CANDIDATES', '2025-07-27 10:50:22', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(847, 1, 'LOGOUT', '2025-07-27 17:25:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful logout'),
(849, 1, 'LOGIN', '2025-07-27 17:42:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '6.9365877,122.0842323', 'Successful login'),
(852, 1, 'LOGIN', '2025-07-27 20:17:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(853, 1, 'ADD_ELECTION', '2025-07-27 14:38:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Another, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(854, 1, 'ADD_ELECTION', '2025-07-27 17:04:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: XZC, School Year: 2025-2026, Semester: 1st Semester, Status: Upcoming, The period clashes with an existing Ongoing election. Status set to Upcoming.'),
(855, 1, 'DELETE_ELECTION', '2025-07-27 17:06:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: XZC'),
(856, 1, 'UPDATE_ELECTION', '2025-07-27 23:06:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=63, Name=Another, Status=Published'),
(857, 1, 'DELETE_ELECTION', '2025-07-27 17:06:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Another'),
(858, 1, 'DELETE_ELECTION', '2025-07-27 17:06:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Test'),
(859, 1, 'ADD_ELECTION', '2025-07-27 17:07:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Student Council Elections, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(860, 1, 'VIEW_CANDIDATES', '2025-07-27 17:20:47', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 82 Candidacy None'),
(861, 1, 'VIEW_CANDIDATES', '2025-07-27 17:20:52', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(862, NULL, 'Submitted candidacy application', '2025-07-27 23:26:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-27 23:26:29\",\"field_count\":4,\"has_files\":true}'),
(863, NULL, 'Submitted candidacy application', '2025-07-27 23:27:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-27 23:27:55\",\"field_count\":4,\"has_files\":true}'),
(864, NULL, 'Submitted candidacy application', '2025-07-27 23:29:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-27 23:29:39\",\"field_count\":4,\"has_files\":true}'),
(865, 1, 'VIEW_CANDIDATES', '2025-07-27 17:29:50', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(866, 1, 'VIEW_CANDIDATES', '2025-07-27 17:31:10', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(867, 1, 'VIEW_CANDIDATES', '2025-07-27 17:33:17', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(868, 1, 'VIEW_CANDIDATES', '2025-07-27 17:45:17', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(869, 1, 'VIEW_CANDIDATES', '2025-07-27 17:46:31', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(870, 1, 'VIEW_CANDIDATES', '2025-07-27 17:53:34', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(871, NULL, 'Submitted candidacy application', '2025-07-27 23:57:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-27 23:57:50\",\"field_count\":4,\"has_files\":true}'),
(872, 1, 'VIEW_CANDIDATES', '2025-07-27 17:57:56', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(873, 1, 'VIEW_CANDIDATES', '2025-07-27 17:58:27', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(874, NULL, 'Submitted candidacy application', '2025-07-27 23:58:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-27 23:58:56\",\"field_count\":4,\"has_files\":true}'),
(875, 1, 'VIEW_CANDIDATES', '2025-07-27 17:59:27', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(876, NULL, 'Submitted candidacy application', '2025-07-27 23:59:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-27 23:59:38\",\"field_count\":4,\"has_files\":true}'),
(877, 1, 'VIEW_CANDIDATES', '2025-07-27 17:59:45', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(881, 1, 'ADD_MODERATOR', '2025-07-28 02:08:44', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Added moderator modclaahist Email modclaahistwmsueduph Precinct 20252026-CLA-Student Council Elections-2'),
(883, 1, 'ADD_MODERATOR', '2025-07-28 02:10:29', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Added moderator modcais Email modcaiswmsucom Precinct 20252026-CAIS-Student Council Elections-1'),
(884, 1, 'VIEW_CANDIDATES', '2025-07-28 06:20:09', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(885, 1, 'VIEW_CANDIDATES', '2025-07-28 06:20:24', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(886, 1, 'VIEW_CANDIDATES', '2025-07-28 06:20:30', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(887, 1, 'VIEW_CANDIDATES', '2025-07-28 06:20:56', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(888, 1, 'Submitted candidacy application', '2025-07-28 12:21:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-28 12:21:06\",\"field_count\":4,\"has_files\":true}'),
(889, 1, 'VIEW_CANDIDATES', '2025-07-28 06:21:06', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(890, 1, 'VIEW_CANDIDATES', '2025-07-28 06:25:58', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(891, 1, 'VIEW_CANDIDATES', '2025-07-28 06:27:50', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(892, 1, 'VIEW_CANDIDATES', '2025-07-28 06:29:54', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(893, 1, 'VIEW_CANDIDATES', '2025-07-28 06:30:04', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(894, 1, 'VIEW_CANDIDATES', '2025-07-28 06:30:12', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(895, 1, 'VIEW_CANDIDATES', '2025-07-28 06:30:22', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(896, 1, 'VIEW_CANDIDATES', '2025-07-28 06:30:33', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(897, 1, 'VIEW_CANDIDATES', '2025-07-28 06:30:48', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(898, 1, 'LOGIN', '2025-07-29 11:38:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '6.9334265,122.0745011', 'Successful login'),
(899, 1, 'ADD_MODERATOR', '2025-07-29 07:10:01', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Added moderator moadac Email moadacwmsueduph Precinct 20252026-CLA-Student Council Elections-1'),
(900, 1, 'LOGOUT', '2025-07-29 13:11:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful logout'),
(903, 1, 'LOGIN', '2025-07-29 19:50:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '6.9365867,122.0842297', 'Successful login'),
(904, 1, 'ADD_ELECTION', '2025-07-29 14:37:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Tester, School Year: 2025-2026, Semester: 1st Semester, Status: Upcoming, The period clashes with an existing Ongoing election. Status set to Upcoming.'),
(905, 1, 'ADD_ELECTION', '2025-07-29 14:38:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2025-2026, Semester: 1st Semester, Status: Upcoming, The period clashes with an existing Ongoing election. Status set to Upcoming.'),
(907, 1, 'VIEW_CANDIDATES', '2025-07-29 15:51:30', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(908, 1, 'Submitted candidacy application', '2025-07-29 21:51:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-29 21:51:58\",\"field_count\":4,\"has_files\":true}'),
(909, 1, 'VIEW_CANDIDATES', '2025-07-29 15:51:58', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(910, NULL, 'Submitted candidacy application', '2025-07-29 21:52:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-29 21:52:36\",\"field_count\":4,\"has_files\":true}'),
(911, 1, 'VIEW_CANDIDATES', '2025-07-29 15:52:38', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(912, 1, 'Submitted candidacy application', '2025-07-29 21:55:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-29 21:55:03\",\"field_count\":4,\"has_files\":true}'),
(913, 1, 'VIEW_CANDIDATES', '2025-07-29 15:55:03', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(914, 1, 'VIEW_CANDIDATES', '2025-07-29 15:55:08', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(915, 1, 'VIEW_CANDIDATES', '2025-07-29 15:55:25', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(916, 1, 'VIEW_CANDIDATES', '2025-07-29 15:56:26', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(917, 1, 'VIEW_CANDIDATES', '2025-07-29 15:56:46', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections');
INSERT INTO `user_activities` (`id`, `user_id`, `action`, `timestamp`, `device_info`, `ip_address`, `location`, `behavior_patterns`) VALUES
(918, 1, 'VIEW_CANDIDATES', '2025-07-29 15:58:11', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(919, 1, 'Submitted candidacy application', '2025-07-29 21:58:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-29 21:58:30\",\"field_count\":4,\"has_files\":true}'),
(920, 1, 'VIEW_CANDIDATES', '2025-07-29 15:58:30', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(921, 1, 'Submitted candidacy application', '2025-07-29 21:58:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-07-29 21:58:54\",\"field_count\":4,\"has_files\":true}'),
(922, 1, 'VIEW_CANDIDATES', '2025-07-29 15:58:55', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(923, 1, 'VIEW_CANDIDATES', '2025-07-29 15:58:56', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(924, 1, 'ADD_MODERATOR', '2025-07-29 16:01:13', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Added moderator modesu Email modesuwmsueduph Precinct 20252026-CL-Student Council Elections-1'),
(931, 1, 'LOGIN', '2025-07-29 23:10:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '::1', '6.9334422,122.0730807', 'Successful login'),
(932, 1, 'ADD_ELECTION', '2025-07-29 17:24:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '::1', 'N/A', 'Added election: Name: New Elections, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(933, 1, 'VIEW_CANDIDATES', '2025-07-29 17:27:54', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome135000 Safari53736 OPR120000', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(934, 1, 'VIEW_CANDIDATES', '2025-07-29 17:27:57', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome135000 Safari53736 OPR120000', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(935, 1, 'VIEW_CANDIDATES', '2025-07-29 17:30:33', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome135000 Safari53736 OPR120000', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(936, 1, 'VIEW_CANDIDATES', '2025-07-29 17:30:35', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome135000 Safari53736 OPR120000', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(937, 1, 'VIEW_CANDIDATES', '2025-07-29 17:30:35', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome135000 Safari53736 OPR120000', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(938, 1, 'VIEW_CANDIDATES', '2025-07-29 17:30:36', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome135000 Safari53736 OPR120000', '::1', 'N/A', 'Viewed candidates for event ID 78 Candidacy Test'),
(939, 1, 'VIEW_CANDIDATES', '2025-07-29 17:31:26', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome135000 Safari53736 OPR120000', '::1', 'N/A', 'Viewed candidates for event ID 79 Candidacy Student Council Elections'),
(940, 1, 'VIEW_CANDIDATES', '2025-07-29 17:32:47', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome135000 Safari53736 OPR120000', '::1', 'N/A', 'Viewed candidates for event ID 84 Candidacy New Elections'),
(941, 1, 'LOGOUT', '2025-07-29 23:35:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful logout'),
(943, 1, 'VIEW_CANDIDATES', '2025-07-29 17:48:54', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome135000 Safari53736 OPR120000', '::1', 'N/A', 'Viewed candidates for event ID 84 Candidacy New Elections'),
(944, 1, 'LOGIN', '2025-07-30 01:16:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '6.9334422,122.0730807', 'Successful login'),
(946, 1, 'LOGIN', '2025-07-31 17:45:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '6.936589,122.0842007', 'Successful login'),
(947, 1, 'LOGIN', '2025-07-31 21:43:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '6.929188,122.0950951', 'Successful login'),
(948, 1, 'UPDATE_ELECTION', '2025-07-31 21:44:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=68, Name=New Elections, Status=Ended'),
(949, 1, 'DELETE_ELECTION', '2025-07-31 15:44:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: New Elections'),
(950, 1, 'DELETE_ELECTION', '2025-07-31 15:44:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Student Council Elections'),
(951, 1, 'LOGIN', '2025-07-31 22:28:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', '6.929188,122.0950951', 'Successful login'),
(952, 1, 'ADD_ELECTION', '2025-07-31 17:02:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test 1, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(953, 1, 'UPDATE_PARTY', '2025-07-31 17:03:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 99, Name: Party A, Election: Test 1'),
(954, 1, 'UPDATE_PARTY', '2025-07-31 17:08:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 99, Name: Party A, Election: Test 1'),
(955, 1, 'UPDATE_PARTY', '2025-07-31 17:08:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 99, Name: Party A, Election: Test 1, Image: party_688b86f0cce868.73942028.jpg'),
(956, 1, 'UPDATE_ELECTION', '2025-07-31 23:14:42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=69, Name=Student Council Elections, Status=Ongoing'),
(957, 1, 'UPDATE_PARTY', '2025-07-31 17:14:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 99, Name: Party A, Election: Student Council Elections'),
(958, 1, 'UPDATE_PARTY', '2025-07-31 17:14:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 100, Name: Party B, Election: Student Council Elections'),
(959, 1, 'UPDATE_ELECTION', '2025-07-31 23:48:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=69, Name=tite, Status=Ongoing'),
(960, 1, 'UPDATE_ELECTION', '2025-07-31 23:48:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=69, Name=Student Council Elections, Status=Ongoing'),
(962, NULL, 'Submitted candidacy application', '2025-08-01 01:02:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-08-01 01:02:07\",\"field_count\":4,\"has_files\":true}'),
(963, NULL, 'Submitted candidacy application', '2025-08-01 01:02:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-08-01 01:02:25\",\"field_count\":4,\"has_files\":true}'),
(964, NULL, 'Submitted candidacy application', '2025-08-01 01:02:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-08-01 01:02:36\",\"field_count\":4,\"has_files\":true}'),
(965, 1, 'VIEW_CANDIDATES', '2025-07-31 19:02:49', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 86 Candidacy Student Council Elections'),
(966, NULL, 'Submitted candidacy application', '2025-08-01 01:03:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-08-01 01:03:00\",\"field_count\":4,\"has_files\":true}'),
(967, NULL, 'Submitted candidacy application', '2025-08-01 01:04:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-08-01 01:04:00\",\"field_count\":4,\"has_files\":true}'),
(968, NULL, 'Submitted candidacy application', '2025-08-01 01:04:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-08-01 01:04:09\",\"field_count\":4,\"has_files\":true}'),
(969, 1, 'VIEW_CANDIDATES', '2025-07-31 19:04:16', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 86 Candidacy Student Council Elections'),
(970, 1, 'VIEW_CANDIDATES', '2025-07-31 19:04:18', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome138000 Safari53736', '::1', 'N/A', 'Viewed candidates for event ID 86 Candidacy Student Council Elections'),
(974, 1, 'LOGIN', '2025-08-01 01:20:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '::1', '6.929188,122.0950951', 'Successful login'),
(975, 1, 'LOGOUT', '2025-08-01 01:31:35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '::1', 'N/A', 'Successful logout'),
(976, 1, 'LOGIN', '2025-11-21 14:59:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', '6.946816,122.1066752', 'Successful login'),
(977, 1, 'ADD_ELECTION', '2025-11-21 08:22:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(978, 1, 'LOGIN', '2025-11-24 14:47:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', '7.7561856,125.0066432', 'Successful login'),
(979, 1, 'ADD_ELECTION', '2025-11-24 07:52:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test 1, School Year: 2025-2026, Semester: 1st Semester, Status: Upcoming, The period clashes with an existing Ongoing election. Status set to Upcoming.'),
(980, 1, 'ADD_ELECTION', '2025-11-24 07:56:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test er, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(981, 1, 'UPDATE_ELECTION', '2025-11-24 14:56:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=72, Name=Test er, Status=Ended'),
(982, 1, 'UPDATE_ELECTION', '2025-11-24 14:56:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=70, Name=Test, Status=Ended'),
(983, 1, 'UPDATE_ELECTION', '2025-11-24 14:56:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=71, Name=Test 1, Status=Ended'),
(984, 1, 'ADD_ELECTION', '2025-11-24 07:57:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: asd, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(985, 1, 'UPDATE_ELECTION', '2025-11-24 14:58:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=73, Name=asd, Status=Ended'),
(986, 1, 'DELETE_ELECTION', '2025-11-24 07:58:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: asd'),
(987, 1, 'ADD_ELECTION', '2025-11-24 07:58:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(988, 1, 'ADD_ELECTION', '2025-11-24 07:58:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test 1, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(989, 1, 'ADD_ELECTION', '2025-11-24 08:05:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: asd, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing, Note: This election period overlaps with other ongoing elections: Test, Test 1'),
(990, 1, 'UPDATE_ELECTION', '2025-11-24 15:05:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=76, Name=asd, Status=Ended'),
(991, 1, 'DELETE_ELECTION', '2025-11-24 08:05:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: asd'),
(992, 1, 'UPDATE_ELECTION', '2025-11-24 15:05:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=74, Name=Test, Status=Ended'),
(993, 1, 'DELETE_ELECTION', '2025-11-24 08:05:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Test'),
(994, 1, 'ADD_ELECTION', '2025-11-24 08:07:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2025-2026, Semester: 2nd Semester, Status: Ongoing, Note: This election period overlaps with other ongoing elections: Test 1'),
(995, 1, 'UPDATE_ELECTION', '2025-11-24 15:10:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=77, Name=Test, Status=Ended'),
(996, 1, 'UPDATE_ELECTION', '2025-11-24 15:10:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=75, Name=Test 1, Status=Ended'),
(997, 1, 'DELETE_ELECTION', '2025-11-24 08:10:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Test'),
(998, 1, 'DELETE_ELECTION', '2025-11-24 08:10:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Test 1'),
(999, 1, 'ADD_ELECTION', '2025-11-24 08:10:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(1000, 1, 'ADD_ELECTION', '2025-11-24 08:11:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test 2, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing, Note: This election period overlaps with other ongoing elections: Test'),
(1001, 1, 'ADD_ELECTION', '2025-11-24 08:15:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test hey, School Year: 2025-2026, Semester: 2nd Semester, Status: Ongoing, Note: This election period overlaps with other ongoing elections: Test, Test 2'),
(1002, 1, 'UPDATE_ELECTION', '2025-11-24 15:15:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=78, Name=Test, Status=Ended (was: Ongoing)'),
(1003, 1, 'UPDATE_ELECTION', '2025-11-24 15:16:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=79, Name=Test 2, Status=Ended (was: Ongoing)'),
(1004, 1, 'UPDATE_ELECTION', '2025-11-24 15:16:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=80, Name=Test hey, Status=Ended (was: Ongoing)'),
(1005, 1, 'DELETE_ELECTION', '2025-11-24 08:16:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Test hey'),
(1006, 1, 'DELETE_ELECTION', '2025-11-24 08:16:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Test 2'),
(1007, 1, 'DELETE_ELECTION', '2025-11-24 08:16:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Test'),
(1008, 1, 'ADD_ELECTION', '2025-11-24 08:18:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(1009, 1, 'ADD_ELECTION', '2025-11-24 08:21:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test 1, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing, Note: This election period overlaps with other ongoing elections: Test'),
(1010, 1, 'ADD_ELECTION', '2025-11-24 08:21:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Tester, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing, Note: This election period overlaps with other ongoing elections: Test, Test 1'),
(1011, 1, 'UPDATE_ELECTION', '2025-11-24 15:25:35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=81, Name=Test, Status=Ongoing (was: Ongoing)'),
(1012, 1, 'UPDATE_ELECTION', '2025-11-24 15:30:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=81, Name=Test, Status=Ended (was: Ongoing)'),
(1013, 1, 'UPDATE_ELECTION', '2025-11-24 15:30:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=82, Name=Test 1, Status=Ended (was: Ongoing)'),
(1014, 1, 'UPDATE_ELECTION', '2025-11-24 15:30:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated election ID=83, Name=Tester, Status=Ended (was: Ongoing)'),
(1015, 1, 'DELETE_ELECTION', '2025-11-24 08:30:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Test'),
(1016, 1, 'DELETE_ELECTION', '2025-11-24 08:30:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Test 1'),
(1017, 1, 'DELETE_ELECTION', '2025-11-24 08:30:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Tester'),
(1018, 1, 'ADD_ELECTION', '2025-11-24 08:32:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(1019, 1, 'ADD_ELECTION', '2025-11-24 08:32:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test 1, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing, Note: This election period overlaps with other ongoing elections: Test'),
(1020, 1, 'UPDATE_PARTY', '2025-11-24 08:33:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 105, Name: haha, Election: Test'),
(1021, 1, 'UPDATE_PARTY', '2025-11-24 08:34:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 102, Name: Independent, Election: Test 1'),
(1022, 1, 'UPDATE_PARTY', '2025-11-24 08:37:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 105, Name: haha, Election: Test'),
(1023, 1, 'UPDATE_PARTY', '2025-11-24 08:37:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 105, Name: hahaha, Election: Test'),
(1024, 1, 'UPDATE_PARTY', '2025-11-24 08:41:42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 106, Name: haha, Election: Test'),
(1025, 1, 'UPDATE_PARTY', '2025-11-24 08:41:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 106, Name: haha, Election: Test'),
(1026, 1, 'UPDATE_PARTY', '2025-11-24 08:42:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 106, Name: hahahaha, Election: Test'),
(1027, 1, 'UPDATE_PARTY', '2025-11-24 08:42:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 105, Name: haha, Election: Test'),
(1028, 1, 'UPDATE_PARTY', '2025-11-24 08:44:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 105, Name: haha, Election: Test'),
(1029, 1, 'UPDATE_PARTY', '2025-11-24 08:46:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 105, Name: haha, Election: Test'),
(1030, 1, 'DELETE_PARTY', '2025-11-24 08:50:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted party ID: 103, Name: Independent, Election: Test 1'),
(1031, 1, 'DELETE_PARTY', '2025-11-24 08:50:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted party ID: 102, Name: Independent, Election: Test 1'),
(1032, 1, 'DELETE_POSITION_PARTY', '2025-11-24 15:50:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"President\" for party \"haha\". Candidates: 32. Position row removed: yes'),
(1033, 1, 'DELETE_POSITION_PARTY', '2025-11-24 15:50:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"President\" for party \"hehe\". Candidates: 31. Position row removed: yes'),
(1034, 1, 'DELETE_PARTY', '2025-11-24 08:50:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted party ID: 104, Name: hehe, Election: Test'),
(1035, 1, 'DELETE_PARTY', '2025-11-24 08:50:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted party ID: 107, Name: hahahaha, Election: Test'),
(1036, 1, 'DELETE_PARTY', '2025-11-24 08:50:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted party ID: 106, Name: hahahaha, Election: Test'),
(1037, 1, 'DELETE_PARTY', '2025-11-24 08:50:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted party ID: 108, Name: haha, Election: Test 1'),
(1038, 1, 'DELETE_PARTY', '2025-11-24 08:52:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted party ID: 105, Name: haha, Election: Test'),
(1039, 1, 'UPDATE_CANDIDACY', '2025-11-24 16:31:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated candidacy ID=40, Name=Test, Status=Ongoing, start=2025-12-01 15:30:00, end=2025-12-31 16:30:00'),
(1040, 1, 'UPDATE_CANDIDACY', '2025-11-24 16:31:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated candidacy ID=40, Name=Test 1, Status=Ongoing, start=2025-12-01 15:30:00, end=2025-12-31 16:30:00'),
(1041, 1, 'UPDATE_CANDIDACY', '2025-11-24 16:31:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated candidacy ID=40, Name=Test, Status=Ongoing, start=2025-12-01 15:30:00, end=2025-12-31 16:30:00'),
(1042, 1, 'UPDATE_CANDIDACY', '2025-11-24 16:35:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated candidacy ID=41, Name=Test 1, Status=Ongoing, start=2025-12-01 15:32:00, end=2025-12-31 16:30:00'),
(1043, 1, 'UPDATE_CANDIDACY', '2025-11-24 16:35:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated candidacy ID=41, Name=Test 1, Status=Ongoing, start=2025-12-01 15:32:00, end=2025-12-31 16:30:00'),
(1044, 1, 'VIEW_CANDIDATES', '2025-11-24 14:43:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 87, Candidacy: Test'),
(1045, 1, 'VIEW_CANDIDATES', '2025-11-24 14:44:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 87, Candidacy: Test'),
(1046, 1, 'VIEW_CANDIDATES', '2025-11-24 14:44:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 87, Candidacy: Test'),
(1047, 1, 'VIEW_CANDIDATES', '2025-11-24 14:45:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 87, Candidacy: Test'),
(1048, 1, 'VIEW_CANDIDATES', '2025-11-24 14:46:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 87, Candidacy: Test'),
(1049, 1, 'VIEW_CANDIDATES', '2025-11-24 14:47:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 87, Candidacy: Test'),
(1050, 1, 'VIEW_CANDIDATES', '2025-11-24 14:48:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 87, Candidacy: Test'),
(1051, 1, 'LOGIN', '2025-11-25 23:25:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', '8.503296,123.3682432', 'Successful login'),
(1052, 1, 'DELETE_POSITION_PARTY', '2025-11-26 11:22:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"asd\" for party \"A\". Candidates: 35. Position row removed: yes'),
(1053, 1, 'DELETE_POSITION_PARTY', '2025-11-26 11:23:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"A\". Candidates: 38. Position row removed: yes'),
(1054, 1, 'DELETE_POSITION_PARTY', '2025-11-26 11:23:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"A\". Candidates: 37. Position row removed: yes'),
(1055, NULL, 'Submitted candidacy application', '2025-11-26 11:29:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-11-26 11:29:07\",\"field_count\":4,\"has_files\":true}'),
(1056, NULL, 'Submitted candidacy application', '2025-11-26 11:32:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-11-26 11:32:11\",\"field_count\":4,\"has_files\":true}'),
(1057, NULL, 'Submitted candidacy application', '2025-11-26 11:32:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-11-26 11:32:47\",\"field_count\":4,\"has_files\":true}'),
(1058, NULL, 'Submitted candidacy application', '2025-11-26 11:45:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-11-26 11:45:36\",\"field_count\":4,\"has_files\":true}'),
(1059, NULL, 'Submitted candidacy application', '2025-11-26 11:50:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-11-26 11:50:52\",\"field_count\":4,\"has_files\":true}'),
(1060, NULL, 'Submitted candidacy application', '2025-11-26 11:51:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-11-26 11:51:01\",\"field_count\":4,\"has_files\":true}'),
(1061, NULL, 'Submitted candidacy application', '2025-11-26 11:51:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-11-26 11:51:08\",\"field_count\":4,\"has_files\":true}'),
(1062, 1, 'VIEW_CANDIDATES', '2025-11-26 04:51:14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 88, Candidacy: Test 1'),
(1063, 1, 'VIEW_CANDIDATES', '2025-11-26 04:51:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 87, Candidacy: Test'),
(1064, 1, 'VIEW_CANDIDATES', '2025-11-26 04:51:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 87, Candidacy: Test'),
(1065, 1, 'VIEW_CANDIDATES', '2025-11-26 04:51:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 87, Candidacy: Test'),
(1066, 1, 'VIEW_CANDIDATES', '2025-11-26 04:51:42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 87, Candidacy: Test'),
(1067, 1, 'VIEW_CANDIDATES', '2025-11-26 04:51:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 87, Candidacy: Test'),
(1068, 1, 'VIEW_CANDIDATES', '2025-11-26 04:51:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 87, Candidacy: Test'),
(1069, 1, 'VIEW_CANDIDATES', '2025-11-26 04:51:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 88, Candidacy: Test 1'),
(1070, 1, 'VIEW_CANDIDATES', '2025-11-26 04:51:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 88, Candidacy: Test 1'),
(1071, 1, 'LOGIN', '2025-11-26 12:16:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '::1', '6.9533696,122.0739072', 'Successful login'),
(1077, 1, 'LOGIN', '2025-11-27 02:30:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', '6.9533696,122.0739072', 'Successful login'),
(1078, 1, 'ADD_MODERATOR', '2025-11-26 19:57:53', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome142000 Safari53736', '::1', 'N/A', 'Added moderator modcsnew Email modcsnewgmailcom Precinct'),
(1079, 1, 'ADD_MODERATOR', '2025-11-26 20:19:24', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome142000 Safari53736', '::1', 'N/A', 'Added moderator modacs Email teststaffadducom Precinct'),
(1080, 1, 'UPDATE_MODERATOR', '2025-11-26 21:18:05', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome142000 Safari53736', '::1', 'N/A', 'Updated moderator ID 117 Name modcser Email modcswmsucom Precincts 2025-2026 1stCCSTestNEW-1 2025-2026 1stCCSTest 1newer-1'),
(1082, 1, 'LOGIN', '2025-11-27 11:21:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', '6.936633,122.0842099', 'Successful login'),
(1085, 1, 'LOGIN', '2025-11-28 06:29:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', '6.9533696,122.0739072', 'Successful login'),
(1097, 1, 'LOGIN', '2025-12-01 11:54:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', '6.946816,122.126336', 'Successful login'),
(1098, 1, 'ADD_ELECTION', '2025-12-01 05:08:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test New, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(1099, 1, 'ADD_ELECTION', '2025-12-01 05:08:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Bass, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing, Note: This election period overlaps with other ongoing elections: Test New'),
(1100, NULL, 'Submitted candidacy application', '2025-12-01 13:11:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-12-01 13:11:11\",\"field_count\":4,\"has_files\":true}'),
(1101, NULL, 'Submitted candidacy application', '2025-12-01 13:36:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-12-01 13:36:28\",\"field_count\":4,\"has_files\":true}'),
(1102, NULL, 'Submitted candidacy application', '2025-12-01 13:36:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-12-01 13:36:43\",\"field_count\":4,\"has_files\":true}'),
(1103, 1, 'VIEW_CANDIDATES', '2025-12-01 07:05:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 90, Candidacy: Bass'),
(1104, 1, 'VIEW_CANDIDATES', '2025-12-01 07:05:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 89, Candidacy: Test New'),
(1105, 1, 'VIEW_CANDIDATES', '2025-12-01 07:05:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 89, Candidacy: Test New'),
(1106, 1, 'VIEW_CANDIDATES', '2025-12-01 07:06:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 90, Candidacy: Bass'),
(1107, 1, 'Submitted candidacy application', '2025-12-01 14:06:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-12-01 14:06:31\",\"field_count\":4,\"has_files\":true}'),
(1108, 1, 'VIEW_CANDIDATES', '2025-12-01 07:06:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 90, Candidacy: Bass'),
(1109, 1, 'VIEW_CANDIDATES', '2025-12-01 07:06:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 90, Candidacy: Bass'),
(1110, 1, 'Submitted candidacy application', '2025-12-01 14:07:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-12-01 14:07:09\",\"field_count\":4,\"has_files\":true}'),
(1111, 1, 'VIEW_CANDIDATES', '2025-12-01 07:07:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 90, Candidacy: Bass'),
(1112, 1, 'UPDATE_MODERATOR', '2025-12-01 07:07:56', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome142000 Safari53736', '::1', 'N/A', 'Updated moderator ID 117 Name modcser Email modcswmsucom Precincts 2025-2026 1stCCSTest NewCS-2'),
(1113, 1, 'UPDATE_MODERATOR', '2025-12-01 07:08:29', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome142000 Safari53736', '::1', 'N/A', 'Updated moderator ID 117 Name modcser Email modcswmsucom Precincts 2025-2026 1stCCSBassNEWERS-1'),
(1115, 1, 'UPDATE_MODERATOR', '2025-12-01 07:09:27', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome142000 Safari53736', '::1', 'N/A', 'Updated moderator ID 117 Name modcser Email modcswmsucom Precincts'),
(1118, 1, 'LOGIN', '2025-12-02 13:02:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', '9.5485952,125.58336', 'Successful login'),
(1119, 1, 'ADD_MODERATOR', '2025-12-02 13:15:58', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome142000 Safari53736', '::1', 'N/A', 'Added moderator testerbazinga Email testergmailcom Precinct ID 163'),
(1121, 1, 'VIEW_CANDIDATES', '2025-12-02 13:24:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 90, Candidacy: Bass'),
(1122, 1, 'LOGIN', '2025-12-02 20:45:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', '9.5485952,125.58336', 'Successful login'),
(1123, 1, 'LOGIN', '2025-12-04 02:51:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', '6.9599232,122.1328896', 'Successful login'),
(1125, 1, 'ADD_MODERATOR', '2025-12-04 02:59:40', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome142000 Safari53736', '::1', 'N/A', 'Added moderator modcserzxc Email modcserwmsucom Precinct ID 163'),
(1126, 1, 'ADD_ELECTION', '2025-12-04 03:03:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: WMSU Election, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(1127, 1, 'UPDATE_MODERATOR', '2025-12-04 03:22:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 123 - precinct 165'),
(1128, 1, 'ADD_MODERATOR', '2025-12-04 03:26:37', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome142000 Safari53736', '::1', 'N/A', 'Added moderator modlaw Email modlawwmsueduph Precinct ID 165'),
(1129, NULL, 'Submitted candidacy application', '2025-12-04 03:31:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-12-04 03:31:40\",\"field_count\":4,\"has_files\":true}'),
(1130, 1, 'VIEW_CANDIDATES', '2025-12-04 03:31:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1131, 1, 'VIEW_CANDIDATES', '2025-12-04 03:31:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1132, 1, 'VIEW_CANDIDATES', '2025-12-04 03:32:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1133, 1, 'VIEW_CANDIDATES', '2025-12-04 03:38:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1134, 1, 'VIEW_CANDIDATES', '2025-12-04 03:40:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1135, 1, 'VIEW_CANDIDATES', '2025-12-04 03:40:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1136, 1, 'VIEW_CANDIDATES', '2025-12-04 03:41:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1137, 1, 'VIEW_CANDIDATES', '2025-12-04 03:41:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1138, 1, 'Submitted candidacy application', '2025-12-04 03:41:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-12-04 03:41:58\",\"field_count\":4,\"has_files\":true}'),
(1139, 1, 'VIEW_CANDIDATES', '2025-12-04 03:41:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1140, 1, 'VIEW_CANDIDATES', '2025-12-04 03:42:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1141, 1, 'Submitted candidacy application', '2025-12-04 03:42:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-12-04 03:42:52\",\"field_count\":4,\"has_files\":true}'),
(1142, 1, 'VIEW_CANDIDATES', '2025-12-04 03:42:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1143, 1, 'Submitted candidacy application', '2025-12-04 03:43:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-12-04 03:43:22\",\"field_count\":4,\"has_files\":true}'),
(1144, 1, 'VIEW_CANDIDATES', '2025-12-04 03:43:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1145, 1, 'Submitted candidacy application', '2025-12-04 03:44:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-12-04 03:44:06\",\"field_count\":4,\"has_files\":true}'),
(1146, 1, 'VIEW_CANDIDATES', '2025-12-04 03:44:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1147, NULL, 'Submitted candidacy application', '2025-12-04 03:44:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-12-04 03:44:28\",\"field_count\":4,\"has_files\":true}'),
(1148, NULL, 'Submitted candidacy application', '2025-12-04 03:45:35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2025-12-04 03:45:35\",\"field_count\":4,\"has_files\":true}'),
(1149, 1, 'VIEW_CANDIDATES', '2025-12-04 03:45:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1150, 1, 'VIEW_CANDIDATES', '2025-12-04 03:46:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1151, 1, 'VIEW_CANDIDATES', '2025-12-04 03:46:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election');
INSERT INTO `user_activities` (`id`, `user_id`, `action`, `timestamp`, `device_info`, `ip_address`, `location`, `behavior_patterns`) VALUES
(1152, 1, 'Submitted candidacy application', '2025-12-04 03:47:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-12-04 03:47:15\",\"field_count\":4,\"has_files\":true}'),
(1153, 1, 'VIEW_CANDIDATES', '2025-12-04 03:47:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1155, 1, 'UPDATE_MODERATOR', '2025-12-04 03:50:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 122 - precinct 0'),
(1157, 1, 'UPDATE_MODERATOR', '2025-12-04 03:53:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 117 - precinct 163'),
(1158, 1, 'UPDATE_MODERATOR', '2025-12-04 03:54:14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 118 - precinct 164'),
(1159, 1, 'LOGIN', '2025-12-09 11:20:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', '6.9366203,122.0842109', 'Successful login'),
(1163, 1, 'LOGIN', '2025-12-12 14:15:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', '6.936445566461944,122.0842962649738', 'Successful login'),
(1164, 1, 'ADD_ELECTION', '2025-12-12 14:15:42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test New Election, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing'),
(1165, 1, 'LOGOUT', '2025-12-12 14:15:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful logout'),
(1167, 1, 'LOGIN', '2025-12-13 13:20:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', '6.936446621906708,122.08425477958409', 'Successful login'),
(1168, 1, 'ADD_ELECTION', '2025-12-13 13:23:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: zxc, School Year: 2025-2026, Semester: 1st Semester, Status: Ongoing, Note: This election period overlaps with other ongoing elections: Test New Election'),
(1169, 1, 'VIEW_CANDIDATES', '2025-12-13 14:01:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 92, Candidacy: WMSU Election'),
(1170, 1, 'LOGIN', '2025-12-13 22:08:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', '6.9364479884120165,122.08426434077253', 'Successful login'),
(1171, 1, 'ADD_ELECTION', '2025-12-14 01:41:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: test, Academic Year ID: 4, Status: Ongoing'),
(1172, 1, 'DELETE_PARTY', '2025-12-14 02:10:42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted party ID: 115, Name: Independent, Election: test'),
(1173, 1, 'DELETE_PARTY', '2025-12-14 02:10:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted party ID: 117, Name: asdasd, Election: test'),
(1174, 1, 'DELETE_PARTY', '2025-12-14 02:10:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted party ID: 116, Name: asd, Election: test'),
(1175, NULL, 'Submitted candidacy application', '2025-12-14 05:52:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-12-14 05:52:09\",\"field_count\":4,\"has_files\":true}'),
(1176, NULL, 'Submitted candidacy application', '2025-12-14 05:52:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-12-14 05:52:20\",\"field_count\":4,\"has_files\":true}'),
(1177, NULL, 'Submitted candidacy application', '2025-12-14 05:52:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-12-14 05:52:27\",\"field_count\":4,\"has_files\":true}'),
(1178, NULL, 'Submitted candidacy application', '2025-12-14 05:52:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-12-14 05:52:34\",\"field_count\":4,\"has_files\":true}'),
(1179, 1, 'VIEW_CANDIDATES', '2025-12-14 05:52:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1180, 1, 'VIEW_CANDIDATES', '2025-12-14 08:31:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1181, 1, 'VIEW_CANDIDATES', '2025-12-14 08:31:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1182, 1, 'VIEW_CANDIDATES', '2025-12-14 08:31:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1183, 1, 'VIEW_CANDIDATES', '2025-12-14 08:31:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1184, 1, 'VIEW_CANDIDATES', '2025-12-14 08:32:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: test'),
(1185, 1, 'VIEW_CANDIDATES', '2025-12-14 08:33:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1186, 1, 'VIEW_CANDIDATES', '2025-12-14 08:33:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1187, 1, 'VIEW_CANDIDATES', '2025-12-14 08:33:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1188, 1, 'VIEW_CANDIDATES', '2025-12-14 08:33:42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1189, 1, 'VIEW_CANDIDATES', '2025-12-14 08:33:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1190, 1, 'VIEW_CANDIDATES', '2025-12-14 08:34:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1191, 1, 'VIEW_CANDIDATES', '2025-12-14 08:34:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1192, 1, 'VIEW_CANDIDATES', '2025-12-14 08:34:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1193, 1, 'VIEW_CANDIDATES', '2025-12-14 08:35:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1194, 1, 'VIEW_CANDIDATES', '2025-12-14 08:38:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1195, 1, 'VIEW_CANDIDATES', '2025-12-14 08:38:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1196, 1, 'VIEW_CANDIDATES', '2025-12-14 08:39:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1197, 1, 'VIEW_CANDIDATES', '2025-12-14 08:39:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1198, 1, 'VIEW_CANDIDATES', '2025-12-14 08:40:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1199, 1, 'Submitted candidacy application', '2025-12-14 08:40:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2025-12-14 08:40:30\",\"field_count\":4,\"has_files\":true}'),
(1200, 1, 'VIEW_CANDIDATES', '2025-12-14 08:40:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1201, 1, 'VIEW_CANDIDATES', '2025-12-14 08:42:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1202, 1, 'VIEW_CANDIDATES', '2025-12-14 08:43:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1203, 1, 'VIEW_CANDIDATES', '2025-12-14 08:43:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1204, 1, 'LOGIN', '2025-12-14 13:46:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', '6.936445566461944,122.0842962649738', 'Successful login'),
(1205, 1, 'VIEW_CANDIDATES', '2025-12-14 13:46:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1206, 1, 'VIEW_CANDIDATES', '2025-12-14 13:47:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1207, 1, 'UPDATE_MODERATOR', '2025-12-14 17:37:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 117 - precinct 167'),
(1208, 1, 'ADD_MODERATOR', '2025-12-14 17:39:01', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome143000 Safari53736', '::1', 'N/A', 'Added moderator modcsnew Email modcsnewwmsueduph Precinct ID 168'),
(1209, 1, 'UPDATE_MODERATOR', '2025-12-14 17:40:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 117 - precinct 0'),
(1210, 1, 'UPDATE_MODERATOR', '2025-12-14 17:40:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 117 - precinct 167'),
(1217, 1, 'LOGIN', '2025-12-14 19:55:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', '6.936445566461944,122.0842962649738', 'Successful login'),
(1218, 1, 'VIEW_CANDIDATES', '2025-12-14 22:20:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1219, 1, 'LOGIN', '2025-12-14 23:30:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', '6.936445566461944,122.0842962649738', 'Successful login'),
(1238, 1, 'LOGIN', '2025-12-15 00:41:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Successful login'),
(1239, 1, 'ADD_ELECTION', '2025-12-15 00:41:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Added election: Name: Test New, Academic Year ID: 8, Status: Ongoing'),
(1240, 1, 'ADD_ELECTION', '2025-12-15 00:50:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Added election: Name: testzxc, Academic Year ID: 4, Status: Ongoing, Note: Overlaps with other ongoing elections: Test New'),
(1241, 1, 'VIEW_CANDIDATES', '2025-12-15 01:22:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 95, Candidacy: 92'),
(1242, 1, 'VIEW_CANDIDATES', '2025-12-15 01:23:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 95, Candidacy: 92'),
(1243, 1, 'Submitted candidacy application', '2025-12-14 09:23:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', NULL, '{\"form_submission_time\":\"2025-12-15 01:23:53\",\"field_count\":4,\"has_files\":true}'),
(1244, 1, 'VIEW_CANDIDATES', '2025-12-15 01:23:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 95, Candidacy: 92'),
(1245, 1, 'Submitted candidacy application', '2025-12-14 09:24:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', NULL, '{\"form_submission_time\":\"2025-12-15 01:24:03\",\"field_count\":4,\"has_files\":true}'),
(1246, 1, 'VIEW_CANDIDATES', '2025-12-15 01:24:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 95, Candidacy: 92'),
(1247, 1, 'Submitted candidacy application', '2025-12-14 09:24:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', NULL, '{\"form_submission_time\":\"2025-12-15 01:24:15\",\"field_count\":4,\"has_files\":true}'),
(1248, 1, 'VIEW_CANDIDATES', '2025-12-15 01:24:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 95, Candidacy: 92'),
(1249, NULL, 'Submitted candidacy application', '2025-12-14 09:25:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', NULL, '{\"form_submission_time\":\"2025-12-15 01:25:06\",\"field_count\":4,\"has_files\":true}'),
(1250, 1, 'VIEW_CANDIDATES', '2025-12-15 01:25:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 95, Candidacy: 92'),
(1251, 1, 'VIEW_CANDIDATES', '2025-12-15 01:25:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 95, Candidacy: 92'),
(1252, 1, 'Submitted candidacy application', '2025-12-14 09:25:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', NULL, '{\"form_submission_time\":\"2025-12-15 01:25:23\",\"field_count\":4,\"has_files\":true}'),
(1253, 1, 'VIEW_CANDIDATES', '2025-12-15 01:25:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 95, Candidacy: 92'),
(1254, 1, 'Submitted candidacy application', '2025-12-14 09:25:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', NULL, '{\"form_submission_time\":\"2025-12-15 01:25:37\",\"field_count\":4,\"has_files\":true}'),
(1255, 1, 'VIEW_CANDIDATES', '2025-12-15 01:25:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 95, Candidacy: 92'),
(1256, 1, 'VIEW_CANDIDATES', '2025-12-15 01:25:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 95, Candidacy: 92'),
(1258, 1, 'VIEW_CANDIDATES', '2025-12-15 01:40:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 95, Candidacy: 92'),
(1259, 1, 'VIEW_CANDIDATES', '2025-12-15 01:48:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 95, Candidacy: 92'),
(1264, 1, 'UPDATE_MODERATOR', '2025-12-14 10:26:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Updated moderator 117 - precinct 167'),
(1265, 1, 'LOGIN', '2025-12-15 02:32:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Successful login'),
(1266, 1, 'ADD_ELECTION', '2025-12-15 02:32:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Added election: Name: Main Electionism, Academic Year ID: 4, Status: Ongoing'),
(1267, 1, 'VIEW_CANDIDATES', '2025-12-15 02:34:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 96, Candidacy: 94'),
(1268, 1, 'VIEW_CANDIDATES', '2025-12-15 02:35:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 96, Candidacy: 94'),
(1269, 1, 'Submitted candidacy application', '2025-12-14 10:36:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', NULL, '{\"form_submission_time\":\"2025-12-15 02:36:39\",\"field_count\":4,\"has_files\":true}'),
(1270, 1, 'VIEW_CANDIDATES', '2025-12-15 02:36:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 96, Candidacy: 94'),
(1271, 1, 'VIEW_CANDIDATES', '2025-12-15 02:37:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 96, Candidacy: 94'),
(1272, 1, 'VIEW_CANDIDATES', '2025-12-15 02:37:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 96, Candidacy: 94'),
(1273, 1, 'Submitted candidacy application', '2025-12-14 10:37:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', NULL, '{\"form_submission_time\":\"2025-12-15 02:37:22\",\"field_count\":4,\"has_files\":true}'),
(1274, 1, 'VIEW_CANDIDATES', '2025-12-15 02:37:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 96, Candidacy: 94'),
(1275, 1, 'Submitted candidacy application', '2025-12-14 10:37:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', NULL, '{\"form_submission_time\":\"2025-12-15 02:37:33\",\"field_count\":4,\"has_files\":true}'),
(1276, 1, 'VIEW_CANDIDATES', '2025-12-15 02:37:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 96, Candidacy: 94'),
(1277, NULL, 'Submitted candidacy application', '2025-12-14 10:38:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', NULL, '{\"form_submission_time\":\"2025-12-15 02:38:59\",\"field_count\":4,\"has_files\":true}'),
(1278, 1, 'VIEW_CANDIDATES', '2025-12-15 02:39:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 96, Candidacy: 94'),
(1279, 1, 'VIEW_CANDIDATES', '2025-12-15 02:39:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 96, Candidacy: 94'),
(1280, 1, 'UPDATE_MODERATOR', '2025-12-14 10:39:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Updated moderator 117 - precinct 167'),
(1281, 1, 'UPDATE_MODERATOR', '2025-12-14 10:40:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Updated moderator 132 - precinct 168'),
(1287, 1, 'LOGOUT', '2025-12-15 06:39:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Successful logout'),
(1288, 1, 'LOGIN', '2025-12-15 06:43:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Successful login'),
(1289, 1, 'LOGIN', '2025-12-15 07:30:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '138.84.107.198', '6.9486397,122.0975429', 'Successful login'),
(1290, 1, 'ADD_ELECTION', '2025-12-15 07:37:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '138.84.107.198', 'N/A', 'Added election: Name: Wmsu Elects, Academic Year ID: 11, Status: Ongoing'),
(1293, 1, 'UPDATE_MODERATOR', '2025-12-14 15:48:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '138.84.107.198', 'N/A', 'Updated moderator 117 - precinct 168'),
(1295, NULL, 'Submitted candidacy application', '2025-12-14 15:50:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '138.84.107.198', NULL, '{\"form_submission_time\":\"2025-12-15 07:50:31\",\"field_count\":4,\"has_files\":true}'),
(1296, NULL, 'Submitted candidacy application', '2025-12-14 15:51:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '138.84.107.198', NULL, '{\"form_submission_time\":\"2025-12-15 07:51:10\",\"field_count\":4,\"has_files\":true}'),
(1297, NULL, 'Submitted candidacy application', '2025-12-14 15:51:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '138.84.107.198', NULL, '{\"form_submission_time\":\"2025-12-15 07:51:36\",\"field_count\":4,\"has_files\":true}'),
(1298, NULL, 'Submitted candidacy application', '2025-12-14 15:51:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '138.84.107.198', NULL, '{\"form_submission_time\":\"2025-12-15 07:51:51\",\"field_count\":4,\"has_files\":true}'),
(1299, 1, 'VIEW_CANDIDATES', '2025-12-15 07:52:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '138.84.107.198', 'N/A', 'Viewed candidates for event ID: 97, Candidacy: 95'),
(1300, NULL, 'Submitted candidacy application', '2025-12-14 15:53:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '138.84.107.198', NULL, '{\"form_submission_time\":\"2025-12-15 07:53:02\",\"field_count\":4,\"has_files\":true}'),
(1301, NULL, 'Submitted candidacy application', '2025-12-14 15:53:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '138.84.107.198', NULL, '{\"form_submission_time\":\"2025-12-15 07:53:17\",\"field_count\":4,\"has_files\":true}'),
(1302, 1, 'VIEW_CANDIDATES', '2025-12-15 07:53:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '138.84.107.198', 'N/A', 'Viewed candidates for event ID: 97, Candidacy: 95'),
(1303, NULL, 'Submitted candidacy application', '2025-12-14 15:54:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '138.84.107.198', NULL, '{\"form_submission_time\":\"2025-12-15 07:54:24\",\"field_count\":4,\"has_files\":true}'),
(1304, 1, 'VIEW_CANDIDATES', '2025-12-15 07:55:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '138.84.107.198', 'N/A', 'Viewed candidates for event ID: 97, Candidacy: 95'),
(1305, 1, 'VIEW_CANDIDATES', '2025-12-15 07:55:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '138.84.107.198', 'N/A', 'Viewed candidates for event ID: 97, Candidacy: 95'),
(1310, 1, 'UPDATE_MODERATOR', '2025-12-14 17:01:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '138.84.107.198', 'N/A', 'Updated moderator 117 - precinct 168'),
(1311, 1, 'LOGIN', '2025-12-15 09:25:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Successful login'),
(1312, 1, 'LOGIN', '2025-12-15 12:07:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Successful login'),
(1313, 1, 'ADD_ELECTION', '2025-12-15 12:08:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Added election: Name: WMSU Election, Academic Year ID: 12, Status: Ongoing'),
(1320, 1, 'VIEW_CANDIDATES', '2025-12-15 12:28:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 98, Candidacy: 96'),
(1324, 1, 'LOGIN', '2025-12-15 12:41:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Successful login'),
(1325, 1, 'VIEW_CANDIDATES', '2025-12-15 12:42:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 98, Candidacy: 96'),
(1326, 1, 'Submitted candidacy application', '2025-12-14 20:42:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', NULL, '{\"form_submission_time\":\"2025-12-15 12:42:52\",\"field_count\":4,\"has_files\":true}'),
(1327, 1, 'VIEW_CANDIDATES', '2025-12-15 12:42:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 98, Candidacy: 96'),
(1328, 1, 'Submitted candidacy application', '2025-12-14 20:43:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', NULL, '{\"form_submission_time\":\"2025-12-15 12:43:03\",\"field_count\":4,\"has_files\":true}'),
(1329, 1, 'VIEW_CANDIDATES', '2025-12-15 12:43:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 98, Candidacy: 96'),
(1330, 1, 'Submitted candidacy application', '2025-12-14 20:43:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', NULL, '{\"form_submission_time\":\"2025-12-15 12:43:13\",\"field_count\":4,\"has_files\":true}'),
(1331, 1, 'VIEW_CANDIDATES', '2025-12-15 12:43:14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 98, Candidacy: 96'),
(1332, 1, 'Submitted candidacy application', '2025-12-14 20:43:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', NULL, '{\"form_submission_time\":\"2025-12-15 12:43:24\",\"field_count\":4,\"has_files\":true}'),
(1333, 1, 'VIEW_CANDIDATES', '2025-12-15 12:43:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Viewed candidates for event ID: 98, Candidacy: 96'),
(1335, 1, 'UPDATE_MODERATOR', '2025-12-14 20:44:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '138.84.107.198', 'N/A', 'Updated moderator 117 - precinct 168'),
(1338, 1, 'UPDATE_MODERATOR', '2025-12-14 20:55:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '138.84.107.198', 'N/A', 'Updated moderator 117 - precinct 167'),
(1339, 1, 'LOGIN', '2025-12-15 12:59:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', '6.936445566461944,122.0842962649738', 'Successful login'),
(1340, 1, 'LOGOUT', '2025-12-15 13:22:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '49.149.100.110', 'N/A', 'Successful logout'),
(1342, 1, 'LOGIN', '2026-01-07 07:26:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '138.84.109.216', '6.9486328,122.0975415', 'Successful login'),
(1345, 1, 'LOGIN', '2026-01-27 08:42:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '138.84.108.197', '6.948697666639986,122.09808045962276', 'Successful login'),
(1346, 1, 'LOGIN', '2026-02-04 11:01:14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.80.40', 'N/A', 'Successful login'),
(1350, 1, 'LOGIN', '2026-02-04 11:10:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.80.40', 'N/A', 'Successful login'),
(1351, 1, 'ADD_ELECTION', '2026-02-04 11:14:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.80.40', 'N/A', 'Added election: Name: USC ELECTION 2025-2026 2ND, Academic Year ID: 14, Status: Ongoing'),
(1353, 1, 'LOGIN', '2026-02-04 11:44:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.80.40', 'N/A', 'Successful login'),
(1354, 1, 'ADD_MODERATOR', '2026-02-03 19:49:53', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome144000 Safari53736', '175.176.80.40', 'N/A', 'Added moderator CCS-CS Email modcs2wmsucom Precinct ID 169'),
(1358, 1, 'LOGIN', '2026-02-04 12:02:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.80.40', 'N/A', 'Successful login'),
(1361, 1, 'LOGIN', '2026-02-04 12:07:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.80.40', 'N/A', 'Successful login'),
(1362, 1, 'LOGOUT', '2026-02-04 12:25:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.127', 'N/A', 'Successful logout'),
(1363, 1, 'LOGIN', '2026-02-04 21:44:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1364, 1, 'LOGIN', '2026-02-04 22:11:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1365, 1, 'LOGOUT', '2026-02-04 22:12:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful logout'),
(1368, 1, 'LOGIN', '2026-02-04 22:19:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1369, 1, 'VIEW_CANDIDATES', '2026-02-04 22:31:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Viewed candidates for event ID: 99, Candidacy: 97'),
(1370, 1, 'LOGIN', '2026-02-05 00:12:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1371, 1, 'VIEW_CANDIDATES', '2026-02-05 00:20:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Viewed candidates for event ID: 96, Candidacy: 94'),
(1372, 1, 'VIEW_CANDIDATES', '2026-02-05 00:21:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Viewed candidates for event ID: 94, Candidacy: 91'),
(1374, 1, 'LOGIN', '2026-02-05 00:46:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1375, 1, 'ADD_ELECTION', '2026-02-05 02:21:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Added election: Name: CHEM ENG FACULTY OFFICERS, Academic Year ID: 14, Status: Ongoing'),
(1376, 1, 'ADD_ELECTION', '2026-02-05 02:57:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Added election: Name: ELECTRICAL ENG CLASS ELECTION 2025-2026, Academic Year ID: 14, Status: Ongoing'),
(1378, 1, 'LOGIN', '2026-02-05 03:25:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1380, 1, 'LOGIN', '2026-02-05 03:28:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1381, 1, 'LOGIN', '2026-02-05 03:31:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1382, 1, 'LOGIN', '2026-02-05 03:38:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1383, NULL, 'Submitted candidacy application', '2026-02-04 11:39:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', NULL, '{\"form_submission_time\":\"2026-02-05 03:39:19\",\"field_count\":8,\"has_files\":true}'),
(1384, 1, 'LOGIN', '2026-02-05 03:43:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1386, 1, 'LOGIN', '2026-02-05 03:58:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1387, 1, 'LOGIN', '2026-02-05 03:59:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1388, 1, 'LOGIN', '2026-02-05 04:00:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1389, 1, 'LOGIN', '2026-02-05 04:01:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1390, 1, 'LOGIN', '2026-02-05 04:03:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1391, 1, 'LOGIN', '2026-02-05 04:04:42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1393, 1, 'LOGIN', '2026-02-05 04:07:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1394, 1, 'LOGIN', '2026-02-05 09:18:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.84.177', 'N/A', 'Successful login'),
(1395, 1, 'LOGIN', '2026-02-05 10:39:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.85.221', 'N/A', 'Successful login'),
(1397, 1, 'LOGIN', '2026-02-05 10:51:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.85.221', 'N/A', 'Successful login'),
(1398, 1, 'LOGIN', '2026-02-05 15:18:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.85.152', 'N/A', 'Successful login'),
(1399, 1, 'LOGIN', '2026-02-05 16:15:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0', '113.19.124.73', '6.91,122.06', 'Successful login'),
(1401, 1, 'LOGIN', '2026-02-05 16:21:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0', '113.19.124.73', '6.91,122.06', 'Successful login'),
(1403, 1, 'LOGIN', '2026-02-05 16:24:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0', '113.19.124.73', '6.91,122.06', 'Successful login'),
(1404, 1, 'ADD_ELECTION', '2026-02-05 16:25:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0', '113.19.124.73', 'N/A', 'Added election: Name: MR &amp; MS CCS, Academic Year ID: 14, Status: Ongoing, Note: Overlaps with other ongoing elections: ELECTRICAL ENG CLASS ELECTION 2025-2026'),
(1405, 1, 'ADD_ELECTION', '2026-02-05 16:27:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0', '113.19.124.73', 'N/A', 'Added election: Name: MR &amp; MS CCS, Academic Year ID: 14, Status: Ongoing, Note: Overlaps with other ongoing elections: ELECTRICAL ENG CLASS ELECTION 2025-2026'),
(1406, 1, 'LOGOUT', '2026-02-05 16:32:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0', '113.19.124.73', 'N/A', 'Successful logout'),
(1407, 1, 'LOGIN', '2026-02-07 16:03:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.85.62', 'N/A', 'Successful login'),
(1408, 1, 'VIEW_CANDIDATES', '2026-02-07 17:32:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.85.62', 'N/A', 'Viewed candidates for event ID: 103, Candidacy: 99'),
(1409, 1, 'VIEW_CANDIDATES', '2026-02-07 18:03:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.85.62', 'N/A', 'Viewed candidates for event ID: 103, Candidacy: 99'),
(1411, 1, 'LOGIN', '2026-02-07 18:07:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.85.62', 'N/A', 'Successful login'),
(1412, 1, 'VIEW_CANDIDATES', '2026-02-07 18:07:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.85.62', 'N/A', 'Viewed candidates for event ID: 103, Candidacy: 99'),
(1413, 1, 'VIEW_CANDIDATES', '2026-02-07 20:13:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.85.62', 'N/A', 'Viewed candidates for event ID: 103, Candidacy: 99'),
(1414, 1, 'VIEW_CANDIDATES', '2026-02-07 20:15:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.85.62', 'N/A', 'Viewed candidates for event ID: 103, Candidacy: 99'),
(1415, 1, 'VIEW_CANDIDATES', '2026-02-07 21:37:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '175.176.85.62', 'N/A', 'Viewed candidates for event ID: 103, Candidacy: 99'),
(1416, 1, 'LOGIN', '2026-02-08 11:08:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1417, 1, 'LOGIN', '2026-02-08 19:37:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1419, 1, 'LOGIN', '2026-02-08 21:53:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1420, 1, 'VIEW_CANDIDATES', '2026-02-08 14:54:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 103, Candidacy: 99'),
(1421, 1, 'Submitted candidacy application', '2026-02-08 21:55:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-08 21:55:20\",\"field_count\":4,\"has_files\":true}'),
(1422, 1, 'VIEW_CANDIDATES', '2026-02-08 14:55:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 103, Candidacy: 99'),
(1423, 1, 'VIEW_CANDIDATES', '2026-02-08 14:56:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 103, Candidacy: 99'),
(1425, 1, 'LOGIN', '2026-02-09 15:59:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1426, 1, 'ADD_ELECTION', '2026-02-09 10:37:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: test, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1427, 1, 'DELETE_POSITION_PARTY', '2026-02-09 18:17:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Vice-President\" for party \"test\". Candidates: 136. Position row removed: yes'),
(1428, 1, 'LOGIN', '2026-02-09 18:25:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1429, 1, 'ADD_ELECTION', '2026-02-09 12:14:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Tester, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1430, NULL, 'Submitted candidacy application', '2026-02-09 19:23:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-09 19:23:21\",\"field_count\":4,\"has_files\":true}'),
(1431, NULL, 'Submitted candidacy application', '2026-02-09 19:23:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-09 19:23:33\",\"field_count\":4,\"has_files\":true}'),
(1432, 1, 'VIEW_CANDIDATES', '2026-02-09 12:39:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1433, 1, 'VIEW_CANDIDATES', '2026-02-09 12:39:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 106, Candidacy: 102'),
(1434, 1, 'VIEW_CANDIDATES', '2026-02-09 12:39:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 106, Candidacy: 102'),
(1435, 1, 'VIEW_CANDIDATES', '2026-02-09 12:39:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 106, Candidacy: 102'),
(1436, 1, 'VIEW_CANDIDATES', '2026-02-09 12:40:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 106, Candidacy: 102'),
(1437, 1, 'VIEW_CANDIDATES', '2026-02-09 12:40:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 106, Candidacy: 102'),
(1438, 1, 'VIEW_CANDIDATES', '2026-02-09 12:40:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1439, 1, 'VIEW_CANDIDATES', '2026-02-09 12:40:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1440, 1, 'VIEW_CANDIDATES', '2026-02-09 12:41:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1441, 1, 'VIEW_CANDIDATES', '2026-02-09 12:41:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103');
INSERT INTO `user_activities` (`id`, `user_id`, `action`, `timestamp`, `device_info`, `ip_address`, `location`, `behavior_patterns`) VALUES
(1442, 1, 'VIEW_CANDIDATES', '2026-02-09 12:41:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1443, 1, 'VIEW_CANDIDATES', '2026-02-09 12:42:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1444, 1, 'VIEW_CANDIDATES', '2026-02-09 12:42:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1445, 1, 'VIEW_CANDIDATES', '2026-02-09 12:43:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1446, 1, 'VIEW_CANDIDATES', '2026-02-09 12:44:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1447, 1, 'VIEW_CANDIDATES', '2026-02-09 12:44:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1448, 1, 'VIEW_CANDIDATES', '2026-02-09 12:44:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1449, 1, 'VIEW_CANDIDATES', '2026-02-09 12:44:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1450, 1, 'VIEW_CANDIDATES', '2026-02-09 12:44:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1451, 1, 'VIEW_CANDIDATES', '2026-02-09 12:45:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1452, 1, 'VIEW_CANDIDATES', '2026-02-09 12:45:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1453, 1, 'VIEW_CANDIDATES', '2026-02-09 12:45:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1454, 1, 'VIEW_CANDIDATES', '2026-02-09 13:56:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1455, 1, 'VIEW_CANDIDATES', '2026-02-09 13:56:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1456, 1, 'VIEW_CANDIDATES', '2026-02-09 13:56:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 107, Candidacy: 103'),
(1457, 1, 'VIEW_CANDIDATES', '2026-02-09 13:58:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 108, Candidacy: 102'),
(1458, 1, 'VIEW_CANDIDATES', '2026-02-09 13:58:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 108, Candidacy: 102'),
(1459, 1, 'VIEW_CANDIDATES', '2026-02-09 13:58:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 108, Candidacy: 102'),
(1460, 1, 'Submitted candidacy application', '2026-02-09 20:58:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-09 20:58:26\",\"field_count\":4,\"has_files\":true}'),
(1461, 1, 'VIEW_CANDIDATES', '2026-02-09 13:58:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 108, Candidacy: 102'),
(1462, 1, 'VIEW_CANDIDATES', '2026-02-09 14:07:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 109, Candidacy: 103'),
(1463, 1, 'Submitted candidacy application', '2026-02-09 21:08:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-09 21:08:01\",\"field_count\":4,\"has_files\":true}'),
(1464, 1, 'VIEW_CANDIDATES', '2026-02-09 14:08:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 109, Candidacy: 103'),
(1465, 1, 'Submitted candidacy application', '2026-02-09 21:08:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-09 21:08:10\",\"field_count\":4,\"has_files\":true}'),
(1466, 1, 'VIEW_CANDIDATES', '2026-02-09 14:08:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 109, Candidacy: 103'),
(1467, 1, 'VIEW_CANDIDATES', '2026-02-09 14:08:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 109, Candidacy: 103'),
(1468, 1, 'VIEW_CANDIDATES', '2026-02-09 14:08:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 110, Candidacy: 103'),
(1469, 1, 'Submitted candidacy application', '2026-02-09 21:09:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-09 21:09:03\",\"field_count\":4,\"has_files\":true}'),
(1470, 1, 'VIEW_CANDIDATES', '2026-02-09 14:09:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 110, Candidacy: 103'),
(1471, 1, 'VIEW_CANDIDATES', '2026-02-09 14:10:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 110, Candidacy: 103'),
(1473, 1, 'VIEW_CANDIDATES', '2026-02-09 14:11:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 110, Candidacy: 103'),
(1474, NULL, 'Submitted candidacy application', '2026-02-09 21:11:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-09 21:11:39\",\"field_count\":4,\"has_files\":true}'),
(1475, 1, 'VIEW_CANDIDATES', '2026-02-09 14:11:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 110, Candidacy: 103'),
(1476, 1, 'VIEW_CANDIDATES', '2026-02-09 14:11:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 110, Candidacy: 103'),
(1477, 1, 'VIEW_CANDIDATES', '2026-02-09 14:12:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 110, Candidacy: 103'),
(1478, 1, 'VIEW_CANDIDATES', '2026-02-09 14:12:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 110, Candidacy: 103'),
(1480, 1, 'LOGIN', '2026-02-11 12:17:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1482, 1, 'UPDATE_MODERATOR', '2026-02-11 13:34:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 118 - precinct 170'),
(1483, 1, 'UPDATE_MODERATOR', '2026-02-11 14:43:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 118 - precinct 170'),
(1485, 1, 'UPDATE_MODERATOR', '2026-02-11 14:44:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 118 - precinct 0'),
(1491, 1, 'UPDATE_MODERATOR', '2026-02-11 14:45:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 118 - precinct 170'),
(1492, 1, 'ADD_ELECTION', '2026-02-11 08:40:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1493, 1, 'DELETE_PARTY', '2026-02-11 08:40:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted party ID: 143, Name: test, Election: test'),
(1494, 1, 'UPDATE_MODERATOR', '2026-02-11 15:43:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 118 - precinct 171'),
(1495, 1, 'VIEW_CANDIDATES', '2026-02-11 08:47:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 111, Candidacy: 104'),
(1496, 1, 'VIEW_CANDIDATES', '2026-02-11 08:49:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 111, Candidacy: 104'),
(1497, 1, 'Submitted candidacy application', '2026-02-11 15:50:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-11 15:50:07\",\"field_count\":4,\"has_files\":true}'),
(1498, 1, 'VIEW_CANDIDATES', '2026-02-11 08:50:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 111, Candidacy: 104'),
(1499, 1, 'Submitted candidacy application', '2026-02-11 15:50:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-11 15:50:18\",\"field_count\":4,\"has_files\":true}'),
(1500, 1, 'VIEW_CANDIDATES', '2026-02-11 08:50:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 111, Candidacy: 104'),
(1501, 1, 'Submitted candidacy application', '2026-02-11 15:50:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-11 15:50:31\",\"field_count\":4,\"has_files\":true}'),
(1502, 1, 'VIEW_CANDIDATES', '2026-02-11 08:50:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 111, Candidacy: 104'),
(1503, 1, 'VIEW_CANDIDATES', '2026-02-11 08:50:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 111, Candidacy: 104'),
(1505, 1, 'VIEW_CANDIDATES', '2026-02-11 08:54:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 111, Candidacy: 104'),
(1506, 1, 'Submitted candidacy application', '2026-02-11 15:54:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-11 15:54:40\",\"field_count\":4,\"has_files\":true}'),
(1507, 1, 'VIEW_CANDIDATES', '2026-02-11 08:54:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 111, Candidacy: 104'),
(1508, 1, 'UPDATE_MODERATOR', '2026-02-11 15:56:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 118 - precinct 0'),
(1509, 1, 'UPDATE_MODERATOR', '2026-02-11 15:57:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 118 - precinct 171'),
(1513, 1, 'ADD_ELECTION', '2026-02-11 10:22:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Testzxc, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1514, 1, 'UPDATE_MODERATOR', '2026-02-11 17:33:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 118 - precinct 172'),
(1524, 1, 'LOGIN', '2026-02-12 07:33:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1525, 1, 'VIEW_CANDIDATES', '2026-02-12 00:33:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 113, Candidacy: 105'),
(1526, 1, 'VIEW_CANDIDATES', '2026-02-12 00:33:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 111, Candidacy: 104'),
(1527, 1, 'VIEW_CANDIDATES', '2026-02-12 00:34:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 113, Candidacy: 105'),
(1528, 1, 'VIEW_CANDIDATES', '2026-02-12 00:54:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 113, Candidacy: 105'),
(1529, 1, 'VIEW_CANDIDATES', '2026-02-12 00:54:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 111, Candidacy: 104'),
(1530, 1, 'VIEW_CANDIDATES', '2026-02-12 00:54:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 113, Candidacy: 105'),
(1531, 1, 'VIEW_CANDIDATES', '2026-02-12 04:27:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 113, Candidacy: 105'),
(1532, 1, 'VIEW_CANDIDATES', '2026-02-12 04:28:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 113, Candidacy: 105'),
(1533, NULL, 'Submitted candidacy application', '2026-02-12 11:29:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2026-02-12 11:29:49\",\"field_count\":4,\"has_files\":true}'),
(1534, NULL, 'Submitted candidacy application', '2026-02-12 11:29:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2026-02-12 11:29:58\",\"field_count\":4,\"has_files\":true}'),
(1535, NULL, 'Submitted candidacy application', '2026-02-12 11:30:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2026-02-12 11:30:06\",\"field_count\":4,\"has_files\":true}'),
(1536, NULL, 'Submitted candidacy application', '2026-02-12 11:30:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0', '::1', NULL, '{\"form_submission_time\":\"2026-02-12 11:30:17\",\"field_count\":4,\"has_files\":true}'),
(1537, 1, 'VIEW_CANDIDATES', '2026-02-12 04:30:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 113, Candidacy: 105'),
(1538, 1, 'VIEW_CANDIDATES', '2026-02-12 04:32:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 113, Candidacy: 105'),
(1539, 1, 'VIEW_CANDIDATES', '2026-02-12 04:32:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 113, Candidacy: 105'),
(1540, 1, 'VIEW_CANDIDATES', '2026-02-12 04:32:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 113, Candidacy: 105'),
(1542, 1, 'VIEW_CANDIDATES', '2026-02-12 04:54:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 113, Candidacy: 105'),
(1543, 1, 'ADD_ELECTION', '2026-02-12 05:22:42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: New Election, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1544, 1, 'VIEW_CANDIDATES', '2026-02-12 05:24:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 114, Candidacy: 106'),
(1545, 1, 'VIEW_CANDIDATES', '2026-02-12 05:24:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 114, Candidacy: 106'),
(1546, 1, 'Submitted candidacy application', '2026-02-12 12:25:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-12 12:25:02\",\"field_count\":4,\"has_files\":true}'),
(1547, 1, 'VIEW_CANDIDATES', '2026-02-12 05:25:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 114, Candidacy: 106'),
(1548, 1, 'VIEW_CANDIDATES', '2026-02-12 05:25:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 114, Candidacy: 106'),
(1551, 1, 'ADD_ELECTION', '2026-02-12 06:58:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: hey ^*&, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1552, 1, 'ADD_ELECTION', '2026-02-12 07:36:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: hey, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1553, 1, 'VIEW_CANDIDATES', '2026-02-12 08:00:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 115, Candidacy: 108'),
(1554, 1, 'VIEW_CANDIDATES', '2026-02-12 08:01:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 115, Candidacy: 108'),
(1557, 1, 'LOGIN', '2026-02-16 06:09:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1558, 1, 'ADD_ELECTION', '2026-02-15 23:11:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: meow, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1559, 1, 'DELETE_ELECTION', '2026-02-15 23:21:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: waasd'),
(1560, 1, 'ADD_ELECTION', '2026-02-15 23:21:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1561, 19372, 'LOGIN', '2026-02-16 06:33:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1562, 19372, 'LOGIN', '2026-02-16 06:33:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1563, 19425, 'LOGIN', '2026-02-16 06:35:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1564, 1, 'ADD_ELECTION', '2026-02-15 23:40:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: ma, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1565, 1, 'VIEW_CANDIDATES', '2026-02-15 23:41:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1566, 1, 'VIEW_CANDIDATES', '2026-02-15 23:51:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1567, 1, 'VIEW_CANDIDATES', '2026-02-15 23:51:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1568, 1, 'VIEW_CANDIDATES', '2026-02-15 23:51:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1569, 1, 'VIEW_CANDIDATES', '2026-02-15 23:51:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1570, 1, 'VIEW_CANDIDATES', '2026-02-15 23:52:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1571, 1, 'VIEW_CANDIDATES', '2026-02-15 23:56:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1572, 1, 'VIEW_CANDIDATES', '2026-02-15 23:56:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1573, 1, 'VIEW_CANDIDATES', '2026-02-15 23:57:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1574, 1, 'VIEW_CANDIDATES', '2026-02-15 23:57:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1575, 1, 'VIEW_CANDIDATES', '2026-02-15 23:57:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1576, 1, 'VIEW_CANDIDATES', '2026-02-15 23:57:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1577, 1, 'VIEW_CANDIDATES', '2026-02-15 23:57:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1578, 1, 'Submitted candidacy application', '2026-02-16 06:58:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-16 06:58:05\",\"field_count\":4,\"has_files\":true}'),
(1579, 1, 'VIEW_CANDIDATES', '2026-02-15 23:58:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1580, 1, 'Submitted candidacy application', '2026-02-16 06:58:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-16 06:58:13\",\"field_count\":4,\"has_files\":true}'),
(1581, 1, 'VIEW_CANDIDATES', '2026-02-15 23:58:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1582, 1, 'Submitted candidacy application', '2026-02-16 06:58:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-16 06:58:21\",\"field_count\":4,\"has_files\":true}'),
(1583, 1, 'VIEW_CANDIDATES', '2026-02-15 23:58:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1584, 1, 'Submitted candidacy application', '2026-02-16 06:58:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-16 06:58:29\",\"field_count\":4,\"has_files\":true}'),
(1585, 1, 'VIEW_CANDIDATES', '2026-02-15 23:58:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1586, 1, 'Submitted candidacy application', '2026-02-16 06:58:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-16 06:58:37\",\"field_count\":4,\"has_files\":true}'),
(1587, 1, 'VIEW_CANDIDATES', '2026-02-15 23:58:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1588, 1, 'Submitted candidacy application', '2026-02-16 06:58:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-02-16 06:58:44\",\"field_count\":4,\"has_files\":true}'),
(1589, 1, 'VIEW_CANDIDATES', '2026-02-15 23:58:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1590, 1, 'UPDATE_MODERATOR', '2026-02-16 07:00:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated moderator 132 - precinct 174'),
(1591, 1, 'LOGIN', '2026-03-03 15:19:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1592, 1, 'LOGIN', '2026-03-03 15:59:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1593, 1, 'VIEW_CANDIDATES', '2026-03-03 10:21:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 116, Candidacy: 110'),
(1594, 1, 'ADD_ELECTION', '2026-03-03 10:25:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: asdasd, School Year: 2026 - 2027, Semester: 2nd Semester, Status: Ongoing'),
(1596, 1, 'LOGIN', '2026-03-05 05:19:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1597, 1, 'LOGIN', '2026-03-05 06:08:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1598, 1, 'LOGIN', '2026-03-05 19:29:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1600, 1, 'LOGIN', '2026-03-06 09:56:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1601, 1, 'ADD_ELECTION', '2026-03-06 03:04:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1606, 1, 'LOGIN', '2026-03-06 13:33:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.93645825,122.084396', 'Successful login'),
(1607, 1, 'VIEW_CANDIDATES', '2026-03-06 06:33:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 117, Candidacy: 113'),
(1608, 1, 'VIEW_CANDIDATES', '2026-03-06 06:33:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 117, Candidacy: 113'),
(1609, 1, 'VIEW_CANDIDATES', '2026-03-06 06:34:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 117, Candidacy: 113'),
(1610, 1, 'LOGOUT', '2026-03-06 13:40:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful logout'),
(1613, 1, 'LOGIN', '2026-03-06 14:45:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936466947697507,122.084396', 'Successful login'),
(1615, 1, 'LOGIN', '2026-03-07 14:43:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936470807389718,122.084396', 'Successful login'),
(1616, 1, 'VIEW_CANDIDATES', '2026-03-07 08:25:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 117, Candidacy: 113'),
(1617, 1, 'VIEW_CANDIDATES', '2026-03-07 08:25:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 117, Candidacy: 113'),
(1619, 1, 'ADD_ELECTION', '2026-03-07 08:30:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1620, 1, 'LOGIN', '2026-03-08 16:35:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1621, 1, 'LOGIN', '2026-03-09 15:56:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.910198,122.077164', 'Successful login'),
(1622, 1, 'LOGIN', '2026-03-10 01:43:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936469896736592,122.08439745825444', 'Successful login'),
(1623, 1, 'LOGIN', '2026-03-10 11:49:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936476196872128,122.08439774628394', 'Successful login'),
(1624, 1, 'LOGIN', '2026-03-10 23:57:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936476196872128,122.08439774628394', 'Successful login'),
(1625, 1, 'LOGIN', '2026-03-11 12:54:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936653,122.08448799999998', 'Successful login'),
(1626, 1, 'LOGIN', '2026-03-12 05:47:42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936474073395993,122.0843976492029', 'Successful login'),
(1627, 1, 'ADD_ELECTION', '2026-03-12 04:55:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1628, 1, 'LOGIN', '2026-03-13 10:11:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936469896736592,122.08439745825444', 'Successful login'),
(1630, 1, 'VIEW_CANDIDATES', '2026-03-13 04:20:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 118, Candidacy: 115'),
(1631, 1, 'VIEW_CANDIDATES', '2026-03-13 04:39:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 118, Candidacy: 115'),
(1632, 1, 'Submitted candidacy application', '2026-03-13 11:39:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-13 11:39:41\",\"field_count\":4,\"has_files\":true}'),
(1633, 1, 'VIEW_CANDIDATES', '2026-03-13 04:39:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 118, Candidacy: 115'),
(1634, 1, 'VIEW_CANDIDATES', '2026-03-13 04:40:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 118, Candidacy: 115'),
(1635, 19495, 'LOGIN', '2026-03-13 11:42:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '::1', 'N/A', 'Successful login'),
(1636, 1, 'VIEW_CANDIDATES', '2026-03-13 04:44:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 118, Candidacy: 115'),
(1637, 1, 'VIEW_CANDIDATES', '2026-03-13 04:44:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 118, Candidacy: 115'),
(1638, 1, 'VIEW_CANDIDATES', '2026-03-13 04:44:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 118, Candidacy: 115'),
(1639, 1, 'VIEW_CANDIDATES', '2026-03-13 04:47:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 118, Candidacy: 115'),
(1640, 1, 'Submitted candidacy application', '2026-03-13 11:48:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-13 11:48:39\",\"field_count\":4,\"has_files\":true}'),
(1641, 1, 'VIEW_CANDIDATES', '2026-03-13 04:48:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 118, Candidacy: 115'),
(1642, 1, 'VIEW_CANDIDATES', '2026-03-13 04:50:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 118, Candidacy: 115'),
(1643, 1, 'VIEW_CANDIDATES', '2026-03-13 04:50:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 118, Candidacy: 115'),
(1644, 1, 'VIEW_CANDIDATES', '2026-03-13 04:51:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 118, Candidacy: 115'),
(1645, 1, 'ADD_MODERATOR', '2026-03-13 12:04:12', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator test Email testgmailcom Precinct ID 180'),
(1646, 1, 'VIEW_CANDIDATES', '2026-03-13 07:12:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 119, Candidacy: 115'),
(1647, 1, 'LOGOUT', '2026-03-13 15:21:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful logout'),
(1649, 1, 'LOGIN', '2026-03-13 15:36:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936469896736592,122.08439745825444', 'Successful login'),
(1650, 1, 'LOGIN', '2026-03-14 02:59:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936469896736592,122.08439745825444', 'Successful login'),
(1651, 1, 'ADD_ELECTION', '2026-03-13 20:13:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test Election 1, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1652, 1, 'VIEW_CANDIDATES', '2026-03-13 20:17:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 120, Candidacy: 116'),
(1653, 1, 'VIEW_CANDIDATES', '2026-03-13 20:18:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 120, Candidacy: 116'),
(1654, 1, 'Submitted candidacy application', '2026-03-14 03:18:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-14 03:18:45\",\"field_count\":4,\"has_files\":true}'),
(1655, 1, 'VIEW_CANDIDATES', '2026-03-13 20:18:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 120, Candidacy: 116'),
(1656, 1, 'VIEW_CANDIDATES', '2026-03-13 20:19:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 120, Candidacy: 116'),
(1657, 1, 'Submitted candidacy application', '2026-03-14 03:19:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-14 03:19:33\",\"field_count\":4,\"has_files\":true}'),
(1658, 1, 'VIEW_CANDIDATES', '2026-03-13 20:19:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 120, Candidacy: 116'),
(1659, 1, 'Submitted candidacy application', '2026-03-14 03:19:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-14 03:19:59\",\"field_count\":4,\"has_files\":true}'),
(1660, 1, 'VIEW_CANDIDATES', '2026-03-13 20:20:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 120, Candidacy: 116'),
(1661, 1, 'Submitted candidacy application', '2026-03-14 03:20:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-14 03:20:17\",\"field_count\":4,\"has_files\":true}'),
(1662, 1, 'VIEW_CANDIDATES', '2026-03-13 20:20:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 120, Candidacy: 116'),
(1663, 1, 'ADD_MODERATOR', '2026-03-14 03:22:43', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator Wmsu Moderator New Email wmsumoderator123gmailcom Precinct ID 183'),
(1664, 1, 'ADD_MODERATOR', '2026-03-14 03:23:06', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator Wmsu Moderator External Email wmsumoderatorexternal123gmailcom Precinct ID 184'),
(1665, 1, 'LOGOUT', '2026-03-14 03:24:14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful logout'),
(1666, 19501, 'LOGIN', '2026-03-14 03:24:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936469896736592,122.08439745825444', 'Successful login'),
(1668, 19501, 'LOGIN', '2026-03-14 03:27:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936482345416614,122.08439802738299', 'Successful login'),
(1669, 1, 'LOGIN', '2026-03-14 03:28:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.93645825,122.084396', 'Successful login'),
(1670, 1, 'VIEW_CANDIDATES', '2026-03-13 20:29:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 120, Candidacy: 116'),
(1671, 1, 'LOGOUT', '2026-03-14 03:29:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful logout'),
(1672, 19498, 'LOGIN', '2026-03-14 03:30:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.93645825,122.084396', 'Successful login'),
(1673, 19502, 'LOGIN', '2026-03-14 03:31:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.93645825,122.084396', 'Successful login'),
(1675, 19495, 'LOGIN', '2026-03-14 04:18:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936469896736592,122.08439745825444', 'Successful login'),
(1676, 19495, 'LOGIN', '2026-03-14 04:19:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.93645825,122.084396', 'Successful login'),
(1677, 1, 'LOGIN', '2026-03-14 17:26:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936474073395993,122.0843976492029', 'Successful login'),
(1678, 1, 'LOGIN', '2026-03-15 11:01:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.91020072998033,122.07716607347568', 'Successful login'),
(1679, 1, 'LOGIN', '2026-03-15 11:05:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.91020072998033,122.07716607347568', 'Successful login'),
(1680, 1, 'ADD_ELECTION', '2026-03-15 04:19:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test Election I, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1681, 1, 'ADD_ELECTION', '2026-03-15 04:21:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test Election I, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1682, 1, 'ADD_ELECTION', '2026-03-15 04:23:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test Election I, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1683, 1, 'DELETE_ELECTION', '2026-03-15 04:26:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Test Election I'),
(1684, 1, 'ADD_MODERATOR', '2026-03-15 11:48:53', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator Wmsu Moderator Main Email wmsumoderator123gmailcom Precinct ID 185'),
(1685, 19495, 'LOGIN', '2026-03-15 11:56:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.91020072998033,122.07716607347568', 'Successful login'),
(1686, 19495, 'LOGIN', '2026-03-15 11:57:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.910200189940994,122.07716607347568', 'Successful login'),
(1687, 19495, 'LOGIN', '2026-03-15 12:06:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '::1', 'N/A', 'Successful login'),
(1688, 19495, 'LOGIN', '2026-03-15 12:08:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '::1', 'N/A', 'Successful login'),
(1689, 19495, 'LOGIN', '2026-03-15 12:08:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '::1', 'N/A', 'Successful login'),
(1690, 19495, 'LOGIN', '2026-03-15 12:09:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '::1', 'N/A', 'Successful login'),
(1691, 19495, 'LOGIN', '2026-03-15 12:09:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '::1', 'N/A', 'Successful login');
INSERT INTO `user_activities` (`id`, `user_id`, `action`, `timestamp`, `device_info`, `ip_address`, `location`, `behavior_patterns`) VALUES
(1692, 19495, 'LOGIN', '2026-03-15 12:09:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '::1', 'N/A', 'Successful login'),
(1693, 19495, 'LOGIN', '2026-03-15 12:10:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '::1', 'N/A', 'Successful login'),
(1694, 19495, 'LOGIN', '2026-03-15 12:10:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '::1', 'N/A', 'Successful login'),
(1696, 19495, 'LOGIN', '2026-03-15 12:46:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '::1', 'N/A', 'Successful login'),
(1697, 19495, 'LOGIN', '2026-03-15 12:47:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '::1', 'N/A', 'Successful login'),
(1698, 19495, 'LOGIN', '2026-03-15 12:51:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '::1', 'N/A', 'Successful login'),
(1699, 1, 'VIEW_CANDIDATES', '2026-03-15 06:07:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 121, Candidacy: 117'),
(1700, 1, 'Submitted candidacy application', '2026-03-15 13:07:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-15 13:07:41\",\"field_count\":5,\"has_files\":true}'),
(1701, 1, 'VIEW_CANDIDATES', '2026-03-15 06:07:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 121, Candidacy: 117'),
(1702, 1, 'Submitted candidacy application', '2026-03-15 13:07:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-15 13:07:58\",\"field_count\":5,\"has_files\":true}'),
(1703, 1, 'VIEW_CANDIDATES', '2026-03-15 06:08:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 121, Candidacy: 117'),
(1704, 19495, 'LOGIN', '2026-03-15 13:08:35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.910198,122.077164', 'Successful login'),
(1705, 1, 'VIEW_CANDIDATES', '2026-03-15 06:08:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 121, Candidacy: 117'),
(1706, 1, 'VIEW_CANDIDATES', '2026-03-15 06:09:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 121, Candidacy: 117'),
(1707, 1, 'Submitted candidacy application', '2026-03-15 13:09:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-15 13:09:22\",\"field_count\":5,\"has_files\":true}'),
(1708, 1, 'VIEW_CANDIDATES', '2026-03-15 06:09:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 121, Candidacy: 117'),
(1709, 1, 'Submitted candidacy application', '2026-03-15 13:09:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-15 13:09:41\",\"field_count\":5,\"has_files\":true}'),
(1710, 1, 'VIEW_CANDIDATES', '2026-03-15 06:09:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 121, Candidacy: 117'),
(1711, 1, 'VIEW_CANDIDATES', '2026-03-15 06:09:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 121, Candidacy: 117'),
(1712, 19495, 'LOGIN', '2026-03-15 14:50:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936477618529145,122.08438802268876', 'Successful login'),
(1713, 1, 'LOGIN', '2026-03-15 16:02:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936328849141466,122.0842982966255', 'Successful login'),
(1714, 1, 'ADD_ELECTION', '2026-03-15 09:59:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: USC Election 2025-2026, School Year: 2025 - 2026, Semester: 2nd Semester, Status: Ongoing'),
(1715, 1, 'LOGOUT', '2026-03-15 17:04:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful logout'),
(1716, 1, 'LOGIN', '2026-03-15 17:05:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936316211408128,122.08428698430777', 'Successful login'),
(1717, 1, 'LOGIN', '2026-03-15 17:08:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.9364385,122.08403400000002', 'Successful login'),
(1718, 1, 'LOGOUT', '2026-03-15 17:10:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful logout'),
(1719, 1, 'LOGIN', '2026-03-15 17:10:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.9364385,122.08403400000002', 'Successful login'),
(1720, 1, 'ADD_ELECTION', '2026-03-15 10:22:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2025 - 2026, Semester: 2nd Semester, Status: Ongoing'),
(1721, 1, 'LOGOUT', '2026-03-15 17:32:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful logout'),
(1722, 1, 'LOGIN', '2026-03-15 17:32:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.9364385,122.08403400000002', 'Successful login'),
(1723, 19505, 'LOGIN', '2026-03-15 17:32:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.9364385,122.08403400000002', 'Successful login'),
(1724, 19508, 'LOGIN', '2026-03-15 17:39:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '::1', 'N/A', 'Successful login'),
(1725, 1, 'VIEW_CANDIDATES', '2026-03-15 10:41:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 122, Candidacy: 121'),
(1726, 1, 'VIEW_CANDIDATES', '2026-03-15 10:47:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 122, Candidacy: 121'),
(1727, 1, 'Submitted candidacy application', '2026-03-15 17:48:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-15 17:48:39\",\"field_count\":4,\"has_files\":true}'),
(1728, 1, 'VIEW_CANDIDATES', '2026-03-15 10:48:42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 122, Candidacy: 121'),
(1729, 1, 'Submitted candidacy application', '2026-03-15 17:50:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-15 17:50:21\",\"field_count\":4,\"has_files\":true}'),
(1730, 1, 'VIEW_CANDIDATES', '2026-03-15 10:50:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 122, Candidacy: 121'),
(1731, 1, 'VIEW_CANDIDATES', '2026-03-15 10:50:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 122, Candidacy: 121'),
(1732, 1, 'VIEW_CANDIDATES', '2026-03-15 10:51:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 122, Candidacy: 121'),
(1733, 1, 'ADD_MODERATOR', '2026-03-15 17:57:12', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator CS Moderator Email csmoderatorwmsucom Precinct ID 188'),
(1735, 1, 'ADD_ELECTION', '2026-03-15 11:20:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: nur-election, School Year: 2025 - 2026, Semester: 2nd Semester, Status: Ongoing'),
(1736, 1, 'LOGIN', '2026-03-16 00:09:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936489898464519,122.08438032730902', 'Successful login'),
(1737, 19505, 'LOGIN', '2026-03-16 00:30:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1738, 19501, 'LOGIN', '2026-03-16 11:48:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1739, 19501, 'LOGIN', '2026-03-16 11:54:35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1740, 19505, 'LOGIN', '2026-03-16 12:07:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1741, 1, 'ADD_ELECTION', '2026-03-16 06:40:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2025 - 2026, Semester: 2nd Semester, Status: Ongoing'),
(1742, 1, 'ADD_ELECTION', '2026-03-16 06:47:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Tester, School Year: 2025 - 2026, Semester: 2nd Semester, Status: Ongoing'),
(1743, 1, 'DELETE_ELECTION', '2026-03-16 14:18:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Tester'),
(1744, 1, 'ADD_ELECTION', '2026-03-16 14:18:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Testzxc, School Year: 2025 - 2026, Semester: 2nd Semester, Status: Ongoing'),
(1745, 1, 'DELETE_ELECTION', '2026-03-16 18:02:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Testzxc'),
(1746, 1, 'ADD_ELECTION', '2026-03-16 18:02:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: New, School Year: 2025 - 2026, Semester: 2nd Semester, Status: Ongoing'),
(1747, 1, 'DELETE_ELECTION', '2026-03-16 12:15:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: New'),
(1748, 1, 'ADD_ELECTION', '2026-03-16 19:21:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Election Testnism, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1749, 1, 'ADD_ELECTION', '2026-03-16 19:25:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Another Electioner, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1750, 1, 'UPDATE_PARTY', '2026-03-16 12:43:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 172, Name: Independent, Election ID: 128'),
(1751, 1, 'UPDATE_PARTY', '2026-03-16 12:43:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 172, Name: B, Election ID: 128'),
(1752, 1, 'UPDATE_PARTY', '2026-03-16 12:44:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 172, Name: A, Election ID: 127'),
(1753, 1, 'UPDATE_PARTY', '2026-03-16 12:44:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Updated party ID: 172, Name: Party A, Election ID: 127'),
(1754, 19495, 'LOGIN', '2026-03-16 23:39:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1755, 19505, 'LOGIN', '2026-03-17 00:06:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(1756, 19505, 'LOGIN', '2026-03-17 00:08:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1757, 19513, 'LOGIN', '2026-03-17 00:09:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1758, 19495, 'LOGIN', '2026-03-17 00:29:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(1765, 1, 'VIEW_CANDIDATES', '2026-03-16 17:40:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1766, 1, 'VIEW_CANDIDATES', '2026-03-16 17:41:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1767, 1, 'VIEW_CANDIDATES', '2026-03-16 17:42:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1768, 1, 'VIEW_CANDIDATES', '2026-03-16 17:42:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1769, 1, 'VIEW_CANDIDATES', '2026-03-16 17:42:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1770, 1, 'VIEW_CANDIDATES', '2026-03-16 17:42:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1771, 1, 'VIEW_CANDIDATES', '2026-03-16 17:43:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1772, 1, 'VIEW_CANDIDATES', '2026-03-16 17:43:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1773, 1, 'VIEW_CANDIDATES', '2026-03-16 17:43:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1774, 1, 'VIEW_CANDIDATES', '2026-03-16 17:44:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1775, 1, 'Submitted candidacy application', '2026-03-17 00:44:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-17 00:44:15\",\"field_count\":5,\"has_files\":true}'),
(1776, 1, 'VIEW_CANDIDATES', '2026-03-16 17:45:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1777, 1, 'VIEW_CANDIDATES', '2026-03-16 17:45:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1778, 1, 'VIEW_CANDIDATES', '2026-03-16 18:00:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1779, 1, 'VIEW_CANDIDATES', '2026-03-16 18:04:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1780, 1, 'VIEW_CANDIDATES', '2026-03-16 19:25:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1781, 1, 'VIEW_CANDIDATES', '2026-03-16 19:26:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1782, 1, 'Submitted candidacy application', '2026-03-17 02:26:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-17 02:26:31\",\"field_count\":5,\"has_files\":true}'),
(1783, 1, 'VIEW_CANDIDATES', '2026-03-16 19:26:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1784, 1, 'VIEW_CANDIDATES', '2026-03-16 19:31:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1785, 1, 'VIEW_CANDIDATES', '2026-03-16 19:31:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1786, 1, 'VIEW_CANDIDATES', '2026-03-16 19:32:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1787, 1, 'VIEW_CANDIDATES', '2026-03-16 19:32:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1788, 1, 'VIEW_CANDIDATES', '2026-03-16 19:32:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1789, 1, 'VIEW_CANDIDATES', '2026-03-16 19:45:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1790, 1, 'VIEW_CANDIDATES', '2026-03-17 00:36:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1791, 1, 'VIEW_CANDIDATES', '2026-03-17 00:36:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1792, 1, 'VIEW_CANDIDATES', '2026-03-17 00:36:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1793, 1, 'Submitted candidacy application', '2026-03-17 07:37:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-17 07:37:13\",\"field_count\":5,\"has_files\":true}'),
(1794, 1, 'VIEW_CANDIDATES', '2026-03-17 00:37:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1795, 1, 'Submitted candidacy application', '2026-03-17 07:37:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-17 07:37:36\",\"field_count\":5,\"has_files\":true}'),
(1796, 1, 'VIEW_CANDIDATES', '2026-03-17 00:37:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1797, 1, 'ADD_MODERATOR', '2026-03-17 07:39:26', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator College of Engineering under Computer Engineering Moderator Email coemoderatorwmsueduph Precinct ID 192'),
(1798, 1, 'ADD_MODERATOR', '2026-03-17 07:40:22', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator CCS-Comsci-WMSUESU Email ccscomcsciesuwmsueduph Precinct ID 191'),
(1799, 1, 'ADD_MODERATOR', '2026-03-17 07:41:15', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator CCs-It-WMSU-Main Email ccsitmainwmsueduph Precinct ID 190'),
(1800, 1, 'ADD_MODERATOR', '2026-03-17 07:45:44', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator CCS-Comsci-WMSUMain Email ccs-comsci-modwmsueduph Precinct ID 189'),
(1801, 19495, 'LOGIN', '2026-03-17 07:52:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1802, 19510, 'LOGIN', '2026-03-17 07:59:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1803, 19502, 'LOGIN', '2026-03-17 08:39:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(1804, 19519, 'LOGIN', '2026-03-17 08:57:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(1805, 19498, 'LOGIN', '2026-03-17 09:17:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(1806, 19498, 'LOGIN', '2026-03-17 09:18:14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(1807, 1, 'LOGIN', '2026-03-17 10:03:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.936477618529145,122.08438802268876', 'Successful login'),
(1808, 1, 'VIEW_CANDIDATES', '2026-03-17 05:48:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1809, 1, 'VIEW_CANDIDATES', '2026-03-17 05:48:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 123, Candidacy: 127'),
(1810, 1, 'VIEW_CANDIDATES', '2026-03-17 05:49:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1811, 1, 'Submitted candidacy application', '2026-03-17 12:50:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-17 12:50:30\",\"field_count\":4,\"has_files\":true}'),
(1812, 1, 'VIEW_CANDIDATES', '2026-03-17 05:50:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1813, 1, 'Submitted candidacy application', '2026-03-17 12:51:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-17 12:51:49\",\"field_count\":4,\"has_files\":true}'),
(1814, 1, 'VIEW_CANDIDATES', '2026-03-17 05:51:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1815, 1, 'Submitted candidacy application', '2026-03-17 12:54:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-17 12:54:12\",\"field_count\":4,\"has_files\":true}'),
(1816, 1, 'VIEW_CANDIDATES', '2026-03-17 05:54:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1817, 1, 'Submitted candidacy application', '2026-03-17 12:55:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-17 12:55:22\",\"field_count\":4,\"has_files\":true}'),
(1818, 1, 'VIEW_CANDIDATES', '2026-03-17 05:55:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1819, 1, 'Submitted candidacy application', '2026-03-17 12:57:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-17 12:57:57\",\"field_count\":4,\"has_files\":true}'),
(1820, 1, 'VIEW_CANDIDATES', '2026-03-17 05:58:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1821, 1, 'VIEW_CANDIDATES', '2026-03-17 06:03:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1822, 1, 'Submitted candidacy application', '2026-03-17 13:08:42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-17 13:08:42\",\"field_count\":4,\"has_files\":true}'),
(1823, 1, 'VIEW_CANDIDATES', '2026-03-17 06:08:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1824, 19512, 'LOGIN', '2026-03-17 13:09:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(1825, 1, 'VIEW_CANDIDATES', '2026-03-17 06:23:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1826, 19521, 'LOGIN', '2026-03-17 13:25:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(1827, 1, 'VIEW_CANDIDATES', '2026-03-17 06:31:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1828, 1, 'VIEW_CANDIDATES', '2026-03-17 06:32:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1829, 1, 'VIEW_CANDIDATES', '2026-03-17 06:32:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1830, 1, 'VIEW_CANDIDATES', '2026-03-17 06:39:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1831, 1, 'Submitted candidacy application', '2026-03-17 13:39:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-17 13:39:51\",\"field_count\":4,\"has_files\":true}'),
(1832, 1, 'VIEW_CANDIDATES', '2026-03-17 06:39:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1833, 1, 'VIEW_CANDIDATES', '2026-03-17 06:40:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1834, 1, 'VIEW_CANDIDATES', '2026-03-17 06:40:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1835, 1, 'VIEW_CANDIDATES', '2026-03-17 06:40:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1836, 1, 'Submitted candidacy application', '2026-03-17 13:45:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-17 13:45:36\",\"field_count\":4,\"has_files\":true}'),
(1837, 1, 'VIEW_CANDIDATES', '2026-03-17 06:45:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1838, 19523, 'LOGIN', '2026-03-17 13:55:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(1839, 1, 'VIEW_CANDIDATES', '2026-03-17 06:56:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1840, 19522, 'LOGIN', '2026-03-17 13:58:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(1841, 1, 'VIEW_CANDIDATES', '2026-03-17 06:59:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1842, 1, 'VIEW_CANDIDATES', '2026-03-17 07:00:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 124, Candidacy: 128'),
(1843, 1, 'ADD_MODERATOR', '2026-03-17 14:33:40', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator ccstest Email ccstestgmailcom Precinct ID 195'),
(1844, 1, 'ADD_MODERATOR', '2026-03-17 14:35:04', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator ccsesu Email ccsesugmailcom Precinct ID 193'),
(1845, 1, 'ADD_MODERATOR', '2026-03-17 14:35:27', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator ccsit Email ccsitwmsueduph Precinct ID 196'),
(1846, 1, 'ADD_MODERATOR', '2026-03-17 14:35:46', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator cengineering Email ceengineeringwmsueduph Precinct ID 194'),
(1847, 19495, 'LOGIN', '2026-03-17 15:32:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.93645825,122.084396', 'Successful login'),
(1848, 19522, 'LOGIN', '2026-03-17 15:33:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.93645825,122.084396', 'Successful login'),
(1849, 19521, 'LOGIN', '2026-03-17 15:34:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1850, 1, 'LOGIN', '2026-03-17 16:38:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.93647022824363,122.0843895107452', 'Successful login'),
(1851, 1, 'LOGIN', '2026-03-17 16:47:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.93647022824363,122.0843895107452', 'Successful login'),
(1852, 1, 'LOGIN', '2026-03-18 05:24:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', '6.93647022824363,122.0843895107452', 'Successful login'),
(1853, 1, 'ADD_ELECTION', '2026-03-18 05:42:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Election A, School Year: 2024 - 2025, Semester: 1st Semester, Status: Ongoing'),
(1854, 19513, 'LOGIN', '2026-03-18 05:48:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1855, 1, 'VIEW_CANDIDATES', '2026-03-17 22:49:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 125, Candidacy: 129'),
(1856, 1, 'VIEW_CANDIDATES', '2026-03-17 22:49:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 125, Candidacy: 129'),
(1857, 1, 'Submitted candidacy application', '2026-03-18 05:50:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-18 05:50:01\",\"field_count\":4,\"has_files\":true}'),
(1858, 1, 'VIEW_CANDIDATES', '2026-03-17 22:50:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 125, Candidacy: 129'),
(1859, 1, 'VIEW_CANDIDATES', '2026-03-17 22:52:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 125, Candidacy: 129'),
(1860, 1, 'Submitted candidacy application', '2026-03-18 05:52:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-18 05:52:23\",\"field_count\":4,\"has_files\":true}'),
(1861, 1, 'VIEW_CANDIDATES', '2026-03-17 22:52:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 125, Candidacy: 129'),
(1862, 1, 'Submitted candidacy application', '2026-03-18 05:52:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-18 05:52:51\",\"field_count\":4,\"has_files\":true}'),
(1863, 1, 'VIEW_CANDIDATES', '2026-03-17 22:53:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 125, Candidacy: 129'),
(1864, 1, 'Submitted candidacy application', '2026-03-18 05:53:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-18 05:53:13\",\"field_count\":4,\"has_files\":true}'),
(1865, 1, 'VIEW_CANDIDATES', '2026-03-17 22:53:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 125, Candidacy: 129'),
(1866, 1, 'ADD_MODERATOR', '2026-03-18 05:54:57', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator CCS-Comsci-WMSUMain Email ccsmaingmailcom Precinct ID 198'),
(1867, 19495, 'LOGIN', '2026-03-18 06:00:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1868, 19493, 'LOGIN', '2026-03-18 06:04:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1869, 19522, 'LOGIN', '2026-03-18 06:13:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1870, 1, 'ADD_ELECTION', '2026-03-18 06:26:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: M7 Elections, School Year: 2023 - 2024, Semester: 1st Semester, Status: Ongoing'),
(1871, 1, 'VIEW_CANDIDATES', '2026-03-17 23:28:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 126, Candidacy: 130'),
(1872, 1, 'Submitted candidacy application', '2026-03-18 06:28:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-18 06:28:53\",\"field_count\":4,\"has_files\":true}'),
(1873, 1, 'VIEW_CANDIDATES', '2026-03-17 23:29:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 126, Candidacy: 130'),
(1874, 1, 'Submitted candidacy application', '2026-03-18 06:29:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-18 06:29:11\",\"field_count\":4,\"has_files\":true}'),
(1875, 1, 'VIEW_CANDIDATES', '2026-03-17 23:29:14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 126, Candidacy: 130'),
(1876, 1, 'Submitted candidacy application', '2026-03-18 06:29:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-18 06:29:30\",\"field_count\":4,\"has_files\":true}'),
(1877, 1, 'VIEW_CANDIDATES', '2026-03-17 23:29:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 126, Candidacy: 130'),
(1878, 1, 'Submitted candidacy application', '2026-03-18 06:29:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-18 06:29:51\",\"field_count\":4,\"has_files\":true}'),
(1879, 1, 'VIEW_CANDIDATES', '2026-03-17 23:29:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 126, Candidacy: 130'),
(1880, 1, 'Submitted candidacy application', '2026-03-18 06:30:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-18 06:30:03\",\"field_count\":4,\"has_files\":true}'),
(1881, 1, 'VIEW_CANDIDATES', '2026-03-17 23:30:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 126, Candidacy: 130'),
(1882, 1, 'Submitted candidacy application', '2026-03-18 06:30:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-18 06:30:20\",\"field_count\":4,\"has_files\":true}'),
(1883, 1, 'VIEW_CANDIDATES', '2026-03-17 23:30:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 126, Candidacy: 130'),
(1884, 1, 'ADD_MODERATOR', '2026-03-18 06:33:52', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator wmsu ccs mod Email wmsuccsmodgmailcom Precinct ID 201'),
(1885, 1, 'VIEW_CANDIDATES', '2026-03-17 23:34:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 126, Candidacy: 130'),
(1886, 1, 'ADD_MODERATOR', '2026-03-18 06:35:37', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome145000 Safari53736', '::1', 'N/A', 'Added moderator wmsu it mod Email wmsuitmodgmailcom Precinct ID 202'),
(1887, 19495, 'LOGIN', '2026-03-18 06:36:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1888, 19496, 'LOGIN', '2026-03-18 06:38:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1889, 19510, 'LOGIN', '2026-03-18 06:40:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1890, 19521, 'LOGIN', '2026-03-18 06:40:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1891, 19520, 'LOGIN', '2026-03-18 06:40:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1892, 19510, 'LOGIN', '2026-03-18 06:41:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1893, 1, 'LOGIN', '2026-03-19 22:24:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', '6.93647022824363,122.0843895107452', 'Successful login'),
(1894, 1, 'LOGIN', '2026-03-21 05:52:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', '6.9364385,122.08403400000002', 'Successful login'),
(1895, 1, 'ADD_ELECTION', '2026-03-21 09:22:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: test, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1896, 1, 'LOGIN', '2026-03-21 22:43:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', '6.9364385,122.08403400000002', 'Successful login'),
(1897, 1, 'DELETE_POSITION_PARTY', '2026-03-21 23:17:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"test\". Candidates: 222. Position row removed: yes'),
(1898, 1, 'DELETE_POSITION_PARTY', '2026-03-21 23:17:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"President\" for party \"test\". Candidates: 223. Position row removed: yes'),
(1899, 1, 'ADD_ELECTION', '2026-03-21 23:21:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: M7 Elections, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1900, 1, 'DELETE_POSITION_PARTY', '2026-03-22 11:50:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team Liquid\". Candidates: 228. Position row removed: yes'),
(1901, 1, 'DELETE_POSITION_PARTY', '2026-03-22 11:50:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team ONIC\". Candidates: 229. Position row removed: yes'),
(1902, 1, 'DELETE_POSITION_PARTY', '2026-03-22 11:51:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Vice-Mayor\" for party \"Team Liquid\". Candidates: 230. Position row removed: yes'),
(1903, 1, 'DELETE_POSITION_PARTY', '2026-03-22 11:51:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Vice-Mayor\" for party \"Team ONIC\". Candidates: 231. Position row removed: yes'),
(1904, 1, 'DELETE_POSITION_PARTY', '2026-03-22 11:51:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Senator\" for party \"Team Liquid\". Candidates: 232. Position row removed: yes'),
(1905, 1, 'DELETE_POSITION_PARTY', '2026-03-22 11:51:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Senator\" for party \"Team ONIC\". Candidates: 233. Position row removed: yes'),
(1906, 1, 'DELETE_POSITION_PARTY', '2026-03-22 11:51:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Councillor\" for party \"Team Liquid\". Candidates: 234. Position row removed: yes'),
(1907, 1, 'DELETE_POSITION_PARTY', '2026-03-22 11:51:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Councillor\" for party \"Team ONIC\". Candidates: 235. Position row removed: yes'),
(1908, 1, 'LOGIN', '2026-03-22 18:47:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', '6.9364385,122.08403400000002', 'Successful login');
INSERT INTO `user_activities` (`id`, `user_id`, `action`, `timestamp`, `device_info`, `ip_address`, `location`, `behavior_patterns`) VALUES
(1909, 1, 'VIEW_CANDIDATES', '2026-03-22 11:55:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1910, 19508, 'LOGIN', '2026-03-22 23:20:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(1911, 19505, 'LOGIN', '2026-03-23 05:26:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1912, 19511, 'LOGIN', '2026-03-23 05:26:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1913, 19513, 'LOGIN', '2026-03-23 05:26:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1914, 19495, 'LOGIN', '2026-03-23 05:55:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1915, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:00:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team Liquid\". Candidates: 236. Position row removed: yes'),
(1916, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:00:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team ONIC\". Candidates: 237. Position row removed: yes'),
(1917, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:12:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team ONIC\". Candidates: 239. Position row removed: yes'),
(1918, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:12:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team Liquid\". Candidates: 238. Position row removed: yes'),
(1919, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:13:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team Liquid\". Candidates: 240. Position row removed: yes'),
(1920, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:13:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team ONIC\". Candidates: 241. Position row removed: yes'),
(1921, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:18:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team Liquid\". Candidates: 242. Position row removed: yes'),
(1922, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:18:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team ONIC\". Candidates: 243. Position row removed: yes'),
(1923, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:18:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team Liquid\". Candidates: 244. Position row removed: yes'),
(1924, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:18:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team ONIC\". Candidates: 245. Position row removed: yes'),
(1925, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:19:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team Liquid\". Candidates: 246. Position row removed: yes'),
(1926, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:19:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team ONIC\". Candidates: 247. Position row removed: yes'),
(1927, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:23:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team Liquid\". Candidates: 248. Position row removed: yes'),
(1928, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:23:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team ONIC\". Candidates: 249. Position row removed: yes'),
(1929, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:24:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team Liquid\". Candidates: 250. Position row removed: yes'),
(1930, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:24:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team ONIC\". Candidates: 251. Position row removed: yes'),
(1931, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:46:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team Liquid\". Candidates: 252. Position row removed: yes'),
(1932, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:46:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team ONIC\". Candidates: 253. Position row removed: yes'),
(1933, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:47:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team Liquid\". Candidates: 254. Position row removed: yes'),
(1934, 1, 'DELETE_POSITION_PARTY', '2026-03-23 06:47:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Team ONIC\". Candidates: 255. Position row removed: yes'),
(1935, 1, 'VIEW_CANDIDATES', '2026-03-22 23:47:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1936, 1, 'VIEW_CANDIDATES', '2026-03-22 23:47:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1937, 1, 'VIEW_CANDIDATES', '2026-03-23 00:02:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1938, 1, 'VIEW_CANDIDATES', '2026-03-23 00:02:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1939, 1, 'VIEW_CANDIDATES', '2026-03-23 00:03:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1940, 1, 'VIEW_CANDIDATES', '2026-03-23 00:03:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1941, 1, 'VIEW_CANDIDATES', '2026-03-23 00:03:35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1942, 1, 'VIEW_CANDIDATES', '2026-03-23 00:03:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1943, 1, 'VIEW_CANDIDATES', '2026-03-23 00:04:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1944, 1, 'VIEW_CANDIDATES', '2026-03-23 00:04:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1945, 1, 'VIEW_CANDIDATES', '2026-03-23 00:04:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1946, 1, 'VIEW_CANDIDATES', '2026-03-23 00:04:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1947, 1, 'VIEW_CANDIDATES', '2026-03-23 00:04:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1948, 1, 'VIEW_CANDIDATES', '2026-03-23 00:05:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1949, 1, 'VIEW_CANDIDATES', '2026-03-23 00:05:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1950, 1, 'VIEW_CANDIDATES', '2026-03-23 00:05:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1951, 1, 'VIEW_CANDIDATES', '2026-03-23 00:05:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1952, 1, 'VIEW_CANDIDATES', '2026-03-23 00:05:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1953, 1, 'VIEW_CANDIDATES', '2026-03-23 00:05:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1954, 1, 'VIEW_CANDIDATES', '2026-03-23 00:05:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1955, 1, 'VIEW_CANDIDATES', '2026-03-23 00:05:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1956, 1, 'VIEW_CANDIDATES', '2026-03-23 00:05:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1957, 1, 'VIEW_CANDIDATES', '2026-03-23 00:05:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1958, 1, 'VIEW_CANDIDATES', '2026-03-23 00:06:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1959, 1, 'VIEW_CANDIDATES', '2026-03-23 00:06:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1960, 1, 'VIEW_CANDIDATES', '2026-03-23 00:07:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1961, 1, 'VIEW_CANDIDATES', '2026-03-23 00:07:14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1962, 1, 'VIEW_CANDIDATES', '2026-03-23 00:09:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1963, 1, 'Submitted candidacy application', '2026-03-23 07:10:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-23 07:10:18\",\"field_count\":4,\"has_files\":true}'),
(1964, 1, 'VIEW_CANDIDATES', '2026-03-23 00:10:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1965, 1, 'Submitted candidacy application', '2026-03-23 07:10:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-23 07:10:57\",\"field_count\":4,\"has_files\":true}'),
(1966, 1, 'VIEW_CANDIDATES', '2026-03-23 00:11:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1967, 1, 'Submitted candidacy application', '2026-03-23 07:11:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-23 07:11:23\",\"field_count\":4,\"has_files\":true}'),
(1968, 1, 'VIEW_CANDIDATES', '2026-03-23 00:11:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1969, 1, 'Submitted candidacy application', '2026-03-23 07:12:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-23 07:12:23\",\"field_count\":4,\"has_files\":true}'),
(1970, 1, 'VIEW_CANDIDATES', '2026-03-23 00:12:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 127, Candidacy: 132'),
(1971, 19530, 'LOGIN', '2026-03-23 07:26:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1972, 19495, 'LOGIN', '2026-03-23 07:27:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(1973, 1, 'ADD_ELECTION', '2026-03-23 10:22:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Another test, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1974, 1, 'DELETE_ELECTION', '2026-03-23 03:32:29', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '::1', 'N/A', 'Deleted election: Another test'),
(1975, 1, 'ADD_ELECTION', '2026-03-23 10:33:14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: New, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(1976, 1, 'VIEW_CANDIDATES', '2026-03-23 03:39:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 128, Candidacy: 134'),
(1977, 19513, 'LOGIN', '2026-03-23 10:40:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(1978, 19495, 'LOGIN', '2026-03-23 10:41:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1979, 19493, 'LOGIN', '2026-03-23 10:42:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(1980, 19493, 'LOGIN', '2026-03-23 10:42:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', '6.9364385,122.08403400000002', 'Successful login'),
(1981, 1, 'VIEW_CANDIDATES', '2026-03-23 03:42:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 128, Candidacy: 134'),
(1982, 1, 'VIEW_CANDIDATES', '2026-03-23 03:42:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 128, Candidacy: 134'),
(1983, 1, 'VIEW_CANDIDATES', '2026-03-23 03:42:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 128, Candidacy: 134'),
(1984, 1, 'VIEW_CANDIDATES', '2026-03-23 03:43:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 128, Candidacy: 134'),
(1985, 1, 'LOGIN', '2026-03-24 06:19:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', '6.936421999999999,122.08407583333334', 'Successful login'),
(1986, 19495, 'LOGIN', '2026-03-24 07:27:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(1987, 1, 'ADD_ELECTION', '2026-03-24 07:29:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Major New One, School Year: 2026 - 2027, Semester: 2nd Semester, Status: Ongoing'),
(1988, 1, 'VIEW_CANDIDATES', '2026-03-24 00:37:35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 129, Candidacy: 135'),
(1989, 1, 'VIEW_CANDIDATES', '2026-03-24 00:39:14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 129, Candidacy: 135'),
(1990, 1, 'Submitted candidacy application', '2026-03-24 07:39:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-24 07:39:28\",\"field_count\":4,\"has_files\":true}'),
(1991, 1, 'VIEW_CANDIDATES', '2026-03-24 00:39:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 129, Candidacy: 135'),
(1992, 1, 'Submitted candidacy application', '2026-03-24 07:39:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-24 07:39:49\",\"field_count\":4,\"has_files\":true}'),
(1993, 1, 'VIEW_CANDIDATES', '2026-03-24 00:39:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 129, Candidacy: 135'),
(1994, 1, 'Submitted candidacy application', '2026-03-24 07:40:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-24 07:40:12\",\"field_count\":4,\"has_files\":true}'),
(1995, 1, 'VIEW_CANDIDATES', '2026-03-24 00:40:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 129, Candidacy: 135'),
(1996, 1, 'Submitted candidacy application', '2026-03-24 07:40:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-24 07:40:31\",\"field_count\":4,\"has_files\":true}'),
(1997, 1, 'VIEW_CANDIDATES', '2026-03-24 00:40:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 129, Candidacy: 135'),
(1998, 1, 'VIEW_CANDIDATES', '2026-03-24 00:40:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 129, Candidacy: 135'),
(1999, 19508, 'LOGIN', '2026-03-24 07:42:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(2000, 1, 'ADD_ELECTION', '2026-03-24 07:47:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Newest Election So Far, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(2001, 1, 'DELETE_POSITION_PARTY', '2026-03-24 07:48:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted position \"Mayor\" for party \"Party A\". Candidates: 268. Position row removed: yes'),
(2002, 19508, 'LOGIN', '2026-03-24 07:49:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(2003, 19495, 'LOGIN', '2026-03-24 07:50:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(2004, 19513, 'LOGIN', '2026-03-24 07:51:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(2005, 19495, 'LOGIN', '2026-03-24 07:52:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(2006, 1, 'VIEW_CANDIDATES', '2026-03-24 00:52:14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 130, Candidacy: 136'),
(2007, 1, 'VIEW_CANDIDATES', '2026-03-24 00:52:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 130, Candidacy: 136'),
(2008, 1, 'VIEW_CANDIDATES', '2026-03-24 00:52:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 130, Candidacy: 136'),
(2009, 1, 'VIEW_CANDIDATES', '2026-03-24 00:52:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 130, Candidacy: 136'),
(2010, 1, 'VIEW_CANDIDATES', '2026-03-24 00:53:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 130, Candidacy: 136'),
(2011, 1, 'VIEW_CANDIDATES', '2026-03-24 00:53:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 130, Candidacy: 136'),
(2012, 1, 'Submitted candidacy application', '2026-03-24 07:53:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-24 07:53:46\",\"field_count\":4,\"has_files\":true}'),
(2013, 1, 'VIEW_CANDIDATES', '2026-03-24 00:53:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 130, Candidacy: 136'),
(2014, 1, 'VIEW_CANDIDATES', '2026-03-24 00:53:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 130, Candidacy: 136'),
(2015, 1, 'VIEW_CANDIDATES', '2026-03-24 00:54:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 130, Candidacy: 136'),
(2016, 1, 'VIEW_CANDIDATES', '2026-03-24 00:54:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 130, Candidacy: 136'),
(2017, 1, 'Submitted candidacy application', '2026-03-24 07:54:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-24 07:54:25\",\"field_count\":4,\"has_files\":true}'),
(2018, 1, 'VIEW_CANDIDATES', '2026-03-24 00:54:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 130, Candidacy: 136'),
(2019, 1, 'Submitted candidacy application', '2026-03-24 07:54:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-24 07:54:46\",\"field_count\":4,\"has_files\":true}'),
(2020, 1, 'VIEW_CANDIDATES', '2026-03-24 00:54:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 130, Candidacy: 136'),
(2021, 1, 'Submitted candidacy application', '2026-03-24 07:54:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-24 07:54:58\",\"field_count\":4,\"has_files\":true}'),
(2022, 1, 'VIEW_CANDIDATES', '2026-03-24 00:55:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 130, Candidacy: 136'),
(2023, 1, 'Submitted candidacy application', '2026-03-24 07:55:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-24 07:55:31\",\"field_count\":4,\"has_files\":true}'),
(2024, 1, 'VIEW_CANDIDATES', '2026-03-24 00:55:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 130, Candidacy: 136'),
(2025, 1, 'LOGIN', '2026-03-27 11:37:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', '6.9364385,122.08403400000002', 'Successful login'),
(2026, 1, 'ADD_ELECTION', '2026-03-27 11:37:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing'),
(2027, 1, 'VIEW_CANDIDATES', '2026-03-27 06:26:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 131, Candidacy: 137'),
(2028, 1, 'VIEW_CANDIDATES', '2026-03-27 06:27:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 131, Candidacy: 137'),
(2029, 1, 'Submitted candidacy application', '2026-03-27 13:27:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-27 13:27:50\",\"field_count\":4,\"has_files\":true}'),
(2030, 1, 'VIEW_CANDIDATES', '2026-03-27 06:28:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 131, Candidacy: 137'),
(2031, 1, 'Submitted candidacy application', '2026-03-27 13:29:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-27 13:29:15\",\"field_count\":4,\"has_files\":true}'),
(2032, 1, 'VIEW_CANDIDATES', '2026-03-27 06:29:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 131, Candidacy: 137'),
(2033, 1, 'Submitted candidacy application', '2026-03-27 13:29:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-27 13:29:48\",\"field_count\":4,\"has_files\":true}'),
(2034, 1, 'VIEW_CANDIDATES', '2026-03-27 06:29:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 131, Candidacy: 137'),
(2035, 1, 'Submitted candidacy application', '2026-03-27 13:30:14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-27 13:30:14\",\"field_count\":4,\"has_files\":true}'),
(2036, 1, 'VIEW_CANDIDATES', '2026-03-27 06:30:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 131, Candidacy: 137'),
(2037, 1, 'VIEW_CANDIDATES', '2026-03-27 06:30:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 131, Candidacy: 137'),
(2038, 1, 'ADD_MODERATOR', '2026-03-27 13:58:09', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome146000 Safari53736', '::1', 'N/A', 'Added moderator Wmsu Moderator New Email wmsumoderatornewgmailcom Precinct ID 220'),
(2039, 1, 'ADD_MODERATOR', '2026-03-27 13:58:48', 'Mozilla50 Windows NT 100 Win64 x64 AppleWebKit53736 KHTML like Gecko Chrome146000 Safari53736', '::1', 'N/A', 'Added moderator wmsu csmod newer Email wmsumodmodgmailcom Precinct ID 219'),
(2040, 1, 'ADD_ELECTION', '2026-03-27 14:06:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: New, School Year: 2026 - 2027, Semester: 2nd Semester, Status: Ongoing'),
(2041, 1, 'VIEW_CANDIDATES', '2026-03-27 07:07:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 132, Candidacy: 138'),
(2042, 1, 'VIEW_CANDIDATES', '2026-03-27 07:07:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 132, Candidacy: 138'),
(2043, 1, 'Submitted candidacy application', '2026-03-27 14:07:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-27 14:07:45\",\"field_count\":4,\"has_files\":true}'),
(2044, 1, 'VIEW_CANDIDATES', '2026-03-27 07:07:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 132, Candidacy: 138'),
(2045, 1, 'Submitted candidacy application', '2026-03-27 14:08:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-27 14:08:59\",\"field_count\":4,\"has_files\":true}'),
(2046, 1, 'VIEW_CANDIDATES', '2026-03-27 07:09:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 132, Candidacy: 138'),
(2047, 1, 'Submitted candidacy application', '2026-03-27 14:10:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-27 14:10:24\",\"field_count\":4,\"has_files\":true}'),
(2048, 1, 'VIEW_CANDIDATES', '2026-03-27 07:10:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 132, Candidacy: 138'),
(2049, 1, 'Submitted candidacy application', '2026-03-27 14:10:35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-27 14:10:35\",\"field_count\":4,\"has_files\":true}'),
(2050, 1, 'VIEW_CANDIDATES', '2026-03-27 07:10:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 132, Candidacy: 138'),
(2051, 1, 'VIEW_CANDIDATES', '2026-03-27 07:10:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 132, Candidacy: 138'),
(2052, 1, 'LOGIN', '2026-03-27 20:46:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', '6.9364385,122.08403400000002', 'Successful login'),
(2053, 1, 'ADD_ELECTION', '2026-03-27 20:47:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: New Electioner, School Year: 2025 - 2026, Semester: 1st Semester, Status: Ongoing'),
(2054, 1, 'DELETE_ELECTION', '2026-03-27 21:56:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: New Electioner'),
(2055, 1, 'ADD_ELECTION', '2026-03-28 05:10:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2025 - 2026, Semester: 1st Semester, Status: Ongoing'),
(2056, 1, 'DELETE_ELECTION', '2026-03-27 22:15:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Deleted election: Test'),
(2057, 1, 'ADD_ELECTION', '2026-03-28 05:16:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Test, School Year: 2025 - 2026, Semester: 1st Semester, Status: Ongoing'),
(2058, 19495, 'LOGIN', '2026-03-28 05:22:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(2059, 1, 'VIEW_CANDIDATES', '2026-03-27 22:22:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2060, 1, 'VIEW_CANDIDATES', '2026-03-27 22:22:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 132, Candidacy: 138'),
(2061, 1, 'VIEW_CANDIDATES', '2026-03-27 22:22:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 131, Candidacy: 137'),
(2062, 1, 'VIEW_CANDIDATES', '2026-03-27 22:23:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2063, 1, 'VIEW_CANDIDATES', '2026-03-27 22:23:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2064, 1, 'Submitted candidacy application', '2026-03-28 05:23:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-28 05:23:26\",\"field_count\":4,\"has_files\":true}'),
(2065, 1, 'VIEW_CANDIDATES', '2026-03-27 22:23:35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2066, 1, 'VIEW_CANDIDATES', '2026-03-27 22:23:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2067, 1, 'VIEW_CANDIDATES', '2026-03-27 22:23:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2068, 1, 'VIEW_CANDIDATES', '2026-03-27 22:24:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2069, 1, 'VIEW_CANDIDATES', '2026-03-27 22:28:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2070, 1, 'VIEW_CANDIDATES', '2026-03-27 22:28:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2071, 1, 'VIEW_CANDIDATES', '2026-03-27 22:29:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2072, 1, 'VIEW_CANDIDATES', '2026-03-27 22:30:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2073, 1, 'VIEW_CANDIDATES', '2026-03-27 22:30:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2074, 1, 'VIEW_CANDIDATES', '2026-03-27 22:31:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2075, 1, 'VIEW_CANDIDATES', '2026-03-27 22:31:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2076, 1, 'VIEW_CANDIDATES', '2026-03-27 22:32:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2077, 1, 'VIEW_CANDIDATES', '2026-03-27 22:33:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2078, 1, 'VIEW_CANDIDATES', '2026-03-27 22:33:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2079, 1, 'VIEW_CANDIDATES', '2026-03-27 22:33:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2080, 1, 'Submitted candidacy application', '2026-03-28 05:33:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-28 05:33:59\",\"field_count\":4,\"has_files\":true}'),
(2081, 1, 'VIEW_CANDIDATES', '2026-03-27 22:34:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2082, 1, 'Submitted candidacy application', '2026-03-28 05:34:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', NULL, '{\"form_submission_time\":\"2026-03-28 05:34:16\",\"field_count\":4,\"has_files\":true}'),
(2083, 1, 'VIEW_CANDIDATES', '2026-03-27 22:34:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Viewed candidates for event ID: 133, Candidacy: 141'),
(2084, 19513, 'LOGIN', '2026-03-28 05:35:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(2085, 19530, 'LOGIN', '2026-03-28 05:37:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(2086, 19495, 'LOGIN', '2026-03-28 05:38:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(2087, 19530, 'LOGIN', '2026-03-28 05:39:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Successful login'),
(2088, 19493, 'LOGIN', '2026-03-28 05:47:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(2089, 19530, 'LOGIN', '2026-03-28 06:04:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(2090, 19495, 'LOGIN', '2026-03-28 06:04:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(2091, 19495, 'LOGIN', '2026-03-28 16:18:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '::1', 'N/A', 'Successful login'),
(2092, 1, 'LOGIN', '2026-03-29 09:09:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', '6.9364385,122.08403400000002', 'Successful login'),
(2093, 1, 'ADD_ELECTION', '2026-03-29 09:14:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '::1', 'N/A', 'Added election: Name: Newest, School Year: 2026 - 2027, Semester: 1st Semester, Status: Ongoing');

-- --------------------------------------------------------

--
-- Table structure for table `voters`
--

CREATE TABLE `voters` (
  `id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `student_id` text NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `course` int(10) UNSIGNED DEFAULT NULL,
  `major` int(11) DEFAULT NULL,
  `year_level` int(11) UNSIGNED DEFAULT NULL,
  `college` int(11) DEFAULT NULL,
  `department` int(11) DEFAULT NULL,
  `wmsu_campus` int(11) DEFAULT NULL,
  `external_campus` int(11) DEFAULT NULL,
  `first_cor` varchar(255) DEFAULT NULL,
  `second_cor` varchar(255) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `activation_token` varchar(255) DEFAULT NULL,
  `activation_expiry` datetime DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 0,
  `status` varchar(50) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `needs_update` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `voters`
--

INSERT INTO `voters` (`id`, `academic_year_id`, `student_id`, `email`, `password`, `first_name`, `middle_name`, `last_name`, `course`, `major`, `year_level`, `college`, `department`, `wmsu_campus`, `external_campus`, `first_cor`, `second_cor`, `semester`, `activation_token`, `activation_expiry`, `is_active`, `status`, `rejection_reason`, `needs_update`) VALUES
(9, 55, '2020-01520', 'xt202001520@wmsu.edu.ph', '$2y$10$cnnei8o1Nsgh.rA0fSk8huYx88iv6d3naA.sAeNp.GYmAe0Vw2T4W', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch -1', 17, NULL, 29, 22, 158, 8, NULL, '69b2551e51356.png', '69b2551e516e3.png', '1st Semester', '40b6986fb9c683c019c42004f2545a4131bed0c65771cf7c1141ccb8895de9110e49eb7cad172e4587f191ffee2655a67310', '2026-03-13 06:54:38', 0, 'archived', NULL, 1),
(10, 56, '2020-01524', 'xt202001524@wmsu.edu.ph', '$2y$10$2ToO9mK.2b5bg36rQsK/7OCrd7B8FrS7UiaFVPErx8cLnh94.r6Xy', 'TESTER', 'TESTER', 'TESTER', 17, NULL, 24, 22, 158, 8, NULL, '69c1d1d779ac4.jpg', '69c1d1d779ded.jpg', '1st Semester', '874ae5ab35f96a7c123b4313a826c6bdc1fd911e3647bc0204f034a8810e19d45db73fd8ff41fc3e8ec85e149b7c69ca7205', '2026-03-25 00:50:47', 0, 'archived', NULL, 1),
(11, 55, '2020-01521', 'xt202001521@wmsu.edu.ph', '$2y$10$xqrinSjw7pQGmRkzZPBwCORs9uG8Trubkpdi.s8C6kWQYFYtTOjue', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch 1', 17, NULL, 29, 22, 158, 8, NULL, '69b3840ed0c5d.png', '69b3840ed11f9.png', '1st Semester', '55dc6b021c0fc62d6cafe5124d946743d4973895ff5a4450a6899d8841e74809df000a4ac2aa0e6c545fcc74e49ef822300f', '2026-03-14 04:27:10', 0, 'archived', NULL, 1),
(12, 55, '2020-01525', 'xt202001525@wmsu.edu.ph', '$2y$10$xqrinSjw7pQGmRkzZPBwCORs9uG8Trubkpdi.s8C6kWQYFYtTOjue', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch 2', 17, NULL, 29, 22, 158, 8, NULL, '69b3840ed0c5d.png', '69b3840ed11f9.png', '1st Semester', '55dc6b021c0fc62d6cafe5124d946743d4973895ff5a4450a6899d8841e74809df000a4ac2aa0e6c545fcc74e49ef822300f', '2026-03-14 04:27:10', 0, 'archived', NULL, 1),
(13, 55, '2020-01526', 'xt202001526@wmsu.edu.ph', '$2y$10$xqrinSjw7pQGmRkzZPBwCORs9uG8Trubkpdi.s8C6kWQYFYtTOjue', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch 2', 17, NULL, 29, 22, 158, 10, 12, '69b3840ed0c5d.png', '69b3840ed11f9.png', '1st Semester', '55dc6b021c0fc62d6cafe5124d946743d4973895ff5a4450a6899d8841e74809df000a4ac2aa0e6c545fcc74e49ef822300f', '2026-03-14 04:27:10', 0, 'archived', NULL, 1),
(14, 55, '2021-00168', 'qb202100168@wmsu.edu.ph', '$2y$10$U8gzv2cokyqEpmrLQKJsXO9MAznMR7gptoGYsdc1dkn/v5lLsKLAO', 'Antonette', 'Suela', 'Manolis', 17, NULL, 26, 22, 158, 8, NULL, '69b67b5dea9ec.png', '69b67b5deac8a.png', '2nd Semester', 'd350a32cf9e3ed36533210698b92bb66c4b6abf4db63556fdc7628a592bf303c9d57acba5c16869300dd5898b8cfa127d11a', '2026-03-16 10:26:53', 0, 'archived', NULL, 1),
(15, 55, '2021-01252', 'qb202101252@wmsu.edu.ph', '$2y$10$4nYwpGvnGTDoerbzy39YZ.oziFCR352G1gqzjaZqknSlKQBSJmWfq', 'Nur', 'Benito', 'Balla', 17, NULL, 26, 22, 158, 8, NULL, '69b67bbdb4066.png', '69b67bbdb498a.png', '2nd Semester', 'f2dd07422caba3aa7bc81fd87966c9a7d59cb4db49f89b5401e3ac61748555b41cb9bd34492acc56267eb413b7d179ff004f', '2026-03-16 10:28:29', 0, 'archived', NULL, 1),
(16, 55, '2021-00274', 'qb202100274@wmsu.edu.ph', '$2y$10$kcOowLWY/Wg3BdY9oLLz5OR16I9MDfImme.VjECQ.w3GNjC/0kf3e', 'Irene', NULL, 'Aquino', 17, NULL, 26, 22, 158, 8, NULL, '69b67c3ef0d4a.png', '69b67c3ef100b.png', '2nd Semester', '5c7f30ea2a2122a0f0ebdc1685e32033c45dfbbe1c47e729c863522e5d92004fb79dca9f3078f895768db8d2e61b918b2470', '2026-03-16 10:30:38', 0, 'archived', NULL, 1),
(17, 55, '1999-12345', 'xt199912345@wmsu.edu.ph', '$2y$10$1Un2sStY.aPJWqzVc52TouKX5lrkLTJKUR2wxiKfKJTpSGPfBP1si', '[TESTER] Test', '[TESTER] from', '[TESTER] IT', 18, NULL, 44, 22, 159, 8, NULL, '69b826880d90e.jpg', '69b826880ddd4.jpg', '1st Semester', '7ca4120f7b38840841301a129c61dce09bd898ddf17975a40d4c265dbd9918a3fa00ad4de0b76949131e0d2354f6b3d1f93c', '2026-03-17 16:49:28', 0, 'archived', NULL, 1),
(18, 55, '1998-12345', 'xt199812345@wmsu.edu.ph', '$2y$10$xqrinSjw7pQGmRkzZPBwCORs9uG8Trubkpdi.s8C6kWQYFYtTOjue', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch Somewhere', 17, NULL, 29, 22, 158, 10, 12, '69b3840ed0c5d.png', '69b3840ed11f9.png', '1st Semester', '55dc6b021c0fc62d6cafe5124d946743d4973895ff5a4450a6899d8841e74809df000a4ac2aa0e6c545fcc74e49ef822300f', '2026-03-14 04:27:10', 0, 'archived', NULL, 1),
(19, 55, '1997-12345', 'ce199712345@wmsu.edu.ph', '$2y$10$HEqtFJLYGOrbrvk7BKYd8.meGGYtHL.wTFkVJWqTU71RVG0uEa7Qu', '[TESTER] Test from another Scratch', '[TESTER] Engineering', '[TESTER] Computer Engineering', 29, NULL, 39, 28, 170, 8, NULL, '69b8e0711ee0d.png', '69b8e0711f188.png', '1st Semester', '8b86db4df5e99ce006e66b8ffe03d9c627bba07f021c4ed0a8cfc87bcb0047c072471b1d55a62ee9e3b424bc6a6de8d09f19', '2026-03-18 06:02:41', 0, 'archived', NULL, 1),
(20, 55, '1999-54321', 'ce199954321@wmsu.edu.ph', '$2y$10$.TzEgR./EEigYG4PnrIsxey4swr7lalpMcfOD3QqnrEGUfrLXxwHi', '[TESTER] Tester from another scratch', '[TESTER] from Engineering', '[TESTER] Computer Engineering as again', 29, NULL, 39, 28, 170, 8, NULL, '69b8e0b22401c.png', '69b8e0b224322.png', '1st Semester', 'f677d96e9cefb29a253c4c770dee0fff6f1f63ab623f218166223942b96b78f9173e1996cebb6787760c56ad9d2ea2b9e0a0', '2026-03-18 06:03:46', 0, 'archived', NULL, 1),
(21, 55, '1999-12134', 'xt199912134@wmsu.edu.ph', '$2y$10$FNQ/ql9v1t19tzvMTyroIusPbovjyUrjbh53zS1CAO9zWoboG/dj.', '[TESTER] Test from CCS', '[TESTER] Tester from Com-SCI', '[TESTER] Tester from Software Engineering', 17, 7, 29, 22, 158, 10, 12, '69b8e0ffac350.png', '69b8e0ffac639.png', '1st Semester', '77d8a63396987faf223b2007587a6f0272edd80373bd929e02059257516f1ec76d43fd917977b77b68d59de22694feb6f74f', '2026-03-18 06:05:03', 0, 'archived', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `voters_copy_adviser`
--

CREATE TABLE `voters_copy_adviser` (
  `adviser_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `course_old` varchar(255) DEFAULT NULL,
  `major_old` varchar(255) DEFAULT NULL,
  `year_level` varchar(50) NOT NULL,
  `college_old` varchar(255) DEFAULT NULL,
  `department_old` varchar(255) DEFAULT NULL,
  `wmsu_campus_old` varchar(255) DEFAULT NULL,
  `external_campus_old` varchar(255) DEFAULT NULL,
  `first_cor` text NOT NULL,
  `second_cor` text NOT NULL,
  `activation_token` varchar(100) DEFAULT NULL,
  `activation_expiry` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `status` text NOT NULL DEFAULT 'pending',
  `academic_year_id` int(11) NOT NULL,
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `major_id` int(10) DEFAULT NULL,
  `college_id` int(10) DEFAULT NULL,
  `department_id` int(10) DEFAULT NULL,
  `wmsu_campus_id` int(10) DEFAULT NULL,
  `external_campus_id` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voter_columns`
--

CREATE TABLE `voter_columns` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `number` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voter_custom_fields`
--

CREATE TABLE `voter_custom_fields` (
  `id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_order` int(11) NOT NULL,
  `field_type` enum('text','number','date','file','dropdown','select','email','password') NOT NULL,
  `is_required` tinyint(1) DEFAULT 1,
  `field_sample` varchar(255) DEFAULT NULL,
  `field_description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_visible` tinyint(1) DEFAULT 1,
  `column_width` int(11) DEFAULT 12 COMMENT 'Bootstrap grid width (e.g., 6 for half, 12 for full)',
  `options` varchar(2555) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voter_custom_files`
--

CREATE TABLE `voter_custom_files` (
  `id` int(11) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voter_custom_responses`
--

CREATE TABLE `voter_custom_responses` (
  `id` int(11) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `id` int(11) NOT NULL,
  `voting_period_id` int(11) NOT NULL,
  `precinct` varchar(255) NOT NULL,
  `position` text NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `vote_timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voting_periods`
--

CREATE TABLE `voting_periods` (
  `id` int(11) NOT NULL,
  `election_id` int(11) NOT NULL,
  `start_period` datetime DEFAULT NULL,
  `end_period` datetime DEFAULT NULL,
  `re_start_period` datetime DEFAULT NULL,
  `re_end_period` datetime DEFAULT NULL,
  `status` text DEFAULT 'Scheduled',
  `created_at` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voting_periods`
--

INSERT INTO `voting_periods` (`id`, `election_id`, `start_period`, `end_period`, `re_start_period`, `re_end_period`, `status`, `created_at`) VALUES
(75, 137, '2026-03-27 07:00:00', '2026-03-28 16:40:00', NULL, NULL, 'Published', '2026-03-27'),
(76, 138, '2026-03-27 07:00:00', '2026-03-28 16:00:00', NULL, NULL, 'Published', '2026-03-27'),
(77, 141, '2026-03-28 05:53:00', '2026-03-28 16:09:00', '2026-03-28 06:03:40', '2026-03-29 06:03:43', 'Published', '2026-03-28');

-- --------------------------------------------------------

--
-- Table structure for table `year_levels`
--

CREATE TABLE `year_levels` (
  `id` int(10) UNSIGNED NOT NULL,
  `level` int(11) NOT NULL,
  `description` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `year_levels`
--

INSERT INTO `year_levels` (`id`, `level`, `description`) VALUES
(1, 1, 'First Year'),
(2, 2, 'Second Year'),
(3, 3, 'Third Year'),
(4, 4, 'Fourth Year'),
(5, 5, 'Fifth Year');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `actual_year_levels`
--
ALTER TABLE `actual_year_levels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_course_id` (`course_id`),
  ADD KEY `fk_major_id` (`major_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `advisers`
--
ALTER TABLE `advisers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_adv_college` (`college_id`),
  ADD KEY `fk_adv_department` (`department_id`),
  ADD KEY `fk_adv_major` (`major_id`),
  ADD KEY `fk_adv_wmsu_campus` (`wmsu_campus_id`),
  ADD KEY `fk_adv_external_campus` (`external_campus_id`),
  ADD KEY `fk_advisers_actual_year_level` (`year_level`);

--
-- Indexes for table `adviser_import_details`
--
ALTER TABLE `adviser_import_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archived_details_short`
--
ALTER TABLE `archived_details_short`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_archived_election` (`election_id`);

--
-- Indexes for table `archived_precincts`
--
ALTER TABLE `archived_precincts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `campuses`
--
ALTER TABLE `campuses`
  ADD PRIMARY KEY (`campus_id`),
  ADD UNIQUE KEY `campus_name` (`campus_name`),
  ADD KEY `fk_parent_campus` (`parent_id`);

--
-- Indexes for table `candidacy`
--
ALTER TABLE `candidacy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_candidacy_election` (`election_id`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_id` (`form_id`);

--
-- Indexes for table `candidate_files`
--
ALTER TABLE `candidate_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidate_id` (`candidate_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `candidate_responses`
--
ALTER TABLE `candidate_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidate_id` (`candidate_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `colleges`
--
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`college_id`);

--
-- Indexes for table `college_coordinates`
--
ALTER TABLE `college_coordinates`
  ADD PRIMARY KEY (`coordinate_id`),
  ADD UNIQUE KEY `unique_college_campus` (`college_id`,`campus_id`),
  ADD KEY `fk_college_coordinates_campus` (`campus_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_courses_college` (`college_id`);

--
-- Indexes for table `course_year_levels`
--
ALTER TABLE `course_year_levels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_year_level` (`year_level_id`),
  ADD KEY `course_id` (`course_id`,`year_level_id`) USING BTREE;

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `elections`
--
ALTER TABLE `elections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email`
--
ALTER TABLE `email`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_email_adviser` (`adviser_id`);

--
-- Indexes for table `email_errors`
--
ALTER TABLE `email_errors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_adviser_id` (`adviser_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `email_role_log`
--
ALTER TABLE `email_role_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_events_candidacy_new` (`candidacy`);

--
-- Indexes for table `form_fields`
--
ALTER TABLE `form_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_id` (`form_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `majors`
--
ALTER TABLE `majors`
  ADD PRIMARY KEY (`major_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `major_year_levels`
--
ALTER TABLE `major_year_levels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `moderators`
--
ALTER TABLE `moderators`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_moderators_college` (`college`),
  ADD KEY `fk_moderators_department` (`department`),
  ADD KEY `fk_moderators_major` (`major`),
  ADD KEY `fk_moderator_precinct` (`precinct`);

--
-- Indexes for table `parties`
--
ALTER TABLE `parties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_parties_election` (`election_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_position_per_election` (`name`,`party`,`level`,`election_id`),
  ADD KEY `election_id` (`election_id`);

--
-- Indexes for table `precincts`
--
ALTER TABLE `precincts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_precinct_college` (`college`),
  ADD KEY `fk_precinct_department` (`department`),
  ADD KEY `fk_precinct_campus` (`type`),
  ADD KEY `fk_precinct_external_campus` (`college_external`),
  ADD KEY `fk_precinct_election` (`election`),
  ADD KEY `fk_precinct_major` (`major_id`);

--
-- Indexes for table `precinct_elections`
--
ALTER TABLE `precinct_elections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_precinct_election_newest` (`election_name`),
  ADD KEY `fk_precinct` (`precinct_id`);

--
-- Indexes for table `precinct_voters`
--
ALTER TABLE `precinct_voters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `precinct_student_idx` (`precinct`,`student_id`);

--
-- Indexes for table `qr_sending_log`
--
ALTER TABLE `qr_sending_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registration_attempts`
--
ALTER TABLE `registration_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`registration_time`);

--
-- Indexes for table `registration_forms`
--
ALTER TABLE `registration_forms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_registration_election` (`election_name`);

--
-- Indexes for table `tied_candidates`
--
ALTER TABLE `tied_candidates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_id` (`form_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `voters`
--
ALTER TABLE `voters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course` (`course`),
  ADD KEY `major` (`major`),
  ADD KEY `college` (`college`),
  ADD KEY `department` (`department`),
  ADD KEY `wmsu_campus` (`wmsu_campus`),
  ADD KEY `external_campus` (`external_campus`),
  ADD KEY `fk_voter_year_level` (`year_level`);

--
-- Indexes for table `voters_copy_adviser`
--
ALTER TABLE `voters_copy_adviser`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vca_major` (`major_id`),
  ADD KEY `fk_vca_college` (`college_id`),
  ADD KEY `fk_vca_department` (`department_id`),
  ADD KEY `fk_vca_wmsu_campus` (`wmsu_campus_id`),
  ADD KEY `fk_vca_external_campus` (`external_campus_id`),
  ADD KEY `fk_vca_course` (`course_id`);

--
-- Indexes for table `voter_columns`
--
ALTER TABLE `voter_columns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_voter_columns_academic_year` (`academic_year_id`);

--
-- Indexes for table `voter_custom_fields`
--
ALTER TABLE `voter_custom_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `academic_year_id` (`academic_year_id`);

--
-- Indexes for table `voter_custom_files`
--
ALTER TABLE `voter_custom_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voter_id` (`voter_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `voter_custom_responses`
--
ALTER TABLE `voter_custom_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voter_id` (`voter_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `precinct` (`precinct`,`student_id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- Indexes for table `voting_periods`
--
ALTER TABLE `voting_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_voting_periods_election` (`election_id`);

--
-- Indexes for table `year_levels`
--
ALTER TABLE `year_levels`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `actual_year_levels`
--
ALTER TABLE `actual_year_levels`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `advisers`
--
ALTER TABLE `advisers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `adviser_import_details`
--
ALTER TABLE `adviser_import_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `archived_details_short`
--
ALTER TABLE `archived_details_short`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `archived_precincts`
--
ALTER TABLE `archived_precincts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `campuses`
--
ALTER TABLE `campuses`
  MODIFY `campus_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `candidacy`
--
ALTER TABLE `candidacy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=425;

--
-- AUTO_INCREMENT for table `candidate_files`
--
ALTER TABLE `candidate_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=623;

--
-- AUTO_INCREMENT for table `candidate_responses`
--
ALTER TABLE `candidate_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1663;

--
-- AUTO_INCREMENT for table `colleges`
--
ALTER TABLE `colleges`
  MODIFY `college_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `college_coordinates`
--
ALTER TABLE `college_coordinates`
  MODIFY `coordinate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `course_year_levels`
--
ALTER TABLE `course_year_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- AUTO_INCREMENT for table `elections`
--
ALTER TABLE `elections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT for table `email`
--
ALTER TABLE `email`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `email_errors`
--
ALTER TABLE `email_errors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `email_role_log`
--
ALTER TABLE `email_role_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=135;

--
-- AUTO_INCREMENT for table `form_fields`
--
ALTER TABLE `form_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=490;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `majors`
--
ALTER TABLE `majors`
  MODIFY `major_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `major_year_levels`
--
ALTER TABLE `major_year_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `moderators`
--
ALTER TABLE `moderators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=152;

--
-- AUTO_INCREMENT for table `parties`
--
ALTER TABLE `parties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=287;

--
-- AUTO_INCREMENT for table `precincts`
--
ALTER TABLE `precincts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=221;

--
-- AUTO_INCREMENT for table `precinct_elections`
--
ALTER TABLE `precinct_elections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=270;

--
-- AUTO_INCREMENT for table `precinct_voters`
--
ALTER TABLE `precinct_voters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3508;

--
-- AUTO_INCREMENT for table `qr_sending_log`
--
ALTER TABLE `qr_sending_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `registration_attempts`
--
ALTER TABLE `registration_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `registration_forms`
--
ALTER TABLE `registration_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `tied_candidates`
--
ALTER TABLE `tied_candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=425;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19533;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2094;

--
-- AUTO_INCREMENT for table `voters`
--
ALTER TABLE `voters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `voters_copy_adviser`
--
ALTER TABLE `voters_copy_adviser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24466;

--
-- AUTO_INCREMENT for table `voter_columns`
--
ALTER TABLE `voter_columns`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `voter_custom_fields`
--
ALTER TABLE `voter_custom_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1783;

--
-- AUTO_INCREMENT for table `voter_custom_files`
--
ALTER TABLE `voter_custom_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `voter_custom_responses`
--
ALTER TABLE `voter_custom_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=311;

--
-- AUTO_INCREMENT for table `voting_periods`
--
ALTER TABLE `voting_periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `year_levels`
--
ALTER TABLE `year_levels`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `actual_year_levels`
--
ALTER TABLE `actual_year_levels`
  ADD CONSTRAINT `fk_course_id` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_major_id` FOREIGN KEY (`major_id`) REFERENCES `majors` (`major_id`) ON DELETE SET NULL;

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `advisers`
--
ALTER TABLE `advisers`
  ADD CONSTRAINT `fk_adv_college` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_adv_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_adv_external_campus` FOREIGN KEY (`external_campus_id`) REFERENCES `campuses` (`campus_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_adv_major` FOREIGN KEY (`major_id`) REFERENCES `majors` (`major_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_adv_wmsu_campus` FOREIGN KEY (`wmsu_campus_id`) REFERENCES `campuses` (`campus_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_advisers_actual_year_level` FOREIGN KEY (`year_level`) REFERENCES `actual_year_levels` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `archived_details_short`
--
ALTER TABLE `archived_details_short`
  ADD CONSTRAINT `fk_archived_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `campuses`
--
ALTER TABLE `campuses`
  ADD CONSTRAINT `fk_parent_campus` FOREIGN KEY (`parent_id`) REFERENCES `campuses` (`campus_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `candidacy`
--
ALTER TABLE `candidacy`
  ADD CONSTRAINT `fk_candidacy_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `registration_forms` (`id`);

--
-- Constraints for table `candidate_files`
--
ALTER TABLE `candidate_files`
  ADD CONSTRAINT `candidate_files_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`),
  ADD CONSTRAINT `candidate_files_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `form_fields` (`id`);

--
-- Constraints for table `candidate_responses`
--
ALTER TABLE `candidate_responses`
  ADD CONSTRAINT `candidate_responses_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`),
  ADD CONSTRAINT `candidate_responses_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `form_fields` (`id`);

--
-- Constraints for table `college_coordinates`
--
ALTER TABLE `college_coordinates`
  ADD CONSTRAINT `fk_college_coordinates_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`campus_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_college_coordinates_college` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_courses_college` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE CASCADE;

--
-- Constraints for table `course_year_levels`
--
ALTER TABLE `course_year_levels`
  ADD CONSTRAINT `fk_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_year_level` FOREIGN KEY (`year_level_id`) REFERENCES `year_levels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE CASCADE;

--
-- Constraints for table `email`
--
ALTER TABLE `email`
  ADD CONSTRAINT `fk_email_adviser` FOREIGN KEY (`adviser_id`) REFERENCES `advisers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_events_candidacy_new` FOREIGN KEY (`candidacy`) REFERENCES `elections` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `form_fields`
--
ALTER TABLE `form_fields`
  ADD CONSTRAINT `form_fields_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `registration_forms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `majors`
--
ALTER TABLE `majors`
  ADD CONSTRAINT `majors_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `moderators`
--
ALTER TABLE `moderators`
  ADD CONSTRAINT `fk_moderator_precinct` FOREIGN KEY (`precinct`) REFERENCES `precincts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_moderators_college` FOREIGN KEY (`college`) REFERENCES `colleges` (`college_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_moderators_department` FOREIGN KEY (`department`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_moderators_major` FOREIGN KEY (`major`) REFERENCES `majors` (`major_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `parties`
--
ALTER TABLE `parties`
  ADD CONSTRAINT `fk_parties_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`);

--
-- Constraints for table `precincts`
--
ALTER TABLE `precincts`
  ADD CONSTRAINT `fk_precinct_campus` FOREIGN KEY (`type`) REFERENCES `campuses` (`campus_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_precinct_college` FOREIGN KEY (`college`) REFERENCES `colleges` (`college_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_precinct_department` FOREIGN KEY (`department`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_precinct_election` FOREIGN KEY (`election`) REFERENCES `elections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_precinct_external_campus` FOREIGN KEY (`college_external`) REFERENCES `campuses` (`campus_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_precinct_major` FOREIGN KEY (`major_id`) REFERENCES `majors` (`major_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `precinct_elections`
--
ALTER TABLE `precinct_elections`
  ADD CONSTRAINT `fk_precinct` FOREIGN KEY (`precinct_id`) REFERENCES `precincts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_precinct_election_new` FOREIGN KEY (`election_name`) REFERENCES `elections` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_precinct_election_newest` FOREIGN KEY (`election_name`) REFERENCES `elections` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `registration_forms`
--
ALTER TABLE `registration_forms`
  ADD CONSTRAINT `fk_registration_election` FOREIGN KEY (`election_name`) REFERENCES `elections` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD CONSTRAINT `user_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `voters`
--
ALTER TABLE `voters`
  ADD CONSTRAINT `fk_voter_year_level` FOREIGN KEY (`year_level`) REFERENCES `actual_year_levels` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_voters_college` FOREIGN KEY (`college`) REFERENCES `colleges` (`college_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_voters_course` FOREIGN KEY (`course`) REFERENCES `courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_voters_department` FOREIGN KEY (`department`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_voters_external_campus` FOREIGN KEY (`external_campus`) REFERENCES `campuses` (`campus_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_voters_major` FOREIGN KEY (`major`) REFERENCES `majors` (`major_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_voters_wmsu_campus` FOREIGN KEY (`wmsu_campus`) REFERENCES `campuses` (`campus_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `voters_copy_adviser`
--
ALTER TABLE `voters_copy_adviser`
  ADD CONSTRAINT `fk_vca_college` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`),
  ADD CONSTRAINT `fk_vca_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`),
  ADD CONSTRAINT `fk_vca_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `fk_vca_external_campus` FOREIGN KEY (`external_campus_id`) REFERENCES `campuses` (`campus_id`),
  ADD CONSTRAINT `fk_vca_major` FOREIGN KEY (`major_id`) REFERENCES `majors` (`major_id`),
  ADD CONSTRAINT `fk_vca_wmsu_campus` FOREIGN KEY (`wmsu_campus_id`) REFERENCES `campuses` (`campus_id`);

--
-- Constraints for table `voter_columns`
--
ALTER TABLE `voter_columns`
  ADD CONSTRAINT `fk_voter_columns_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `voter_custom_fields`
--
ALTER TABLE `voter_custom_fields`
  ADD CONSTRAINT `voter_custom_fields_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `voter_custom_files`
--
ALTER TABLE `voter_custom_files`
  ADD CONSTRAINT `voter_custom_files_ibfk_1` FOREIGN KEY (`voter_id`) REFERENCES `voters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `voter_custom_files_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `voter_custom_fields` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `voter_custom_responses`
--
ALTER TABLE `voter_custom_responses`
  ADD CONSTRAINT `voter_custom_responses_ibfk_1` FOREIGN KEY (`voter_id`) REFERENCES `voters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `voter_custom_responses_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `voter_custom_fields` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`precinct`,`student_id`) REFERENCES `precinct_voters` (`precinct`, `student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `votes_ibfk_3` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `voting_periods`
--
ALTER TABLE `voting_periods`
  ADD CONSTRAINT `fk_voting_periods_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
