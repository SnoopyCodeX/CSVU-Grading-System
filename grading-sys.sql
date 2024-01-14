-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 13, 2024 at 11:32 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

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
  `contact` int(11) NOT NULL,
  `roles` varchar(255) NOT NULL,
  `sid` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `ap_userdetails`
--

INSERT INTO `ap_userdetails` (`id`, `firstName`, `middleName`, `lastName`, `email`, `password`, `gender`, `contact`, `roles`, `sid`) VALUES
(1, 'Albert', 'Pogi', 'Winkler', 'asklahanov@gmail.com', '$6$Crypt$1qVc0jGeKh7JeEWTUq5Nfadlm9iXqTnb6kSMA0M2B17o6sJh2CG/I9AQytLtkFfpIkxlLZRCtP.FR2.gWiOxG/', 'male', 2147483647, 'student', '123123123'),
(2, 'Albert', 'Pogi', 'Winkler', 'askalkaba73@gmail.com', '$6$Crypt$A/jDa6/VXgega4JS.fwcrnSPsSGc8iYDamgc9d0eVLyMU725Br1vK00ffObZUSbr/Enjrgh2S40phWnAcb.2w1', 'male', 2147483647, 'admin', '432432432'),
(3, 'Criztian ', 'Mitra', 'Pogi', 'criztianpog123@gmail.com', '$6$Crypt$.IPCVAofnRERoq/EF59k00yPGSCWboFqBr/evkTVdPrpx.TjOUlI0Mi/.3jJYjPdsL7A3MZWpjAgu3DkjHmAj0', 'male', 2147483647, 'admin', '283828382'),
(4, 'Criztian', 'Pogi', 'Tuplano', 'asukalkaba@gmail.com', '$6$Crypt$.IPCVAofnRERoq/EF59k00yPGSCWboFqBr/evkTVdPrpx.TjOUlI0Mi/.3jJYjPdsL7A3MZWpjAgu3DkjHmAj0', 'male', 92874747, 'student', '1231231232'),
(11, 'John Roy', 'Middlename', 'Lapida', 'johnroy062102calimlim@gmail.com', '$6$Crypt$Svhm9rLEJJ6E99h1C2wo5Sdjz4PhjfD3w93g7EkIfrIB15bnY5Os5sdaYDCIGYeqG1JrQzM6A2EJpCJHIsSNd0', 'male', 2147483647, 'admin', '12345555'),
(12, 'Albert', 'Pogi', 'Aklan', 'contact@hax4life.org', '$6$Crypt$CAJbLqrN2i959BY9fYtFPc3nLNhHKkAqQVbbpCdez3H/OkFiLEq.JchLmxMmhCaxm/ekMcs1On5oCsfJLxzKR1', 'male', 2147483647, 'admin', '32432432'),
(13, 'supsup', 'mo tite', 'ko', 'dalij89129@pursip.com', '$6$Crypt$.IPCVAofnRERoq/EF59k00yPGSCWboFqBr/evkTVdPrpx.TjOUlI0Mi/.3jJYjPdsL7A3MZWpjAgu3DkjHmAj0', 'male', 255656988, 'admin', '12514544');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ap_userdetails`
--
ALTER TABLE `ap_userdetails`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ap_userdetails`
--
ALTER TABLE `ap_userdetails`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
