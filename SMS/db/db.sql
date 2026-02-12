-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 30, 2026 at 10:33 PM
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
-- Database: `sms`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `day` date DEFAULT NULL,
  `statuss` enum('present','absent') DEFAULT NULL,
  `remarks` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `day`, `statuss`, `remarks`) VALUES
(1, 10, '2026-01-29', 'present', ''),
(2, 1, '2026-01-29', 'present', ''),
(3, 4, '2026-01-29', 'present', ''),
(4, 5, '2026-01-29', 'present', ''),
(5, 6, '2026-01-29', 'present', ''),
(6, 7, '2026-01-29', 'present', ''),
(7, 8, '2026-01-29', 'present', ''),
(8, 9, '2026-01-29', 'absent', ''),
(9, 11, '2026-01-29', 'absent', ''),
(10, 12, '2026-01-29', '', ''),
(11, 13, '2026-01-29', '', ''),
(12, 74, '2026-01-30', 'present', ''),
(13, 75, '2026-01-30', 'present', ''),
(14, 76, '2026-01-30', 'present', ''),
(15, 77, '2026-01-30', 'absent', ''),
(16, 78, '2026-01-30', '', ''),
(17, 79, '2026-01-30', '', ''),
(18, 80, '2026-01-30', 'absent', ''),
(19, 81, '2026-01-30', '', ''),
(20, 82, '2026-01-30', 'absent', ''),
(21, 83, '2026-01-30', '', ''),
(22, 54, '2026-01-30', 'present', ''),
(23, 55, '2026-01-30', 'present', ''),
(24, 56, '2026-01-30', 'present', ''),
(25, 57, '2026-01-30', 'absent', ''),
(26, 58, '2026-01-30', 'absent', ''),
(27, 59, '2026-01-30', '', ''),
(28, 60, '2026-01-30', '', ''),
(29, 61, '2026-01-30', '', ''),
(30, 62, '2026-01-30', '', ''),
(31, 63, '2026-01-30', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(100) DEFAULT NULL,
  `section` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `section`) VALUES
