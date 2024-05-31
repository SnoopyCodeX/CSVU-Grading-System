-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 31, 2024 at 06:38 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `grading-sys`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `subject` int NOT NULL,
  `school_year` int NOT NULL,
  `term` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `year_level` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `course` int NOT NULL,
  `passing_rate` double NOT NULL,
  `max_score` int NOT NULL,
  `instructor` int NOT NULL,
  `type` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `name`, `subject`, `school_year`, `term`, `year_level`, `course`, `passing_rate`, `max_score`, `instructor`, `type`) VALUES
(93, 'Activity1', 46, 9, '1st sem', '1st Year', 4, 0.5, 50, 145, 35),
(95, 'Project1', 46, 9, '1st sem', '1st Year', 4, 0.5, 80, 145, 37),
(96, 'Finalexam', 46, 9, '1st sem', '1st Year', 4, 0.75, 100, 145, 38),
(97, 'Activity1', 47, 9, '1st sem', '1st Year', 4, 0.5, 50, 145, 35),
(99, 'Project1', 47, 9, '1st sem', '1st Year', 4, 0.75, 80, 145, 37),
(100, 'Finalexam', 47, 9, '1st sem', '1st Year', 4, 0.75, 100, 145, 38),
(101, 'Activity1', 48, 9, '1st sem', '1st Year', 4, 0.5, 50, 145, 35),
(103, 'Project1', 48, 9, '1st sem', '1st Year', 4, 0.75, 50, 145, 37),
(104, 'Finalexam', 48, 9, '1st sem', '1st Year', 4, 0.75, 100, 145, 38),
(105, 'Activity1', 49, 9, '1st sem', '1st Year', 4, 0.5, 30, 145, 35),
(107, 'Project1', 49, 9, '1st sem', '1st Year', 4, 0.75, 80, 145, 37),
(108, 'Finalexam1', 49, 9, '1st sem', '1st Year', 4, 0.75, 100, 145, 38),
(109, 'Activity1', 50, 9, '1st sem', '1st Year', 4, 0.5, 50, 145, 35),
(111, 'Finalexam', 50, 9, '1st sem', '1st Year', 4, 0.75, 100, 145, 38),
(112, 'Activity1', 51, 9, '1st sem', '1st Year', 4, 0.5, 50, 145, 35),
(114, 'Project1', 51, 9, '1st sem', '1st Year', 4, 0.5, 80, 145, 37),
(115, 'Final exam', 51, 9, '1st sem', '1st Year', 4, 0.75, 100, 145, 38),
(116, 'Activity1', 52, 9, '1st sem', '1st Year', 4, 0.5, 50, 145, 35),
(118, 'Project1', 52, 9, '1st sem', '1st Year', 4, 0.5, 75, 145, 37),
(119, 'Finalexam', 52, 9, '1st sem', '1st Year', 4, 0.2, 50, 145, 38),
(120, 'Activity1', 53, 9, '1st sem', '1st Year', 4, 0.5, 50, 145, 35),
(122, 'Project1', 53, 9, '1st sem', '1st Year', 4, 0.5, 50, 145, 37),
(123, 'Finalexam', 53, 9, '1st sem', '1st Year', 4, 0.5, 50, 145, 38),
(124, 'Activity1', 54, 9, '1st sem', '1st Year', 4, 0.5, 50, 145, 35),
(127, 'Project1', 54, 9, '1st sem', '1st Year', 4, 0.5, 50, 145, 37),
(128, 'Finalexam', 54, 9, '1st sem', '1st Year', 4, 0.5, 50, 145, 38),
(129, 'Project1', 50, 9, '1st sem', '1st Year', 4, 0.5, 50, 145, 37),
(130, 'Activity1', 72, 9, '1st sem', '4th Year', 4, 0.5, 50, 147, 39),
(131, 'Finalexam1', 72, 9, '1st sem', '4th Year', 4, 0.5, 50, 147, 40),
(132, 'Activity1', 73, 9, '1st sem', '4th Year', 4, 0.75, 100, 147, 39),
(133, 'Finalexam1', 73, 9, '1st sem', '4th Year', 4, 0.75, 100, 147, 40),
(134, 'Activity1', 63, 9, '1st sem', '4th Year', 4, 0.5, 50, 147, 39),
(135, 'Finalexam', 63, 9, '1st sem', '4th Year', 4, 0.75, 100, 147, 40),
(136, 'Activity1', 64, 9, '1st sem', '4th Year', 4, 0.5, 50, 147, 39),
(137, 'Finalexam', 64, 9, '1st sem', '4th Year', 4, 0.75, 100, 147, 40),
(138, 'Activity1', 65, 9, '1st sem', '4th Year', 4, 0.5, 50, 147, 39),
(139, 'Finalexam', 65, 9, '1st sem', '4th Year', 4, 0.75, 100, 147, 40),
(140, 'Activity 1', 37, 9, '1st sem', '1st Year', 7, 0.5, 5, 144, 41),
(141, 'Final Exam', 37, 9, '1st sem', '1st Year', 7, 0.75, 10, 144, 42),
(142, 'Activity 1', 38, 9, '1st sem', '1st Year', 7, 0.5, 5, 144, 41),
(143, 'Final Exam', 38, 9, '1st sem', '1st Year', 7, 0.75, 10, 144, 42),
(144, 'Activity1', 74, 9, '1st sem', '4th Year', 14, 0.5, 50, 146, 43),
(146, 'Final exam', 74, 9, '1st sem', '4th Year', 14, 0.75, 100, 146, 45),
(147, 'Activity1', 75, 9, '1st sem', '4th Year', 14, 0.5, 50, 146, 43),
(149, 'Final exam', 75, 9, '1st sem', '4th Year', 14, 0.75, 50, 146, 45),
(150, 'Activity1', 77, 9, '1st sem', '4th Year', 15, 0.5, 50, 146, 43),
(151, 'Recitation1', 77, 9, '1st sem', '4th Year', 15, 0.5, 20, 146, 46),
(153, 'Finalexam', 77, 9, '1st sem', '4th Year', 15, 0.75, 100, 146, 47),
(154, 'Activity1', 76, 9, '1st sem', '4th Year', 15, 0.5, 50, 146, 43),
(156, 'Recitation1', 76, 9, '1st sem', '4th Year', 15, 0.75, 10, 146, 46),
(157, 'Final exam', 76, 9, '1st sem', '4th Year', 15, 0.75, 100, 146, 47),
(160, '1st activity', 56, 9, '1st sem', '2nd Year', 4, 0.75, 100, 146, 43),
(161, 'test', 56, 9, '1st sem', '2nd Year', 4, 0.8, 100, 146, 47),
(162, 'Recitation 1', 56, 9, '1st sem', '2nd Year', 4, 0.5, 40, 146, 46),
(164, 'Recitation1', 46, 9, '1st sem', '1st Year', 4, 0.1, 10, 145, 49);

