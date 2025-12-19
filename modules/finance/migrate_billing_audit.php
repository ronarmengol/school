<?php
/**
 * Database Migration: Create billing_audit_log table
 * Run this file once to create the table needed for undo billing feature
 */

require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Migration</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{color:#10b981;background:#f0fdf4;padding:15px;border-radius:8px;margin:10px 0;}";
echo ".error{color:#dc2626;background:#fef2f2;padding:15px;border-radius:8px;margin:10px 0;}";
echo ".info{color:#0891b2;background:#ecfeff;padding:15px;border-radius:8px;margin:10px 0;}";
echo "h1{color:#1e293b;}pre{background:#fff;padding:10px;border-radius:4px;overflow:auto;}</style>";
echo "</head><body>";

echo "<h1>🔧 Database Migration: billing_audit_log</h1>";

// Check if table already exists
$check_sql = "SHOW TABLES LIKE 'billing_audit_log'";
$result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($result) > 0) {
  echo "<div class='info'>";
  echo "<strong>ℹ️ Table Already Exists</strong><br>";
  echo "The <code>billing_audit_log</code> table already exists in the database.";
  echo "</div>";

  // Show table structure
  echo "<h3>Current Table Structure:</h3>";
  $desc_result = mysqli_query($conn, "DESCRIBE billing_audit_log");
  echo "<pre>";
  while ($row = mysqli_fetch_assoc($desc_result)) {
    echo sprintf(
      "%-20s %-20s %-10s %-10s\n",
      $row['Field'],
      $row['Type'],
      $row['Null'],
      $row['Key']
    );
  }
  echo "</pre>";
} else {
  // Create the table
  $create_sql = "CREATE TABLE `billing_audit_log` (
      `log_id` int(11) NOT NULL AUTO_INCREMENT,
      `action_type` enum('UNDO_BILLING','BULK_BILLING') NOT NULL,
      `class_id` int(11) NOT NULL,
      `term_id` int(11) NOT NULL,
      `invoices_affected` int(11) DEFAULT 0,
      `total_amount` decimal(10,2) DEFAULT 0.00,
      `performed_by` int(11) DEFAULT NULL,
      `performed_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `details` text DEFAULT NULL,
      PRIMARY KEY (`log_id`),
      KEY `class_id` (`class_id`),
      KEY `term_id` (`term_id`),
      KEY `performed_by` (`performed_by`),
      CONSTRAINT `billing_audit_log_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
      CONSTRAINT `billing_audit_log_ibfk_2` FOREIGN KEY (`term_id`) REFERENCES `terms` (`term_id`) ON DELETE CASCADE,
      CONSTRAINT `billing_audit_log_ibfk_3` FOREIGN KEY (`performed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

  if (mysqli_query($conn, $create_sql)) {
    echo "<div class='success'>";
    echo "<strong>✅ Success!</strong><br>";
    echo "The <code>billing_audit_log</code> table has been created successfully.";
    echo "</div>";

    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $desc_result = mysqli_query($conn, "DESCRIBE billing_audit_log");
    echo "<pre>";
    echo sprintf("%-20s %-20s %-10s %-10s\n", "Field", "Type", "Null", "Key");
    echo str_repeat("-", 70) . "\n";
    while ($row = mysqli_fetch_assoc($desc_result)) {
      echo sprintf(
        "%-20s %-20s %-10s %-10s\n",
        $row['Field'],
        $row['Type'],
        $row['Null'],
        $row['Key']
      );
    }
    echo "</pre>";

    echo "<div class='info'>";
    echo "<strong>✓ Next Steps:</strong><br>";
    echo "1. The undo billing feature is now ready to use<br>";
    echo "2. Navigate to Finance → Billing Status<br>";
    echo "3. Look for the 'Undo' button next to fully billed classes<br>";
    echo "4. You can safely delete this file after migration";
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
echo "<p><a href='billing_status.php'>← Back to Billing Status</a></p>";
echo "</body></html>";

mysqli_close($conn);
?>