<?php
require_once '../../includes/auth_functions.php';
require_once '../../config/database.php';
check_auth();
check_role(['super_admin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['settings'])) {

    $settings = $_POST['settings'];
    $tab = $_POST['tab'] ?? 'general';

    // Handle Logo Upload
    if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] == 0) {
        $upload_dir = __DIR__ . '/../../uploads/';

        // Create uploads directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['school_logo']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_exts)) {
            $new_filename = 'school_logo_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['school_logo']['tmp_name'], $upload_path)) {
                // Delete old logo if exists
                $old_logo = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'school_logo'");
                if ($old_logo && $row = mysqli_fetch_assoc($old_logo)) {
                    $old_file = $upload_dir . $row['setting_value'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }

                // Save new logo path to settings
                $logo_key = 'school_logo';
                $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('$logo_key', '$new_filename') 
                        ON DUPLICATE KEY UPDATE setting_value = '$new_filename'";
                mysqli_query($conn, $sql);
            }
        }
    }

    foreach ($settings as $key => $value) {
        $key = mysqli_real_escape_string($conn, $key);
        $value = mysqli_real_escape_string($conn, $value);

        // Upsert logic (Insert on Duplicate Key Update)
        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value') 
                ON DUPLICATE KEY UPDATE setting_value = '$value'";

        mysqli_query($conn, $sql);
    }

    // If AJAX request, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
        exit();
    }

    // Redirect back with success
    header("Location: index.php?tab=$tab&success=saved");
    exit();
} else {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }
    header("Location: index.php");
    exit();
}
?>