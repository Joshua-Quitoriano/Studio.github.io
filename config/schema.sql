-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2025 at 04:06 AM
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
-- Database: `socmed`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `academic_info_id` int(11) NOT NULL,
  `preferred_date` date NOT NULL,
  `preferred_time` time NOT NULL,
  `actual_date` date DEFAULT NULL,
  `actual_time` time DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed','cancelled') DEFAULT 'pending',
  `processed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointment_schedules`
--

CREATE TABLE `appointment_schedules` (
  `id` int(11) NOT NULL,
  `student_type` enum('regular_college','octoberian','senior_high') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `time_slots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`time_slots`)),
  `max_appointments_per_slot` int(11) NOT NULL DEFAULT 3,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_courses`
--

CREATE TABLE `college_courses` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('regular','octoberian') NOT NULL DEFAULT 'regular',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- User Login Summary Table
CREATE TABLE IF NOT EXISTS `user_login_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `login_date` DATE NOT NULL,
  `first_login` timestamp NULL DEFAULT NULL,
  `last_logout` timestamp NULL DEFAULT NULL,
  `login_count` int(11) DEFAULT 0,
  `failed_attempts` int(11) DEFAULT 0,
  `last_ip_address` varchar(45) DEFAULT NULL,
  `last_user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_date` (`user_id`, `login_date`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_login_summary_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `college_courses`
--

INSERT INTO `college_courses` (`id`, `code`, `name`, `type`, `created_at`) VALUES
(1, 'BSIT', 'Bachelor of Science in Information Technology', 'regular', '2025-03-06 14:49:18'),
(2, 'BSP', 'Bachelor of Science in Psychology', 'regular', '2025-03-06 14:49:18'),
(3, 'BSTM', 'Bachelor of Science in Tourism Management', 'regular', '2025-03-06 14:49:18'),
(4, 'BSHM', 'Bachelor of Science in Hospitality Management', 'regular', '2025-03-06 14:49:18'),
(5, 'BSBA', 'Bachelor of Science in Business Administration', 'regular', '2025-03-06 14:49:18'),
(6, 'BSOA', 'Bachelor of Science in Office Administration', 'regular', '2025-03-06 14:49:18'),
(7, 'BSCrim', 'Bachelor of Science in Criminology', 'regular', '2025-03-06 14:49:18'),
(8, 'BEEd', 'Bachelor of Elementary Education', 'regular', '2025-03-06 14:49:18'),
(9, 'BSEd', 'Bachelor of Secondary Education', 'regular', '2025-03-06 14:49:18'),
(10, 'BSCpE', 'Bachelor of Science in Computer Engineering', 'regular', '2025-03-06 14:49:18'),
(11, 'BSEntrep', 'Bachelor of Science in Entrepreneurship', 'regular', '2025-03-06 14:49:18'),
(12, 'BSAIS', 'Bachelor of Science in Accounting Information System', 'regular', '2025-03-06 14:49:18'),
(13, 'BLIS', 'Bachelor of Library and Information Science', 'regular', '2025-03-06 14:49:18');

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `octoberian_courses`
--

CREATE TABLE `octoberian_courses` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `octoberian_courses`
--

INSERT INTO `octoberian_courses` (`id`, `code`, `name`, `created_at`) VALUES
(1, 'BSIT', 'Bachelor of Science in Information Technology', '2025-03-06 15:12:45'),
(2, 'BSP', 'Bachelor of Science in Psychology', '2025-03-06 15:12:45'),
(3, 'BSTM', 'Bachelor of Science in Tourism Management', '2025-03-06 15:12:45'),
(4, 'BSHM', 'Bachelor of Science in Hospitality Management', '2025-03-06 15:12:45'),
(5, 'BSBA', 'Bachelor of Science in Business Administration', '2025-03-06 15:12:45'),
(6, 'BSOA', 'Bachelor of Science in Office Administration', '2025-03-06 15:12:45'),
(7, 'BSCrim', 'Bachelor of Science in Criminology', '2025-03-06 15:12:45'),
(8, 'BEEd', 'Bachelor of Elementary Education', '2025-03-06 15:12:45'),
(9, 'BSEd', 'Bachelor of Secondary Education', '2025-03-06 15:12:45'),
(10, 'BSCpE', 'Bachelor of Science in Computer Engineering', '2025-03-06 15:12:45'),
(11, 'BSEntrep', 'Bachelor of Science in Entrepreneurship', '2025-03-06 15:12:45'),
(12, 'BSAIS', 'Bachelor of Science in Accounting Information System', '2025-03-06 15:12:45'),
(13, 'BLIS', 'Bachelor of Library and Information Science', '2025-03-06 15:12:45');

-- --------------------------------------------------------

--
-- Table structure for table `shs_strands`
--

CREATE TABLE `shs_strands` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shs_strands`
--

INSERT INTO `shs_strands` (`id`, `code`, `name`, `created_at`) VALUES
(1, 'GAS', 'General Academic Strand', '2025-03-06 14:48:00'),
(2, 'ABM', 'Accountancy Business and Management', '2025-03-06 14:48:00'),
(3, 'HUMMS', 'Humanities and Social Sciences', '2025-03-06 14:48:00'),
(4, 'STEM', 'Science Technology Engineering and Mathematics', '2025-03-06 14:48:00'),
(5, 'ICT', 'Information and Computer Technology', '2025-03-06 14:48:00'),
(6, 'HE', 'Home Economics', '2025-03-06 14:48:00'),
(7, 'PA', 'Performing Arts', '2025-03-06 14:48:00'),
(8, 'IA', 'Industrial Arts', '2025-03-06 14:48:00');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `student_type` enum('regular_college','octoberian','senior_high') NOT NULL,
  `college_course_id` int(11) DEFAULT NULL,
  `shs_strand_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `student_academic_info`
--

CREATE TABLE `student_academic_info` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `section` varchar(20) DEFAULT NULL,

  `school_year` varchar(20) NOT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `college_course_id` int(11) DEFAULT NULL,
  `shs_strand_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `studio_photos`
--

CREATE TABLE `studio_photos` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `studio_sessions`
--

CREATE TABLE `studio_sessions` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `started_by` int(11) NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_time` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','studio') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `role`, `status`, `profile_picture`, `created_at`) VALUES
(1, 'Joshpogi', '$2y$10$dUk3.mmJ6YnHpUuhAOTuKehTMDyJpUsqlpBxi9jlyvryAxkqrPkaa', 'josh@gmail.com', '', 'admin', 'active', 'default', '2025-03-06 15:27:34'),
(2, 'CedPogi', '$2y$10$RfgQ67JReB6bvGo1vn/vw.A26apBGNSjWq/UYr6AvAPa85g94qRFO', 'cedrick@gmail.com', 'CedrickGliban', 'studio', 'active', NULL, '2025-03-06 16:40:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
CREATE TABLE `user_login_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `login_date` DATE NOT NULL,
  `first_login` timestamp NULL DEFAULT NULL,
  `last_logout` timestamp NULL DEFAULT NULL,
  `login_count` int(11) DEFAULT 0,
  `failed_attempts` int(11) DEFAULT 0,
  `last_ip_address` varchar(45) DEFAULT NULL,
  `last_user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_date` (`user_id`, `login_date`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_login_summary_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `academic_info_id` (`academic_info_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_appointment_status` (`status`);

--
-- Indexes for table `appointment_schedules`
--
ALTER TABLE `appointment_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `college_courses`
--
ALTER TABLE `college_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_code_type` (`code`,`type`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gallery_student` (`student_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `octoberian_courses`
--
ALTER TABLE `octoberian_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `shs_strands`
--
ALTER TABLE `shs_strands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_student_email` (`email`),
  ADD KEY `idx_student_name` (`last_name`,`first_name`),
  ADD KEY `idx_student_type` (`student_type`),
  ADD KEY `idx_student_course` (`college_course_id`),
  ADD KEY `idx_student_strand` (`shs_strand_id`);

--
-- Indexes for table `student_academic_info`
--
ALTER TABLE `student_academic_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_academic_info_student` (`student_id`),

  ADD KEY `idx_school_year` (`school_year`);

--
-- Indexes for table `studio_photos`
--
ALTER TABLE `studio_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_studio_photos_appointment` (`appointment_id`);

--
-- Indexes for table `studio_sessions`
--
ALTER TABLE `studio_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `started_by` (`started_by`),
  ADD KEY `idx_studio_sessions_appointment` (`appointment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointment_schedules`
--
ALTER TABLE `appointment_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `college_courses`
--
ALTER TABLE `college_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `octoberian_courses`
--
ALTER TABLE `octoberian_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `shs_strands`
--
ALTER TABLE `shs_strands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_academic_info`
--
ALTER TABLE `student_academic_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `studio_photos`
--
ALTER TABLE `studio_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `studio_sessions`
--
ALTER TABLE `studio_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`academic_info_id`) REFERENCES `student_academic_info` (`id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `appointment_schedules`
--
ALTER TABLE `appointment_schedules`
  ADD CONSTRAINT `appointment_schedules_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `gallery`
--
ALTER TABLE `gallery`
  ADD CONSTRAINT `gallery_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;


--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`college_course_id`) REFERENCES `college_courses` (`id`),
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`shs_strand_id`) REFERENCES `shs_strands` (`id`);

--
-- Constraints for table `student_academic_info`
--
ALTER TABLE `student_academic_info`
  ADD CONSTRAINT `student_academic_info_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `studio_photos`
--
ALTER TABLE `studio_photos`
  ADD CONSTRAINT `studio_photos_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`),
  ADD CONSTRAINT `studio_photos_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `studio_sessions`
--
ALTER TABLE `studio_sessions`
  ADD CONSTRAINT `studio_sessions_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`),
  ADD CONSTRAINT `studio_sessions_ibfk_2` FOREIGN KEY (`started_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