-- --------------------------------------------------------

--
-- Table structure for table `activity_scores`
--

CREATE TABLE `activity_scores` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `activity_id` int NOT NULL,
  `instructor_id` int NOT NULL,
  `score` decimal(10,0) NOT NULL,
  `term` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `year_level` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_scores`
--

INSERT INTO `activity_scores` (`id`, `student_id`, `activity_id`, `instructor_id`, `score`, `term`, `year_level`) VALUES
(136, 168, 150, 146, '45', '1st sem', '4th Year'),
(137, 169, 150, 146, '30', '1st sem', '4th Year'),
(138, 168, 151, 146, '20', '1st sem', '4th Year'),
(139, 169, 151, 146, '15', '1st sem', '4th Year'),
(142, 168, 153, 146, '79', '1st sem', '4th Year'),
(143, 169, 153, 146, '84', '1st sem', '4th Year'),
(144, 168, 154, 146, '25', '1st sem', '4th Year'),
(145, 169, 154, 146, '50', '1st sem', '4th Year'),
(148, 168, 156, 146, '10', '1st sem', '4th Year'),
(149, 169, 156, 146, '10', '1st sem', '4th Year'),
(150, 168, 157, 146, '100', '1st sem', '4th Year'),
(151, 169, 157, 146, '90', '1st sem', '4th Year'),
(152, 163, 160, 146, '80', '1st sem', '2nd Year'),
(153, 163, 161, 146, '87', '1st sem', '2nd Year'),
(154, 163, 162, 146, '40', '1st sem', '2nd Year'),
(156, 153, 93, 145, '50', '1st sem', '1st Year'),
(157, 154, 93, 145, '50', '1st sem', '1st Year'),
(160, 153, 95, 145, '80', '1st sem', '1st Year'),
(161, 154, 95, 145, '80', '1st sem', '1st Year'),
(162, 153, 96, 145, '100', '1st sem', '1st Year'),
(163, 154, 96, 145, '100', '1st sem', '1st Year');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int NOT NULL,
  `course` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `course_code` varchar(12) COLLATE utf8mb4_general_ci NOT NULL,
  `adviser` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course`, `course_code`, `adviser`) VALUES
(1, 'BS Secondary Education Major in Math', 'BSED-Math', 147),
(2, 'BS Secondary Education Major in English', 'BSED-English', 143),
(3, 'BS Secondary Education Major in Science', 'BSED-Science', 148),
(4, 'BS Information Technology', 'BSIT', 149),
(5, 'BS Business Administration', 'BSBA', 148),
(6, 'BS Office Administration', 'BSOA', 144),
(7, 'BS Psychology', 'BSP', 145),
(9, 'BS Tourism', 'BSTM', 145),
(15, 'BS Hospitality Management', 'BSHM', 146);

-- --------------------------------------------------------

--
-- Table structure for table `grade_requests`
--

CREATE TABLE `grade_requests` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `section_id` int NOT NULL,
  `term` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade_requests`
--

INSERT INTO `grade_requests` (`id`, `student_id`, `section_id`, `term`, `status`) VALUES
(23, 168, 216, '1st Sem', 'approved'),
(24, 169, 216, '1st Sem', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `grading_criterias`
--

CREATE TABLE `grading_criterias` (
  `id` int NOT NULL,
  `criteria_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `percentage` double NOT NULL,
  `instructor` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grading_criterias`
--

INSERT INTO `grading_criterias` (`id`, `criteria_name`, `percentage`, `instructor`) VALUES
(35, 'Activity', 0.2, 145),
(37, 'Project', 0.3, 145),
(38, 'Final exam', 0.4, 145),
(39, 'Activity', 0.5, 147),
(40, 'Final Exam', 0.5, 147),
(41, 'Projects', 0.3, 144),
(42, 'Final Exam', 0.7, 144),
(43, 'Activity', 0.2, 146),
(46, 'Recitations', 0.4, 146),
(47, 'Final exam', 0.4, 146),
(49, 'recitation', 0.1, 145);

-- --------------------------------------------------------

--
-- Table structure for table `instructor_change_grade_request`
--

CREATE TABLE `instructor_change_grade_request` (
  `id` int NOT NULL,
  `instructor_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `school_year` int NOT NULL,
  `pdf_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `token` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected','grade-changed') NOT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instructor_grade_release_requests`
--

