<?php
/**
 * Database Migration: Create school_notes table
 * Run this file once to create the notes system
 */

require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Migration - School Notes</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{color:#10b981;background:#f0fdf4;padding:15px;border-radius:8px;margin:10px 0;}";
echo ".error{color:#dc2626;background:#fef2f2;padding:15px;border-radius:8px;margin:10px 0;}";
echo ".info{color:#0891b2;background:#ecfeff;padding:15px;border-radius:8px;margin:10px 0;}";
echo "h1{color:#1e293b;}pre{background:#fff;padding:10px;border-radius:4px;overflow:auto;}</style>";
echo "</head><body>";

echo "<h1>🔧 Database Migration: School Notes System</h1>";

// Check if table already exists
$check_sql = "SHOW TABLES LIKE 'school_notes'";
$result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($result) > 0) {
  echo "<div class='info'>";
  echo "<strong>ℹ️ Table Already Exists</strong><br>";
  echo "The <code>school_notes</code> table already exists in the database.";
  echo "</div>";

  // Show table structure
  echo "<h3>Current Table Structure:</h3>";
  $desc_result = mysqli_query($conn, "DESCRIBE school_notes");
  echo "<pre>";
  echo sprintf("%-25s %-30s %-10s %-10s\n", "Field", "Type", "Null", "Key");
  echo str_repeat("-", 80) . "\n";
  while ($row = mysqli_fetch_assoc($desc_result)) {
    echo sprintf(
      "%-25s %-30s %-10s %-10s\n",
      $row['Field'],
      $row['Type'],
      $row['Null'],
      $row['Key']
    );
  }
  echo "</pre>";
} else {
  // Create the table
  $create_sql = "CREATE TABLE `school_notes` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

  if (mysqli_query($conn, $create_sql)) {
    echo "<div class='success'>";
    echo "<strong>✅ Success!</strong><br>";
    echo "The <code>school_notes</code> table has been created successfully.";
    echo "</div>";

    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $desc_result = mysqli_query($conn, "DESCRIBE school_notes");
    echo "<pre>";
    echo sprintf("%-25s %-30s %-10s %-10s\n", "Field", "Type", "Null", "Key");
    echo str_repeat("-", 80) . "\n";
    while ($row = mysqli_fetch_assoc($desc_result)) {
      echo sprintf(
        "%-25s %-30s %-10s %-10s\n",
        $row['Field'],
        $row['Type'],
        $row['Null'],
        $row['Key']
      );
    }
    echo "</pre>";

    echo "<div class='info'>";
    echo "<strong>✓ Features:</strong><br>";
    echo "• Priority levels: Low, Medium, High, Urgent<br>";
    echo "• Categories: Student, Classroom, Facility, General<br>";
    echo "• Status tracking: Open, In Progress, Resolved, Closed<br>";
    echo "• Links to students and classes<br>";
    echo "• Tracks who created and resolved notes<br>";
    echo "• High/Urgent priority notes appear on admin dashboard";
    echo "</div>";
  } else {
    echo "<div class='error'>";
    echo "<strong>❌ Error!</strong><br>";
    echo "Failed to create table: " . mysqli_error($conn);
    echo "</div>";

    echo "<h3>SQL Attempted:</h3>";
    echo "<pre>" . htmlspecialchars($create_sql) . "</pre>";
  }
}

echo "<hr>";
echo "<p><a href='../attendance/index.php'>← Back to Attendance</a> | <a href='../dashboard/admin_dashboard.php'>Dashboard</a></p>";
echo "</body></html>";

mysqli_close($conn);
?>