-- School Management System Database Schema
-- Updated: 2025-12-18
-- Collation: utf8mb4_general_ci

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(4, 'accountant'),
(2, 'admin'),
(5, 'parent'),
(6, 'student'),
(1, 'super_admin'),
(3, 'teacher');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`username`, `password_hash`, `role_id`, `email`, `full_name`, `is_active`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'admin@school.com', 'Super Admin', 1);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_group` varchar(50) DEFAULT 'general',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `settings` (`setting_id`, `setting_key`, `setting_value`, `setting_group`) VALUES 
(1,'school_name','Sunrise high school','school'),
(2,'school_motto','Excellence in Education','school'),
(3,'current_year','2025','academic'),
(4,'current_term','1','academic'),
(5,'currency_symbol','K','finance'),
(6,'enable_attendance','1','features'),
(7,'enable_parent_portal','1','features'),
(11,'school_logo','school_logo_1765860797.png','general'),
(16,'logo_position','above','school');

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `year_id` int(11) NOT NULL AUTO_INCREMENT,
  `year_name` varchar(20) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `terms`
--

CREATE TABLE `terms` (
  `term_id` int(11) NOT NULL AUTO_INCREMENT,
  `academic_year_id` int(11) NOT NULL,
  `term_name` varchar(50) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`term_id`),
  KEY `academic_year_id` (`academic_year_id`),
  CONSTRAINT `terms_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`year_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_name` varchar(50) NOT NULL,
  `section_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `admission_number` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `current_class_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `enrollment_date` date DEFAULT NULL,
  `status` enum('Active','Suspended','Alumni','Transferred') DEFAULT 'Active',
  `photo_path` varchar(255) DEFAULT NULL,
  `guardian1_name` varchar(100) DEFAULT NULL,
  `guardian1_contact` varchar(20) DEFAULT NULL,
  `guardian2_name` varchar(100) DEFAULT NULL,
  `guardian2_contact` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `admission_number` (`admission_number`),
  KEY `current_class_id` (`current_class_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`current_class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL,
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`teacher_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `exam_id` int(11) NOT NULL AUTO_INCREMENT,
  `term_id` int(11) NOT NULL,
  `exam_name` varchar(100) NOT NULL,
  `start_date` date DEFAULT NULL,
  PRIMARY KEY (`exam_id`),
  KEY `term_id` (`term_id`),
  CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`term_id`) REFERENCES `terms` (`term_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `result_id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`result_id`),
  KEY `exam_id` (`exam_id`),
  KEY `student_id` (`student_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE,
  CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `exam_results_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE `fee_structures` (
  `structure_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`structure_id`),
  KEY `class_id` (`class_id`),
  KEY `term_id` (`term_id`),
  CONSTRAINT `fee_structures_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structures_ibfk_2` FOREIGN KEY (`term_id`) REFERENCES `terms` (`term_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fees`
--

CREATE TABLE `student_fees` (
  `invoice_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) DEFAULT NULL,
  `student_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('Unpaid','Partial','Paid') DEFAULT 'Unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`invoice_id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `student_id` (`student_id`),
  KEY `term_id` (`term_id`),
  CONSTRAINT `student_fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `student_fees_ibfk_2` FOREIGN KEY (`term_id`) REFERENCES `terms` (`term_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `recorded_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `student_fees` (`invoice_id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`attendance_id`),
  KEY `student_id` (`student_id`),
  KEY `class_id` (`class_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_academic_history`
--

CREATE TABLE `student_academic_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `promoted_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `promoted_by` int(11) DEFAULT NULL,
  `final_status` enum('Promoted','Retained','Graduated','Transferred') DEFAULT 'Promoted',
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `student_id` (`student_id`),
  KEY `academic_year_id` (`academic_year_id`),
  KEY `class_id` (`class_id`),
  KEY `promoted_by` (`promoted_by`),
  CONSTRAINT `student_academic_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `student_academic_history_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`year_id`) ON DELETE CASCADE,
  CONSTRAINT `student_academic_history_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `student_academic_history_ibfk_4` FOREIGN KEY (`promoted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotion_batches`
--

CREATE TABLE `promotion_batches` (
  `batch_id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_name` varchar(100) NOT NULL,
  `from_year_id` int(11) NOT NULL,
  `to_year_id` int(11) NOT NULL,
  `promotion_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `promoted_by` int(11) DEFAULT NULL,
  `students_promoted` int(11) DEFAULT 0,
  `is_rolled_back` tinyint(1) DEFAULT 0,
  `rollback_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`batch_id`),
  KEY `from_year_id` (`from_year_id`),
  KEY `to_year_id` (`to_year_id`),
  KEY `promoted_by` (`promoted_by`),
  CONSTRAINT `promotion_batches_ibfk_1` FOREIGN KEY (`from_year_id`) REFERENCES `academic_years` (`year_id`) ON DELETE CASCADE,
  CONSTRAINT `promotion_batches_ibfk_2` FOREIGN KEY (`to_year_id`) REFERENCES `academic_years` (`year_id`) ON DELETE CASCADE,
  CONSTRAINT `promotion_batches_ibfk_3` FOREIGN KEY (`promoted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `event_type` enum('holiday','event','reminder','exam') NOT NULL DEFAULT 'event',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_messages`
--

CREATE TABLE `calendar_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_categories`
--

CREATE TABLE `library_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `library_books`
--

CREATE TABLE `library_books` (
  `book_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `year_published` int(4) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `available_copies` int(11) DEFAULT 0,
  `location_shelf` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`book_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `library_books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `library_categories` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `library_issuances`
--

CREATE TABLE `library_issuances` (
  `issuance_id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('Issued','Returned','Overdue','Lost') DEFAULT 'Issued',
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `fine_status` enum('N/A','Unpaid','Paid') DEFAULT 'N/A',
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`issuance_id`),
  KEY `book_id` (`book_id`),
  KEY `student_id` (`student_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `library_issuances_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `library_books` (`book_id`) ON DELETE CASCADE,
  CONSTRAINT `library_issuances_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `library_issuances_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