CREATE TABLE `instructor_grade_release_requests` (
  `id` int NOT NULL,
  `instructor_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `grade_sheet_file` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `file_uid` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `school_year` int NOT NULL,
  `term` varchar(7) COLLATE utf8mb3_unicode_ci NOT NULL,
  `status` enum('approved','pending','rejected','grade-released') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `instructor_grade_release_requests`
--

INSERT INTO `instructor_grade_release_requests` (`id`, `instructor_id`, `subject_id`, `grade_sheet_file`, `file_uid`, `school_year`, `term`, `status`, `created_at`, `updated_at`) VALUES
(31, 146, 77, 'Instructor Grade Sheet 2024-04-26-08-02-36.pdf', 'd00a9fc9b0315bc010e9ed6776613a5e', 9, '1st Sem', 'grade-released', '2024-04-26 12:02:36', '2024-05-03 07:16:25'),
(32, 146, 76, 'Instructor Grade Sheet 2024-04-26-08-02-58.pdf', '812596aa9a0290e35da2f07063563037', 9, '1st Sem', 'grade-released', '2024-04-26 12:02:58', '2024-05-03 07:16:29'),
(33, 145, 46, 'Instructor Grade Sheet 2024-05-31-01-30-02.pdf', 'a3f93636c6c7076f3fbcc9fa3a08f185', 9, '1st Sem', 'grade-released', '2024-05-31 13:30:02', '2024-05-31 13:31:14');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(12) COLLATE utf8mb4_general_ci NOT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `email`, `token`, `status`, `createdAt`) VALUES
(8, 'johnroy062102calimlim@cvsu.edu.ph', '7f8291c7c292efa446012d7c2ef9ec443443c852c2d900832d6e0ad1b4ad6e01', 'expired', '2024-05-22 13:42:57');

-- --------------------------------------------------------

--
-- Table structure for table `pending_account_mails`
--

CREATE TABLE `pending_account_mails` (
  `id` int NOT NULL,
  `email` varchar(64) NOT NULL,
  `raw_password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school_year`
--

CREATE TABLE `school_year` (
  `id` int NOT NULL,
  `school_year` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `semester` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_year`
--

INSERT INTO `school_year` (`id`, `school_year`, `semester`, `status`) VALUES
(9, '2024 - 2025', '1st sem', 'active'),
(10, '2024 - 2025', '2nd sem', 'inactive');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `school_year` int NOT NULL,
  `year_level` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `course` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `name`, `school_year`, `year_level`, `course`) VALUES
