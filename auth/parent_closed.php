<?php
require_once '../config/constants.php';
require_once '../includes/load_settings.php'; // To get school name
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Restricted - <?php echo get_setting('school_name', APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/style.css">
</head>
<body style="background-color: #f3f4f6; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0;">

    <div style="background: white; padding: 40px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); max-width: 500px; width: 90%; text-align: center;">
        
        <div style="margin-bottom: 20px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#ef4444" style="width: 64px; height: 64px; margin: 0 auto;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
        </div>

        <h2 style="color: #1f2937; margin-bottom: 15px; font-size: 24px;">Parent Portal Closed</h2>
        
        <p style="color: #4b5563; line-height: 1.6; margin-bottom: 25px;">
            The parent portal is currently disabled. <br>
            Please consult the school administration for assistance.
        </p>
        
        <a href="login.php" style="display: inline-block; background-color: #3b82f6; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: background-color 0.2s;">
            Return to Login
        </a>
    </div>

</body>
</html>
