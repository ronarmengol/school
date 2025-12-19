<?php
/**
 * Pre-Deployment Checker
 * Run this script before deploying to InfinityFree to verify everything is ready
 */

echo "<h1>🚀 School Management System - Pre-Deployment Checker</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .check { margin: 15px 0; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: green; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 15px 0; }
    h2 { color: #1e293b; border-bottom: 2px solid #6366f1; padding-bottom: 10px; }
    ul { line-height: 1.8; }
    code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; }
</style>";

$checks_passed = 0;
$checks_failed = 0;
$warnings = 0;

// Check 1: Database Configuration
echo "<div class='check'>";
echo "<h2>1. Database Configuration</h2>";
if (file_exists(__DIR__ . '/config/database.php')) {
  echo "<p class='success'>✓ database.php exists</p>";
  $db_content = file_get_contents(__DIR__ . '/config/database.php');

  if (strpos($db_content, 'if0_XXXXXXXX') !== false || strpos($db_content, 'your_password_here') !== false) {
    echo "<p class='warning'>⚠ WARNING: You still have placeholder credentials!</p>";
    echo "<p>Please update the InfinityFree database credentials in <code>config/database.php</code></p>";
    $warnings++;
  } else {
    echo "<p class='success'>✓ Database credentials appear to be configured</p>";
    $checks_passed++;
  }
} else {
  echo "<p class='error'>✗ database.php not found!</p>";
  $checks_failed++;
}
echo "</div>";

// Check 2: Required Directories
echo "<div class='check'>";
echo "<h2>2. Required Directories</h2>";
$required_dirs = ['assets', 'config', 'includes', 'modules', 'uploads'];
foreach ($required_dirs as $dir) {
  if (is_dir(__DIR__ . '/' . $dir)) {
    echo "<p class='success'>✓ {$dir}/ exists</p>";
    $checks_passed++;
  } else {
    echo "<p class='error'>✗ {$dir}/ not found!</p>";
    $checks_failed++;
  }
}
echo "</div>";

// Check 3: Critical Files
echo "<div class='check'>";
echo "<h2>3. Critical Files</h2>";
$required_files = [
  'index.php',
  'auth/login.php',
  'config/constants.php',
  'includes/header.php',
  'includes/footer.php',
  '.htaccess',
  'database.sql'
];
foreach ($required_files as $file) {
  if (file_exists(__DIR__ . '/' . $file)) {
    echo "<p class='success'>✓ {$file} exists</p>";
    $checks_passed++;
  } else {
    echo "<p class='error'>✗ {$file} not found!</p>";
    $checks_failed++;
  }
}
echo "</div>";

// Check 4: Folder Permissions
echo "<div class='check'>";
echo "<h2>4. Folder Permissions (Local Check)</h2>";
$writable_dirs = ['uploads', 'assets/uploads'];
foreach ($writable_dirs as $dir) {
  $full_path = __DIR__ . '/' . $dir;
  if (is_dir($full_path)) {
    if (is_writable($full_path)) {
      echo "<p class='success'>✓ {$dir}/ is writable</p>";
      $checks_passed++;
    } else {
      echo "<p class='warning'>⚠ {$dir}/ is not writable (set to 755 or 777 on server)</p>";
      $warnings++;
    }
  } else {
    echo "<p class='warning'>⚠ {$dir}/ doesn't exist (create it on server)</p>";
    $warnings++;
  }
}
echo "</div>";

// Check 5: Database File
echo "<div class='check'>";
echo "<h2>5. Database Export</h2>";
if (file_exists(__DIR__ . '/database.sql')) {
  $db_size = filesize(__DIR__ . '/database.sql');
  $db_size_mb = round($db_size / 1024 / 1024, 2);
  echo "<p class='success'>✓ database.sql exists ({$db_size_mb} MB)</p>";

  if ($db_size_mb > 400) {
    echo "<p class='error'>✗ Database file is larger than InfinityFree's 400MB limit!</p>";
    $checks_failed++;
  } else {
    echo "<p class='success'>✓ Database size is within InfinityFree limits</p>";
    $checks_passed++;
  }
} else {
  echo "<p class='error'>✗ database.sql not found! Export your database first.</p>";
  $checks_failed++;
}
echo "</div>";

// Check 6: Debug Files (should be removed)
echo "<div class='check'>";
echo "<h2>6. Debug Files Check</h2>";
$debug_files = [
  'modules/finance/debug_invoices.php',
  'modules/finance/debug_query.php',
  'modules/finance/debug_adm001.php',
  'modules/students/add_guardian_fields.php'
];
$debug_found = false;
foreach ($debug_files as $file) {
  if (file_exists(__DIR__ . '/' . $file)) {
    echo "<p class='warning'>⚠ Debug file found: {$file} (consider removing before deployment)</p>";
    $debug_found = true;
    $warnings++;
  }
}
if (!$debug_found) {
  echo "<p class='success'>✓ No debug files found</p>";
  $checks_passed++;
}
echo "</div>";

// Summary
echo "<div class='info'>";
echo "<h2>📊 Summary</h2>";
echo "<p><strong>Checks Passed:</strong> <span class='success'>{$checks_passed}</span></p>";
echo "<p><strong>Checks Failed:</strong> <span class='error'>{$checks_failed}</span></p>";
echo "<p><strong>Warnings:</strong> <span class='warning'>{$warnings}</span></p>";

if ($checks_failed == 0 && $warnings == 0) {
  echo "<h3 class='success'>🎉 All checks passed! You're ready to deploy!</h3>";
} elseif ($checks_failed == 0) {
  echo "<h3 class='warning'>⚠ Ready to deploy, but please review warnings above</h3>";
} else {
  echo "<h3 class='error'>❌ Please fix the errors above before deploying</h3>";
}
echo "</div>";

// Next Steps
echo "<div class='info'>";
echo "<h2>📋 Next Steps</h2>";
echo "<ol>";
echo "<li>Review and fix any errors or warnings above</li>";
echo "<li>Update database credentials in <code>config/database.php</code> with your InfinityFree details</li>";
echo "<li>Read the <code>DEPLOYMENT_GUIDE.md</code> file for detailed instructions</li>";
echo "<li>Create a database in InfinityFree Control Panel</li>";
echo "<li>Upload all files via FTP (FileZilla recommended)</li>";
echo "<li>Import <code>database.sql</code> via phpMyAdmin</li>";
echo "<li>Set folder permissions for <code>uploads/</code> to 755 or 777</li>";
echo "<li>Test the application on your InfinityFree domain</li>";
echo "<li>Change default admin password immediately</li>";
echo "</ol>";
echo "</div>";

echo "<div class='info'>";
echo "<h2>📚 Resources</h2>";
echo "<ul>";
echo "<li><a href='DEPLOYMENT_GUIDE.md' target='_blank'>View Deployment Guide</a></li>";
echo "<li><a href='https://infinityfree.net' target='_blank'>InfinityFree Website</a></li>";
echo "<li><a href='https://forum.infinityfree.net' target='_blank'>InfinityFree Support Forum</a></li>";
echo "</ul>";
echo "</div>";
?>