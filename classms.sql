-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 12, 2026 at 08:04 PM
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
-- Database: `classms`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `teacher_id`, `subject_id`, `title`, `message`, `created_at`) VALUES
(1, 2, 1, 'Grade 10 Maths - Extra Class', 'Extra class on Saturday 2PM in Room 2.', '2026-02-10 16:37:48'),
(2, 3, 2, 'Grade 10 Science - Lab', 'Bring lab coat and notebook for next lab session.', '2026-02-10 16:37:48'),
(3, 4, NULL, 'General Notice', 'School will have a small test next week. Prepare well!', '2026-02-10 16:37:48'),
(4, 25, 22, 'fgjhkj', 'dghthjr', '2026-02-12 18:40:04');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_replies`
--

CREATE TABLE `announcement_replies` (
  `id` int(10) UNSIGNED NOT NULL,
  `announcement_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `reply_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcement_replies`
--

INSERT INTO `announcement_replies` (`id`, `announcement_id`, `student_id`, `reply_text`, `created_at`) VALUES
(1, 1, 5, 'Thank you teacher, I will attend.', '2026-02-10 16:37:48'),
(2, 2, 6, 'Noted, I will bring lab coat.', '2026-02-10 16:37:48'),
(3, 3, 9, 'Okay, thank you for informing us.', '2026-02-10 16:37:48'),
(5, 4, 24, 'ok', '2026-02-12 18:45:17');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `subject_id`, `teacher_id`, `title`, `description`, `due_date`, `created_at`) VALUES
(1, 1, 2, 'Algebra Worksheet', 'Solve the given algebra problems.', '2026-02-20', '2026-02-10 16:37:48'),
(2, 2, 3, 'Science Report', 'Write a short report about ecosystems.', '2026-02-22', '2026-02-10 16:37:48'),
(3, 4, 2, 'Trigonometry Basics', 'Practice trigonometry questions.', '2026-02-25', '2026-02-10 16:37:48'),
(4, 6, 4, 'Essay Writing', 'Write an essay about education.', '2026-02-28', '2026-02-10 16:37:48'),
(5, 22, 25, 'vbnfghnf', 'ghjjyh', '2026-02-12', '2026-02-12 18:12:26');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `student_id` int(10) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL,
  `marked_at` datetime NOT NULL DEFAULT current_timestamp(),
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`student_id`, `attendance_date`, `status`, `marked_at`, `note`) VALUES
(5, '2026-02-10', 'present', '2026-02-10 22:07:48', ''),
(6, '2026-02-10', 'late', '2026-02-10 22:07:48', 'Traffic'),
(9, '2026-02-10', 'present', '2026-02-10 22:07:48', ''),
(12, '2026-02-10', 'absent', '2026-02-10 22:07:48', 'Sick'),
(24, '2026-02-13', 'present', '2026-02-13 00:06:23', 'good boy');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `sender_role` enum('student','teacher') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `seen_by_student` tinyint(1) NOT NULL DEFAULT 0,
  `seen_by_teacher` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `student_id`, `teacher_id`, `sender_role`, `message`, `created_at`, `seen_by_student`, `seen_by_teacher`) VALUES