(1, '1', 'A'),
(2, '1', 'B'),
(3, '2', 'A'),
(4, '2', 'B'),
(5, '3', 'A'),
(6, '3', 'B'),
(7, '4', 'A'),
(8, '4', 'B'),
(9, '5', 'A'),
(10, '5', 'B');

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `marks` varchar(50) DEFAULT NULL,
  `date` varchar(100) NOT NULL,
  `obtain_marks` varchar(100) NOT NULL,
  `exam_type` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marks`
--

INSERT INTO `marks` (`id`, `student_id`, `subject`, `marks`, `date`, `obtain_marks`, `exam_type`) VALUES
(1, 14, 'Mathematics', '100', '2026-01-30', '20', ''),
(2, 1, 'Mathematics', '100', '2026', '20', ''),
(3, 15, 'Mathematics', '100', '2026-01-30', '60', ''),
(4, 16, 'Mathematics', '100', '2026-01-30', '90', ''),
(5, 17, 'Mathematics', '100', '2026-01-30', '89', ''),
(6, 18, 'Mathematics', '100', '2026-01-30', '78', ''),
(7, 19, 'Mathematics', '100', '2026-01-30', '22', ''),
(8, 20, 'Mathematics', '100', '2026-01-30', '44', ''),
(9, 21, 'Mathematics', '100', '2026-01-30', '65', ''),
(10, 22, 'Mathematics', '100', '2026-01-30', '65', ''),
(11, 23, 'Mathematics', '100', '2026-01-30', '45', '');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `class_id`, `name`, `dob`, `gender`, `email`, `phone`) VALUES
(1, 1, 'sheriyar', '0000-00-00', 'male', 'khan@gmail.com', '03086367941'),
(4, 1, 'Ali Khan', '2012-01-05', 'male', 'ali1a1@gmail.com', '0301000001'),
(5, 1, 'Ahmed Raza', '2012-02-10', 'male', 'ali1a2@gmail.com', '0301000002'),
(6, 1, 'Hassan Ali', '2012-03-15', 'male', 'ali1a3@gmail.com', '0301000003'),
(7, 1, 'Usman Tariq', '2012-04-20', 'male', 'ali1a4@gmail.com', '0301000004'),
(8, 1, 'Bilal Ahmed', '2012-05-25', 'male', 'ali1a5@gmail.com', '0301000005'),
(9, 1, 'Ayaan Khan', '2012-06-10', 'male', 'ali1a6@gmail.com', '0301000006'),
(10, 1, 'Saad Malik', '2012-07-18', 'male', 'ali1a7@gmail.com', '0301000007'),
(11, 1, 'Hamza Noor', '2012-08-22', 'male', 'ali1a8@gmail.com', '0301000008'),
(12, 1, 'Zain Abbas', '2012-09-12', 'male', 'ali1a9@gmail.com', '0301000009'),
(13, 1, 'Daniyal Shah', '2012-10-30', 'male', 'ali1a10@gmail.com', '0301000010'),
(14, 2, 'Sara Khan', '2012-01-07', 'female', 'sara1b1@gmail.com', '0302000001'),
(15, 2, 'Ayesha Noor', '2012-02-14', 'female', 'sara1b2@gmail.com', '0302000002'),
(16, 2, 'Fatima Ali', '2012-03-19', 'female', 'sara1b3@gmail.com', '0302000003'),
(17, 2, 'Hira Raza', '2012-04-23', 'female', 'sara1b4@gmail.com', '0302000004'),
(18, 2, 'Iqra Ahmed', '2012-05-27', 'female', 'sara1b5@gmail.com', '0302000005'),
(19, 2, 'Zara Khan', '2012-06-11', 'female', 'sara1b6@gmail.com', '0302000006'),
(20, 2, 'Maham Tariq', '2012-07-21', 'female', 'sara1b7@gmail.com', '0302000007'),
(21, 2, 'Noor Fatima', '2012-08-25', 'female', 'sara1b8@gmail.com', '0302000008'),
(22, 2, 'Anaya Shah', '2012-09-13', 'female', 'sara1b9@gmail.com', '0302000009'),
(23, 2, 'Laiba Malik', '2012-10-29', 'female', 'sara1b10@gmail.com', '0302000010'),
(24, 3, 'Ali Khan', '2012-01-05', 'male', 'ali1a1@gmail.com', '0301000001'),
(25, 3, 'Ahmed Raza', '2012-02-10', 'male', 'ali1a2@gmail.com', '0301000002'),
(26, 3, 'Hassan Ali', '2012-03-15', 'male', 'ali1a3@gmail.com', '0301000003'),
(27, 3, 'Usman Tariq', '2012-04-20', 'male', 'ali1a4@gmail.com', '0301000004'),
(28, 3, 'Bilal Ahmed', '2012-05-25', 'male', 'ali1a5@gmail.com', '0301000005'),
(29, 3, 'Ayaan Khan', '2012-06-10', 'male', 'ali1a6@gmail.com', '0301000006'),
(30, 3, 'Saad Malik', '2012-07-18', 'male', 'ali1a7@gmail.com', '0301000007'),
(31, 3, 'Hamza Noor', '2012-08-22', 'male', 'ali1a8@gmail.com', '0301000008'),
(32, 3, 'Zain Abbas', '2012-09-12', 'male', 'ali1a9@gmail.com', '0301000009'),
(33, 3, 'Daniyal Shah', '2012-10-30', 'male', 'ali1a10@gmail.com', '0301000010'),
(34, 4, 'Sara Khan', '2012-01-07', 'female', 'sara1b1@gmail.com', '0302000001'),
(35, 4, 'Ayesha Noor', '2012-02-14', 'female', 'sara1b2@gmail.com', '0302000002'),
(36, 4, 'Fatima Ali', '2012-03-19', 'female', 'sara1b3@gmail.com', '0302000003'),
(37, 4, 'Hira Raza', '2012-04-23', 'female', 'sara1b4@gmail.com', '0302000004'),
(38, 4, 'Iqra Ahmed', '2012-05-27', 'female', 'sara1b5@gmail.com', '0302000005'),
(39, 4, 'Zara Khan', '2012-06-11', 'female', 'sara1b6@gmail.com', '0302000006'),
(40, 4, 'Maham Tariq', '2012-07-21', 'female', 'sara1b7@gmail.com', '0302000007'),
(41, 4, 'Noor Fatima', '2012-08-25', 'female', 'sara1b8@gmail.com', '0302000008'),
(42, 4, 'Anaya Shah', '2012-09-13', 'female', 'sara1b9@gmail.com', '0302000009'),
(43, 4, 'Laiba Malik', '2012-10-29', 'female', 'sara1b10@gmail.com', '0302000010'),
(44, 5, 'Ali Khan', '2012-01-05', 'male', 'ali1a1@gmail.com', '0301000001'),
(45, 5, 'Ahmed Raza', '2012-02-10', 'male', 'ali1a2@gmail.com', '0301000002'),
(46, 5, 'Hassan Ali', '2012-03-15', 'male', 'ali1a3@gmail.com', '0301000003'),
(47, 5, 'Usman Tariq', '2012-04-20', 'male', 'ali1a4@gmail.com', '0301000004'),
(48, 5, 'Bilal Ahmed', '2012-05-25', 'male', 'ali1a5@gmail.com', '0301000005'),
(49, 5, 'Ayaan Khan', '2012-06-10', 'male', 'ali1a6@gmail.com', '0301000006'),
(50, 5, 'Saad Malik', '2012-07-18', 'male', 'ali1a7@gmail.com', '0301000007'),
(51, 5, 'Hamza Noor', '2012-08-22', 'male', 'ali1a8@gmail.com', '0301000008'),
(52, 5, 'Zain Abbas', '2012-09-12', 'male', 'ali1a9@gmail.com', '0301000009'),
(53, 5, 'Daniyal Shah', '2012-10-30', 'male', 'ali1a10@gmail.com', '0301000010'),
(54, 6, 'Sara Khan', '2012-01-07', 'female', 'sara1b1@gmail.com', '0302000001'),
(55, 6, 'Ayesha Noor', '2012-02-14', 'female', 'sara1b2@gmail.com', '0302000002'),
(56, 6, 'Fatima Ali', '2012-03-19', 'female', 'sara1b3@gmail.com', '0302000003'),
(57, 6, 'Hira Raza', '2012-04-23', 'female', 'sara1b4@gmail.com', '0302000004'),
(58, 6, 'Iqra Ahmed', '2012-05-27', 'female', 'sara1b5@gmail.com', '0302000005'),
(59, 6, 'Zara Khan', '2012-06-11', 'female', 'sara1b6@gmail.com', '0302000006'),
(60, 6, 'Maham Tariq', '2012-07-21', 'female', 'sara1b7@gmail.com', '0302000007'),
(61, 6, 'Noor Fatima', '2012-08-25', 'female', 'sara1b8@gmail.com', '0302000008'),
(62, 6, 'Anaya Shah', '2012-09-13', 'female', 'sara1b9@gmail.com', '0302000009'),
(63, 6, 'Laiba Malik', '2012-10-29', 'female', 'sara1b10@gmail.com', '0302000010'),
(64, 7, 'Ali Khan', '2012-01-05', 'male', 'ali1a1@gmail.com', '0301000001'),
(65, 7, 'Ahmed Raza', '2012-02-10', 'male', 'ali1a2@gmail.com', '0301000002'),
(66, 7, 'Hassan Ali', '2012-03-15', 'male', 'ali1a3@gmail.com', '0301000003'),
(67, 7, 'Usman Tariq', '2012-04-20', 'male', 'ali1a4@gmail.com', '0301000004'),
(68, 7, 'Bilal Ahmed', '2012-05-25', 'male', 'ali1a5@gmail.com', '0301000005'),
(69, 7, 'Ayaan Khan', '2012-06-10', 'male', 'ali1a6@gmail.com', '0301000006'),
(70, 7, 'Saad Malik', '2012-07-18', 'male', 'ali1a7@gmail.com', '0301000007'),
(71, 7, 'Hamza Noor', '2012-08-22', 'male', 'ali1a8@gmail.com', '0301000008'),
(72, 7, 'Zain Abbas', '2012-09-12', 'male', 'ali1a9@gmail.com', '0301000009'),
(73, 7, 'Daniyal Shah', '2012-10-30', 'male', 'ali1a10@gmail.com', '0301000010'),
(74, 8, 'Sara Khan', '2012-01-07', 'female', 'sara1b1@gmail.com', '0302000001'),
(75, 8, 'Ayesha Noor', '2012-02-14', 'female', 'sara1b2@gmail.com', '0302000002'),
(76, 8, 'Fatima Ali', '2012-03-19', 'female', 'sara1b3@gmail.com', '0302000003'),
(77, 8, 'Hira Raza', '2012-04-23', 'female', 'sara1b4@gmail.com', '0302000004'),
(78, 8, 'Iqra Ahmed', '2012-05-27', 'female', 'sara1b5@gmail.com', '0302000005'),
(79, 8, 'Zara Khan', '2012-06-11', 'female', 'sara1b6@gmail.com', '0302000006'),
(80, 8, 'Maham Tariq', '2012-07-21', 'female', 'sara1b7@gmail.com', '0302000007'),
(81, 8, 'Noor Fatima', '2012-08-25', 'female', 'sara1b8@gmail.com', '0302000008'),
(82, 8, 'Anaya Shah', '2012-09-13', 'female', 'sara1b9@gmail.com', '0302000009'),
(83, 8, 'Laiba Malik', '2012-10-29', 'female', 'sara1b10@gmail.com', '0302000010'),
(84, 9, 'Ali Khan', '2012-01-05', 'male', 'ali1a1@gmail.com', '0301000001'),
(85, 9, 'Ahmed Raza', '2012-02-10', 'male', 'ali1a2@gmail.com', '0301000002'),
(86, 9, 'Hassan Ali', '2012-03-15', 'male', 'ali1a3@gmail.com', '0301000003'),
(87, 9, 'Usman Tariq', '2012-04-20', 'male', 'ali1a4@gmail.com', '0301000004'),
(88, 9, 'Bilal Ahmed', '2012-05-25', 'male', 'ali1a5@gmail.com', '0301000005'),
(89, 9, 'Ayaan Khan', '2012-06-10', 'male', 'ali1a6@gmail.com', '0301000006'),
(90, 9, 'Saad Malik', '2012-07-18', 'male', 'ali1a7@gmail.com', '0301000007'),
(91, 9, 'Hamza Noor', '2012-08-22', 'male', 'ali1a8@gmail.com', '0301000008'),
(92, 9, 'Zain Abbas', '2012-09-12', 'male', 'ali1a9@gmail.com', '0301000009'),
(93, 9, 'Daniyal Shah', '2012-10-30', 'male', 'ali1a10@gmail.com', '0301000010'),
(94, 10, 'Sara Khan', '2012-01-07', 'female', 'sara1b1@gmail.com', '0302000001'),
(95, 10, 'Ayesha Noor', '2012-02-14', 'female', 'sara1b2@gmail.com', '0302000002'),
(96, 10, 'Fatima Ali', '2012-03-19', 'female', 'sara1b3@gmail.com', '0302000003'),
(97, 10, 'Hira Raza', '2012-04-23', 'female', 'sara1b4@gmail.com', '0302000004'),
(98, 10, 'Iqra Ahmed', '2012-05-27', 'female', 'sara1b5@gmail.com', '0302000005'),
(99, 10, 'Zara Khan', '2012-06-11', 'female', 'sara1b6@gmail.com', '0302000006'),
(100, 10, 'Maham Tariq', '2012-07-21', 'female', 'sara1b7@gmail.com', '0302000007'),
(101, 10, 'Noor Fatima', '2012-08-25', 'female', 'sara1b8@gmail.com', '0302000008'),
(102, 10, 'Anaya Shah', '2012-09-13', 'female', 'sara1b9@gmail.com', '0302000009'),
(103, 10, 'Laiba Malik', '2012-10-29', 'female', 'sara1b10@gmail.com', '0302000010');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `subject` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `assigned_classes` varchar(100) NOT NULL,
  `experience` varchar(50) NOT NULL,
  `address` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `name`, `subject`, `email`, `phone`, `status`, `assigned_classes`, `experience`, `address`) VALUES
(1, 'Amir', 'Science', 'AMIR@gmail.com', '3333333', 'active', '4,10', '5', 'kaka khel'),
(2, 'shaffan', 'Mathematics', 'choudharyshaffan@gmail.com', '03019864779', 'active', '3,9,10', '3', 'bb'),
(3, 'Muhammad Aslam', 'Math', 'aslam1@gmail.com', '0311000001', 'Active', '1A,1B', '8', 'Peshawar'),
(4, 'Sadia Noor', 'English', 'sadia2@gmail.com', '0311000002', 'Active', '2A,2B', '6', 'Peshawar'),
(5, 'Imran Khan', 'Physics', 'imran3@gmail.com', '0311000003', 'Active', '3A,3B', '10', 'Mardan'),
(6, 'Ayesha Malik', 'Chemistry', 'ayesha4@gmail.com', '0311000004', 'Active', '4A,4B', '7', 'Charsadda'),
(7, 'Naveed Akhtar', 'Biology', 'naveed5@gmail.com', '0311000005', 'Active', '5A,5B', '12', 'Peshawar'),
(8, 'Hina Shah', 'Urdu', 'hina6@gmail.com', '0311000006', 'Active', '1A', '5', 'Swabi'),
(9, 'Salman Raza', 'Islamiyat', 'salman7@gmail.com', '0311000007', 'Active', '1B', '9', 'Mardan'),
(10, 'Farah Iqbal', 'Pak Studies', 'farah8@gmail.com', '0311000008', 'Active', '2A', '6', 'Peshawar'),
(11, 'Kamran Ali', 'Computer', 'kamran9@gmail.com', '0311000009', 'Active', '2B', '11', 'Nowshera'),
(12, 'Sana Noor', 'Math', 'sana10@gmail.com', '0311000010', 'Active', '3A', '4', 'Swabi'),
(13, 'Adnan Khan', 'English', 'adnan11@gmail.com', '0311000011', 'Active', '3B', '8', 'Peshawar'),
(14, 'Rabia Akram', 'Urdu', 'rabia12@gmail.com', '0311000012', 'Active', '4A', '7', 'Charsadda'),
(15, 'Tariq Mehmood', 'Physics', 'tariq13@gmail.com', '0311000013', 'Active', '4B', '13', 'Mardan'),
(16, 'Nida Abbas', 'Chemistry', 'nida14@gmail.com', '0311000014', 'Active', '5A', '5', 'Peshawar'),
(17, 'Faisal Khan', 'Biology', 'faisal15@gmail.com', '0311000015', 'Active', '5B', '9', 'Swabi'),
(18, 'Zeeshan Ali', 'Math', 'zeeshan16@gmail.com', '0311000016', 'Active', '1A,2A', '14', 'Peshawar'),
(19, 'Bushra Khan', 'English', 'bushra17@gmail.com', '0311000017', 'Active', '1B,2B', '6', 'Mardan'),
(20, 'Usman Farooq', 'Computer', 'usman18@gmail.com', '0311000018', 'Active', '3A,4A', '10', 'Peshawar'),
(21, 'Kiran Malik', 'Islamiyat', 'kiran19@gmail.com', '0311000019', 'Active', '3B,4B', '7', 'Swabi'),
(22, 'Shahid Afridi', 'Sports', 'shahid20@gmail.com', '0311000020', 'Active', '5A,5B', '15', 'Charsadda'),
(23, 'Noman Raza', 'Science', 'noman21@gmail.com', '0311000021', 'Active', '1A', '8', 'Mardan'),
(24, 'Alina Noor', 'English', 'alina22@gmail.com', '0311000022', 'Active', '1B', '4', 'Peshawar'),
(25, 'Waqar Ahmed', 'Math', 'waqar23@gmail.com', '0311000023', 'Active', '2A', '12', 'Nowshera'),
(26, 'Saira Khan', 'Urdu', 'saira24@gmail.com', '0311000024', 'Active', '2B', '6', 'Swabi'),
(27, 'Bilal Hussain', 'Physics', 'bilal25@gmail.com', '0311000025', 'Active', '3A', '9', 'Peshawar'),
(28, 'Hammad Ali', 'Chemistry', 'hammad26@gmail.com', '0311000026', 'Active', '3B', '11', 'Mardan'),
(29, 'Saima Iqbal', 'Biology', 'saima27@gmail.com', '0311000027', 'Active', '4A', '7', 'Peshawar'),
(30, 'Junaid Khan', 'Computer', 'junaid28@gmail.com', '0311000028', 'Active', '4B', '10', 'Swabi'),
(31, 'Maryam Shah', 'English', 'maryam29@gmail.com', '0311000029', 'Active', '5A', '5', 'Charsadda'),
(32, 'Rizwan Akhtar', 'Math', 'rizwan30@gmail.com', '0311000030', 'Active', '5B', '13', 'Peshawar');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `sid` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `pasword` varchar(255) DEFAULT NULL,
  `roles` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`sid`, `name`, `email`, `pasword`, `roles`, `created_at`) VALUES
(1, 'shaffan', 'choudharyshaffan@gmail.com', '$2y$10$7MV.eeS82Ako5yyYiVRhBeMSdjkUluKDlxN/cDRaWeFpm0DHa9g2O', 'admin', '2026-01-27 19:00:00'),
(2, 'ahmad', 'khansheriyar487@gmail.com', '$2y$10$7MV.eeS82Ako5yyYiVRhBeMSdjkUluKDlxN/cDRaWeFpm0DHa9g2O', 'teacher', '2026-01-27 19:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_students_classes` (`class_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`sid`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `sid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_classes` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
