-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 06, 2024 at 10:46 AM
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
-- Database: `grading-sys`
--

-- --------------------------------------------------------

--
-- Table structure for table `ap_activities`
--

CREATE TABLE `ap_activities` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `subject` int(11) NOT NULL,
  `school_year` int(11) NOT NULL,
  `term` varchar(255) NOT NULL,
  `year_level` varchar(255) NOT NULL,
  `course` int(11) NOT NULL,
  `passing_rate` double NOT NULL,
  `max_score` int(11) NOT NULL,
  `instructor` int(11) NOT NULL,
  `section` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ap_activities`
--

INSERT INTO `ap_activities` (`id`, `name`, `subject`, `school_year`, `term`, `year_level`, `course`, `passing_rate`, `max_score`, `instructor`, `section`) VALUES
(1, 'Example activity', 1, 1, '1st Sem', '1st Year', 1, 0.25, 50, 23, 1),
(2, 'Another example activity', 2, 1, '1st Sem', '1st Year', 1, 0.25, 50, 23, 4);

-- --------------------------------------------------------

--
-- Table structure for table `ap_courses`
--

CREATE TABLE `ap_courses` (
  `id` int(11) NOT NULL,
  `course` varchar(255) NOT NULL,
  `course_code` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ap_courses`
--

INSERT INTO `ap_courses` (`id`, `course`, `course_code`) VALUES
(1, 'BS Secondary Education Major in Math', 'BSED-Math'),
(2, 'BS Secondary Education Major in English', 'BSED-English'),
(3, 'BS Secondary Education Major in Science', 'BSED-Science'),
(4, 'BS Information Technology', 'BSIT'),
(5, 'BS Business Administration', 'BSBA'),
(6, 'BS Office Administration', 'BSOA'),
(7, 'BS Psychology', 'BSPsych'),
(8, 'BS Hospitality Management', 'BSHM'),
(9, 'BS Tourism', 'BSTr');

-- --------------------------------------------------------

--
-- Table structure for table `ap_grade_requests`
--

CREATE TABLE `ap_grade_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `term` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ap_grade_requests`
--

