-- School Notes System
-- Allows teachers and admins to create notes with priority levels
-- High priority notes appear on admin dashboard
-- Created: 2025-12-19

CREATE TABLE IF NOT EXISTS `school_notes` (
  `note_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `note_content` text NOT NULL,
  `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `category` enum('Student','Classroom','Facility','General') NOT NULL DEFAULT 'General',
  `related_student_id` int(11) DEFAULT NULL,
  `related_class_id` int(11) DEFAULT NULL,
  `status` enum('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`note_id`),
  KEY `created_by` (`created_by`),
  KEY `related_student_id` (`related_student_id`),
  KEY `related_class_id` (`related_class_id`),
  KEY `resolved_by` (`resolved_by`),
  KEY `priority` (`priority`),
  KEY `status` (`status`),
  CONSTRAINT `school_notes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `school_notes_ibfk_2` FOREIGN KEY (`related_student_id`) REFERENCES `students` (`student_id`) ON DELETE SET NULL,
  CONSTRAINT `school_notes_ibfk_3` FOREIGN KEY (`related_class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL,
  CONSTRAINT `school_notes_ibfk_4` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