(31, '1', 9, '1st Year', 1),
(32, '2', 9, '1st Year', 1),
(33, '3', 9, '1st Year', 1),
(34, '4', 9, '1st Year', 1),
(35, '1', 9, '2nd Year', 1),
(36, '2', 9, '2nd Year', 1),
(37, '3', 9, '2nd Year', 1),
(38, '4', 9, '2nd Year', 1),
(39, '1', 9, '3rd Year', 1),
(40, '2', 9, '3rd Year', 1),
(41, '3', 9, '3rd Year', 1),
(42, '4', 9, '3rd Year', 1),
(43, '1', 9, '4th Year', 1),
(44, '2', 9, '4th Year', 1),
(45, '3', 9, '4th Year', 1),
(46, '4', 9, '4th Year', 1),
(47, '1', 9, '5th Year', 1),
(48, '2', 9, '5th Year', 1),
(49, '3', 9, '5th Year', 1),
(50, '4', 9, '5th Year', 1),
(51, '1', 9, '1st Year', 2),
(52, '2', 9, '1st Year', 2),
(53, '3', 9, '1st Year', 2),
(54, '4', 9, '1st Year', 2),
(55, '1', 9, '2nd Year', 2),
(56, '2', 9, '2nd Year', 2),
(57, '3', 9, '2nd Year', 2),
(58, '4', 9, '2nd Year', 2),
(59, '1', 9, '3rd Year', 2),
(60, '2', 9, '3rd Year', 2),
(61, '3', 9, '3rd Year', 2),
(62, '4', 9, '3rd Year', 2),
(63, '1', 9, '4th Year', 2),
(64, '2', 9, '4th Year', 2),
(65, '3', 9, '4th Year', 2),
(66, '4', 9, '4th Year', 2),
(67, '1', 9, '5th Year', 2),
(68, '2', 9, '5th Year', 2),
(69, '3', 9, '5th Year', 2),
(70, '4', 9, '5th Year', 2),
(71, '1', 9, '1st Year', 3),
(72, '2', 9, '1st Year', 3),
(73, '3', 9, '1st Year', 3),
(74, '4', 9, '1st Year', 3),
(75, '1', 9, '2nd Year', 3),
(76, '2', 9, '2nd Year', 3),
(77, '3', 9, '2nd Year', 3),
(78, '4', 9, '2nd Year', 3),
(79, '1', 9, '3rd Year', 3),
(80, '2', 9, '3rd Year', 3),
(81, '3', 9, '3rd Year', 3),
(82, '4', 9, '3rd Year', 3),
(83, '1', 9, '4th Year', 3),
(84, '2', 9, '4th Year', 3),
(85, '3', 9, '4th Year', 3),
(86, '4', 9, '4th Year', 3),
(87, '1', 9, '5th Year', 3),
(88, '2', 9, '5th Year', 3),
(89, '3', 9, '5th Year', 3),
(90, '4', 9, '5th Year', 3),
(91, '1', 9, '1st Year', 4),
(92, '2', 9, '1st Year', 4),
(93, '3', 9, '1st Year', 4),
(94, '4', 9, '1st Year', 4),
(95, '1', 9, '2nd Year', 4),
(96, '2', 9, '2nd Year', 4),
(97, '3', 9, '2nd Year', 4),
(98, '4', 9, '2nd Year', 4),
(99, '1', 9, '3rd Year', 4),
(100, '2', 9, '3rd Year', 4),
(101, '3', 9, '3rd Year', 4),
(102, '4', 9, '3rd Year', 4),
(103, '1', 9, '4th Year', 4),
(104, '2', 9, '4th Year', 4),
(105, '3', 9, '4th Year', 4),
(106, '4', 9, '4th Year', 4),
(107, '1', 9, '5th Year', 4),
(108, '2', 9, '5th Year', 4),
(109, '3', 9, '5th Year', 4),
(110, '4', 9, '5th Year', 4),
(111, '1', 9, '1st Year', 5),
(112, '2', 9, '1st Year', 5),
(113, '3', 9, '1st Year', 5),
(114, '4', 9, '1st Year', 5),
(115, '1', 9, '2nd Year', 5),
(116, '2', 9, '2nd Year', 5),
(117, '3', 9, '2nd Year', 5),
(118, '4', 9, '2nd Year', 5),
(119, '1', 9, '3rd Year', 5),
(120, '2', 9, '3rd Year', 5),
(121, '3', 9, '3rd Year', 5),
(122, '4', 9, '3rd Year', 5),
(123, '1', 9, '4th Year', 5),
(124, '2', 9, '4th Year', 5),
(125, '3', 9, '4th Year', 5),
(126, '4', 9, '4th Year', 5),
(127, '1', 9, '5th Year', 5),
(128, '2', 9, '5th Year', 5),
(129, '3', 9, '5th Year', 5),
(130, '4', 9, '5th Year', 5),
(131, '1', 9, '1st Year', 6),
(132, '2', 9, '1st Year', 6),
(133, '3', 9, '1st Year', 6),
(134, '4', 9, '1st Year', 6),
(135, '1', 9, '2nd Year', 6),
(136, '2', 9, '2nd Year', 6),
(137, '3', 9, '2nd Year', 6),
(138, '4', 9, '2nd Year', 6),
(139, '1', 9, '3rd Year', 6),
(140, '2', 9, '3rd Year', 6),
(141, '3', 9, '3rd Year', 6),
(142, '4', 9, '3rd Year', 6),
(143, '1', 9, '4th Year', 6),
(144, '2', 9, '4th Year', 6),
(145, '3', 9, '4th Year', 6),
(146, '4', 9, '4th Year', 6),
(147, '1', 9, '5th Year', 6),
(148, '2', 9, '5th Year', 6),
(149, '3', 9, '5th Year', 6),
(150, '4', 9, '5th Year', 6),
(151, '1', 9, '1st Year', 7),
(152, '2', 9, '1st Year', 7),
(153, '3', 9, '1st Year', 7),
(154, '4', 9, '1st Year', 7),
(155, '1', 9, '2nd Year', 7),
(156, '2', 9, '2nd Year', 7),
(157, '3', 9, '2nd Year', 7),
(158, '4', 9, '2nd Year', 7),
(159, '1', 9, '3rd Year', 7),
(160, '2', 9, '3rd Year', 7),
(161, '3', 9, '3rd Year', 7),
(162, '4', 9, '3rd Year', 7),
(163, '1', 9, '4th Year', 7),
(164, '2', 9, '4th Year', 7),
(165, '3', 9, '4th Year', 7),
(166, '4', 9, '4th Year', 7),
(167, '1', 9, '5th Year', 7),
(168, '2', 9, '5th Year', 7),
(169, '3', 9, '5th Year', 7),
(170, '4', 9, '5th Year', 7),
(171, '1', 9, '1st Year', 8),
(172, '2', 9, '1st Year', 8),
(173, '3', 9, '1st Year', 8),
(174, '4', 9, '1st Year', 8),
(175, '1', 9, '2nd Year', 8),
(176, '2', 9, '2nd Year', 8),
(177, '3', 9, '2nd Year', 8),
(178, '4', 9, '2nd Year', 8),
(179, '1', 9, '3rd Year', 8),
(180, '2', 9, '3rd Year', 8),
(181, '3', 9, '3rd Year', 8),
(182, '4', 9, '3rd Year', 8),
(183, '1', 9, '4th Year', 8),
(184, '2', 9, '4th Year', 8),
(185, '3', 9, '4th Year', 8),
(186, '4', 9, '4th Year', 8),
(187, '1', 9, '5th Year', 8),
(188, '2', 9, '5th Year', 8),
(189, '3', 9, '5th Year', 8),
(190, '4', 9, '5th Year', 8),
(191, '1', 9, '1st Year', 9),
(192, '2', 9, '1st Year', 9),
(193, '3', 9, '1st Year', 9),
(194, '4', 9, '1st Year', 9),
(195, '1', 9, '2nd Year', 9),
(196, '2', 9, '2nd Year', 9),
(197, '3', 9, '2nd Year', 9),
(198, '4', 9, '2nd Year', 9),
(199, '1', 9, '3rd Year', 9),
(200, '2', 9, '3rd Year', 9),
(201, '3', 9, '3rd Year', 9),
(202, '4', 9, '3rd Year', 9),
(203, '1', 9, '4th Year', 9),
(204, '2', 9, '4th Year', 9),
(205, '3', 9, '4th Year', 9),
(206, '4', 9, '4th Year', 9),
(207, '1', 9, '5th Year', 9),
(208, '2', 9, '5th Year', 9),
(209, '3', 9, '5th Year', 9),
(210, '4', 9, '5th Year', 9),
(212, '1', 9, '4th Year', 14),
(213, '2', 9, '4th Year', 14),
(214, '3', 9, '4th Year', 14),
(215, '4', 9, '4th Year', 14),
(216, '1', 9, '4th Year', 15),
(217, '2', 9, '4th Year', 15),
(218, '3', 9, '4th Year', 15),
(219, '4', 9, '4th Year', 15);

-- --------------------------------------------------------

--
-- Table structure for table `section_students`
--