INSERT INTO `ap_grade_requests` (`id`, `student_id`, `section_id`, `term`, `status`) VALUES
(2, 25, 1, '1st Sem', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `ap_school_year`
--

CREATE TABLE `ap_school_year` (
  `id` int(11) NOT NULL,
  `school_year` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ap_school_year`
--

INSERT INTO `ap_school_year` (`id`, `school_year`) VALUES
(1, '2024 - 2025'),
(2, '2025 - 2026');

-- --------------------------------------------------------

--
-- Table structure for table `ap_sections`
--

CREATE TABLE `ap_sections` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `school_year` int(11) NOT NULL,
  `term` varchar(255) NOT NULL,
  `year_level` varchar(255) NOT NULL,
  `course` int(11) NOT NULL,
  `instructor` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ap_sections`
--

INSERT INTO `ap_sections` (`id`, `name`, `school_year`, `term`, `year_level`, `course`, `instructor`) VALUES
(1, 'Newton', 1, '1st Sem', '1st Year', 1, 23),
(2, 'Cordial', 1, '1st Sem', '3rd Year', 4, 27),
(3, 'Diamond', 2, '1st Sem', '1st Year', 1, 27),
(4, 'Athena', 2, '1st Sem', '1st Year', 1, 23);

-- --------------------------------------------------------

--
-- Table structure for table `ap_section_students`
--

CREATE TABLE `ap_section_students` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ap_section_students`
--

INSERT INTO `ap_section_students` (`id`, `section_id`, `student_id`) VALUES
(29, 2, 19),
(35, 3, 26),
(36, 1, 25),
(37, 1, 24),
(38, 1, 1),
(39, 1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `ap_section_subjects`
--

CREATE TABLE `ap_section_subjects` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ap_section_subjects`
--

INSERT INTO `ap_section_subjects` (`id`, `section_id`, `subject_id`) VALUES
(2, 2, 1),
(11, 3, 1),
(12, 3, 2),
(13, 3, 3),
(14, 1, 1),
(15, 1, 3),
(16, 1, 2),
(17, 4, 1),
(18, 4, 2),
(19, 4, 3);

-- --------------------------------------------------------

--
-- Table structure for table `ap_student_final_grades`
--

CREATE TABLE `ap_student_final_grades` (
  `id` int(11) NOT NULL,
  `subject` int(11) NOT NULL,
  `term` varchar(255) NOT NULL,
  `year_level` varchar(255) NOT NULL,
  `section` int(11) NOT NULL,
  `student` int(11) NOT NULL,
  `school_year` int(11) NOT NULL,
  `grade` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ap_student_final_grades`
--

INSERT INTO `ap_student_final_grades` (`id`, `subject`, `term`, `year_level`, `section`, `student`, `school_year`, `grade`) VALUES
(1, 1, '1st Sem', '1st Year', 1, 25, 1, 76),
(2, 1, '1st Sem', '1st Year', 1, 24, 1, 96),
(3, 1, '1st Sem', '1st Year', 1, 1, 1, 100),
(4, 1, '1st Sem', '1st Year', 1, 4, 1, 94);

-- --------------------------------------------------------

--
-- Table structure for table `ap_student_grades`
--

CREATE TABLE `ap_student_grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `grade` decimal(10,0) NOT NULL,
  `term` varchar(255) NOT NULL,
  `year_level` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ap_student_grades`
--

INSERT INTO `ap_student_grades` (`id`, `student_id`, `activity_id`, `instructor_id`, `section_id`, `grade`, `term`, `year_level`) VALUES
(1, 25, 1, 23, 1, 38, '1st Sem', '1st Year'),
(2, 24, 1, 23, 1, 48, '1st Sem', '1st Year'),
(3, 1, 1, 23, 1, 50, '1st Sem', '1st Year'),
(4, 4, 1, 23, 1, 47, '1st Sem', '1st Year');

-- --------------------------------------------------------

--
-- Table structure for table `ap_subjects`
--

CREATE TABLE `ap_subjects` (
  `id` int(11) NOT NULL,
  `course` int(11) NOT NULL,
  `year_level` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `units` int(11) NOT NULL,
  `credits_units` int(11) NOT NULL,
  `term` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ap_subjects`
--

INSERT INTO `ap_subjects` (`id`, `course`, `year_level`, `name`, `units`, `credits_units`, `term`) VALUES
(1, 1, '1st year', 'Discrete Mathematics', 25, 25, '1st Sem'),
(2, 1, '1st year', 'Boolean Algebra', 25, 25, '1st Sem'),
(3, 1, '1st year', 'Statistics', 25, 25, '1st Sem'),
(4, 3, '1st year', 'Physics', 25, 26, '1st Sem');

-- --------------------------------------------------------

--
-- Table structure for table `ap_userdetails`
--

CREATE TABLE `ap_userdetails` (
  `id` int(12) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `ap_userdetails`
--

INSERT INTO `ap_userdetails` (`id`, `firstName`, `middleName`, `lastName`, `email`, `password`, `gender`, `contact`, `birthday`, `year_level`, `roles`, `sid`) VALUES
(2, 'Albert', 'Pogi', 'Winkler', 'askalkaba73@cvsu.edu.ph', '$6$Crypt$A/jDa6/VXgega4JS.fwcrnSPsSGc8iYDamgc9d0eVLyMU725Br1vK00ffObZUSbr/Enjrgh2S40phWnAcb.2w1', 'female', '09123456890', '1990-01-01', '', 'admin', '432432432'),
(3, 'Criztian ', 'Mitra', 'Pogi', 'criztianpog123@cvsu.edu.ph', '$6$Crypt$.IPCVAofnRERoq/EF59k00yPGSCWboFqBr/evkTVdPrpx.TjOUlI0Mi/.3jJYjPdsL7A3MZWpjAgu3DkjHmAj0', 'male', '09123456890', '1990-01-01', '', 'admin', '283828382'),
(11, 'John Roy', '', 'Lapida', 'johnroy062102calimlim@cvsu.edu.ph', '$6$Crypt$Svhm9rLEJJ6E99h1C2wo5Sdjz4PhjfD3w93g7EkIfrIB15bnY5Os5sdaYDCIGYeqG1JrQzM6A2EJpCJHIsSNd0', 'female', '09123456890', '1990-01-01', '', 'admin', '12345555'),
(12, 'Albert', 'Pogi', 'Aklan', 'contact@cvsu.edu.ph', '$6$Crypt$S8.saR6abJ/tBvAGbJQw5Rb66jTwAqzBrQLl7eAQa/gmeDDQaTM5MS59iV.opdyykT0RVOUHz.uCghBneYD1d.', 'male', '09123456890', '1990-01-01', '', 'admin', '32432432'),
(23, 'Albert', '', 'Winkler', 'albert@cvsu.edu.ph', '$6$Crypt$PZeqbcn2b92pYsPbMIaJM6JIORhx4WCJm0GPWHc/pXnxAmaRdfyhTEZSCMOBipJpMFfto5x75FtH3LgWghiFS0', 'male', '09123456890', '1900-01-01', NULL, 'instructor', NULL),
(27, 'Hopie', '', 'Soberanya', 'hopiesoberanya@cvsu.edu.ph', '$6$Crypt$2.5jLN/VjjKz65iH.igjic1yExlwo/nj2LN/j1v5zvr45F/rw93wjc9JrUAYO601ENixniWR8FU/64qWl2Vl/.', 'female', '09123456890', '1900-01-01', NULL, 'instructor', NULL),
(37, 'Albert', 'Pogi', 'Winkler', 'asklahanov@cvsu.edu.ph', '$6$Crypt$Mf7OCOYK0wekT5h/CUQCFkIhAF7fE2Z1fBpwndCm.q8I8fe.XvVFg3zJpHJMOq/nkjn0fY/lUJKUCQRyBrKsk1', 'male', '09123456890', '1900-01-01', '3rd year', 'student', '123123123'),
(38, 'Criztian', 'Pogi', 'Tuplano', 'asukalkaba@cvsu.edu.ph', '$6$Crypt$.IPCVAofnRERoq/EF59k00yPGSCWboFqBr/evkTVdPrpx.TjOUlI0Mi/.3jJYjPdsL7A3MZWpjAgu3DkjHmAj0', 'male', '09123456890', '1900-01-01', '4th year', 'student', '1231231232'),
(39, 'Albert', '', 'Pogi', 'albertpogi123@cvsu.edu.ph', '$6$Crypt$EsHmqkQvmwperb8BCLW071h5wYsb/nu6wrJospA4bBOedphtupEzPs7.Xj6G2O.jrZg6VDrzA7zy0PHPTkcOG1', 'male', '09123456890', '1900-01-01', '3rd year', 'student', '780870948572'),
(40, 'Louise', '', 'Rouxe', 'louiserouxe@cvsu.edu.ph', '$6$Crypt$6VtryvO3DqQgz32i5hEQsNs43DwdTbEmlcxNe1816FMWVBWrMYb0kvb.JWz4/YN.fEXMEH2b8JqgBsVHQYVIk1', 'female', '09123456890', '1900-01-01', '2nd year', 'student', '5423413'),
(41, 'Rex', '', 'Rider', 'rexrider@cvsu.edu.ph', '$6$Crypt$AdOgW1ELa7yGkykBpgkQPrpjo1iPyrV06NZvokMNvmfPCWVZJe1HXueA7m.IZ7gjSAfPJ5p6By7X4C06e2s6T.', 'male', '09123456890', '1900-01-01', '1st year', 'student', '0987687'),
(42, 'Dummy', '', 'Student', 'dummystudent@cvsu.edu.ph', '$6$Crypt$l9s07CApiXIAhWVHoOKGAmwp5WPuJioK50k3cdJ8qWF4vcr1q0j2nnyPO/nRGZoO7mNn8UOJbU8mabJm1ZQyA/', 'male', '09123456890', '1900-01-01', '5th year', 'student', '57856234534');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ap_activities`
--
ALTER TABLE `ap_activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ap_courses`
--
ALTER TABLE `ap_courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ap_grade_requests`
--
ALTER TABLE `ap_grade_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ap_school_year`
--
ALTER TABLE `ap_school_year`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ap_sections`
--
ALTER TABLE `ap_sections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ap_section_students`
--
ALTER TABLE `ap_section_students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ap_section_subjects`
--
ALTER TABLE `ap_section_subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ap_student_final_grades`
--
ALTER TABLE `ap_student_final_grades`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ap_student_grades`
--
ALTER TABLE `ap_student_grades`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ap_subjects`
--
ALTER TABLE `ap_subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ap_userdetails`
--
ALTER TABLE `ap_userdetails`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ap_activities`
--
ALTER TABLE `ap_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ap_courses`
--
ALTER TABLE `ap_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ap_grade_requests`
--
ALTER TABLE `ap_grade_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ap_school_year`
--
ALTER TABLE `ap_school_year`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ap_sections`
--
ALTER TABLE `ap_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ap_section_students`
--
ALTER TABLE `ap_section_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `ap_section_subjects`
--
ALTER TABLE `ap_section_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `ap_student_final_grades`
--
ALTER TABLE `ap_student_final_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ap_student_grades`
--
ALTER TABLE `ap_student_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ap_subjects`
--
ALTER TABLE `ap_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ap_userdetails`
--
ALTER TABLE `ap_userdetails`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
