<?php
// config/database.php
// Auto-detects environment and uses appropriate database settings

// Detect if we're on InfinityFree or local environment
$is_local = (
    php_sapi_name() === 'cli' ||
    (isset($_SERVER['HTTP_HOST']) && (
        $_SERVER['HTTP_HOST'] === 'localhost' ||
        $_SERVER['HTTP_HOST'] === '127.0.0.1' ||
        strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0
    ))
);

if ($is_local) {
    // LOCAL DEVELOPMENT SETTINGS
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '12345'); // Your local MySQL password
    define('DB_NAME', 'school_management_db');
} else {
    // INFINITYFREE / PRODUCTION SETTINGS
    // Replace these with your actual InfinityFree database credentials
    // You can find these in your InfinityFree Control Panel > MySQL Databases
    define('DB_HOST', 'sql200.infinityfree.com');  // Replace with your actual host
    define('DB_USER', 'if0_XXXXXXXX');              // Replace with your actual username
    define('DB_PASS', 'your_password_here');        // Replace with your actual password
    define('DB_NAME', 'if0_XXXXXXXX_school');       // Replace with your actual database name
}

// Connect to database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    // More detailed error for debugging (disable in production)
    if ($is_local) {
        die("Connection failed: " . mysqli_connect_error() . "<br>Host: " . DB_HOST . "<br>User: " . DB_USER . "<br>Database: " . DB_NAME);
    } else {
        die("Database connection failed. Please contact the administrator.");
    }
}

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Start Session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>