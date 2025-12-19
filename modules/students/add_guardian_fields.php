<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin']);

echo "<h2>Adding Guardian Fields to Students Table</h2>";
echo "<style>
  .success { color: green; font-weight: bold; }
  .error { color: red; font-weight: bold; }
  .info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 15px 0; }
</style>";

// Check if columns already exist
$check_sql = "SHOW COLUMNS FROM students LIKE 'guardian1_name'";
$result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($result) > 0) {
  echo "<p class='info'>✓ Guardian fields already exist in the database.</p>";
} else {
  echo "<p class='info'>Adding guardian fields to students table...</p>";

  $alterations = [
    "ALTER TABLE students ADD COLUMN guardian1_name VARCHAR(100) DEFAULT NULL AFTER photo_path",
    "ALTER TABLE students ADD COLUMN guardian1_contact VARCHAR(20) DEFAULT NULL AFTER guardian1_name",
    "ALTER TABLE students ADD COLUMN guardian2_name VARCHAR(100) DEFAULT NULL AFTER guardian1_contact",
    "ALTER TABLE students ADD COLUMN guardian2_contact VARCHAR(20) DEFAULT NULL AFTER guardian2_name"
  ];

  $success_count = 0;
  $errors = [];

  foreach ($alterations as $sql) {
    if (mysqli_query($conn, $sql)) {
      $success_count++;
    } else {
      $errors[] = mysqli_error($conn);
    }
  }

  if ($success_count == count($alterations)) {
    echo "<p class='success'>✓ Successfully added all guardian fields!</p>";
    echo "<ul>";
    echo "<li>guardian1_name (VARCHAR 100)</li>";
    echo "<li>guardian1_contact (VARCHAR 20)</li>";
    echo "<li>guardian2_name (VARCHAR 100)</li>";
    echo "<li>guardian2_contact (VARCHAR 20)</li>";
    echo "</ul>";
  } else {
    echo "<p class='error'>❌ Some errors occurred:</p>";
    foreach ($errors as $error) {
      echo "<p class='error'>- $error</p>";
    }
  }
}

// Verify the columns exist
echo "<h3>Current Students Table Structure</h3>";
$columns_sql = "SHOW COLUMNS FROM students";
$result = mysqli_query($conn, $columns_sql);

echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
  $highlight = (strpos($row['Field'], 'guardian') !== false) ? "style='background-color: #ffff99;'" : "";
  echo "<tr $highlight>";
  echo "<td>{$row['Field']}</td>";
  echo "<td>{$row['Type']}</td>";
  echo "<td>{$row['Null']}</td>";
  echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
  echo "</tr>";
}
echo "</table>";

echo "<p><a href='add.php'>→ Go to Add Student Page</a></p>";
?>