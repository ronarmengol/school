-- ============================================
-- MANUAL DATABASE MIGRATION INSTRUCTIONS
-- ============================================
-- 
-- This file contains the SQL to create the billing_audit_log table
-- needed for the Undo Billing feature.
--
-- HOW TO RUN:
-- Option 1: Via phpMyAdmin
--   1. Open phpMyAdmin (http://localhost/phpmyadmin)
--   2. Select the 'school' database
--   3. Click on the 'SQL' tab
--   4. Copy and paste the SQL below
--   5. Click 'Go'
--
-- Option 2: Via MySQL Command Line
--   1. Open MySQL command line
--   2. Run: USE school;
--   3. Copy and paste the SQL below
--   4. Press Enter
--
-- ============================================

-- Create the billing audit log table
CREATE TABLE IF NOT EXISTS `billing_audit_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `action_type` enum('UNDO_BILLING','BULK_BILLING') NOT NULL,
  `class_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `invoices_affected` int(11) DEFAULT 0,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `performed_by` int(11) NOT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `details` text DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `class_id` (`class_id`),
  KEY `term_id` (`term_id`),
  KEY `performed_by` (`performed_by`),
  CONSTRAINT `billing_audit_log_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `billing_audit_log_ibfk_2` FOREIGN KEY (`term_id`) REFERENCES `terms` (`term_id`) ON DELETE CASCADE,
  CONSTRAINT `billing_audit_log_ibfk_3` FOREIGN KEY (`performed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Verify the table was created
SELECT 'billing_audit_log table created successfully!' as Status;

-- Show the table structure
DESCRIBE billing_audit_log;
