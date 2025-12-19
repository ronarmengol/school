-- Student Academic History Table
-- Tracks which class a student was in for each academic year

CREATE TABLE IF NOT EXISTS `student_academic_history` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Promotion Batches Table
-- Tracks bulk promotion operations for rollback capability

CREATE TABLE IF NOT EXISTS `promotion_batches` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
