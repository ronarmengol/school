<?php
/**
 * Database Migration: Add Guardian Fields to Students Table
 * Run this file once to add guardian columns to the students table
 */

require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Migration - Guardian Fields</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{color:#10b981;background:#f0fdf4;padding:15px;border-radius:8px;margin:10px 0;}";
echo ".error{color:#dc2626;background:#fef2f2;padding:15px;border-radius:8px;margin:10px 0;}";
echo ".info{color:#0891b2;background:#ecfeff;padding:15px;border-radius:8px;margin:10px 0;}";
echo "h1{color:#1e293b;}pre{background:#fff;padding:10px;border-radius:4px;overflow:auto;}</style>";
echo "</head><body>";

echo "<h1>🔧 Database Migration: Guardian Fields</h1>";

// Check if columns already exist
$check_sql = "SHOW COLUMNS FROM students LIKE 'guardian1_name'";
$result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($result) > 0) {
  echo "<div class='info'>";
  echo "<strong>ℹ️ Columns Already Exist</strong><br>";
  echo "The guardian columns already exist in the students table.";
  echo "</div>";
} else {
  // Add the guardian columns
  $alter_sql = "ALTER TABLE `students` 
        ADD COLUMN `guardian1_name` varchar(100) DEFAULT NULL AFTER `status`,
        ADD COLUMN `guardian1_contact` varchar(20) DEFAULT NULL AFTER `guardian1_name`,
        ADD COLUMN `guardian2_name` varchar(100) DEFAULT NULL AFTER `guardian1_contact`,
        ADD COLUMN `guardian2_contact` varchar(20) DEFAULT NULL AFTER `guardian2_name`";

  if (mysqli_query($conn, $alter_sql)) {
    echo "<div class='success'>";
    echo "<strong>✅ Success!</strong><br>";
    echo "Guardian columns have been added to the students table successfully.";
    echo "</div>";

    echo "<div class='info'>";
    echo "<strong>✓ Columns Added:</strong><br>";
    echo "• guardian1_name (varchar 100)<br>";
    echo "• guardian1_contact (varchar 20)<br>";
    echo "• guardian2_name (varchar 100)<br>";
    echo "• guardian2_contact (varchar 20)";
    echo "</div>";
  } else {
    echo "<div class='error'>";
    echo "<strong>❌ Error!</strong><br>";
    echo "Failed to add columns: " . mysqli_error($conn);
    echo "</div>";
  }
}

echo "<hr>";
echo "<p><a href='add.php'>← Back to Add Student</a> | <a href='index.php'>Students List</a></p>";
echo "</body></html>";

mysqli_close($conn);
?>