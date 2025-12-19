<?php
// includes/load_settings.php

// Ensure DB connection
if (!isset($conn)) {
    // Attempt to connect if not already connected (fallback)
    // In a proper flow, database.php is already included
}

$APP_SETTINGS = [];

if (isset($conn)) {
    $result = mysqli_query($conn, "SELECT * FROM settings");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $APP_SETTINGS[$row['setting_key']] = $row['setting_value'];
        }
    }
}

// Global helper function to get setting
function get_setting($key, $default = '') {
    global $APP_SETTINGS;
    return isset($APP_SETTINGS[$key]) ? $APP_SETTINGS[$key] : $default;
}
?>