(1, 5, 2, 'student', 'Sir, can you explain question 5?', '2026-02-10 16:37:48', 1, 1),
(2, 5, 2, 'teacher', 'Yes Amal, I will explain in the next class.', '2026-02-10 16:37:48', 1, 0),
(3, 6, 3, 'student', 'Teacher, what is the format for the report?', '2026-02-10 16:37:48', 0, 1),
(4, 24, 25, 'teacher', 'hi', '2026-02-12 18:38:41', 1, 1),
(5, 24, 25, 'teacher', 'hi', '2026-02-12 18:39:18', 1, 1),
(6, 24, 25, 'teacher', 'vkjh', '2026-02-12 19:02:42', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `student_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`student_id`, `subject_id`, `created_at`) VALUES
(5, 1, '2026-02-10 16:37:48'),
(5, 2, '2026-02-10 16:37:48'),
(5, 3, '2026-02-10 16:37:48'),
(6, 1, '2026-02-10 16:37:48'),
(6, 2, '2026-02-10 16:37:48'),
(7, 1, '2026-02-10 16:37:48'),
(7, 3, '2026-02-10 16:37:48'),
(8, 2, '2026-02-10 16:37:48'),
(8, 3, '2026-02-10 16:37:48'),
(9, 4, '2026-02-10 16:37:48'),
(9, 5, '2026-02-10 16:37:48'),
(9, 6, '2026-02-10 16:37:48'),
(10, 4, '2026-02-10 16:37:48'),
(10, 6, '2026-02-10 16:37:48'),
(11, 5, '2026-02-10 16:37:48'),
(11, 6, '2026-02-10 16:37:48'),
(12, 4, '2026-02-10 16:37:48'),
(12, 5, '2026-02-10 16:37:48'),
(13, 1, '2026-02-10 16:37:48'),
(13, 2, '2026-02-10 16:37:48'),
(14, 2, '2026-02-10 16:37:48'),
(14, 3, '2026-02-10 16:37:48'),
(15, 4, '2026-02-10 16:37:48'),
(15, 5, '2026-02-10 16:37:48'),
(16, 4, '2026-02-10 16:37:48'),
(16, 6, '2026-02-10 16:37:48'),
(17, 1, '2026-02-10 16:37:48'),
(17, 3, '2026-02-10 16:37:48'),
(18, 2, '2026-02-10 16:37:48'),
(19, 5, '2026-02-10 16:37:48'),
(20, 6, '2026-02-10 16:37:48');

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `grade` varchar(20) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(120) DEFAULT NULL,
  `guardian_phone` varchar(30) DEFAULT NULL,
  `dob` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_profiles`
--

INSERT INTO `student_profiles` (`user_id`, `grade`, `address`, `guardian_name`, `guardian_phone`, `dob`) VALUES
(5, '10', 'Colombo', 'Guardian Amal', '0712000001', '2009-03-12'),
(6, '10', 'Gampaha', 'Guardian Nethu', '0712000002', '2009-07-02'),
(7, '10', 'Kandy', 'Guardian Ishan', '0712000003', '2009-01-18'),
(8, '10', 'Negombo', 'Guardian Tharindu', '0712000004', '2009-11-21'),
(9, '11', 'Matara', 'Guardian Hasini', '0712000005', '2008-05-09'),
(10, '11', 'Kurunegala', 'Guardian Kasun', '0712000006', '2008-09-14'),
(11, '11', 'Jaffna', 'Guardian Oshadi', '0712000007', '2008-02-25'),
(12, '11', 'Panadura', 'Guardian Supun', '0712000008', '2008-12-01'),
(13, '10', 'Kalutara', 'Guardian Sachini', '0712000009', '2009-04-30'),
(14, '10', 'Hambantota', 'Guardian Naveen', '0712000010', '2009-06-16'),
(15, '11', 'Badulla', 'Guardian Kavindi', '0712000011', '2008-08-08'),
(16, '11', 'Galle', 'Guardian Pasindu', '0712000012', '2008-10-27'),
(17, '10', 'Nugegoda', 'Guardian Dinu', '0712000013', '2009-09-05'),
(18, '10', 'Moratuwa', 'Guardian Shalani', '0712000014', '2009-02-10'),
(19, '11', 'Anuradhapura', 'Guardian Ravindu', '0712000015', '2008-03-03'),
(20, '11', 'Ratnapura', 'Guardian Minoli', '0712000016', '2008-07-19'),
(23, '6', NULL, NULL, NULL, NULL),
(24, '6', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `grade` varchar(20) NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `grade`, `teacher_id`, `status`, `created_at`) VALUES
(1, 'Mathematics', '10', 2, 'approved', '2026-02-10 16:37:48'),
(2, 'Science', '10', 3, 'approved', '2026-02-10 16:37:48'),
(3, 'English', '10', 4, 'approved', '2026-02-10 16:37:48'),
(4, 'Mathematics', '11', 2, 'approved', '2026-02-10 16:37:48'),
(5, 'Physics', '11', 3, 'approved', '2026-02-10 16:37:48'),
(6, 'English', '11', 4, 'approved', '2026-02-10 16:37:48'),
(17, 'music', '6', 25, 'approved', '2026-02-12 18:10:22'),
(18, 'Mathematics', '6', 25, 'approved', '2026-02-12 18:11:30'),
(19, 'Science', '6', 25, 'approved', '2026-02-12 18:11:30'),
(20, 'English', '6', 25, 'approved', '2026-02-12 18:11:30'),
(21, 'Sinhala', '6', 25, 'approved', '2026-02-12 18:11:30'),
(22, 'Buddhism', '6', 25, 'approved', '2026-02-12 18:11:30'),
(23, 'History', '6', 25, 'approved', '2026-02-12 18:11:30'),
(24, 'Geography', '6', 25, 'approved', '2026-02-12 18:11:30'),
(25, 'ICT', '6', 25, 'approved', '2026-02-12 18:11:30'),
(26, 'Health & PE', '6', 25, 'approved', '2026-02-12 18:11:30');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `assignment_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `marks` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `seen_by_student` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`assignment_id`, `student_id`, `file_path`, `submitted_at`, `marks`, `feedback`, `seen_by_student`) VALUES
(1, 5, 'uploads/submissions/s01_a1.pdf', '2026-02-12 10:30:00', 85, 'Good work.', 1),
(2, 6, 'uploads/submissions/s02_a2.docx', '2026-02-13 14:10:00', 78, 'Improve conclusion.', 0),
(5, 24, '/classms/uploads/submissions/sub_24_5_1770921868.pdf', '2026-02-13 00:14:28', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `term_marks`
--

CREATE TABLE `term_marks` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `term` varchar(20) NOT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `grade` varchar(2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `term_marks`
--

INSERT INTO `term_marks` (`id`, `student_id`, `subject_id`, `term`, `marks`, `grade`, `created_at`, `updated_at`) VALUES
(1, 24, 22, 'Term 1', 76.00, 'A', '2026-02-13 00:04:46', '2026-02-13 00:06:45'),
(2, 24, 20, 'Term 1', 76.00, 'A', '2026-02-13 00:04:46', '2026-02-13 00:06:45'),
(3, 24, 24, 'Term 1', 76.00, 'A', '2026-02-13 00:04:46', '2026-02-13 00:06:45'),
(4, 24, 26, 'Term 1', 76.00, 'A', '2026-02-13 00:04:46', '2026-02-13 00:06:45'),
(5, 24, 23, 'Term 1', 76.00, 'A', '2026-02-13 00:04:46', '2026-02-13 00:06:45'),
(6, 24, 25, 'Term 1', 76.00, 'A', '2026-02-13 00:04:46', '2026-02-13 00:06:45'),
(7, 24, 18, 'Term 1', 76.00, 'A', '2026-02-13 00:04:46', '2026-02-13 00:06:45'),
(8, 24, 17, 'Term 1', 76.00, 'A', '2026-02-13 00:04:46', '2026-02-13 00:06:45'),
(9, 24, 19, 'Term 1', 76.00, 'A', '2026-02-13 00:04:46', '2026-02-13 00:06:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL DEFAULT 'student',
  `full_name` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `full_name`, `email`, `phone`, `password_hash`, `created_at`) VALUES
(1, 'admin', 'System Admin', 'admin@classms.com', '0770000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(2, 'teacher', 'Teacher Nimal Perera', 'nimal.teacher@classms.com', '0770000002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(3, 'teacher', 'Teacher Kumari Silva', 'kumari.teacher@classms.com', '0770000003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(4, 'teacher', 'Teacher Dilshan Fernando', 'dilshan.teacher@classms.com', '0770000004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(5, 'student', 'Student 01 - Amal', 's01@classms.com', '0771000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(6, 'student', 'Student 02 - Nethu', 's02@classms.com', '0771000002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(7, 'student', 'Student 03 - Ishan', 's03@classms.com', '0771000003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(8, 'student', 'Student 04 - Tharindu', 's04@classms.com', '0771000004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(9, 'student', 'Student 05 - Hasini', 's05@classms.com', '0771000005', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(10, 'student', 'Student 06 - Kasun', 's06@classms.com', '0771000006', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(11, 'student', 'Student 07 - Oshadi', 's07@classms.com', '0771000007', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(12, 'student', 'Student 08 - Supun', 's08@classms.com', '0771000008', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(13, 'student', 'Student 09 - Sachini', 's09@classms.com', '0771000009', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(14, 'student', 'Student 10 - Naveen', 's10@classms.com', '0771000010', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(15, 'student', 'Student 11 - Kavindi', 's11@classms.com', '0771000011', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(16, 'student', 'Student 12 - Pasindu', 's12@classms.com', '0771000012', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(17, 'student', 'Student 13 - Dinu', 's13@classms.com', '0771000013', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(18, 'student', 'Student 14 - Shalani', 's14@classms.com', '0771000014', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(19, 'student', 'Student 15 - Ravindu', 's15@classms.com', '0771000015', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(20, 'student', 'Student 16 - Minoli', 's16@classms.com', '0771000016', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-10 16:37:47'),
(23, 'student', 'isurusampath', 'isbandara1022@gmail.com', '0760558956', '$2y$10$2j79NAmMaJDSNekzpiJ0c.TsuWvfPzCKng7fkKyshmMKGidgMVNuK', '2026-02-12 17:56:39'),
(24, 'student', 'amal', 'amal@gmail.com', '0760558956', '$2y$10$OtoMj9kHFvPtVN/g8yMv9uGwMp/V072e2AVJ2U9nqE1RhFF8YME5S', '2026-02-12 18:02:20'),
(25, 'teacher', 'Dehemi meedeniya', 'Dehemimeedeniya@gmail.com', '0760558956', '$2y$10$pMfrc8XW52PbqRg/FCTFF.Ic0riPnbsAbama0kzjdAGBmnXybKIgC', '2026-02-12 18:08:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `announcement_replies`
--
ALTER TABLE `announcement_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcement_id` (`announcement_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`student_id`,`attendance_date`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`student_id`,`subject_id`),
  ADD KEY `fk_enroll_subject` (`subject_id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`assignment_id`,`student_id`),
  ADD KEY `fk_sub_student` (`student_id`);

--
-- Indexes for table `term_marks`
--
ALTER TABLE `term_marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_student_subject_term` (`student_id`,`subject_id`,`term`),
  ADD KEY `idx_student_term` (`student_id`,`term`),
  ADD KEY `idx_subject` (`subject_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `announcement_replies`
--
ALTER TABLE `announcement_replies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `term_marks`
--
ALTER TABLE `term_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_announce_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_announce_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `announcement_replies`
--
ALTER TABLE `announcement_replies`
  ADD CONSTRAINT `fk_reply_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reply_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `fk_assign_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assign_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_att_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_chat_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_chat_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `fk_enroll_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enroll_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `fk_student_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `fk_subjects_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `fk_sub_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sub_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
