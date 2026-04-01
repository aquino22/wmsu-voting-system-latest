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
-- Database: `wmsu_voting_system_archived`
--

-- --------------------------------------------------------

--
-- Table structure for table `archived_academic_years`
--

CREATE TABLE `archived_academic_years` (
  `id` int(11) NOT NULL,
  `year_label` varchar(50) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(20) NOT NULL,
  `archived_on` datetime NOT NULL,
  `custom_voter_option` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `archived_academic_years`
--

INSERT INTO `archived_academic_years` (`id`, `year_label`, `semester`, `start_date`, `end_date`, `status`, `archived_on`, `custom_voter_option`) VALUES
(54, '2026 - 2027', '1st Semester', '2026-03-27', '2026-05-31', 'archived', '2026-03-27 14:02:49', 0),
(55, '2026 - 2027', '2nd Semester', '2026-03-27', '2026-04-30', 'archived', '2026-03-27 14:22:31', 0),
(56, '2025 - 2026', '1st Semester', '2026-03-27', '2026-06-30', 'archived', '2026-03-28 06:08:52', 0);

-- --------------------------------------------------------

--
-- Table structure for table `archived_actual_year_levels`
--

CREATE TABLE `archived_actual_year_levels` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `major_id` int(11) DEFAULT NULL,
  `year_level` int(11) NOT NULL,
  `archived_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `archived_actual_year_levels`
--

INSERT INTO `archived_actual_year_levels` (`id`, `course_id`, `major_id`, `year_level`, `archived_on`) VALUES
(24, 17, NULL, 1, '2026-03-28 06:08:52'),
(25, 17, NULL, 2, '2026-03-28 06:08:52'),
(26, 17, NULL, 3, '2026-03-28 06:08:52'),
(27, 17, NULL, 4, '2026-03-28 06:08:52'),
(28, 17, NULL, 5, '2026-03-28 06:08:52'),
(29, 17, 7, 1, '2026-03-28 06:08:52'),
(30, 17, 7, 2, '2026-03-28 06:08:52'),
(31, 17, 7, 3, '2026-03-28 06:08:52'),
(32, 17, 7, 4, '2026-03-28 06:08:52'),
(33, 17, 7, 5, '2026-03-28 06:08:52'),
(34, 14, NULL, 1, '2026-03-28 06:08:52'),
(35, 14, NULL, 2, '2026-03-28 06:08:52'),
(36, 14, NULL, 3, '2026-03-28 06:08:52'),
(37, 14, NULL, 4, '2026-03-28 06:08:52'),
(38, 14, NULL, 5, '2026-03-28 06:08:52'),
(39, 29, NULL, 1, '2026-03-28 06:08:52'),
(40, 29, NULL, 2, '2026-03-28 06:08:52'),
(41, 29, NULL, 3, '2026-03-28 06:08:52'),
(42, 29, NULL, 4, '2026-03-28 06:08:52'),
(43, 29, NULL, 5, '2026-03-28 06:08:52'),
(44, 18, NULL, 1, '2026-03-28 06:08:52'),
(45, 18, NULL, 2, '2026-03-28 06:08:52'),
(46, 18, NULL, 3, '2026-03-28 06:08:52'),
(47, 18, NULL, 4, '2026-03-28 06:08:52'),
(48, 18, NULL, 5, '2026-03-28 06:08:52');

-- --------------------------------------------------------

--
-- Table structure for table `archived_campuses`
--

CREATE TABLE `archived_campuses` (
  `campus_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `campus_name` varchar(150) NOT NULL,
  `campus_location` varchar(255) DEFAULT NULL,
  `campus_type` varchar(255) NOT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `archived_campuses`
--

INSERT INTO `archived_campuses` (`campus_id`, `parent_id`, `campus_name`, `campus_location`, `campus_type`, `latitude`, `longitude`, `created_at`, `updated_at`, `archived_on`) VALUES
(8, NULL, 'Main Campus', 'Main Campus', '', '6.913199', '122.062221', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(10, NULL, 'WMSU ESU', 'WMSU ESU', '', '6.916287', '122.052187', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(12, 10, 'ESU - Alicia', 'Zamboanga Sibugay', '', '6.911047', '122.058414', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(13, 10, 'ESU - Aurora', 'Zamboanga del Sur', '', '6.913305', '122.062965', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(14, 10, 'ESU - Curuan', 'Zamboanga City', '', '6.91339', '122.063695', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(15, 10, 'ESU - Diplahan', 'Zamboanga Sibugay', '', '6.914072', '122.058929', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(16, 10, 'ESU - Imelda', 'Zamboanga Sibugay', '', '6.915691', '122.05996', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(17, 10, 'ESU - Ipil', 'Zamboanga Sibugay', '', '6.914967', '122.060432', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(18, 10, 'ESU - Mabuhay', 'Zamboanga Sibugay', '', '6.913646', '122.060775', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(19, 10, 'ESU - Malangas', 'Zamboanga Sibugay', '', '6.914413', '122.060818', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(20, 10, 'ESU - Molave', 'Zamboanga del Sur', '', '6.913476', '122.063352', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(21, 10, 'ESU - Naga', 'Zamboanga Sibugay', '', '6.913305', '122.06069', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(22, 10, 'ESU - Olutanga', 'Zamboanga Sibugay', '', '6.914072', '122.06172', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(23, 10, 'ESU - Pagadian City', 'Zamboanga del Sur', '', '6.913007', '122.062665', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(24, 10, 'ESU - Siay', 'Zamboanga Sibugay', '', '6.915734', '122.061591', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(25, 10, 'ESU - Tungawan', 'Zamboanga Sibugay', '', '6.913902', '122.062536', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38'),
(33, NULL, 'New', 'New', '', '6.912815', '122.062131', '2026-03-14 04:01:38', '2026-03-14 12:01:38', '2026-03-14 12:01:38');

-- --------------------------------------------------------

--
-- Table structure for table `archived_candidacies`
--

CREATE TABLE `archived_candidacies` (
  `id` int(11) NOT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `school_year_start` datetime DEFAULT NULL,
  `school_year_end` datetime DEFAULT NULL,
  `start_period` datetime DEFAULT NULL,
  `end_period` datetime DEFAULT NULL,
  `total_filed` int(11) DEFAULT NULL,
  `archived_on` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `voting_period_id` int(11) NOT NULL,
  `election_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_candidacies`
--

INSERT INTO `archived_candidacies` (`id`, `semester`, `school_year_start`, `school_year_end`, `start_period`, `end_period`, `total_filed`, `archived_on`, `status`, `voting_period_id`, `election_id`) VALUES
(80, '1st Semester', '2026-03-27 00:00:00', '2026-05-31 00:00:00', '2026-04-03 11:37:00', '2026-04-10 11:37:00', 4, '2026-03-27', 'archived', 75, 137),
(81, '2nd Semester', '2026-03-27 00:00:00', '2026-04-30 00:00:00', '2026-04-03 14:06:00', '2026-04-10 14:06:00', 4, '2026-03-27', 'archived', 76, 138),
(82, '1st Semester', '2026-03-27 00:00:00', '2026-06-30 00:00:00', '2026-03-28 07:00:00', '2026-03-28 16:00:00', 4, '2026-03-28', 'archived', 77, 141);

-- --------------------------------------------------------

--
-- Table structure for table `archived_candidates`
--

CREATE TABLE `archived_candidates` (
  `id` int(11) NOT NULL,
  `original_id` int(11) DEFAULT NULL,
  `election_name` varchar(255) DEFAULT NULL,
  `candidate_name` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `party` varchar(255) DEFAULT NULL,
  `filed_on` datetime DEFAULT NULL,
  `outcome` varchar(50) DEFAULT NULL,
  `votes_received` int(11) DEFAULT NULL,
  `archived_on` date DEFAULT NULL,
  `college` text DEFAULT NULL,
  `level` text DEFAULT NULL,
  `voting_period_id` int(11) NOT NULL,
  `external_votes` int(255) DEFAULT NULL,
  `internal_votes` int(255) DEFAULT NULL,
  `picture_path` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_candidates`
--

INSERT INTO `archived_candidates` (`id`, `original_id`, `election_name`, `candidate_name`, `position`, `party`, `filed_on`, `outcome`, `votes_received`, `archived_on`, `college`, `level`, `voting_period_id`, `external_votes`, `internal_votes`, `picture_path`) VALUES
(526, 413, '137', '[TESTER] Test [TESTER] from [TESTER] Scratch -1', 'President', 'Party A', '2026-03-27 13:27:50', 'Won', 1, '2026-03-27', '22', 'Central', 75, 1, 0, 'candidate_69c61556d606f0.91663103.jpg'),
(527, 414, '137', 'TESTER TESTER TESTER', 'Mayor', 'Party A', '2026-03-27 13:29:15', 'Won', 1, '2026-03-27', '22', 'Local', 75, 1, 0, 'candidate_69c615ab781842.81116239.jpg'),
(528, 415, '137', '[TESTER] Test [TESTER] from [TESTER] Scratch 1', 'President', 'Party B', '2026-03-27 13:29:48', 'Lost', 0, '2026-03-27', '22', 'Central', 75, 0, 0, 'candidate_69c615cc8ae5b7.35492217.jpg'),
(529, 416, '137', '[TESTER] Test [TESTER] from [TESTER] Scratch 2', 'Mayor', 'Party B', '2026-03-27 13:30:14', 'Lost', 0, '2026-03-27', '22', 'Local', 75, 0, 0, 'candidate_69c615e6887989.20950809.jpg'),
(530, 417, '138', '[TESTER] Test [TESTER] from [TESTER] Scratch -1', 'President', 'A', '2026-03-27 14:07:45', 'Won', 1, '2026-03-27', '22', 'Central', 76, 1, 0, 'candidate_69c61eb1b36fd4.37477656.jpg'),
(531, 418, '138', 'TESTER TESTER TESTER', 'Mayor', 'A', '2026-03-27 14:08:59', 'Won', 1, '2026-03-27', '22', 'Local', 76, 1, 0, 'candidate_69c61efbddc0c2.43545176.jpg'),
(532, 419, '138', '[TESTER] Test [TESTER] from [TESTER] Scratch 1', 'President', 'B', '2026-03-27 14:10:24', 'Lost', 0, '2026-03-27', '22', 'Central', 76, 0, 0, 'candidate_69c61f50c61c25.41664633.jpg'),
(533, 420, '138', '[TESTER] Test [TESTER] from [TESTER] Scratch 2', 'Mayor', 'B', '2026-03-27 14:10:35', 'Lost', 0, '2026-03-27', '22', 'Local', 76, 0, 0, 'candidate_69c61f5b1f4e56.26639346.jpg'),
(534, 421, '141', 'TESTER TESTER TESTER', 'President', 'Party A', '2026-03-28 05:22:36', 'Won', 2, '2026-03-28', '22', 'Central', 77, 2, 0, 'candidate_69c6f51ca5b1a9.41740238.png'),
(535, 422, '141', '[TESTER] Test [TESTER] from [TESTER] Scratch -1', 'President', 'Party B', '2026-03-28 05:23:26', 'Lost', 1, '2026-03-28', '22', 'Central', 77, 1, 0, 'candidate_69c6f54ef38b65.51792088.jpg'),
(536, 423, '141', '[TESTER] Test [TESTER] from [TESTER] Scratch 1', 'Mayor', 'Party A', '2026-03-28 05:33:59', 'Won', 2, '2026-03-28', '22', 'Local', 77, 2, 0, 'candidate_69c6f7c7682da3.57143375.jpg'),
(537, 424, '141', '[TESTER] Test [TESTER] from [TESTER] Scratch 2', 'Mayor', 'Party B', '2026-03-28 05:34:16', 'Lost', 1, '2026-03-28', '22', 'Local', 77, 1, 0, 'candidate_69c6f7d8ac5316.18057821.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `archived_colleges`
--

CREATE TABLE `archived_colleges` (
  `college_id` int(11) NOT NULL,
  `college_name` varchar(255) NOT NULL,
  `college_abbreviation` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `archived_colleges`
--

INSERT INTO `archived_colleges` (`college_id`, `college_name`, `college_abbreviation`, `created_at`, `archived_on`) VALUES
(19, 'College of Law', 'CL', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(20, 'College of Liberal Arts', 'CLA', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(21, 'College of Agriculture', 'CA', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(22, 'College of Computing Studies', 'CCS', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(23, 'College of Architecture', 'CArch', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(24, 'College of Nursing', 'CN', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(25, 'College of Asian & Islamic Studies', 'CAIS', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(26, 'College of Home Economics', 'CHE', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(27, 'College of Public Administration & Development Studies', 'CPADS', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(28, 'College of Engineering', 'CE', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(29, 'College of Medicine', 'CM', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(30, 'College of Criminology', 'CCrim', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(31, 'College of Sports Science & Physical Education', 'CCSPE', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(32, 'College of Science & Mathematics', 'CSM', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(33, 'College of Social Work & Community Development', 'CSWCD', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(34, 'College of Teacher Education', 'CTE', '2026-03-14 03:45:55', '2026-03-14 11:45:55');

-- --------------------------------------------------------

--
-- Table structure for table `archived_college_coordinates`
--

CREATE TABLE `archived_college_coordinates` (
  `coordinate_id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `campus_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `archived_college_coordinates`
--

INSERT INTO `archived_college_coordinates` (`coordinate_id`, `college_id`, `campus_id`, `latitude`, `longitude`, `created_at`, `archived_on`) VALUES
(11, 21, 12, 6.91130300, 122.06621900, '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(20, 21, 8, 6.91319900, 122.06222100, '2026-03-14 03:45:55', '2026-03-14 11:45:55');

-- --------------------------------------------------------

--
-- Table structure for table `archived_courses`
--

CREATE TABLE `archived_courses` (
  `id` int(10) UNSIGNED NOT NULL,
  `college_id` int(11) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `course_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `archived_courses`
--

INSERT INTO `archived_courses` (`id`, `college_id`, `course_name`, `course_code`, `created_at`, `updated_at`, `archived_on`) VALUES
(3, 19, 'Bachelor of Science in Law', 'Law', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(4, 20, 'Bachelor of Science in Accountancy', 'Accountancy', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(5, 20, 'Bachelor of Arts in History', 'History', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(6, 20, 'Bachelor of Arts in English', 'English', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(7, 20, 'Bachelor of Arts in Political Science', 'Political Science', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(8, 20, 'Bachelor of Arts in Mass Communication', 'Mass Communication', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(9, 20, 'Bachelor of Science in Economics', 'Economics', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(10, 20, 'Bachelor of Science in Psychology', 'Psychology', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(11, 21, 'Bachelor of Science in Crop Science', 'Crop Science', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(12, 21, 'Bachelor of Science in Animal Science', 'Animal Science', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(13, 21, 'Bachelor of Science in Food Technology', 'Food Technology', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(14, 21, 'Bachelor of Science in Agribusiness', 'Agribusiness', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(15, 21, 'Bachelor of Science in Agricultural Technology', 'Agricultural Technology', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(16, 21, 'Bachelor of Science in Agronomy', 'Agronomy', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(17, 22, 'Bachelor of Science in Computer Science', 'Computer Science', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(18, 22, 'Bachelor of Science in Information Technology', 'Information Technology', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(19, 22, 'Associate in Computer Technology', 'Computer Technology', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(20, 23, 'Bachelor of Science in Architecture', 'Architecture', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(21, 24, 'Bachelor of Science in Nursing', 'Nursing', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(22, 25, 'Bachelor of Science in Asian Studies', 'Asian Studies', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(23, 25, 'Bachelor of Science in Islamic Studies', 'Islamic Studies', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(24, 26, 'Bachelor of Science in Home Economics', 'Home Economics', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(25, 26, 'Bachelor of Science in Nutrition and Dietetics', 'Nutrition and Dietetics', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(26, 26, 'Bachelor of Science in Hospitality Management', 'Hospitality Management', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(27, 27, 'Bachelor of Science in Public Administration', 'Public Administration', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(28, 28, 'Bachelor of Science in Civil Engineering', 'Civil Engineering', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(29, 28, 'Bachelor of Science in Computer Engineering', 'Computer Engineering', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(30, 28, 'Bachelor of Science in Electrical Engineering', 'Electrical Engineering', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(31, 28, 'Bachelor of Science in Environmental Engineering', 'Environmental Engineering', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(32, 28, 'Bachelor of Science in Geodetic Engineering', 'Geodetic Engineering', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(33, 28, 'Bachelor of Science in Industrial Engineering', 'Industrial Engineering', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(34, 28, 'Bachelor of Science in Mechanical Engineering', 'Mechanical Engineering', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(35, 28, 'Bachelor of Science in Sanitary Engineering', 'Sanitary Engineering', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(36, 29, 'Bachelor of Science in Medicine', 'Medicine', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(37, 30, 'Bachelor of Science in Criminology', 'Criminology', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(38, 31, 'Bachelor of Science in Physical Education', 'Physical Education', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(39, 32, 'Bachelor of Science in Biology', 'Biology', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(40, 32, 'Bachelor of Science in Chemistry', 'Chemistry', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(41, 32, 'Bachelor of Science in Mathematics', 'Mathematics', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(42, 32, 'Bachelor of Science in Physics', 'Physics', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(43, 32, 'Bachelor of Science in Statistics', 'Statistics', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(44, 33, 'Bachelor of Science in Social Work', 'Social Work', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(45, 33, 'Bachelor of Science in Community Development', 'Community Development', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(46, 34, 'Bachelor of Science Culture and Arts Education', 'Culture and Arts Education', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(47, 34, 'Bachelor of Science in Early Childhood Education', 'Early Childhood Education', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(48, 34, 'Bachelor of Science in Elementary Education', 'Elementary Education', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(49, 34, 'Bachelor of Science in Secondary Education', 'Secondary Education', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55'),
(72, 25, 'Bachelor of Science in Testing', 'Testing', '2026-03-14 03:45:55', '2026-03-14 03:45:55', '2026-03-14 11:45:55');

-- --------------------------------------------------------

--
-- Table structure for table `archived_course_year_levels`
--

CREATE TABLE `archived_course_year_levels` (
  `id` int(11) NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `year_level_id` int(10) UNSIGNED NOT NULL,
  `archived_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `archived_course_year_levels`
--

INSERT INTO `archived_course_year_levels` (`id`, `course_id`, `year_level_id`, `archived_on`) VALUES
(1, 14, 1, '2026-03-14 11:45:55'),
(2, 14, 2, '2026-03-14 11:45:55'),
(3, 14, 3, '2026-03-14 11:45:55'),
(4, 14, 4, '2026-03-14 11:45:55'),
(5, 14, 5, '2026-03-14 11:45:55'),
(6, 17, 1, '2026-03-14 11:45:55'),
(7, 17, 2, '2026-03-14 11:45:55'),
(8, 17, 3, '2026-03-14 11:45:55'),
(9, 17, 4, '2026-03-14 11:45:55');

-- --------------------------------------------------------

--
-- Table structure for table `archived_departments`
--

CREATE TABLE `archived_departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `college_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `archived_departments`
--

INSERT INTO `archived_departments` (`department_id`, `department_name`, `college_id`, `created_at`, `archived_at`) VALUES
(144, 'Law', 19, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(145, 'Accountancy', 20, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(146, 'History', 20, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(147, 'English', 20, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(148, 'Political Science', 20, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(149, 'Mass Communication', 20, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(150, 'Economics', 20, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(151, 'Psychology', 20, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(152, 'Crop Science', 21, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(153, 'Animal Science', 21, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(154, 'Food Technology', 21, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(155, 'Agribusiness', 21, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(156, 'Agricultural Technology', 21, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(157, 'Agronomy', 21, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(158, 'Computer Science', 22, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(159, 'Information Technology', 22, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(160, 'Computer Technology', 22, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(161, 'Architecture', 23, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(162, 'Nursing', 24, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(163, 'Asian Studies', 25, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(164, 'Islamic Studies', 25, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(165, 'Home Economics', 26, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(166, 'Nutrition and Dietetics', 26, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(167, 'Hospitality Management', 26, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(168, 'Public Administration', 27, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(169, 'Civil Engineering', 28, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(170, 'Computer Engineering', 28, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(171, 'Electrical Engineering', 28, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(172, 'Environmental Engineering', 28, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(173, 'Geodetic Engineering', 28, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(174, 'Industrial Engineering', 28, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(175, 'Mechanical Engineering', 28, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(176, 'Sanitary Engineering', 28, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(177, 'Medicine', 29, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(178, 'Criminology', 30, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(179, 'Physical Education', 31, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(180, 'Biology', 32, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(181, 'Chemistry', 32, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(182, 'Mathematics', 32, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(183, 'Physics', 32, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(184, 'Statistics', 32, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(185, 'Social Work', 33, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(186, 'Community Development', 33, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(187, 'Culture and Arts Education', 34, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(188, 'Early Childhood Education', 34, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(189, 'Elementary Education', 34, '2026-03-14 03:45:55', '2026-03-14 03:45:55'),
(190, 'Secondary Education', 34, '2026-03-14 03:45:55', '2026-03-14 03:45:55');

-- --------------------------------------------------------

--
-- Table structure for table `archived_elections`
--

CREATE TABLE `archived_elections` (
  `id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `election_name` varchar(255) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `school_year_start` datetime DEFAULT NULL,
  `school_year_end` datetime DEFAULT NULL,
  `start_period` datetime DEFAULT NULL,
  `end_period` datetime DEFAULT NULL,
  `parties` text DEFAULT NULL,
  `turnout` varchar(50) DEFAULT NULL,
  `archived_on` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `voting_period_id` int(11) NOT NULL,
  `external_votes` int(255) DEFAULT NULL,
  `internal_votes` int(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_elections`
--

INSERT INTO `archived_elections` (`id`, `academic_year_id`, `election_name`, `semester`, `school_year_start`, `school_year_end`, `start_period`, `end_period`, `parties`, `turnout`, `archived_on`, `status`, `voting_period_id`, `external_votes`, `internal_votes`) VALUES
(137, 54, 'Test', '1st Semester', '2026-03-27 00:00:00', '2026-05-31 00:00:00', '2026-03-27 11:37:00', '2026-05-31 11:37:00', NULL, NULL, '2026-03-27', 'archived', 75, NULL, NULL),
(138, 55, 'New', '2nd Semester', '2026-03-27 00:00:00', '2026-04-30 00:00:00', '2026-03-27 14:06:00', '2026-04-30 14:06:00', NULL, NULL, '2026-03-27', 'archived', 76, NULL, NULL),
(141, 56, 'Test', '1st Semester', '2026-03-27 00:00:00', '2026-06-30 00:00:00', '2026-03-27 07:00:00', '2026-06-30 16:00:00', NULL, NULL, '2026-03-28', 'archived', 77, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `archived_majors`
--

CREATE TABLE `archived_majors` (
  `major_id` int(11) NOT NULL,
  `major_name` varchar(255) NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `archived_majors`
--

INSERT INTO `archived_majors` (`major_id`, `major_name`, `course_id`, `created_at`, `archived_on`) VALUES
(7, 'Software Engineer', 17, '2026-03-14 03:45:55', '2026-03-14 11:45:55');

-- --------------------------------------------------------

--
-- Table structure for table `archived_major_year_levels`
--

CREATE TABLE `archived_major_year_levels` (
  `id` int(11) NOT NULL,
  `major_id` int(11) NOT NULL,
  `year_level_id` int(11) NOT NULL,
  `archived_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `archived_major_year_levels`
--

INSERT INTO `archived_major_year_levels` (`id`, `major_id`, `year_level_id`, `archived_on`) VALUES
(67, 7, 1, '2026-03-14 11:45:55'),
(68, 7, 2, '2026-03-14 11:45:55'),
(69, 7, 3, '2026-03-14 11:45:55'),
(70, 7, 4, '2026-03-14 11:45:55');

-- --------------------------------------------------------

--
-- Table structure for table `archived_parties`
--

CREATE TABLE `archived_parties` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `election_id` varchar(255) NOT NULL,
  `party_image` varchar(255) DEFAULT NULL,
  `platforms` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` varchar(50) DEFAULT 'archived',
  `archived_on` date DEFAULT NULL,
  `voting_period_id` int(11) NOT NULL,
  `candidate_count` int(11) DEFAULT 0,
  `winners_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_parties`
--

INSERT INTO `archived_parties` (`id`, `name`, `election_id`, `party_image`, `platforms`, `created_at`, `updated_at`, `status`, `archived_on`, `voting_period_id`, `candidate_count`, `winners_count`) VALUES
(190, 'Party A', '137', 'party_69c5fbabdc8ae0.44215529.png', '<p>Test</p>', '2026-03-27 03:38:19', '2026-03-27 03:38:19', 'archived', '2026-03-27', 75, 0, 0),
(191, 'Party B', '137', 'party_69c5fbc205fa57.73521270.png', '<p>Test</p>', '2026-03-27 03:38:42', '2026-03-27 03:38:42', 'archived', '2026-03-27', 75, 0, 0),
(192, 'A', '138', 'party_69c61e6cc45ab1.56310947.png', '<p>hey</p>', '2026-03-27 06:06:36', '2026-03-27 06:06:36', 'archived', '2026-03-27', 76, 0, 0),
(193, 'B', '138', 'party_69c61e74314578.31956546.png', '<p>b</p>', '2026-03-27 06:06:44', '2026-03-27 06:06:44', 'archived', '2026-03-27', 76, 0, 0),
(196, 'Party A', '141', 'party_69c6f3abed5c79.02099543.png', '<p>A</p>', '2026-03-27 21:16:27', '2026-03-27 21:16:27', 'archived', '2026-03-28', 77, 0, 0),
(197, 'Party B', '141', 'party_69c6f3b7c2c8f5.72343981.png', '<p>Party Platforms</p>', '2026-03-27 21:16:39', '2026-03-27 21:16:39', 'archived', '2026-03-28', 77, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `archived_positions`
--

CREATE TABLE `archived_positions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `party` varchar(100) NOT NULL,
  `level` varchar(50) NOT NULL,
  `election_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `allowed_colleges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_colleges`)),
  `allowed_departments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_departments`)),
  `archived_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_positions`
--

INSERT INTO `archived_positions` (`id`, `name`, `party`, `level`, `election_id`, `created_at`, `allowed_colleges`, `allowed_departments`, `archived_at`) VALUES
(18, 'President', 'Party A', 'Central', 137, '2026-03-27 11:38:47', NULL, NULL, '2026-03-27 14:02:49'),
(19, 'President', 'Party B', 'Central', 137, '2026-03-27 11:38:47', NULL, NULL, '2026-03-27 14:02:49'),
(20, 'Mayor', 'Party A', 'Local', 137, '2026-03-27 11:38:58', '[{\"id\":22,\"name\":\"College of Computing Studies\",\"abbr\":\"CCS\"}]', '[{\"id\":158,\"name\":\"Computer Science\",\"college_id\":22},{\"id\":159,\"name\":\"Information Technology\",\"college_id\":22}]', '2026-03-27 14:02:49'),
(21, 'Mayor', 'Party B', 'Local', 137, '2026-03-27 11:38:58', '[{\"id\":22,\"name\":\"College of Computing Studies\",\"abbr\":\"CCS\"}]', '[{\"id\":158,\"name\":\"Computer Science\",\"college_id\":22},{\"id\":159,\"name\":\"Information Technology\",\"college_id\":22}]', '2026-03-27 14:02:49'),
(22, 'President', 'A', 'Central', 138, '2026-03-27 14:06:49', NULL, NULL, '2026-03-27 14:22:31'),
(23, 'President', 'B', 'Central', 138, '2026-03-27 14:06:49', NULL, NULL, '2026-03-27 14:22:31'),
(24, 'Mayor', 'A', 'Local', 138, '2026-03-27 14:06:56', NULL, NULL, '2026-03-27 14:22:31'),
(25, 'Mayor', 'B', 'Local', 138, '2026-03-27 14:06:56', NULL, NULL, '2026-03-27 14:22:31'),
(26, 'President', 'Party A', 'Central', 141, '2026-03-28 05:16:48', NULL, NULL, '2026-03-28 06:08:52'),
(27, 'President', 'Party B', 'Central', 141, '2026-03-28 05:16:48', NULL, NULL, '2026-03-28 06:08:52'),
(28, 'Mayor', 'Party A', 'Local', 141, '2026-03-28 05:33:00', NULL, NULL, '2026-03-28 06:08:52'),
(29, 'Mayor', 'Party B', 'Local', 141, '2026-03-28 05:33:00', NULL, NULL, '2026-03-28 06:08:52');

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
  `college` int(11) DEFAULT NULL,
  `department` int(11) DEFAULT NULL,
  `major_id` int(11) DEFAULT NULL,
  `current_capacity` int(255) NOT NULL DEFAULT 0,
  `max_capacity` int(255) NOT NULL DEFAULT 0,
  `type` int(11) DEFAULT NULL,
  `status` text NOT NULL DEFAULT 'active',
  `college_external` int(11) DEFAULT NULL,
  `election` int(11) DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_precincts`
--

INSERT INTO `archived_precincts` (`id`, `name`, `longitude`, `latitude`, `location`, `created_at`, `updated_at`, `assignment_status`, `occupied_status`, `college`, `department`, `major_id`, `current_capacity`, `max_capacity`, `type`, `status`, `college_external`, `election`, `archived_at`) VALUES
(217, '2026-2026 1st_CCS_Test_Location-1', 122, 7, 'Location', '2026-03-27 04:40:05', '2026-03-27 04:40:05', 'unassigned', 'unoccupied', 22, 158, NULL, 8, 25, 8, 'archived', NULL, 137, '2026-03-27 22:08:52'),
(218, '2026-2026 2nd_CCS_New_wmsumainbuilding-1', 122, 7, 'wmsu main building', '2026-03-27 04:40:35', '2026-03-27 04:40:35', 'unassigned', 'unoccupied', 22, 159, NULL, 1, 25, 8, 'archived', NULL, 137, '2026-03-27 06:22:31'),
(219, '2026-2026 2nd_CCS_New_ZamboangaZamboangadelSurZamboangaPeninsulaPHL-1', 122, 7, 'Zamboanga, Zamboanga del Sur, Zamboanga Peninsula, PHL', '2026-03-27 06:35:01', '2026-03-27 13:58:48', 'unassigned', 'unoccupied', 22, 158, NULL, 3, 25, 10, 'archived', 12, 137, '2026-03-27 06:22:31'),
(220, '2026-2026 2nd_CE_New_WMSUMainBuilding-1', 122, 7, 'WMSU Main Building', '2026-03-27 06:35:26', '2026-03-27 13:58:09', 'unassigned', 'unoccupied', 28, 170, NULL, 2, 25, 8, 'archived', NULL, 137, '2026-03-27 06:22:31');

-- --------------------------------------------------------

--
-- Table structure for table `archived_precinct_elections`
--

CREATE TABLE `archived_precinct_elections` (
  `id` int(11) NOT NULL,
  `precinct_id` int(11) NOT NULL,
  `precinct_name` varchar(255) NOT NULL,
  `election_name` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_at` datetime DEFAULT NULL,
  `voting_period_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_precinct_elections`
--

INSERT INTO `archived_precinct_elections` (`id`, `precinct_id`, `precinct_name`, `election_name`, `assigned_at`, `archived_at`, `voting_period_id`) VALUES
(1, 217, '137', 137, '2026-03-27 03:40:05', '2026-03-27 14:02:49', 75),
(2, 218, '137', 137, '2026-03-27 03:40:35', '2026-03-27 14:02:49', 75),
(3, 219, '137', 137, '2026-03-27 05:35:01', '2026-03-27 14:02:49', 75),
(4, 220, '137', 137, '2026-03-27 05:35:26', '2026-03-27 14:02:49', 75),
(5, 217, '138', 138, '2026-03-27 06:10:51', '2026-03-27 14:22:31', 76),
(6, 218, '138', 138, '2026-03-27 06:10:58', '2026-03-27 14:22:31', 76),
(7, 219, '138', 138, '2026-03-27 06:11:05', '2026-03-27 14:22:31', 76),
(8, 220, '138', 138, '2026-03-27 06:11:13', '2026-03-27 14:22:31', 76),
(9, 217, '141', 141, '2026-03-27 21:35:06', '2026-03-28 06:08:52', 77);

-- --------------------------------------------------------

--
-- Table structure for table `archived_precinct_voters`
--

CREATE TABLE `archived_precinct_voters` (
  `id` int(11) NOT NULL,
  `precinct` varchar(100) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cor` text DEFAULT NULL,
  `status` text NOT NULL DEFAULT 'unverified',
  `archived_at` datetime DEFAULT NULL,
  `voting_period_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_precinct_voters`
--

INSERT INTO `archived_precinct_voters` (`id`, `precinct`, `student_id`, `created_at`, `cor`, `status`, `archived_at`, `voting_period_id`) VALUES
(1, '217', '2020-01520', '2026-03-27 05:58:53', NULL, 'verified', '2026-03-27 14:02:49', 75),
(2, '217', '2020-01524', '2026-03-27 05:58:53', NULL, 'voted', '2026-03-27 14:02:49', 75),
(3, '217', '2020-01521', '2026-03-27 05:58:53', NULL, 'verified', '2026-03-27 14:02:49', 75),
(4, '217', '2020-01525', '2026-03-27 05:58:53', NULL, 'verified', '2026-03-27 14:02:49', 75),
(5, '219', '2020-01526', '2026-03-27 05:58:53', NULL, 'verified', '2026-03-27 14:02:49', 75),
(6, '217', '2021-00168', '2026-03-27 05:58:53', NULL, 'verified', '2026-03-27 14:02:49', 75),
(7, '217', '2021-01252', '2026-03-27 05:58:53', NULL, 'verified', '2026-03-27 14:02:49', 75),
(8, '217', '2021-00274', '2026-03-27 05:58:53', NULL, 'verified', '2026-03-27 14:02:49', 75),
(9, '218', '1999-12345', '2026-03-27 05:58:53', NULL, 'verified', '2026-03-27 14:02:49', 75),
(10, '219', '1998-12345', '2026-03-27 05:58:53', NULL, 'verified', '2026-03-27 14:02:49', 75),
(11, '220', '1997-12345', '2026-03-27 05:58:53', NULL, 'verified', '2026-03-27 14:02:49', 75),
(12, '220', '1999-54321', '2026-03-27 05:58:53', NULL, 'verified', '2026-03-27 14:02:49', 75),
(13, '219', '1999-12134', '2026-03-27 05:58:53', NULL, 'verified', '2026-03-27 14:02:49', 75),
(14, '217', '2020-01520', '2026-03-27 06:18:45', NULL, 'verified', '2026-03-27 14:22:31', 76),
(15, '217', '2020-01524', '2026-03-27 06:18:45', NULL, 'voted', '2026-03-27 14:22:31', 76),
(16, '217', '2020-01521', '2026-03-27 06:18:45', NULL, 'verified', '2026-03-27 14:22:31', 76),
(17, '217', '2020-01525', '2026-03-27 06:18:45', NULL, 'verified', '2026-03-27 14:22:31', 76),
(18, '219', '2020-01526', '2026-03-27 06:18:45', NULL, 'verified', '2026-03-27 14:22:31', 76),
(19, '217', '2021-00168', '2026-03-27 06:18:45', NULL, 'verified', '2026-03-27 14:22:31', 76),
(20, '217', '2021-01252', '2026-03-27 06:18:45', NULL, 'verified', '2026-03-27 14:22:31', 76),
(21, '217', '2021-00274', '2026-03-27 06:18:45', NULL, 'verified', '2026-03-27 14:22:31', 76),
(22, '218', '1999-12345', '2026-03-27 06:18:45', NULL, 'verified', '2026-03-27 14:22:31', 76),
(23, '219', '1998-12345', '2026-03-27 06:18:45', NULL, 'verified', '2026-03-27 14:22:31', 76),
(24, '220', '1997-12345', '2026-03-27 06:18:45', NULL, 'verified', '2026-03-27 14:22:31', 76),
(25, '220', '1999-54321', '2026-03-27 06:18:45', NULL, 'verified', '2026-03-27 14:22:31', 76),
(26, '219', '1999-12134', '2026-03-27 06:18:45', NULL, 'verified', '2026-03-27 14:22:31', 76),
(27, '217', '2020-01520', '2026-03-27 21:35:13', NULL, 'verified', '2026-03-28 06:08:52', 77),
(28, '217', '2020-01524', '2026-03-27 21:35:13', NULL, 'voted', '2026-03-28 06:08:52', 77),
(29, '217', '2020-01521', '2026-03-27 21:35:13', NULL, 'verified', '2026-03-28 06:08:52', 77),
(30, '217', '2020-01525', '2026-03-27 21:35:13', NULL, 'verified', '2026-03-28 06:08:52', 77);

-- --------------------------------------------------------

--
-- Table structure for table `archived_voters`
--

CREATE TABLE `archived_voters` (
  `id` int(11) NOT NULL,
  `student_id` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `course` varchar(255) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `college` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `major` text DEFAULT NULL,
  `election_name` varchar(255) DEFAULT NULL,
  `voting_period_id` int(11) DEFAULT NULL,
  `archived_on` date DEFAULT NULL,
  `status` varchar(255) DEFAULT 'archived',
  `precinct_name` varchar(100) DEFAULT NULL,
  `precinct_type` varchar(50) DEFAULT NULL,
  `has_voted` tinyint(1) DEFAULT 0,
  `wmsu_campus` text DEFAULT NULL,
  `external_campus` text DEFAULT NULL,
  `semester` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_voters`
--

INSERT INTO `archived_voters` (`id`, `student_id`, `email`, `password`, `first_name`, `middle_name`, `last_name`, `course`, `year_level`, `college`, `department`, `major`, `election_name`, `voting_period_id`, `archived_on`, `status`, `precinct_name`, `precinct_type`, `has_voted`, `wmsu_campus`, `external_campus`, `semester`) VALUES
(9, '2020-01520', 'xt202001520@wmsu.edu.ph', '$2y$10$cnnei8o1Nsgh.rA0fSk8huYx88iv6d3naA.sAeNp.GYmAe0Vw2T4W', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch -1', '17', '29', '22', '158', NULL, '137', 75, '2026-03-27', 'archived', '2026-2026 1st_CCS_Test_Location-1', '8', 0, '8', NULL, NULL),
(10, '2020-01524', 'xt202001524@wmsu.edu.ph', '$2y$10$2ToO9mK.2b5bg36rQsK/7OCrd7B8FrS7UiaFVPErx8cLnh94.r6Xy', 'TESTER', 'TESTER', 'TESTER', '17', '24', '22', '158', NULL, '137', 75, '2026-03-27', 'archived', '2026-2026 1st_CCS_Test_Location-1', '8', 1, '8', NULL, NULL),
(11, '2020-01521', 'xt202001521@wmsu.edu.ph', '$2y$10$xqrinSjw7pQGmRkzZPBwCORs9uG8Trubkpdi.s8C6kWQYFYtTOjue', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch 1', '17', '29', '22', '158', NULL, '137', 75, '2026-03-27', 'archived', '2026-2026 1st_CCS_Test_Location-1', '8', 0, '8', NULL, NULL),
(12, '2020-01525', 'xt202001525@wmsu.edu.ph', '$2y$10$xqrinSjw7pQGmRkzZPBwCORs9uG8Trubkpdi.s8C6kWQYFYtTOjue', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch 2', '17', '29', '22', '158', NULL, '137', 75, '2026-03-27', 'archived', '2026-2026 1st_CCS_Test_Location-1', '8', 0, '8', NULL, NULL),
(13, '2020-01526', 'xt202001526@wmsu.edu.ph', '$2y$10$xqrinSjw7pQGmRkzZPBwCORs9uG8Trubkpdi.s8C6kWQYFYtTOjue', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch 2', '17', '29', '22', '158', NULL, '137', 75, '2026-03-27', 'archived', '2026-2026 1st_CCS_Test_ZamboangaZamboangadelSurZamboangaPeninsulaPHL-1', '10', 0, '10', '12', NULL),
(14, '2021-00168', 'qb202100168@wmsu.edu.ph', '$2y$10$U8gzv2cokyqEpmrLQKJsXO9MAznMR7gptoGYsdc1dkn/v5lLsKLAO', 'Antonette', 'Suela', 'Manolis', '17', '26', '22', '158', NULL, '137', 75, '2026-03-27', 'archived', '2026-2026 1st_CCS_Test_Location-1', '8', 0, '8', NULL, NULL),
(15, '2021-01252', 'qb202101252@wmsu.edu.ph', '$2y$10$4nYwpGvnGTDoerbzy39YZ.oziFCR352G1gqzjaZqknSlKQBSJmWfq', 'Nur', 'Benito', 'Balla', '17', '26', '22', '158', NULL, '137', 75, '2026-03-27', 'archived', '2026-2026 1st_CCS_Test_Location-1', '8', 0, '8', NULL, NULL),
(16, '2021-00274', 'qb202100274@wmsu.edu.ph', '$2y$10$kcOowLWY/Wg3BdY9oLLz5OR16I9MDfImme.VjECQ.w3GNjC/0kf3e', 'Irene', NULL, 'Aquino', '17', '26', '22', '158', NULL, '137', 75, '2026-03-27', 'archived', '2026-2026 1st_CCS_Test_Location-1', '8', 0, '8', NULL, NULL),
(17, '1999-12345', 'xt199912345@wmsu.edu.ph', '$2y$10$1Un2sStY.aPJWqzVc52TouKX5lrkLTJKUR2wxiKfKJTpSGPfBP1si', '[TESTER] Test', '[TESTER] from', '[TESTER] IT', '18', '44', '22', '159', NULL, '137', 75, '2026-03-27', 'archived', '2026-2026 1st_CCS_Test_NA-1', '8', 0, '8', NULL, NULL),
(18, '1998-12345', 'xt199812345@wmsu.edu.ph', '$2y$10$xqrinSjw7pQGmRkzZPBwCORs9uG8Trubkpdi.s8C6kWQYFYtTOjue', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch Somewhere', '17', '29', '22', '158', NULL, '137', 75, '2026-03-27', 'archived', '2026-2026 1st_CCS_Test_ZamboangaZamboangadelSurZamboangaPeninsulaPHL-1', '10', 0, '10', '12', NULL),
(19, '1997-12345', 'ce199712345@wmsu.edu.ph', '$2y$10$HEqtFJLYGOrbrvk7BKYd8.meGGYtHL.wTFkVJWqTU71RVG0uEa7Qu', '[TESTER] Test from another Scratch', '[TESTER] Engineering', '[TESTER] Computer Engineering', '29', '39', '28', '170', NULL, '137', 75, '2026-03-27', 'archived', '2026-2026 1st_CE_Test_WMSUMainBuilding-1', '8', 0, '8', NULL, NULL),
(20, '1999-54321', 'ce199954321@wmsu.edu.ph', '$2y$10$.TzEgR./EEigYG4PnrIsxey4swr7lalpMcfOD3QqnrEGUfrLXxwHi', '[TESTER] Tester from another scratch', '[TESTER] from Engineering', '[TESTER] Computer Engineering as again', '29', '39', '28', '170', NULL, '137', 75, '2026-03-27', 'archived', '2026-2026 1st_CE_Test_WMSUMainBuilding-1', '8', 0, '8', NULL, NULL),
(21, '1999-12134', 'xt199912134@wmsu.edu.ph', '$2y$10$FNQ/ql9v1t19tzvMTyroIusPbovjyUrjbh53zS1CAO9zWoboG/dj.', '[TESTER] Test from CCS', '[TESTER] Tester from Com-SCI', '[TESTER] Tester from Software Engineering', '17', '29', '22', '158', '7', '137', 75, '2026-03-27', 'archived', '2026-2026 1st_CCS_Test_ZamboangaZamboangadelSurZamboangaPeninsulaPHL-1', '10', 0, '10', '12', NULL),
(24370, '2020-01520', 'xt202001520@wmsu.edu.ph', '$2y$10$cnnei8o1Nsgh.rA0fSk8huYx88iv6d3naA.sAeNp.GYmAe0Vw2T4W', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch -1', '17', '29', '22', '158', NULL, '138', 76, '2026-03-27', 'archived', '2026-2026 2nd_CCS_New_Location-1', '8', 0, '8', NULL, NULL),
(24371, '2020-01524', 'xt202001524@wmsu.edu.ph', '$2y$10$2ToO9mK.2b5bg36rQsK/7OCrd7B8FrS7UiaFVPErx8cLnh94.r6Xy', 'TESTER', 'TESTER', 'TESTER', '17', '24', '22', '158', NULL, '138', 76, '2026-03-27', 'archived', '2026-2026 2nd_CCS_New_Location-1', '8', 1, '8', NULL, NULL),
(24372, '2020-01521', 'xt202001521@wmsu.edu.ph', '$2y$10$xqrinSjw7pQGmRkzZPBwCORs9uG8Trubkpdi.s8C6kWQYFYtTOjue', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch 1', '17', '29', '22', '158', NULL, '138', 76, '2026-03-27', 'archived', '2026-2026 2nd_CCS_New_Location-1', '8', 0, '8', NULL, NULL),
(24373, '2020-01525', 'xt202001525@wmsu.edu.ph', '$2y$10$xqrinSjw7pQGmRkzZPBwCORs9uG8Trubkpdi.s8C6kWQYFYtTOjue', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch 2', '17', '29', '22', '158', NULL, '138', 76, '2026-03-27', 'archived', '2026-2026 2nd_CCS_New_Location-1', '8', 0, '8', NULL, NULL),
(24374, '2021-00168', 'qb202100168@wmsu.edu.ph', '$2y$10$U8gzv2cokyqEpmrLQKJsXO9MAznMR7gptoGYsdc1dkn/v5lLsKLAO', 'Antonette', 'Suela', 'Manolis', '17', '26', '22', '158', NULL, '138', 76, '2026-03-27', 'archived', '2026-2026 2nd_CCS_New_Location-1', '8', 0, '8', NULL, NULL),
(24375, '2021-01252', 'qb202101252@wmsu.edu.ph', '$2y$10$4nYwpGvnGTDoerbzy39YZ.oziFCR352G1gqzjaZqknSlKQBSJmWfq', 'Nur', 'Benito', 'Balla', '17', '26', '22', '158', NULL, '138', 76, '2026-03-27', 'archived', '2026-2026 2nd_CCS_New_Location-1', '8', 0, '8', NULL, NULL),
(24376, '2021-00274', 'qb202100274@wmsu.edu.ph', '$2y$10$kcOowLWY/Wg3BdY9oLLz5OR16I9MDfImme.VjECQ.w3GNjC/0kf3e', 'Irene', NULL, 'Aquino', '17', '26', '22', '158', NULL, '138', 76, '2026-03-27', 'archived', '2026-2026 2nd_CCS_New_Location-1', '8', 0, '8', NULL, NULL),
(24377, '1999-12345', 'xt199912345@wmsu.edu.ph', '$2y$10$1Un2sStY.aPJWqzVc52TouKX5lrkLTJKUR2wxiKfKJTpSGPfBP1si', '[TESTER] Test', '[TESTER] from', '[TESTER] IT', '18', '44', '22', '159', NULL, '138', 76, '2026-03-27', 'archived', '2026-2026 2nd_CCS_New_wmsumainbuilding-1', '8', 0, '8', NULL, NULL),
(24378, '2020-01526', 'xt202001526@wmsu.edu.ph', '$2y$10$xqrinSjw7pQGmRkzZPBwCORs9uG8Trubkpdi.s8C6kWQYFYtTOjue', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch 2', '17', '29', '22', '158', NULL, '138', 76, '2026-03-27', 'archived', '2026-2026 2nd_CCS_New_ZamboangaZamboangadelSurZamboangaPeninsulaPHL-1', '10', 0, '10', '12', NULL),
(24379, '1998-12345', 'xt199812345@wmsu.edu.ph', '$2y$10$xqrinSjw7pQGmRkzZPBwCORs9uG8Trubkpdi.s8C6kWQYFYtTOjue', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch Somewhere', '17', '29', '22', '158', NULL, '138', 76, '2026-03-27', 'archived', '2026-2026 2nd_CCS_New_ZamboangaZamboangadelSurZamboangaPeninsulaPHL-1', '10', 0, '10', '12', NULL),
(24380, '1999-12134', 'xt199912134@wmsu.edu.ph', '$2y$10$FNQ/ql9v1t19tzvMTyroIusPbovjyUrjbh53zS1CAO9zWoboG/dj.', '[TESTER] Test from CCS', '[TESTER] Tester from Com-SCI', '[TESTER] Tester from Software Engineering', '17', '29', '22', '158', '7', '138', 76, '2026-03-27', 'archived', '2026-2026 2nd_CCS_New_ZamboangaZamboangadelSurZamboangaPeninsulaPHL-1', '10', 0, '10', '12', NULL),
(24381, '1997-12345', 'ce199712345@wmsu.edu.ph', '$2y$10$HEqtFJLYGOrbrvk7BKYd8.meGGYtHL.wTFkVJWqTU71RVG0uEa7Qu', '[TESTER] Test from another Scratch', '[TESTER] Engineering', '[TESTER] Computer Engineering', '29', '39', '28', '170', NULL, '138', 76, '2026-03-27', 'archived', '2026-2026 2nd_CE_New_WMSUMainBuilding-1', '8', 0, '8', NULL, NULL),
(24382, '1999-54321', 'ce199954321@wmsu.edu.ph', '$2y$10$.TzEgR./EEigYG4PnrIsxey4swr7lalpMcfOD3QqnrEGUfrLXxwHi', '[TESTER] Tester from another scratch', '[TESTER] from Engineering', '[TESTER] Computer Engineering as again', '29', '39', '28', '170', NULL, '138', 76, '2026-03-27', 'archived', '2026-2026 2nd_CE_New_WMSUMainBuilding-1', '8', 0, '8', NULL, NULL),
(24383, '2020-01520', 'xt202001520@wmsu.edu.ph', '$2y$10$cnnei8o1Nsgh.rA0fSk8huYx88iv6d3naA.sAeNp.GYmAe0Vw2T4W', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch -1', '17', '29', '22', '158', NULL, '141', 77, '2026-03-28', 'archived', '2026-2026 1st_CCS_Test_Location-1', '8', 0, '8', NULL, NULL),
(24384, '2020-01524', 'xt202001524@wmsu.edu.ph', '$2y$10$2ToO9mK.2b5bg36rQsK/7OCrd7B8FrS7UiaFVPErx8cLnh94.r6Xy', 'TESTER', 'TESTER', 'TESTER', '17', '24', '22', '158', NULL, '141', 77, '2026-03-28', 'archived', '2026-2026 1st_CCS_Test_Location-1', '8', 1, '8', NULL, NULL),
(24385, '2020-01521', 'xt202001521@wmsu.edu.ph', '$2y$10$xqrinSjw7pQGmRkzZPBwCORs9uG8Trubkpdi.s8C6kWQYFYtTOjue', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch 1', '17', '29', '22', '158', NULL, '141', 77, '2026-03-28', 'archived', '2026-2026 1st_CCS_Test_Location-1', '8', 0, '8', NULL, NULL),
(24386, '2020-01525', 'xt202001525@wmsu.edu.ph', '$2y$10$xqrinSjw7pQGmRkzZPBwCORs9uG8Trubkpdi.s8C6kWQYFYtTOjue', '[TESTER] Test', '[TESTER] from', '[TESTER] Scratch 2', '17', '29', '22', '158', NULL, '141', 77, '2026-03-28', 'archived', '2026-2026 1st_CCS_Test_Location-1', '8', 0, '8', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `archived_voters_columns`
--

CREATE TABLE `archived_voters_columns` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `number` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archived_voters_custom_fields`
--

CREATE TABLE `archived_voters_custom_fields` (
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
  `options` varchar(2555) NOT NULL,
  `archived_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archived_voters_custom_files`
--

CREATE TABLE `archived_voters_custom_files` (
  `id` int(11) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archived_voters_custom_responses`
--

CREATE TABLE `archived_voters_custom_responses` (
  `id` int(11) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archived_votes`
--

CREATE TABLE `archived_votes` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `student_id` varchar(100) NOT NULL,
  `voting_period_id` int(11) NOT NULL,
  `precinct` int(11) DEFAULT NULL,
  `archived_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_votes`
--

INSERT INTO `archived_votes` (`id`, `candidate_id`, `student_id`, `voting_period_id`, `precinct`, `archived_on`) VALUES
(4, 413, '2020-01524', 75, 217, '2026-03-27 14:02:49'),
(5, 414, '2020-01524', 75, 217, '2026-03-27 14:02:49'),
(6, 417, '2020-01524', 76, 217, '2026-03-27 14:22:31'),
(7, 418, '2020-01524', 76, 217, '2026-03-27 14:22:31'),
(8, 421, '2020-01524', 77, 217, '2026-03-28 06:08:52'),
(9, 423, '2020-01524', 77, 217, '2026-03-28 06:08:52'),
(10, 422, '2020-01520', 77, 217, '2026-03-28 06:08:52'),
(11, 424, '2020-01520', 77, 217, '2026-03-28 06:08:52'),
(12, 421, '2020-01524', 77, 217, '2026-03-28 06:08:52'),
(13, 423, '2020-01524', 77, 217, '2026-03-28 06:08:52');

-- --------------------------------------------------------

--
-- Table structure for table `archived_voting_periods`
--

CREATE TABLE `archived_voting_periods` (
  `id` int(11) NOT NULL,
  `election_id` int(11) NOT NULL,
  `start_period` datetime NOT NULL,
  `end_period` datetime NOT NULL,
  `re_start_period` datetime DEFAULT NULL,
  `re_end_period` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `archived_voting_periods`
--

INSERT INTO `archived_voting_periods` (`id`, `election_id`, `start_period`, `end_period`, `re_start_period`, `re_end_period`, `status`, `created_at`) VALUES
(75, 137, '2026-03-27 07:00:00', '2026-03-28 16:40:00', NULL, NULL, 'archived', '2026-03-27 06:02:49'),
(76, 138, '2026-03-27 07:00:00', '2026-03-28 16:00:00', NULL, NULL, 'archived', '2026-03-27 06:22:31'),
(77, 141, '2026-03-28 05:53:00', '2026-03-28 16:09:00', NULL, NULL, 'archived', '2026-03-27 22:08:52');

-- --------------------------------------------------------

--
-- Table structure for table `archived_year_levels`
--

CREATE TABLE `archived_year_levels` (
  `id` int(10) UNSIGNED NOT NULL,
  `level` int(11) NOT NULL,
  `description` varchar(50) DEFAULT NULL,
  `archived_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `archived_year_levels`
--

INSERT INTO `archived_year_levels` (`id`, `level`, `description`, `archived_on`) VALUES
(1, 1, 'First Year', '2026-03-14 11:45:55'),
(2, 2, 'Second Year', '2026-03-14 11:45:55'),
(3, 3, 'Third Year', '2026-03-14 11:45:55'),
(4, 4, 'Fourth Year', '2026-03-14 11:45:55'),
(5, 5, 'Fifth Year', '2026-03-14 11:45:55');

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
  `needs_update` tinyint(4) DEFAULT 0,
  `archived_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `archived_academic_years`
--
ALTER TABLE `archived_academic_years`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archived_actual_year_levels`
--
ALTER TABLE `archived_actual_year_levels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_course_id` (`course_id`),
  ADD KEY `fk_major_id` (`major_id`);

--
-- Indexes for table `archived_campuses`
--
ALTER TABLE `archived_campuses`
  ADD PRIMARY KEY (`campus_id`),
  ADD UNIQUE KEY `campus_name` (`campus_name`),
  ADD KEY `fk_parent_campus` (`parent_id`);

--
-- Indexes for table `archived_candidacies`
--
ALTER TABLE `archived_candidacies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_election` (`election_id`);

--
-- Indexes for table `archived_candidates`
--
ALTER TABLE `archived_candidates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archived_colleges`
--
ALTER TABLE `archived_colleges`
  ADD PRIMARY KEY (`college_id`);

--
-- Indexes for table `archived_college_coordinates`
--
ALTER TABLE `archived_college_coordinates`
  ADD PRIMARY KEY (`coordinate_id`),
  ADD UNIQUE KEY `unique_college_campus` (`college_id`,`campus_id`),
  ADD KEY `fk_college_coordinates_campus` (`campus_id`);

--
-- Indexes for table `archived_courses`
--
ALTER TABLE `archived_courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_courses_college` (`college_id`);

--
-- Indexes for table `archived_course_year_levels`
--
ALTER TABLE `archived_course_year_levels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_year_level` (`year_level_id`),
  ADD KEY `course_id` (`course_id`,`year_level_id`) USING BTREE;

--
-- Indexes for table `archived_departments`
--
ALTER TABLE `archived_departments`
  ADD PRIMARY KEY (`department_id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `archived_elections`
--
ALTER TABLE `archived_elections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archived_majors`
--
ALTER TABLE `archived_majors`
  ADD PRIMARY KEY (`major_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `archived_major_year_levels`
--
ALTER TABLE `archived_major_year_levels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archived_parties`
--
ALTER TABLE `archived_parties`
  ADD PRIMARY KEY (`id`,`voting_period_id`),
  ADD KEY `idx_election_name` (`election_id`),
  ADD KEY `idx_voting_period_id` (`voting_period_id`);

--
-- Indexes for table `archived_positions`
--
ALTER TABLE `archived_positions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_position_per_election` (`name`,`party`,`level`,`election_id`),
  ADD KEY `election_id` (`election_id`);

--
-- Indexes for table `archived_precincts`
--
ALTER TABLE `archived_precincts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_precinct_college` (`college`),
  ADD KEY `fk_precinct_department` (`department`),
  ADD KEY `fk_precinct_campus` (`type`),
  ADD KEY `fk_precinct_external_campus` (`college_external`),
  ADD KEY `fk_precinct_election` (`election`),
  ADD KEY `fk_precinct_major` (`major_id`);

--
-- Indexes for table `archived_precinct_elections`
--
ALTER TABLE `archived_precinct_elections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archived_precinct_voters`
--
ALTER TABLE `archived_precinct_voters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archived_voters`
--
ALTER TABLE `archived_voters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `election_name` (`election_name`),
  ADD KEY `voting_period_id` (`voting_period_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `archived_voters_columns`
--
ALTER TABLE `archived_voters_columns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_voter_columns_academic_year` (`academic_year_id`);

--
-- Indexes for table `archived_voters_custom_fields`
--
ALTER TABLE `archived_voters_custom_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `academic_year_id` (`academic_year_id`);

--
-- Indexes for table `archived_voters_custom_files`
--
ALTER TABLE `archived_voters_custom_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voter_id` (`voter_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `archived_voters_custom_responses`
--
ALTER TABLE `archived_voters_custom_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voter_id` (`voter_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `archived_votes`
--
ALTER TABLE `archived_votes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidate_id` (`candidate_id`),
  ADD KEY `voting_period_id` (`voting_period_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `archived_voting_periods`
--
ALTER TABLE `archived_voting_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_voting_election` (`election_id`);

--
-- Indexes for table `archived_year_levels`
--
ALTER TABLE `archived_year_levels`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `archived_actual_year_levels`
--
ALTER TABLE `archived_actual_year_levels`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `archived_campuses`
--
ALTER TABLE `archived_campuses`
  MODIFY `campus_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `archived_candidates`
--
ALTER TABLE `archived_candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=538;

--
-- AUTO_INCREMENT for table `archived_colleges`
--
ALTER TABLE `archived_colleges`
  MODIFY `college_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `archived_college_coordinates`
--
ALTER TABLE `archived_college_coordinates`
  MODIFY `coordinate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `archived_courses`
--
ALTER TABLE `archived_courses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `archived_course_year_levels`
--
ALTER TABLE `archived_course_year_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `archived_departments`
--
ALTER TABLE `archived_departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

--
-- AUTO_INCREMENT for table `archived_majors`
--
ALTER TABLE `archived_majors`
  MODIFY `major_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `archived_major_year_levels`
--
ALTER TABLE `archived_major_year_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `archived_positions`
--
ALTER TABLE `archived_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `archived_precincts`
--
ALTER TABLE `archived_precincts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=221;

--
-- AUTO_INCREMENT for table `archived_precinct_elections`
--
ALTER TABLE `archived_precinct_elections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `archived_precinct_voters`
--
ALTER TABLE `archived_precinct_voters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `archived_voters`
--
ALTER TABLE `archived_voters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24387;

--
-- AUTO_INCREMENT for table `archived_voters_columns`
--
ALTER TABLE `archived_voters_columns`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `archived_voters_custom_fields`
--
ALTER TABLE `archived_voters_custom_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1702;

--
-- AUTO_INCREMENT for table `archived_voters_custom_files`
--
ALTER TABLE `archived_voters_custom_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `archived_voters_custom_responses`
--
ALTER TABLE `archived_voters_custom_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `archived_votes`
--
ALTER TABLE `archived_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `archived_year_levels`
--
ALTER TABLE `archived_year_levels`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `voters`
--
ALTER TABLE `voters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `archived_candidacies`
--
ALTER TABLE `archived_candidacies`
  ADD CONSTRAINT `fk_election` FOREIGN KEY (`election_id`) REFERENCES `archived_elections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `archived_voting_periods`
--
ALTER TABLE `archived_voting_periods`
  ADD CONSTRAINT `fk_voting_election` FOREIGN KEY (`election_id`) REFERENCES `archived_elections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