CREATE TABLE `section_students` (
  `id` int NOT NULL,
  `section_id` int NOT NULL,
  `student_id` int NOT NULL,
  `is_irregular` int NOT NULL DEFAULT '0',
  `irregular_subject_id` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `section_students`
--

INSERT INTO `section_students` (`id`, `section_id`, `student_id`, `is_irregular`, `irregular_subject_id`) VALUES
(145, 91, 153, 0, 0),
(146, 91, 154, 0, 0),
(149, 192, 157, 0, 0),
(150, 91, 158, 0, 0),
(151, 204, 159, 0, 0),
(152, 207, 160, 0, 0),
(153, 191, 161, 0, 0),
(154, 103, 162, 0, 0),
(161, 216, 168, 0, 0),
(162, 216, 169, 0, 0),
(163, 91, 170, 0, 0),
(164, 95, 163, 0, 0),
(165, 151, 155, 0, 0),
(166, 151, 156, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `student_enrolled_subjects`
--

CREATE TABLE `student_enrolled_subjects` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `is_irregular` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `student_enrolled_subjects`
--

INSERT INTO `student_enrolled_subjects` (`id`, `student_id`, `subject_id`, `is_irregular`) VALUES
(100, 169, 76, 0),
(101, 169, 77, 0),
(102, 168, 76, 0),
(103, 168, 77, 0),
(104, 163, 56, 0),
(105, 153, 46, 0),
(106, 154, 46, 0),
(107, 155, 37, 0),
(108, 156, 37, 0);

-- --------------------------------------------------------

--
-- Table structure for table `student_final_grades`
--

CREATE TABLE `student_final_grades` (
  `id` int NOT NULL,
  `subject` int NOT NULL,
  `term` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `student` int NOT NULL,
  `school_year` int NOT NULL,
  `grade` varchar(15) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_final_grades`
--

INSERT INTO `student_final_grades` (`id`, `subject`, `term`, `student`, `school_year`, `grade`) VALUES
(69, 77, '1st Sem', 168, 9, '2.00'),
(70, 77, '1st Sem', 169, 9, '2.25'),
(71, 76, '1st Sem', 168, 9, '1.75'),
(72, 76, '1st Sem', 169, 9, '1.25'),
(73, 46, '1st Sem', 153, 9, '1.00'),
(74, 46, '1st Sem', 154, 9, '1.00');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int NOT NULL,
  `course` int NOT NULL,
  `year_level` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `units` int NOT NULL,
  `credits_units` int NOT NULL,
  `term` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `course`, `year_level`, `name`, `code`, `units`, `credits_units`, `term`) VALUES
(30, 9, '1st year', 'Art Appreciation', 'GNED 01', 1, 1, '1st Sem'),
(31, 9, '1st year', 'Ethics', 'GNED 02', 3, 3, '1st Sem'),
(32, 9, '1st year', 'Tourism And Hospitality Management 1 (Macro Perspective)', 'TOUR 50', 3, 3, '1st Sem'),
(33, 9, '1st year', 'Risk Management As Applied To Safety, Security And Sanitation', 'TOUR 55', 3, 3, '1st Sem'),
(34, 9, '1st year', 'Movement Enhancement', 'FITT 1', 2, 2, '1st Sem'),
(35, 9, '1st year', 'CWTS / LTS / ROTC', 'NSTP 1', 3, 3, '1st Sem'),
(36, 9, '1st year', 'Institutional Orientation', 'CVSU 101', 1, 1, '1st Sem'),
(37, 7, '1st year', 'Mathematics In The Modern World', 'GNED 03', 3, 3, '1st Sem'),
(38, 7, '1st year', 'Mga Babasahin Hinggil Sa Kasasaysayan Ng Pilipinas', 'GNED 04', 3, 3, '1st Sem'),
(39, 7, '1st year', 'Purposive Communication', 'GNED 05', 3, 3, '1st Sem'),
(40, 7, '1st year', 'Science, Technology, And Society', 'GNED 06', 3, 3, '1st Sem'),
(41, 7, '1st year', 'Understanding The Self', 'GNED 08', 3, 3, '1st Sem'),
(42, 7, '1st year', 'Introduction To Psychology', 'BPSY 50', 3, 3, '1st Sem'),
(43, 7, '1st year', 'Movement Enhancement', 'FITT 1', 3, 3, '1st Sem'),
(44, 7, '1st year', 'CWTS / LTS / ROTC', 'NSTP 1', 3, 3, '1st Sem'),
(45, 7, '1st year', 'Institutional Orientation', 'CVSU 101', 1, 1, '1st Sem'),
(46, 4, '1st year', 'Ethics', 'GNED 02', 3, 3, '1st Sem'),
(47, 4, '1st year', 'Purposive Communication', 'GNED 05', 3, 3, '1st Sem'),
(48, 4, '1st year', 'Kontekstwalisadong Komunikasyon Sa Filipino', 'GNED 11', 3, 3, '1st Sem'),
(49, 4, '1st year', 'Discrete Structure', 'COSC 50', 3, 3, '1st Sem'),
(50, 4, '1st year', 'Introduction To Computing', 'DCIT 21', 2, 2, '1st Sem'),
(51, 4, '1st year', 'Computer Programming I', 'DCIT 22', 1, 1, '1st Sem'),
(52, 4, '1st year', 'Movement Enhancement', 'FITT 1', 2, 2, '1st Sem'),
(53, 4, '1st year', 'CWTS / LTS / ROTC', 'NSTP 1', 3, 3, '1st Sem'),
(54, 4, '1st year', 'Institutional Orientation', 'CVSU 101', 1, 1, '1st Sem'),
(55, 4, '2nd year', 'Mga Babasahin Hinggil Sa Kasasaysayan Ng Pilipinas', 'GNED 04', 3, 3, '1st Sem'),
(56, 4, '2nd year', 'The Contemporary World', 'GNED 07', 3, 3, '1st Sem'),
(57, 4, '2nd year', 'Gender And Society', 'GNED 10', 3, 3, '1st Sem'),
(58, 4, '2nd year', 'Panitikang Panlipunan', 'GNED 14', 3, 3, '1st Sem'),
(59, 4, '2nd year', 'Information Management', 'DCIT 24', 2, 2, '1st Sem'),
(60, 4, '2nd year', 'Object Oriented Programming', 'DCIT 50', 2, 2, '1st Sem'),
(61, 4, '2nd year', 'Platform Technologies', 'ITEC 55', 2, 2, '1st Sem'),
(62, 4, '2nd year', 'Physical Activities Towards Health And Fitness I', 'FITT 3', 2, 2, '1st Sem'),
(63, 4, '4th year', 'Systems Administration And Maintenance', 'ITEC 110', 2, 1, '1st Sem'),
(64, 4, '4th year', 'IT Elective 3 (Integrated Programming And', 'ITEC 111', 2, 1, '1st Sem'),
(65, 4, '4th year', 'Technologies 2', 'ITEC 116', 2, 1, '1st Sem'),
(66, 4, '3rd year', 'Application Development and Emerging Technologies', 'DCIT 26', 2, 1, '1st Sem'),
(67, 4, '3rd year', 'Methods of Research', 'DCIT60', 3, 1, '1st Sem'),
(68, 4, '3rd year', 'System Analysis and Design', 'INSY 55', 2, 1, '1st Sem'),
(69, 4, '3rd year', 'Introduction to Human Computer Interaction', 'ITEC 80', 2, 1, '1st Sem'),
(70, 4, '3rd year', 'Information Assurance and Security 1', 'ITEC 85', 2, 1, '1st Sem'),
(71, 4, '3rd year', 'Network Fundamentals', 'ITEC 90', 1, 1, '1st Sem'),
(72, 4, '4th year', 'Social and Professional Issues', 'DCIT 65', 3, 3, '1st Sem'),
(73, 4, '4th year', 'Capstone Project and Research 2', 'ITEC 200B', 3, 3, '1st Sem'),
(76, 15, '4th year', 'Arts Appreciation', 'GNED 01', 3, 3, '1st Sem'),
(77, 15, '4th year', 'Discrete Structure', 'COSC 50', 1, 2, '1st Sem');

-- --------------------------------------------------------

--
-- Table structure for table `subject_instructors`
--

CREATE TABLE `subject_instructors` (
  `id` int NOT NULL,
  `subject_id` int NOT NULL,
  `instructor_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `subject_instructors`
--

INSERT INTO `subject_instructors` (`id`, `subject_id`, `instructor_id`) VALUES
(27, 30, 143),
(28, 31, 143),
(29, 32, 143),
(30, 33, 143),
(31, 34, 143),
(32, 35, 143),
(33, 36, 143),
(34, 37, 144),
(35, 38, 144),
(36, 39, 144),
(37, 40, 144),
(38, 41, 144),
(39, 42, 144),
(40, 43, 144),
(41, 44, 144),
(42, 45, 144),
(43, 46, 145),
(44, 47, 145),
(45, 48, 145),
(46, 49, 145),
(47, 50, 145),
(48, 51, 145),
(49, 52, 145),
(50, 53, 145),
(51, 54, 145),
(52, 55, 146),
(53, 56, 146),
(54, 57, 146),
(55, 58, 146),
(56, 59, 146),
(57, 60, 146),
(58, 61, 146),
(59, 62, 146),
(60, 66, 148),
(61, 67, 148),
(62, 68, 148),
(63, 69, 148),
(64, 70, 148),
(65, 71, 148),
(66, 72, 147),
(67, 73, 147),
(68, 63, 147),
(69, 64, 147),
(70, 65, 147),
(74, 77, 146),
(75, 76, 146),
(76, 76, 145);

-- --------------------------------------------------------

--
-- Table structure for table `subject_instructor_sections`
--

CREATE TABLE `subject_instructor_sections` (
  `id` int NOT NULL,
  `subject_id` int NOT NULL,
  `instructor_id` int NOT NULL,
  `section_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `subject_instructor_sections`
--

INSERT INTO `subject_instructor_sections` (`id`, `subject_id`, `instructor_id`, `section_id`) VALUES
(25, 30, 143, 191),
(26, 30, 143, 192),
(27, 30, 143, 193),
(28, 30, 143, 194),
(29, 31, 143, 191),
(30, 31, 143, 192),
(31, 31, 143, 193),
(32, 31, 143, 194),
(33, 32, 143, 191),
(34, 32, 143, 192),
(35, 32, 143, 193),
(36, 32, 143, 194),
(37, 33, 143, 191),
(38, 33, 143, 192),
(39, 33, 143, 193),
(40, 33, 143, 194),
(41, 34, 143, 191),
(42, 34, 143, 192),
(43, 34, 143, 193),
(44, 34, 143, 194),
(45, 35, 143, 191),
(46, 35, 143, 192),
(47, 35, 143, 193),
(48, 35, 143, 194),
(49, 36, 143, 191),
(50, 36, 143, 192),
(51, 36, 143, 193),
(52, 36, 143, 194),
(53, 37, 144, 151),
(54, 37, 144, 152),
(55, 37, 144, 153),
(56, 37, 144, 154),
(57, 38, 144, 151),
(58, 38, 144, 152),
(59, 38, 144, 153),
(60, 38, 144, 154),
(61, 39, 144, 151),
(62, 39, 144, 152),
(63, 39, 144, 153),
(64, 39, 144, 154),
(65, 40, 144, 151),
(66, 40, 144, 152),
(67, 40, 144, 153),
(68, 40, 144, 154),
(69, 41, 144, 151),
(70, 41, 144, 152),
(71, 41, 144, 153),
(72, 41, 144, 154),
(73, 42, 144, 151),
(74, 42, 144, 152),
(75, 42, 144, 153),
(76, 42, 144, 154),
(77, 43, 144, 151),
(78, 43, 144, 152),
(79, 43, 144, 153),
(80, 43, 144, 154),
(81, 44, 144, 151),
(82, 44, 144, 152),
(83, 44, 144, 153),
(84, 44, 144, 154),
(85, 45, 144, 151),
(86, 45, 144, 152),
(87, 45, 144, 153),
(88, 45, 144, 154),
(89, 46, 145, 91),
(90, 46, 145, 92),
(91, 46, 145, 93),
(92, 46, 145, 94),
(93, 47, 145, 91),
(94, 47, 145, 92),
(95, 47, 145, 93),
(96, 47, 145, 94),
(97, 48, 145, 91),
(98, 48, 145, 92),
(99, 48, 145, 93),
(100, 48, 145, 94),
(101, 49, 145, 91),
(102, 49, 145, 92),
(103, 49, 145, 93),
(104, 49, 145, 94),
(105, 50, 145, 91),
(106, 50, 145, 92),
(107, 50, 145, 93),
(108, 50, 145, 94),
(109, 51, 145, 91),
(110, 51, 145, 92),
(111, 51, 145, 93),
(112, 51, 145, 94),
(113, 52, 145, 91),
(114, 52, 145, 92),
(115, 52, 145, 93),
(116, 52, 145, 94),
(117, 53, 145, 91),
(118, 53, 145, 92),
(119, 53, 145, 93),
(120, 53, 145, 94),
(121, 54, 145, 91),
(122, 54, 145, 92),
(123, 54, 145, 93),
(124, 54, 145, 94),
(125, 56, 146, 95),
(126, 56, 146, 96),
(127, 56, 146, 97),
(128, 56, 146, 98),
(129, 55, 146, 95),
(130, 55, 146, 96),
(131, 55, 146, 97),
(132, 55, 146, 98),
(133, 57, 146, 95),
(134, 57, 146, 96),
(135, 57, 146, 97),
(136, 57, 146, 98),
(137, 58, 146, 95),
(138, 58, 146, 96),
(139, 58, 146, 97),
(140, 58, 146, 98),
(141, 59, 146, 95),
(142, 59, 146, 96),
(143, 59, 146, 97),
(144, 59, 146, 98),
(145, 60, 146, 95),
(146, 60, 146, 96),
(147, 60, 146, 97),
(148, 60, 146, 98),
(149, 61, 146, 95),
(150, 61, 146, 96),
(151, 61, 146, 97),
(152, 61, 146, 98),
(153, 62, 146, 95),
(154, 62, 146, 96),
(155, 62, 146, 97),
(156, 62, 146, 98),
(157, 66, 148, 99),
(158, 66, 148, 100),
(159, 66, 148, 101),
(160, 66, 148, 102),
(161, 67, 148, 99),
(162, 67, 148, 100),
(163, 67, 148, 101),
(164, 67, 148, 102),
(165, 68, 148, 99),
(166, 68, 148, 100),
(167, 68, 148, 101),
(168, 68, 148, 102),
(169, 69, 148, 99),
(170, 69, 148, 100),
(171, 69, 148, 101),
(172, 69, 148, 102),
(173, 70, 148, 99),
(174, 70, 148, 100),
(175, 70, 148, 101),
(176, 70, 148, 102),
(177, 71, 148, 99),
(178, 71, 148, 100),
(179, 71, 148, 101),
(180, 71, 148, 102),
(181, 72, 147, 103),
(182, 72, 147, 104),
(183, 72, 147, 105),
(184, 72, 147, 106),
(185, 73, 147, 103),
(186, 73, 147, 104),
(187, 73, 147, 105),
(188, 73, 147, 106),
(189, 63, 147, 103),
(190, 63, 147, 104),
(191, 63, 147, 105),
(192, 63, 147, 106),
(193, 64, 147, 103),
(194, 64, 147, 104),
(195, 64, 147, 105),
(196, 64, 147, 106),
(197, 65, 147, 103),
(198, 65, 147, 104),
(199, 65, 147, 105),
(200, 65, 147, 106),
(207, 76, 146, 216),
(208, 76, 146, 217),
(209, 76, 145, 218),
(210, 76, 145, 219),
(211, 77, 146, 216);

-- --------------------------------------------------------

--
-- Table structure for table `userdetails`
--

CREATE TABLE `userdetails` (
  `id` int NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `middleName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gender` varchar(255) NOT NULL,
  `contact` varchar(255) NOT NULL,
  `birthday` date DEFAULT NULL,
  `year_level` varchar(255) DEFAULT NULL,
  `roles` varchar(255) NOT NULL,
  `sid` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `userdetails`
--

INSERT INTO `userdetails` (`id`, `firstName`, `middleName`, `lastName`, `email`, `password`, `gender`, `contact`, `birthday`, `year_level`, `roles`, `sid`) VALUES
(11, 'John Roy', '', 'Lapida', 'johnroy062102calimlim@cvsu.edu.ph', '$6$Crypt$Svhm9rLEJJ6E99h1C2wo5Sdjz4PhjfD3w93g7EkIfrIB15bnY5Os5sdaYDCIGYeqG1JrQzM6A2EJpCJHIsSNd0', 'female', '09123456890', '1990-01-01', '', 'admin', '12345555'),
(45, 'Hopie', '', 'Rafaela', 'hopie.rafaela@cvsu.edu.ph', '$6$Crypt$.6Xfcanx2rKf.E/OK2xB7N1g1GEzaueGkq0nnX2REUyOmlQ7LG/7FlCAfg/jmgjaueu5MuikTkZgAs0k9rHlm.', 'female', '09165563616', '2001-03-20', NULL, 'admin', NULL),
(143, 'Lana-Angela ', '', 'Yambao', 'lanaangela.yambao@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'female', '09107561537', '2000-01-01', NULL, 'instructor', NULL),
(144, 'janlouise ', '', 'Policarpio', 'janlouise.policarpio@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'male', '09107561537', '2000-01-02', NULL, 'instructor', NULL),
(145, 'Marriel ', '', 'Bella', 'marriel.bella@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'female', '09107561537', '2000-01-03', NULL, 'instructor', NULL),
(146, 'Leonardo ', '', 'Araga', 'leonardo.araga@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'male', '09107561537', '2000-01-04', NULL, 'instructor', NULL),
(147, 'Michael ', '', 'Pareja', 'michael.pareja@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'male', '09107561537', '2000-01-05', NULL, 'instructor', NULL),
(148, 'Edison ', 'De los ', 'Santos', 'edison.delossantos@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'male', '09107561537', '2000-01-06', NULL, 'instructor', NULL),
(149, 'Glenn Calvin', '', 'Doce', 'glenncalvin.doce@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'male', '09107561537', '2000-01-07', NULL, 'instructor', NULL),
(153, 'Albert', '', 'Winkler', 'albert.winkler@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'male', '09123456890', '2000-02-19', '1st year', 'student', '202402'),
(154, 'Louise', '', 'Rouxe', 'louise.rouxe@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'female', '09123456890', '2001-06-06', '1st year', 'student', '2024046'),
(155, 'Alvin', '', 'Winchester', 'alvin.winchester@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'male', '09123456890', '2001-01-01', '1st year', 'student', '200405'),
(156, 'Erman', '', 'Faminiano', 'erman.famianiano@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'male', '09107561537', '2001-05-10', '1st year', 'student', '202406'),
(157, 'Criztian', '', 'Tuplano', 'criztian.tuplano@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'male', '09107561537', '2001-11-15', '1st year', 'student', '202403'),
(158, 'John', '', 'Doe', 'johndoe@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'female', '09929238878', '2005-03-07', '1st year', 'student', '202401'),
(159, 'John', '', 'Smith', 'johnsmith@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'male', '09929238878', '2005-03-08', '4th year', 'student', '202491'),
(160, 'Glenn', '', 'Tenorio', 'glenn.tenorio@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'male', '09298389246', '1985-01-15', '5th year', 'student', '202407'),
(161, 'Naz', '', 'Mauri', 'naz.mauri@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'male', '09298382761', '2008-03-07', '1st year', 'student', '202408'),
(162, 'Loyd', '', 'Flores', 'loyd.flores@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'male', '09298390211', '2000-10-30', '4th year', 'student', '202409'),
(163, 'Vyxel', '', 'Calimlim', 'vyxel.calimlim@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'male', '09058572372', '2002-06-21', '2nd year', 'student', '2024010'),
(168, 'Paul', '', 'Aleafar', 'paul.aleafar@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'male', '09107561537', '2001-02-06', '4th year', 'student', '202426'),
(169, 'Yaelle', 'Medina', 'Zapanta', 'yaelle.zapanta@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'female', '09929238878', '2005-03-07', '4th Year', 'student', '202427'),
(170, 'John', '', 'Doe', 'johndoe2@cvsu.edu.ph', '$6$Crypt$gJgGuMBB3Cjp/dhCNd7KzPMbYccDfwX9i2.nCQjsA6EhaRHY7kHNbNX8zZGJIjNsyuZNbDTHPQ4vGAqvFW0Hz/', 'female', '09929238878', '2005-03-07', '1st Year', 'student', '2024099');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activity_scores`
--
ALTER TABLE `activity_scores`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `grade_requests`
--
ALTER TABLE `grade_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `grading_criterias`
--
ALTER TABLE `grading_criterias`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `instructor_change_grade_request`
--
ALTER TABLE `instructor_change_grade_request`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `instructor_grade_release_requests`
--
ALTER TABLE `instructor_grade_release_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `pending_account_mails`
--
ALTER TABLE `pending_account_mails`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `school_year`
--
ALTER TABLE `school_year`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `section_students`
--
ALTER TABLE `section_students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_enrolled_subjects`
--
ALTER TABLE `student_enrolled_subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_final_grades`
--
ALTER TABLE `student_final_grades`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subject_instructors`
--
ALTER TABLE `subject_instructors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subject_instructor_sections`
--
ALTER TABLE `subject_instructor_sections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `userdetails`
--
ALTER TABLE `userdetails`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT for table `activity_scores`
--
ALTER TABLE `activity_scores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `grade_requests`
--
ALTER TABLE `grade_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `grading_criterias`
--
ALTER TABLE `grading_criterias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `instructor_change_grade_request`
--
ALTER TABLE `instructor_change_grade_request`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `instructor_grade_release_requests`
--
ALTER TABLE `instructor_grade_release_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pending_account_mails`
--
ALTER TABLE `pending_account_mails`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `school_year`
--
ALTER TABLE `school_year`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;

--
-- AUTO_INCREMENT for table `section_students`
--
ALTER TABLE `section_students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT for table `student_enrolled_subjects`
--
ALTER TABLE `student_enrolled_subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `student_final_grades`
--
ALTER TABLE `student_final_grades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `subject_instructors`
--
ALTER TABLE `subject_instructors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `subject_instructor_sections`
--
ALTER TABLE `subject_instructor_sections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=212;

--
-- AUTO_INCREMENT for table `userdetails`
--
ALTER TABLE `userdetails`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `event_password_reset_token_expiration_check` ON SCHEDULE EVERY 1 MINUTE STARTS '2024-04-04 02:30:01' ON COMPLETION PRESERVE ENABLE DO UPDATE password_reset_tokens SET status='expired' WHERE status='active' AND createdAt <= NOW() - INTERVAL 2 MINUTE$$

CREATE DEFINER=`root`@`localhost` EVENT `event_password_reset_token_delete_expired` ON SCHEDULE EVERY 2 MINUTE STARTS '2024-04-04 02:35:48' ON COMPLETION PRESERVE ENABLE DO DELETE FROM password_reset_tokens WHERE (status IN ('used', 'expired')) AND createdAt <= NOW() - INTERVAL 15 DAY$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
